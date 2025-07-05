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

$DatosTipoMovimientoActual = array();

if (!empty($_POST['BotonModificarMovimiento'])) {
    $_SESSION['Mensaje'] = Validar_Tipo_Movimiento();

    if (empty($_SESSION['Mensaje'])) { // Si no hay errores de validación
        if (Modificar_Tipo_Movimiento($MiConexion) != false) {
            $_SESSION['Mensaje'] = "El Tipo de Movimiento se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listados_tipos_movimientos.php');
            exit;
        }
    } else { // Si hay errores de validación
        $_SESSION['Estilo'] = 'warning';
        $DatosTipoMovimientoActual['IdTipoMovimiento'] = !empty($_POST['IdTipoMovimiento']) ? $_POST['IdTipoMovimiento'] : '';
        $DatosTipoMovimientoActual['Denominacion'] = !empty($_POST['Denominacion']) ? $_POST['Denominacion'] : '';
    }
} else if (!empty($_GET['idTipoMovimiento'])) {
    $DatosTipoMovimientoActual = Datos_Tipo_Movimiento($MiConexion, $_GET['idTipoMovimiento']);
}

ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">

    <div class="pagetitle">
      <h1>Tipos de Movimientos</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Caja</a></li>
          <li class="breadcrumb-item">Tipos de Movimientos</li>
          <li class="breadcrumb-item active">Modificar Tipo de Movimiento</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Tipo de Movimiento</h5>

              <!-- Formulario -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="denominacion" class="col-sm-2 col-form-label">Denominación</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Denominacion" id="denominacion"
                    value="<?php echo !empty($DatosTipoMovimientoActual['Denominacion']) ? $DatosTipoMovimientoActual['Denominacion'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                    <input type='hidden' name="IdTipoMovimiento" value="<?php echo $DatosTipoMovimientoActual['IdTipoMovimiento']; ?>"/>
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarMovimiento">Modificar</button>
                    <a href="listados_tipos_movimientos.php" 
                    class="btn btn-success btn-info" 
                    title="Listado"> Volver al listado  </a>
                </div>
              </form><!-- End Formulario -->

    </section>

</main><!-- End #main -->

<?php
    $_SESSION['Mensaje'] = '';
    require('../shared/footer.inc.php'); // Incluir footer
?>

</body>
</html>