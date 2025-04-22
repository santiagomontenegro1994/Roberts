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
require_once 'funciones/select_general.php';

$MiConexion = ConexionBD();

// Verificar si la caja está seleccionada
if (!isset($_SESSION['Id_Caja']) || empty($_SESSION['Id_Caja'])) {
    echo "<script>
        alert('No hay caja seleccionada, seleccione una caja antes de entrar a la planilla de caja');
        window.location.href = 'index.php';
    </script>";
    exit;
}

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
$queryCaja = "SELECT c.idCaja, c.Fecha, turnos.denominacion, c.cajaInicial
              FROM caja c
              JOIN turnos ON c.idTurno = turnos.idTurno
              WHERE c.idCaja = ?";
$stmtCaja = $MiConexion->prepare($queryCaja);
$stmtCaja->bind_param("i", $idCaja);
$stmtCaja->execute();
$resultadoCaja = $stmtCaja->get_result();

if ($resultadoCaja->num_rows === 0) {
    echo "<script>
        alert('No se encontró la caja seleccionada');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Calcular los totales solo para esta caja
$queryTotales = "SELECT tp.denominacion AS metodoPago, SUM(dc.monto) AS totalMonto
                 FROM detalle_caja dc
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE dc.idCaja = ? AND (tp.denominacion != 'Efectivo' OR dc.idTipoOperacion = 1)
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

// Calcular el total de retiros para esta caja
$queryRetiros = "SELECT SUM(monto) AS totalRetiros
                 FROM detalle_caja
                 WHERE idCaja = ? AND idTipoOperacion = 2";
$stmtRetiros = $MiConexion->prepare($queryRetiros);
$stmtRetiros->bind_param("i", $idCaja);
$stmtRetiros->execute();
$resultadoRetiros = $stmtRetiros->get_result();

$totalRetiros = 0;
if ($filaRetiros = $resultadoRetiros->fetch_assoc()) {
    $totalRetiros = (float)$filaRetiros['totalRetiros'];
}

// Obtener los detalles de la caja específica
$queryDetalles = "SELECT dc.idDetalleCaja, tp.denominacion AS metodoPago, 
                         ts.denominacion AS tipoServicio, 
                         u.nombre AS usuario, dc.monto, dc.observaciones
                  FROM detalle_caja dc
                  JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                  JOIN tipo_servicio ts ON dc.idTipoServicio = ts.idTipoServicio
                  JOIN usuarios u ON dc.idUsuario = u.idUsuario
                  WHERE dc.idCaja = ?
                  ORDER BY dc.idDetalleCaja";
                  
$stmtDetalles = $MiConexion->prepare($queryDetalles);
$stmtDetalles->bind_param("i", $idCaja);
$stmtDetalles->execute();
$resultadoDetalleCaja = $stmtDetalles->get_result();

// Almacenar todos los detalles en un array para poder usarlos múltiples veces
$detalles = [];
while ($fila = $resultadoDetalleCaja->fetch_assoc()) {
    $detalles[] = $fila;
}

$filaCaja = $resultadoCaja->fetch_assoc();
$cajaInicial = (float)$filaCaja['cajaInicial'];
$totalEfectivo = (float)$totalesPorCaja['totalEfectivo'] + $cajaInicial; // Sumar la caja inicial al total efectivo
$totalTransferencia = (float)$totalesPorCaja['totalTransferencia'];
$totalTarjeta = (float)$totalesPorCaja['totalTarjeta'];
$cajaFuerte = $totalEfectivo - $cajaInicial; // Restar la caja inicial al total efectivo
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
                    unset($_SESSION['Mensaje'], $_SESSION['Estilo']); 
                    ?>
                <?php } ?>

                <div class="container">
                    <div class="row mt-2 align-items-center">
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

                        <div class="col-12 col-md-2">
                            <p><strong>Caja ID:</strong> <?php echo $idCaja; ?></p>
                        </div>

                        <div class="col-12 col-md-2">
                            <p><strong>Turno:</strong> <?php echo $filaCaja['denominacion']; ?></p>
                        </div>

                        <div class="col-12 col-md-2">
                            <p><strong>Fecha:</strong> <?php echo $filaCaja['Fecha']; ?></p>
                        </div>
                    </div>
                </div>

                <script>
                    setTimeout(function() {
                        const mensaje = document.getElementById('mensajeExito');
                        if (mensaje) {
                            mensaje.style.display = 'none';
                        }
                    }, 3000);
                </script>

                <h5 class="card-title pb-0 d-flex justify-content-between align-items-center">
                    Detalles de Caja (<?php echo count($detalles); ?> registros)
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
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $fila) { ?>
                                <tr>
                                    <td><?php echo $fila['idDetalleCaja']; ?></td>
                                    <td><?php echo $fila['metodoPago']; ?></td>
                                    <td><?php echo $fila['tipoServicio']; ?></td>
                                    <td><?php echo $fila['usuario']; ?></td>
                                    <td>$<?php echo number_format($fila['monto'], 2); ?></td>
                                    <td><?php echo $fila['observaciones']; ?></td>
                                    <td>
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

                <!-- Totales -->
                <div class="row mt-4 border-top pt-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>T. Efectivo:</strong> $<?php echo number_format($totalEfectivo, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>T. Transferencia:</strong> $<?php echo number_format($totalTransferencia, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>T. Tarjeta:</strong> $<?php echo number_format($totalTarjeta, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>T. Retiros:</strong> $<?php echo number_format($totalRetiros, 2); ?></p>
                    </div>
                </div>

                <!-- Caja Fuerte -->
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <p><strong>Caja Fuerte:</strong> $<?php echo number_format($cajaFuerte, 2); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
require('footer.inc.php');
ob_end_flush();
?>

</body>
</html>
``` 