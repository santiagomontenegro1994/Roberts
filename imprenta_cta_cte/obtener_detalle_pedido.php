<?php
session_start();

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

if (empty($_SESSION['Usuario_Nombre'])) {
    header('HTTP/1.1 403 Forbidden');
    die('No autorizado');
}

$idPedido = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idPedido <= 0) {
    die('ID de pedido inválido');
}

$MiConexion = ConexionBD();
if (!$MiConexion) {
    die('Error de conexión a la base de datos');
}

// Obtener información básica del pedido
$SQL = "SELECT 
            PT.idPedidoTrabajos,
            PT.fecha,
            PT.senia,
            C.nombre AS nombre_cliente,
            C.apellido AS apellido_cliente,
            C.telefono,
            ET.denominacion AS estado_nombre,
            US.usuario,
            COALESCE(SUM(DT.precio), 0) AS precio_total
        FROM pedido_trabajos PT
        INNER JOIN clientes C ON PT.idCliente = C.idCliente
        INNER JOIN estado_pedido_trabajo ET ON PT.idEstado = ET.idEstadoPedidoTrabajo
        INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
        LEFT JOIN detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos AND DT.idActivo = 1
        WHERE PT.idPedidoTrabajos = ?
        GROUP BY PT.idPedidoTrabajos";

$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'i', $idPedido);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die('Pedido no encontrado');
}

$pedido = mysqli_fetch_assoc($result);
$saldo = $pedido['precio_total'] - $pedido['senia'];

// Obtener detalles de los trabajos
$SQL = "SELECT 
            DT.idDetalleTrabajo,
            TT.denominacion AS nombre_trabajo,
            DT.descripcion,
            DT.precio,
            ET.denominacion AS estado_trabajo
        FROM detalle_trabajos DT
        INNER JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
        INNER JOIN estado_trabajo ET ON DT.idEstadoTrabajo = ET.idEstado
        WHERE DT.id_pedido_trabajos = ? AND DT.idActivo = 1
        ORDER BY DT.idDetalleTrabajo ASC";

$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'i', $idPedido);
mysqli_stmt_execute($stmt);
$trabajos = mysqli_stmt_get_result($stmt);

// Obtener historial de pagos
$SQL = "SELECT 
            MC.monto,
            MC.fechaRegistro,
            TP.denominacion AS metodo_pago,
            TM.denominacion AS tipo_movimiento,
            MC.observaciones,
            U.usuario
        FROM movimientos_cuenta MC
        LEFT JOIN tipo_pago TP ON MC.idTipoPago = TP.idTipoPago
        LEFT JOIN tipo_movimiento TM ON MC.idTipoMovimiento = TM.idTipoMovimiento
        LEFT JOIN usuarios U ON MC.idUsuario = U.idUsuario
        WHERE MC.idPedido = ? AND MC.idActivo = 1
        ORDER BY MC.fechaRegistro DESC";

$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'i', $idPedido);
mysqli_stmt_execute($stmt);
$historialPagos = mysqli_stmt_get_result($stmt);
?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">Información del Pedido</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Cliente:</div>
                    <div class="col-8"><?= htmlspecialchars($pedido['nombre_cliente'] . ' ' . $pedido['apellido_cliente']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Teléfono:</div>
                    <div class="col-8"><?= htmlspecialchars($pedido['telefono']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Fecha:</div>
                    <div class="col-8"><?= date('d/m/Y', strtotime($pedido['fecha'])) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Estado:</div>
                    <div class="col-8"><?= htmlspecialchars($pedido['estado_nombre']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Tomado por:</div>
                    <div class="col-8"><?= htmlspecialchars($pedido['usuario']) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">Resumen Financiero</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6 fw-bold">Total Pedido:</div>
                    <div class="col-6 text-end">$<?= number_format($pedido['precio_total'], 2, ',', '.') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 fw-bold">Seña Acumulada:</div>
                    <div class="col-6 text-end">$<?= number_format($pedido['senia'], 2, ',', '.') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 fw-bold">Saldo Pendiente:</div>
                    <div class="col-6 text-end fw-bold <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">
                        $<?= number_format($saldo, 2, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">Trabajos del Pedido</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Trabajo</th>
                                <th>Estado</th>
                                <th class="text-end">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($trabajos) > 0): ?>
                                <?php while ($trabajo = mysqli_fetch_assoc($trabajos)): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($trabajo['nombre_trabajo']) ?></strong>
                                            <?php if (!empty($trabajo['descripcion'])): ?>
                                                <br><small><?= htmlspecialchars($trabajo['descripcion']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($trabajo['estado_trabajo']) ?></td>
                                        <td class="text-end">$<?= number_format($trabajo['precio'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">No hay trabajos registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (mysqli_num_rows($historialPagos) > 0): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">Historial de Pagos</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Método</th>
                                <th class="text-end">Monto</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pago = mysqli_fetch_assoc($historialPagos)): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($pago['fechaRegistro'])) ?></td>
                                    <td><?= htmlspecialchars($pago['tipo_movimiento']) ?></td>
                                    <td><?= htmlspecialchars($pago['metodo_pago']) ?></td>
                                    <td class="text-end text-success fw-bold">
                                        $<?= number_format($pago['monto'], 2, ',', '.') ?>
                                    </td>
                                    <td><?= htmlspecialchars($pago['usuario']) ?></td>
                                </tr>
                                <?php if (!empty($pago['observaciones'])): ?>
                                <tr>
                                    <td colspan="5" class="small text-muted">
                                        <i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($pago['observaciones']) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>