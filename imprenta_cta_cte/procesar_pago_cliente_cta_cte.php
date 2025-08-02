<?php
session_start();
require_once '../funciones/conexion.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idCliente = $_POST['idCliente'] ?? 0;
    $montoPago = $_POST['montoPago'] ?? 0;
    $metodoPago = $_POST['metodoPago'] ?? 0;
    $observaciones = $_POST['observaciones'] ?? '';
    $idUsuario = $_SESSION['Usuario_Id'] ?? 0;
    
    if ($idCliente <= 0 || $montoPago <= 0 || $metodoPago <= 0 || $idUsuario <= 0) {
        $response['message'] = 'Datos inválidos';
        echo json_encode($response);
        exit;
    }
    
    try {
        $MiConexion = ConexionBD();
        $MiConexion->begin_transaction();
        
        // 1. Registrar el movimiento de pago
        $sqlMovimiento = "INSERT INTO movimientos_cuenta (
                            idPedido, idTipoMovimiento, idTipoPago, monto, 
                            observaciones, idUsuario
                          ) VALUES (?, 1, ?, ?, ?, ?)";
        
        $stmtMovimiento = $MiConexion->prepare($sqlMovimiento);
        $stmtMovimiento->bind_param("iidsi", $idCliente, $metodoPago, $montoPago, $observaciones, $idUsuario);
        $stmtMovimiento->execute();
        
        // 2. Actualizar el estado de los trabajos pagados (aquí puedes implementar tu lógica específica)
        // Por ejemplo, marcar como pagados los trabajos más antiguos hasta cubrir el monto
        
        $MiConexion->commit();
        $response['success'] = true;
        $response['message'] = 'Pago registrado correctamente';
    } catch (Exception $e) {
        $MiConexion->rollback();
        $response['message'] = 'Error al registrar el pago: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método no permitido';
}

echo json_encode($response);
?>