<?php
session_start();

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

if (empty($_SESSION['Usuario_Nombre'])) {
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Validar datos recibidos
$idPedido = (int)($_POST['idPedido'] ?? 0);
$monto = (float)($_POST['montoPago'] ?? 0);
$idTipoPago = (int)($_POST['metodoPago'] ?? 0);
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

// Validaciones básicas
if ($idPedido <= 0 || $monto <= 0 || $idTipoPago <= 0) {
    die(json_encode(['success' => false, 'message' => 'Datos inválidos']));
}

$MiConexion = ConexionBD();
if (!$MiConexion) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
}

// Verificar que el tipo de pago sea válido y de entrada
$SQL = "SELECT esEntrada FROM tipo_pago WHERE idTipoPago = ? AND idActivo = 1";
$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'i', $idTipoPago);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tipoPago = mysqli_fetch_assoc($result);

if (!$tipoPago || !$tipoPago['esEntrada']) {
    die(json_encode(['success' => false, 'message' => 'Método de pago no válido']));
}

// Obtener ID del tipo de movimiento para "Pago cuenta corriente"
$SQL = "SELECT idTipoMovimiento FROM tipo_movimiento 
        WHERE es_entrada = 1 AND es_salida = 0 
        AND denominacion LIKE '%cuenta corriente%' 
        AND idActivo = 1 LIMIT 1";
$result = mysqli_query($MiConexion, $SQL);
$tipoMovimiento = mysqli_fetch_assoc($result);

if (!$tipoMovimiento) {
    die(json_encode(['success' => false, 'message' => 'Configuración incompleta: falta tipo de movimiento']));
}

// Registrar el movimiento en cuenta corriente
$SQL = "INSERT INTO movimientos_cuenta (
            idPedido, 
            idTipoMovimiento, 
            idTipoPago, 
            monto, 
            observaciones, 
            idUsuario
        ) VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'iiidsi', 
    $idPedido,
    $tipoMovimiento['idTipoMovimiento'],
    $idTipoPago,
    $monto,
    $observaciones,
    $_SESSION['Usuario_Id']
);

if (!mysqli_stmt_execute($stmt)) {
    die(json_encode([
        'success' => false, 
        'message' => 'Error al registrar el pago: ' . mysqli_error($MiConexion)
    ]));
}

// Actualizar la seña en el pedido
$SQL = "UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?";
$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'di', $monto, $idPedido);

if (!mysqli_stmt_execute($stmt)) {
    die(json_encode([
        'success' => false, 
        'message' => 'Error al actualizar la seña: ' . mysqli_error($MiConexion)
    ]));
}

// Verificar si el pedido está completamente pagado
$SQL = "SELECT 
            PT.senia,
            (SELECT COALESCE(SUM(precio), 0) 
             FROM detalle_trabajos 
             WHERE id_pedido_trabajos = ? AND idActivo = 1) as total
        FROM pedido_trabajos PT
        WHERE PT.idPedidoTrabajos = ?";
$stmt = mysqli_prepare($MiConexion, $SQL);
mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $idPedido);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if ($data['senia'] >= $data['total']) {
    // Cambiar estado a "Pagado" (ajusta el ID según tu sistema)
    $SQL = "UPDATE pedido_trabajos SET idEstado = 4 WHERE idPedidoTrabajos = ?";
    $stmt = mysqli_prepare($MiConexion, $SQL);
    mysqli_stmt_bind_param($stmt, 'i', $idPedido);
    mysqli_stmt_execute($stmt);
}

echo json_encode([
    'success' => true, 
    'message' => 'Pago registrado correctamente',
    'nuevoSaldo' => number_format($data['total'] - ($data['senia'] + $monto), 2, ',', '.')
]);
?>