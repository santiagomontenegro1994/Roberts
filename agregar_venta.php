<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

require_once 'funciones/conexion.php';
$MiConexion=ConexionBD(); 

require_once 'funciones/select_general.php';

$Mensaje='';
$Estilo='warning';
if (!empty($_POST['BotonRegistrar'])) {
    //estoy en condiciones de poder validar los datos
    $Mensaje=Validar_Cliente();
    if (empty($Mensaje)) {
        if (InsertarClientes($MiConexion) != false) {
            $Mensaje = 'Se ha registrado correctamente.';
            $_POST = array(); 
            $Estilo = 'success'; 
        }
    }
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Ventas</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Ventas</li>
          <li class="breadcrumb-item active">Agregar Venta</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Agregar Venta</h5>

          <!-- Sección de Métodos de Pago -->
        <form method='post'>
            <div class="text-center mb-4">
                <h6>Seleccione el Método de Pago</h6>
                <button type="button" class="btn btn-secondary mx-2 metodo-pago" value="Efectivo">Efectivo</button>
                <button type="button" class="btn btn-secondary mx-2 metodo-pago" value="Tarjeta">Tarjeta</button>
                <button type="button" class="btn btn-secondary mx-2 metodo-pago" value="Transferencia">Transferencia</button>
            </div>

            <!-- Sección de Tipos de Servicio -->
            <div class="text-center">
                <h6>Seleccione el Tipo de Servicio</h6>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="BlancoNegro">Impresión Blanco y Negro</button>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="Color">Impresión a Color</button>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="Trabajos">Trabajos</button>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="Fotocopia">Fotocopia</button>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="Escaneo">Escaneo</button>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="Retiros">Retiros</button>
                <button type="button" class="btn btn-secondary mx-2 tipo-servicio" value="VentasVarias">Ventas Varias</button>
            </div>
                

            <!-- Campo para ingresar el valor de dinero -->
            <div class="text-center mt-4">
                <label for="valorDinero" class="form-label">Ingrese el Valor de Dinero</label>
                <div class="input-group w-50 mx-auto">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control text-center" id="valorDinero" name="ValorDinero" placeholder="0" min="0" step="1">
                </div>
            </div>

            <!-- Botones de registrar o reset -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        <form><!-- End Horizontal Form -->
        </div>
      </div>

    </section>

  </main><!-- End #main -->

  <?php
require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo

?>

<script>
    // Manejar la selección de los botones de Métodos de Pago
    const metodoPagoButtons = document.querySelectorAll('.metodo-pago');
    metodoPagoButtons.forEach(button => {
        button.addEventListener('click', () => {
            metodoPagoButtons.forEach(btn => btn.classList.remove('btn-primary')); // Quitar selección previa
            metodoPagoButtons.forEach(btn => btn.classList.add('btn-secondary')); // Restaurar estilo secundario
            button.classList.remove('btn-secondary'); // Quitar estilo secundario
            button.classList.add('btn-primary'); // Agregar estilo seleccionado
        });
    });

    // Manejar la selección de los botones de Tipos de Servicio
    const tipoServicioButtons = document.querySelectorAll('.tipo-servicio');
    tipoServicioButtons.forEach(button => {
        button.addEventListener('click', () => {
            tipoServicioButtons.forEach(btn => btn.classList.remove('btn-primary')); // Quitar selección previa
            tipoServicioButtons.forEach(btn => btn.classList.add('btn-secondary')); // Restaurar estilo secundario
            button.classList.remove('btn-secondary'); // Quitar estilo secundario
            button.classList.add('btn-primary'); // Agregar estilo seleccionado
        });
    });
</script>

</body>

</html>