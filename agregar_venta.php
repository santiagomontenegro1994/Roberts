<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

// Validar si hay una caja seleccionada
if (empty($_SESSION['Id_Caja'])) {
    die('<div class="alert alert-danger text-center mt-4">No hay caja seleccionada. Por favor, seleccione una caja antes de continuar.</div>');
    header('Location: listados_caja.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado
require('barraLateral.inc.php'); // Incluir barra lateral
require_once 'funciones/conexion.php';
require_once 'funciones/select_general.php';

$MiConexion = ConexionBD();

// Obtener los tipos de servicio desde la base de datos
$TiposServicio = Listar_Tipos_Servicio($MiConexion);
$TiposPagos = Listar_Tipos_Pagos($MiConexion);

$Mensaje = '';
$Estilo = 'warning';

if (!empty($_POST['BotonRegistrar'])) {
    // Validar los datos del formulario
    $idCaja = $_SESSION['Id_Caja'] ?? null;
    $idTipoPago = $_POST['idTipoPago'] ?? null;
    $idTipoServicio = $_POST['idTipoServicio'] ?? null;
    $idUsuario = $_SESSION['Usuario_Id'] ?? null;
    $monto = $_POST['ValorDinero'] ?? null;

    if ($idCaja && $idTipoPago && $idTipoServicio && $idUsuario && $monto > 0) {
        // Insertar el detalle de venta en la base de datos
        $query = "INSERT INTO detalle_venta (idCaja, idTipoPago, idTipoServicio, idUsuario, monto) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $MiConexion->prepare($query);
        $stmt->bind_param("iiidf", $idCaja, $idTipoPago, $idTipoServicio, $idUsuario, $monto);

        if ($stmt->execute()) {
            $Mensaje = 'Detalle de venta registrado correctamente.';
            $Estilo = 'success';
        } else {
            $Mensaje = 'Error al registrar el detalle de venta: ' . $stmt->error;
            $Estilo = 'danger';
        }

        $stmt->close();
    } else {
        $Mensaje = 'Por favor, complete todos los campos correctamente.';
        $Estilo = 'warning';
    }
}

$MiConexion->close();

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

          <!-- Sección de Métodos de Pago -->
        <form method="post">
            <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 card-title">Seleccione el Método de Pago</h6>
                <a href="listados_metodos_pago.php" class="btn btn-outline-primary btn-sm">Gestionar Métodos de Pago</a>
            </div>
            <div class="d-flex flex-wrap justify-content-center">
              <?php foreach ($TiposPagos as $tipo) { ?>
                    <button type="button" class="btn btn-secondary mx-2 my-2 metodo-pago" data-id="<?php echo $tipo['idTipoPago']; ?>">
                        <?php echo $tipo['denominacion']; ?>
                    </button>
                <?php } ?>
                <input type="hidden" name="idTipoPago" id="idTipoPago">
            </div>

            <!-- Sección de Tipos de Servicio -->
            <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 card-title">Seleccione el Tipo de Servicio</h6>
                <a href="listados_tipos_servicios.php" class="btn btn-outline-primary btn-sm">Gestionar Tipos de Servicio</a>
            </div>
            <div class="d-flex flex-wrap justify-content-center">
                <?php foreach ($TiposServicio as $tipo) { ?>
                    <button type="button" class="btn btn-secondary mx-2 my-2 tipo-servicio" data-id="<?php echo $tipo['idTipoServicio']; ?>">
                        <?php echo $tipo['denominacion']; ?>
                    </button>
                <?php } ?>
                <input type="hidden" name="idTipoServicio" id="idTipoServicio">
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
        </form><!-- End Horizontal Form -->
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
            document.getElementById('idTipoPago').value = button.getAttribute('data-id'); // Asignar valor al input hidden
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
            document.getElementById('idTipoServicio').value = button.getAttribute('data-id'); // Asignar valor al input hidden
        });
    });
</script>

</body>

</html>