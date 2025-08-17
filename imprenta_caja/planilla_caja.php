<?php
ob_start();
session_start();

// Verificar primero si el usuario está logueado
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// Verificar si la caja está seleccionada
if (!isset($_SESSION['Id_Caja']) || empty($_SESSION['Id_Caja'])) {
    echo "<script>
        alert('No hay caja seleccionada, seleccione una caja antes de entrar a la planilla de caja');
        window.location.href = '../core/index.php';
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
$queryCaja = "SELECT c.idCaja, c.Fecha, c.cajaInicial
              FROM caja c
              WHERE c.idCaja = ?";
$stmtCaja = $MiConexion->prepare($queryCaja);
$stmtCaja->bind_param("i", $idCaja);
$stmtCaja->execute();
$resultadoCaja = $stmtCaja->get_result();

if ($resultadoCaja->num_rows === 0) {
    echo "<script>
        alert('No se encontró la caja seleccionada');
        window.location.href = '../core/index.php';
    </script>";
    exit;
}

// Calcular los totales solo para esta caja
$queryTotales = "SELECT tp.denominacion AS metodoPago, SUM(dc.monto) AS totalMonto
                 FROM detalle_caja dc
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                 WHERE dc.idCaja = ? AND tm.es_entrada = 1
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

// Calcular el total de retiros (sin incluir Caja fuerte)
$queryRetiros = "SELECT SUM(dc.monto) AS totalRetiros
                 FROM detalle_caja dc
                 JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                 WHERE dc.idCaja = ?
                   AND tm.es_salida = 1
                   AND tm.denominacion NOT LIKE '%Caja Fuerte%'";
$stmtRetiros = $MiConexion->prepare($queryRetiros);
$stmtRetiros->bind_param("i", $idCaja);
$stmtRetiros->execute();
$resultadoRetiros = $stmtRetiros->get_result();

$totalRetiros = 0;
if ($filaRetiros = $resultadoRetiros->fetch_assoc()) {
    $totalRetiros = (float)$filaRetiros['totalRetiros'];
}

// Calcular el total de retiros solo de Caja fuerte
$queryRetirosCajaFuerte = "SELECT SUM(dc.monto) AS totalRetiros
                           FROM detalle_caja dc
                           JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                           WHERE dc.idCaja = ?
                             AND tm.es_salida = 1
                             AND tm.denominacion LIKE '%Caja Fuerte%'";
$stmtRetirosCajaFuerte = $MiConexion->prepare($queryRetirosCajaFuerte);
$stmtRetirosCajaFuerte->bind_param("i", $idCaja);
$stmtRetirosCajaFuerte->execute();
$resultadoRetirosCajaFuerte = $stmtRetirosCajaFuerte->get_result();

$totalRetirosCajaFuerte = 0;
if ($filaRetirosCajaFuerte = $resultadoRetirosCajaFuerte->fetch_assoc()) {
    $totalRetirosCajaFuerte = (float)$filaRetirosCajaFuerte['totalRetiros'];
}

// Obtener los detalles de la caja específica
$queryDetalles = "SELECT dc.*, 
                  tp.denominacion AS metodoPago,
                  tm.denominacion AS detalle,
                  CONCAT(u.nombre, ' ', u.apellido) AS usuario,
                  tf.denominacion AS tipoFactura,
                  dc.numeroFactura,
                  dc.facturado
                  FROM detalle_caja dc
                  JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  JOIN usuarios u ON dc.idUsuario = u.idUsuario
                  LEFT JOIN tipo_factura tf ON dc.idTipoFactura = tf.idTipoFactura
                  WHERE dc.idCaja = ?
                  ORDER BY dc.idDetalleCaja";
$stmtDetalles = $MiConexion->prepare($queryDetalles);
$stmtDetalles->bind_param("i", $idCaja);
$stmtDetalles->execute();
$resultadoDetalles = $stmtDetalles->get_result();

$detalles = [];
while ($fila = $resultadoDetalles->fetch_assoc()) {
    $detalles[] = $fila;
}

$filaCaja = $resultadoCaja->fetch_assoc();
$cajaInicial = (float)$filaCaja['cajaInicial'];
$totalEfectivo = (float)$totalesPorCaja['totalEfectivo'];
$totalTransferencia = (float)$totalesPorCaja['totalTransferencia'];
$totalTarjeta = (float)$totalesPorCaja['totalTarjeta'];
$cajaEfectivoActual = $totalEfectivo - $totalRetiros - $totalRetirosCajaFuerte + $cajaInicial;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Caja</title>
    <style>
        .badge-facturado {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }
        
        .factura-info {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
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
                            <span class="me-2 mb-2 mb-md-0"><strong>Caja Inicial:</strong> $<?php echo number_format($cajaInicial, 2, '.', ''); ?></span>
                        </div>

                        <div class="col-12 col-md-2">
                            <p><strong>Caja ID:</strong> <?php echo $idCaja; ?></p>
                        </div>

                        <div class="col-12 col-md-3">
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

                <!-- Totales -->
                <div class="row mt-4 border-top pt-3 text-center">
                    <div class="col">
                        <p class="mb-0"><strong>Efectivo:</strong></p>
                        <p class="fs-6 fw-bold">$<?php echo number_format($totalEfectivo, 2); ?></p>
                    </div>
                    <div class="col">
                        <p class="mb-0"><strong>Transferencia:</strong></p>
                        <p class="fs-6 fw-bold">$<?php echo number_format($totalTransferencia, 2); ?></p>
                    </div>
                    <div class="col">
                        <p class="mb-0"><strong>Tarjeta:</strong></p>
                        <p class="fs-6 fw-bold">$<?php echo number_format($totalTarjeta, 2); ?></p>
                    </div>
                    <div class="col">
                        <p class="mb-0"><strong>Caja Fuerte:</strong></p>
                        <p class="fs-6 fw-bold">$<?php echo number_format($totalRetirosCajaFuerte, 2); ?></p>
                    </div>
                    <div class="col">
                        <p class="mb-0"><strong>Retiros:</strong></p>
                        <p class="fs-6 fw-bold">$<?php echo number_format($totalRetiros, 2); ?></p>
                    </div>
                </div>

                <!-- Caja Fuerte -->
                <div class="col-12 text-center">
                    <p class="mb-0 fs-5 fw-bold">
                        <span class="text-dark">Efectivo en Caja:</span>
                        <span class="text-success">$<?php echo number_format($cajaEfectivoActual, 2); ?></span>
                    </p>
                </div>

                <!-- Tabla de Detalles de Caja -->
                <div class="table-responsive mt-4">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="50">N°</th>
                                <th width="120">Facturación</th>
                                <th>Método de Pago</th>
                                <th>Detalle</th>
                                <th>Usuario</th>
                                <th>Monto</th>
                                <th>Observaciones</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $contador = 1;
                                foreach ($detalles as $fila) { 
                                list($Title, $Color) = ColorDeFilaCaja($fila['idTipoMovimiento']);
                            ?>
                                <tr class="<?php echo $Color; ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?php echo $Title; ?>">
                                    <td><?php echo $contador; $contador++; ?></td>
                                    <td class="text-center">
                                        <?php if ($fila['facturado'] == 1): ?>
                                            <span class="badge bg-success d-inline-flex align-items-center">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                <span>Facturado</span>
                                            </span>
                                            <?php if (!empty($fila['tipoFactura']) && !empty($fila['numeroFactura'])): ?>
                                                <div class="factura-info mt-1">
                                                    <?php echo $fila['tipoFactura'] . ' #' . $fila['numeroFactura']; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary d-inline-flex align-items-center">
                                                <i class="bi bi-x-circle-fill me-1"></i>
                                                <span>No facturado</span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $fila['metodoPago']; ?></td>
                                    <td><?php echo $fila['detalle']; ?></td>
                                    <td><?php echo $fila['usuario']; ?></td>
                                    <td>$<?php echo number_format($fila['monto'], 2); ?></td>
                                    <td><?php echo $fila['observaciones']; ?></td>
                                    <td>
                                        <a href="eliminar_venta.php?idDetalleCaja=<?php echo $fila['idDetalleCaja']; ?>" 
                                           class="btn btn-sm btn-danger me-1"
                                           title="Anular" 
                                           onclick="return confirm('¿Confirma anular este detalle de caja?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                        <a href="modificar_venta.php?idDetalleCaja=<?php echo $fila['idDetalleCaja']; ?>" 
                                           class="btn btn-sm btn-warning"
                                           title="Modificar">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
require('../shared/footer.inc.php');
ob_end_flush();
?>

<script>
    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
</body>
</html>