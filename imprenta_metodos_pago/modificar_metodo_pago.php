<?php
ob_start(); // Inicia el búfer de salida
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php'); // Incluir encabezado
require('../shared/barraLateral.inc.php'); // Incluir barra lateral

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

$DatosMetodoPagoActual = array();

if (!empty($_POST['BotonModificarCliente'])) {
    Validar_Tipos_Pago();

    if (empty($_SESSION['Mensaje'])) { // Si no hay errores de validación
        if (Modificar_Tipo_Pago($MiConexion) != false) {
            $_SESSION['Mensaje'] = "Tu Metodo de pago se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listados_metodos_pago.php');
            exit;
        }
    } else { // Si hay errores de validación
        $_SESSION['Estilo'] = 'warning';
        $DatosMetodoPagoActual['IdTipoPago'] = !empty($_POST['IdTipoPago']) ? $_POST['IdTipoPago'] : '';
        $DatosMetodoPagoActual['Denominacion'] = !empty($_POST['Denominacion']) ? $_POST['Denominacion'] : '';
    }
} else if (!empty($_GET['idTipoPago'])) {
    $DatosMetodoPagoActual = Datos_Tipo_Pago($MiConexion, $_GET['idTipoPago']);
}

ob_end_flush(); // Envía el contenido del búfer al navegador
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>VMetodos de Pago</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Ventas</a></li>
          <li class="breadcrumb-item">Metodos de Pago</li>
          <li class="breadcrumb-item active">Modificar Metodo de Pago</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Metodo de Pago</h5>

              <!-- Horizontal Form -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Denominacion</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Denominacion" id="denominacion"
                    value="<?php echo !empty($DatosMetodoPagoActual['Denominacion']) ? $DatosMetodoPagoActual['Denominacion'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  
                    <input type='hidden' name="IdTipoPago" value="<?php echo $DatosMetodoPagoActual['IdTipoPago']; ?>"/>
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarCliente">Modificar</button>
                    <a href="listados_metodos_pago.php" 
                    class="btn btn-success btn-info " 
                    title="Listado"> Volver al listado  </a>
                </div>
              </form><!-- End Horizontal Form -->

    </section>

  </main><!-- End #main -->

<?php
    $_SESSION['Mensaje']='';
    require ('../shared/footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
?>


</body>

</html>