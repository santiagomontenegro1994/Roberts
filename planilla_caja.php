<?php
ob_start();
session_start();

// Verificar primero si el usuario está logueado
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php');
require('barraLateral.inc.php');
require_once 'funciones/conexion.php';

$MiConexion = ConexionBD();

// Verificar si la caja está seleccionada
//if (!isset($_SESSION['Id_Caja']) || empty($_SESSION['Id_Caja'])) {
//    die('<div class="alert alert-danger text-center mt-4">No hay caja seleccionada. Por favor, seleccione una caja antes de continuar.</div>');
//}

$idCaja = (int)$_SESSION['Id_Caja'];

// Manejar la actualización de Caja Inicial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idCaja'])){
    $postIdCaja = (int)$_POST['idCaja'];
    $cajaInicial = (float)$_POST['cajaInicial'];

    if ($postIdCaja === $idCaja && is_numeric($cajaInicial)) {
        $query = "UPDATE caja SET cajaInicial = ? WHERE idCaja = ?";
        $stmt = $MiConexion->prepare($query);
        $stmt->bind_param("di", $cajaInicial, $idCaja);

        if ($stmt->execute()) {
            header("Location: planilla_caja.php?mensaje=actualizado");
            exit;
        } else {
            echo '<div class="alert alert-danger">Error al actualizar Caja Inicial: ' . $MiConexion->error . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Datos inválidos o ID de caja no coincide</div>';
    }
}

// Obtener los datos de la caja específica
$queryCaja = "SELECT c.idCaja, c.Fecha, c.idTurno, c.cajaInicial
              FROM caja c
              WHERE c.idCaja = ?";
$stmtCaja = $MiConexion->prepare($queryCaja);
$stmtCaja->bind_param("i", $idCaja);
$stmtCaja->execute();
$resultadoCaja = $stmtCaja->get_result();

if ($resultadoCaja->num_rows === 0) {
    echo "<script>
        alert('No hay caja seleccionada, seleccione una caja antes de entrar a la planilla de caja');
        window.location.href = 'index.php'; // Cambia 'menu_principal.php' por la ruta correcta al menú
    </script>";
    exit;
}

// Calcular los totales solo para esta caja
$queryTotales = "SELECT tp.denominacion AS metodoPago, SUM(dc.monto) AS totalMonto
                 FROM detalle_caja dc
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE dc.idCaja = ?
                 GROUP BY tp.denominacion";
$stmtTotales = $MiConexion->prepare($queryTotales);
$stmtTotales->bind_param("i", $idCaja);
$stmtTotales->execute();
$resultadoTotales = $stmtTotales->get_result();

$totalesPorCaja = [
    'totalEfectivo' => 0,
    'totalTransferencia' => 0,
    'totalTarjeta' => 0,
];

while ($fila = $resultadoTotales->fetch_assoc()) {
    $metodoPago = $fila['metodoPago'];
    $totalMonto = $fila['totalMonto'];

    if ($metodoPago === 'Efectivo') {
        $totalesPorCaja['totalEfectivo'] = $totalMonto;
    } elseif ($metodoPago === 'Transferencia') {
        $totalesPorCaja['totalTransferencia'] = $totalMonto;
    } elseif ($metodoPago === 'Tarjeta') {
        $totalesPorCaja['totalTarjeta'] = $totalMonto;
    }
}

// Obtener los detalles de la caja específica
$queryDetalleCaja = "SELECT dc.idDetalleCaja, dc.idCaja, tp.denominacion AS metodoPago, 
                     ts.denominacion AS tipoServicio, u.usuario, dc.monto
                     FROM detalle_caja dc
                     JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                     JOIN tipo_servicio ts ON dc.idTipoServicio = ts.idTipoServicio
                     JOIN usuarios u ON dc.idUsuario = u.idUsuario
                     WHERE dc.idCaja = ?
                     ORDER BY dc.idDetalleCaja DESC";
$stmtDetalleCaja = $MiConexion->prepare($queryDetalleCaja);
$stmtDetalleCaja->bind_param("i", $idCaja);
$stmtDetalleCaja->execute();
$resultadoDetalleCaja = $stmtDetalleCaja->get_result();

if (!$resultadoDetalleCaja) {
    die('<div class="alert alert-danger">Error en la consulta de detalle_caja: ' . $MiConexion->error . '</div>');
}

$filaCaja = $resultadoCaja->fetch_assoc();
$cajaInicial = (float)$filaCaja['cajaInicial'];
$totalEfectivo = (float)$totalesPorCaja['totalEfectivo'];
$totalTransferencia = (float)$totalesPorCaja['totalTransferencia'];
$totalTarjeta = (float)$totalesPorCaja['totalTarjeta'];
$cajaFuerte = $totalEfectivo ;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Caja</title>
</head>

<body>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Planilla de Caja</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'actualizado') { ?>
                    <div id="mensajeExito" class="alert alert-success" role="alert">
                        ¡Caja Inicial actualizada correctamente!
                    </div>
                <?php } ?>

                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['Mensaje']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                    // Vaciar los valores después de mostrarlos
                    unset($_SESSION['Mensaje'], $_SESSION['Estilo']); 
                    ?>
                <?php } ?>

                <div class="container">
                    <!-- Encabezado con datos alineados horizontalmente -->
                    <div class="row mt-2 align-items-center">
                        <!-- Caja Inicial a la izquierda -->
                        <div class="col-12 col-md-6 d-flex flex-wrap align-items-center">
                            <form action="planilla_caja.php" method="POST" class="d-inline d-flex flex-wrap align-items-center">
                                <input type="hidden" name="idCaja" value="<?php echo $idCaja; ?>">
                                <label for="cajaInicial" class="me-2 mb-2 mb-md-0"><strong>Caja Inicial:</strong></label>
                                <input type="number" step="0.01" name="cajaInicial" id="cajaInicial" 
                                       value="<?php echo number_format($cajaInicial, 2, '.', ''); ?>" 
                                       class="form-control form-control-sm text-center w-auto me-2 mb-2 mb-md-0">
                                <button type="submit" class="btn btn-primary btn-sm">Actualizar</button>
                            </form>
                        </div>

                        <!-- Caja ID -->
                        <div class="col-12 col-md-2">
                            <p><strong>Caja ID:</strong> <?php echo $idCaja; ?></p>
                        </div>

                        <!-- Turno -->
                        <div class="col-12 col-md-2">
                            <p><strong>Turno:</strong> <?php echo $filaCaja['idTurno']; ?></p>
                        </div>

                        <!-- Fecha -->
                        <div class="col-12 col-md-2">
                            <p><strong>Fecha:</strong> <?php echo $filaCaja['Fecha']; ?></p>
                        </div>
                    </div>
                </div>

                <script>
                    // Ocultar el mensaje después de 3 segundos
                    setTimeout(function() {
                        const mensaje = document.getElementById('mensajeExito');
                        if (mensaje) {
                            mensaje.style.display = 'none';
                        }
                    }, 3000);
                </script>

                <h5 class="card-title pb-0 d-flex justify-content-between align-items-center">
                    Detalles de Caja
                    <a href="agregar_venta.php" class="btn btn-success btn-sm">
                        Agregar Venta
                    </a>
                </h5>

                <!-- Tabla de Detalles de Caja -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Detalle</th>
                                <th>Método de Pago</th>
                                <th>Tipo de Servicio</th>
                                <th>Usuario</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fila = $resultadoDetalleCaja->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $fila['idDetalleCaja']; ?></td>
                                    <td><?php echo $fila['metodoPago']; ?></td>
                                    <td><?php echo $fila['tipoServicio']; ?></td>
                                    <td><?php echo $fila['usuario']; ?></td>
                                    <td>$<?php echo number_format($fila['monto'], 2); ?></td>
                                    <td>
                                        <!-- Acciones -->
                                        <a href="eliminar_venta.php?idDetalleCaja=<?php echo $fila['idDetalleCaja']; ?>" 
                                           title="Anular" 
                                           onclick="return confirm('¿Confirma anular este detalle de caja?');">
                                            <i class="bi bi-trash-fill text-danger fs-5"></i>
                                        </a>

                                        <a href="modificar_venta.php?idDetalleCaja=<?php echo $fila['idDetalleCaja']; ?>" 
                                           title="Modificar">
                                            <i class="bi bi-pencil-fill text-warning fs-5"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totales en un solo renglón -->
                <div class="row mt-4 border-top pt-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Total Efectivo:</strong> $<?php echo number_format($totalEfectivo, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Total Transferencia:</strong> $<?php echo number_format($totalTransferencia, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Total Tarjeta:</strong> $<?php echo number_format($totalTarjeta, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Caja Fuerte:</strong> $<?php echo number_format($cajaFuerte, 2); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
  $_SESSION['Mensaje']='';
  require ('footer.inc.php');
  ob_end_flush();
?>

</body>
</html>