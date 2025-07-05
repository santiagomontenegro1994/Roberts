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

$DatosVentaActual = array();

if (!empty($_POST['BotonModificarVenta'])) {
    Validar_Venta(); // Función para validar los datos de la venta

    if (empty($_SESSION['Mensaje'])) { // Si no hay errores de validación
        if (Modificar_Venta($MiConexion) != false) {
            $_SESSION['Mensaje'] = "La venta se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: planilla_caja.php');
            exit;
        }
    } else { // Si hay errores de validación
        $_SESSION['Estilo'] = 'warning';
        $DatosVentaActual['idDetalleCaja'] = !empty($_POST['idDetalleCaja']) ? $_POST['idDetalleCaja'] : '';
        $DatosVentaActual['idCaja'] = !empty($_POST['idCaja']) ? $_POST['idCaja'] : '';
        $DatosVentaActual['Monto'] = !empty($_POST['Monto']) ? $_POST['Monto'] : '';
        $DatosVentaActual['idTipoPago'] = isset($_POST['idTipoPago']) ? $_POST['idTipoPago'] : '';
        $DatosVentaActual['idTipoMovimiento'] = isset($_POST['idTipoMovimiento']) ? $_POST['idTipoMovimiento'] : '';
    }
} else if (!empty($_GET['idDetalleCaja'])) {
    $DatosVentaActual = Datos_Venta($MiConexion, $_GET['idDetalleCaja']);
}

// Obtener el idTipoMovimiento actual
$idTipoMovimientoActual = !empty($DatosVentaActual['idTipoMovimiento']) ? $DatosVentaActual['idTipoMovimiento'] : null;
$esEntrada = false;
$esSalida = false;

if ($idTipoMovimientoActual) {
    $sql = "SELECT es_entrada, es_salida FROM tipo_movimiento WHERE idTipoMovimiento = $idTipoMovimientoActual";
    $rs = mysqli_query($MiConexion, $sql);
    if ($rs) {
        $row = mysqli_fetch_assoc($rs);
        $esEntrada = !empty($row['es_entrada']);
        $esSalida = !empty($row['es_salida']);
    }
}

// Listar métodos de pago y tipos de movimiento según corresponda
if ($esEntrada) {
    $TiposPagos = Listar_Tipos_Pagos_Entrada($MiConexion);
    $TiposMovimiento = [];
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_entrada = 1 AND idActivo = 1";
    $rs = mysqli_query($MiConexion, $sql);
    while ($row = mysqli_fetch_assoc($rs)) {
        $TiposMovimiento[] = $row;
    }
} elseif ($esSalida) {
    $TiposPagos = Listar_Tipos_Pagos_Salida($MiConexion);
    $TiposMovimiento = [];
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_salida = 1 AND idActivo = 1";
    $rs = mysqli_query($MiConexion, $sql);
    while ($row = mysqli_fetch_assoc($rs)) {
        $TiposMovimiento[] = $row;
    }
} else {
    $TiposPagos = [];
    $TiposMovimiento = [];
}

ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">

    <div class="pagetitle">
      <h1>Ventas</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Ventas</a></li>
          <li class="breadcrumb-item">Listado de Ventas</li>
          <li class="breadcrumb-item active">Modificar Venta</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Venta</h5>

              <!-- Formulario -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="monto" class="col-sm-2 col-form-label">Monto</label>
                  <div class="col-sm-10">
                    <input type="number" step="0.01" class="form-control" name="Monto" id="monto"
                    value="<?php echo !empty($DatosVentaActual['Monto']) ? $DatosVentaActual['Monto'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="idTipoPago" class="col-sm-2 col-form-label">Tipo de Pago</label>
                  <div class="col-sm-10">
                    <select class="form-control" name="idTipoPago" id="idTipoPago">
                      <option value="">Seleccione un tipo de pago</option>
                      <?php foreach ($TiposPagos as $tipoPago) { ?>
                        <option value="<?php echo $tipoPago['idTipoPago']; ?>"
                          <?php echo (!empty($DatosVentaActual['idTipoPago']) && $DatosVentaActual['idTipoPago'] == $tipoPago['idTipoPago']) ? 'selected' : ''; ?>>
                          <?php echo $tipoPago['denominacion']; ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="idTipoMovimiento" class="col-sm-2 col-form-label">Tipo de Movimiento</label>
                  <div class="col-sm-10">
                    <select class="form-control" name="idTipoMovimiento" id="idTipoMovimiento">
                      <option value="">Seleccione un tipo de movimiento</option>
                      <?php foreach ($TiposMovimiento as $tipoMov) { ?>
                        <option value="<?php echo $tipoMov['idTipoMovimiento']; ?>"
                          <?php echo (!empty($DatosVentaActual['idTipoMovimiento']) && $DatosVentaActual['idTipoMovimiento'] == $tipoMov['idTipoMovimiento']) ? 'selected' : ''; ?>>
                          <?php echo $tipoMov['denominacion']; ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="observaciones" class="col-sm-2 col-form-label">Observaciones</label>
                  <div class="col-sm-10">
                    <textarea class="form-control" name="Observaciones" id="observaciones" rows="3"><?php echo !empty($DatosVentaActual['observaciones']) ? htmlspecialchars($DatosVentaActual['observaciones']) : ''; ?></textarea>
                  </div>
                </div>

                <div class="text-center">
                <input type='hidden' name="idDetalleCaja" value="<?php echo $DatosVentaActual['idDetalleCaja']; ?>"/>
                <input type='hidden' name="idCaja" value="<?php echo $DatosVentaActual['idCaja']; ?>"/>
                <input type='hidden' name="idUsuario" value="<?php echo $_SESSION['Usuario_Id']; ?>"/>
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarVenta">Modificar</button>
                    <a href="planilla_caja.php" 
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