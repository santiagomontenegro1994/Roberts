<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Error desconocido'
];

try {
    // 1. Validaciones básicas
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    if (empty($_SESSION['Usuario_Id'])) {
        throw new Exception('Debe iniciar sesión para realizar esta acción', 401);
    }

    // 2. Obtener y validar datos del formulario
    $idDetalleTrabajo = filter_input(INPUT_POST, 'idDetalleTrabajo', FILTER_VALIDATE_INT);
    $montoPago = filter_input(INPUT_POST, 'montoPago', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $metodoPago = filter_input(INPUT_POST, 'metodoPago', FILTER_VALIDATE_INT);
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING) ?? '';

    if (!$idDetalleTrabajo || $idDetalleTrabajo <= 0) {
        throw new Exception('ID de trabajo no válido', 400);
    }
    if (!$montoPago || $montoPago <= 0) {
        throw new Exception('El monto a pagar debe ser mayor que cero', 400);
    }
    if (!$metodoPago || $metodoPago <= 0) {
        throw new Exception('Método de pago no válido', 400);
    }

    // 3. Conectar a la base de datos
    $MiConexion = ConexionBD();
    if (!$MiConexion) {
        throw new Exception('Error al conectar con la base de datos', 500);
    }

    // Iniciar transacción
    $MiConexion->autocommit(false);

    // 4. Obtener información del trabajo a pagar
    $sqlTrabajo = "SELECT dt.precio, dt.id_pedido_trabajos, pt.idCliente 
                  FROM detalle_trabajos dt
                  INNER JOIN pedido_trabajos pt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos
                  WHERE dt.idDetalleTrabajo = ? AND dt.idEstadoTrabajo = 8 AND dt.idActivo = 1";
    
    $stmt = $MiConexion->prepare($sqlTrabajo);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $MiConexion->error, 500);
    }
    
    $stmt->bind_param("i", $idDetalleTrabajo);
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar consulta: ' . $stmt->error, 500);
    }
    
    $result = $stmt->get_result();
    $trabajo = $result->fetch_assoc();
    $stmt->close();
    
    if (!$trabajo) {
        throw new Exception('Trabajo no encontrado o ya fue pagado', 404);
    }

    // 5. Validar que el monto no exceda el precio del trabajo
    if ($montoPago > $trabajo['precio']) {
        throw new Exception('El monto a pagar no puede ser mayor al precio del trabajo', 400);
    }

    // 6. Registrar el movimiento en movimientos_cuenta
    $idTipoMovimiento = 1; // 1 = Pago de cuenta corriente (ajustar según tu sistema)
    $obsMovimiento = "Pago trabajo #$idDetalleTrabajo - " . substr($observaciones, 0, 200);

    $sqlMovimiento = "INSERT INTO movimientos_cuenta 
                    (idPedido, idTipoMovimiento, idTipoPago, monto, observaciones, idUsuario, idActivo)
                    VALUES (?, ?, ?, ?, ?, ?, 1)";

    $stmt = $MiConexion->prepare($sqlMovimiento);
    if (!$stmt) {
        throw new Exception('Error al preparar inserción en movimientos_cuenta: ' . $MiConexion->error, 500);
    }

    $stmt->bind_param(
        "iiidsi",
        $trabajo['id_pedido_trabajos'],
        $idTipoMovimiento,
        $metodoPago,
        $montoPago,
        $obsMovimiento,
        $_SESSION['Usuario_Id']
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al registrar movimiento: ' . $stmt->error, 500);
    }
    $idMovimiento = $stmt->insert_id;
    $stmt->close();

    // 7. Actualizar estado del trabajo a Pagado (7)
    $sqlUpdateTrabajo = "UPDATE detalle_trabajos 
                        SET idEstadoTrabajo = 7 
                        WHERE idDetalleTrabajo = ?";

    $stmt = $MiConexion->prepare($sqlUpdateTrabajo);
    if (!$stmt) {
        throw new Exception('Error al preparar actualización de trabajo: ' . $MiConexion->error, 500);
    }

    $stmt->bind_param("i", $idDetalleTrabajo);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar trabajo: ' . $stmt->error, 500);
    }
    $stmt->close();

    // 7.1 Actualizar la señal del pedido (sumar el monto pagado a la señal)
    $sqlUpdateSenia = "UPDATE pedido_trabajos 
                    SET senia = senia + ? 
                    WHERE idPedidoTrabajos = ?";

    $stmt = $MiConexion->prepare($sqlUpdateSenia);
    if (!$stmt) {
        throw new Exception('Error al preparar actualización de señal: ' . $MiConexion->error, 500);
    }

    $stmt->bind_param("di", $montoPago, $trabajo['id_pedido_trabajos']);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar señal: ' . $stmt->error, 500);
    }
    $stmt->close();

    // 7.2 Actualizar el estado del pedido si es necesario
    ActualizarEstadoPedido($MiConexion, $trabajo['id_pedido_trabajos']);

    // 8. Registrar en caja si hay una caja abierta
    if (!empty($_SESSION['Id_Caja'])) {
        $sqlCaja = "INSERT INTO detalle_caja 
                   (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones)
                   VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $MiConexion->prepare($sqlCaja);
        if (!$stmt) {
            throw new Exception('Error al preparar inserción en caja: ' . $MiConexion->error, 500);
        }
        
        $obsCaja = "Pago trabajo #$idDetalleTrabajo - Cliente #" . $trabajo['idCliente'];
        $stmt->bind_param(
            "iiiids",
            $_SESSION['Id_Caja'],
            $metodoPago,
            $idTipoMovimiento,
            $_SESSION['Usuario_Id'],
            $montoPago,
            $obsCaja
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar en caja: ' . $stmt->error, 500);
        }
        $stmt->close();
    }

    // 9. Confirmar todas las operaciones
    $MiConexion->commit();

    $response = [
        'success' => true,
        'message' => 'Pago registrado correctamente',
        'idMovimiento' => $idMovimiento
    ];

} catch (Exception $e) {
    // Revertir en caso de error
    if (isset($MiConexion)) {
        $MiConexion->rollback();
    }
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
    
    error_log('[' . date('Y-m-d H:i:s') . '] Error en pago trabajo CC: ' . $e->getMessage());
} finally {
    // Restaurar autocommit y cerrar conexión
    if (isset($MiConexion)) {
        $MiConexion->autocommit(true);
        $MiConexion->close();
    }
}

echo json_encode($response);
exit;
