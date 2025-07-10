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

$Mensaje = '';
$Estilo = 'warning';
if (!empty($_POST['BotonRegistrar'])) {
    // Validar los datos del formulario
    $Mensaje = Validar_Tipo_Movimiento();
    if (empty($Mensaje)) {
        if (InsertarTipoMovimientoSalida($MiConexion) != false) {
          $_SESSION['Mensaje'] = "El Tipo de Movimiento se agregó correctamente!";
          $_SESSION['Estilo'] = 'success';
          header('Location: listados_tipos_movimientos.php');
          exit;
        } else {
          $Mensaje = "Error al intentar agregar el tipo de movimiento.";
        }
    }
}
ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Tipos de Movimientos (Retiro)</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
        <li class="breadcrumb-item">Caja</li>
        <li class="breadcrumb-item">Tipos de Movimientos</li>
        <li class="breadcrumb-item active">Agregar Tipo de Movimiento</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Agregar Tipo de Movimiento (Retiro)</h5>

        <!-- Formulario -->
        <form method='post'>
          <?php if (!empty($Mensaje)) { ?>
              <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
              <?php echo $Mensaje; ?>
              </div>
          <?php } ?>

          <div class="row mb-3">
            <label for="denominacion" class="col-sm-2 col-form-label">Denominación</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" name="Denominacion" id="denominacion"
              value="<?php echo !empty($_POST['Denominacion']) ? htmlspecialchars($_POST['Denominacion']) : ''; ?>">
            </div>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
            <a href="listados_tipos_movimientos.php" class="btn btn-secondary">Volver al Listado</a>
          </div>
        </form><!-- End Formulario -->

      </div>
    </div>
  </section>

</main><!-- End #main -->

<?php
require('../shared/footer.inc.php'); // Incluir footer
?>