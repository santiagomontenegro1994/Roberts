<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Error desconocido',
    'saldoPedido' => null
];

try {
    // 1. Validar sesión y permisos
    if (empty($_SESSION['Usuario_Id'])) {
        throw new Exception('Debe iniciar sesión para realizar esta acción', 401);
    }

    // 2. Validar datos recibidos
    $requiredFields = [
        'idCliente' => FILTER_VALIDATE_INT,
        'tipo' => FILTER_SANITIZE_STRING,
        'monto' => FILTER_VALIDATE_FLOAT
    ];

    $data = [];
    foreach ($requiredFields as $field => $filter) {
        if (!isset($_POST[$field])) {
            throw new Exception("Campo requerido faltante: $field", 400);
        }
        $data[$field] = filter_input(INPUT_POST, $field, $filter);
        if ($data[$field] === false || ($field === 'monto' && $data[$field] <= 0)) {
            throw new Exception("Valor inválido para el campo $field", 400);
        }
    }

    // 3. Procesar datos adicionales según tipo de operación
    $data['metodo'] = isset($_POST['metodo']) ? filter_input(INPUT_POST, 'metodo', FILTER_SANITIZE_STRING) : 'EFECTIVO';
    $data['observaciones'] = isset($_POST['observaciones']) ? filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING) : '';
    $data['idReferencia'] = isset($_POST['idReferencia']) ? filter_input(INPUT_POST, 'idReferencia', FILTER_VALIDATE_INT) : null;

    // 4. Conectar a la base de datos
    $MiConexion = ConexionBD();
    if (!$MiConexion) {
        throw new Exception('Error de conexión a la base de datos', 500);
    }

    // Iniciar transacción para asegurar integridad de datos
    $MiConexion->begin_transaction();

    // 5. Determinar tipo de movimiento
    $tipoMovimiento = '';
    switch (strtoupper($data['tipo'])) {
        case 'DEPOSITO':
            $tipoMovimiento = 'DEPOSITO';
            break;
        case 'PAGO':
            // Validación especial para pagos
            if (empty($data['idReferencia'])) {
                throw new Exception('Para pagos debe seleccionar un trabajo', 400);
            }
            $tipoMovimiento = 'CONSUMO';
            break;
        case 'AJUSTE':
            $tipoMovimiento = 'AJUSTE';
            break;
        default:
            throw new Exception('Tipo de operación no válido', 400);
    }

    // 6. Construir observaciones según tipo de operación
    $observaciones = '';
    switch ($tipoMovimiento) {
        case 'DEPOSITO':
            $observaciones = "Depósito via {$data['metodo']}. {$data['observaciones']}";
            break;
        case 'CONSUMO':
            // Obtener información del trabajo para validar monto
            $sqlTrabajo = "SELECT dt.id_pedido_trabajos, dt.precio, dt.idEstadoTrabajo 
                          FROM detalle_trabajos dt
                          WHERE dt.idDetalleTrabajo = ? AND dt.idActivo = 1";
            $stmt = $MiConexion->prepare($sqlTrabajo);
            $stmt->bind_param("i", $data['idReferencia']);
            $stmt->execute();
            $trabajo = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$trabajo) {
                throw new Exception('Trabajo no encontrado', 404);
            }

            // Validar que el monto sea EXACTAMENTE el precio del trabajo
            if (abs($data['monto'] - $trabajo['precio']) > 0.01) { // Tolerancia de centavos
                throw new Exception('El monto debe ser exactamente $' . number_format($trabajo['precio'], 2, ',', '.') . ' para este trabajo', 400);
            }

            // Validar que el trabajo no esté ya pagado
            if ($trabajo['idEstadoTrabajo'] == 7) {
                throw new Exception('Este trabajo ya fue pagado anteriormente', 400);
            }

            $observaciones = "Pago completo trabajo #{$data['idReferencia']}. {$data['observaciones']}";
            break;
        case 'AJUSTE':
            $motivo = isset($_POST['motivo']) ? filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING) : 'OTRO';
            $observaciones = "Ajuste por $motivo. {$data['observaciones']}";
            break;
    }

    // 7. Registrar el movimiento en cuenta corriente
    $success = ActualizarSaldoCliente(
        $MiConexion,
        $data['idCliente'],
        $data['monto'],
        $tipoMovimiento,
        $_SESSION['Usuario_Id'],
        $data['idReferencia'],
        $data['idReferencia'] ? 'TRABAJO' : null,
        $observaciones
    );

    if (!$success) {
        throw new Exception('Error al registrar la operación en la base de datos', 500);
    }

    $saldoPendiente = null;
    $idPedidoTrabajos = null;

    // 8. Procesamiento especial para pagos de trabajos
    if ($tipoMovimiento === 'CONSUMO' && $data['idReferencia']) {
        // Obtener información completa del trabajo y pedido
        $sqlTrabajoCompleto = "SELECT dt.id_pedido_trabajos, dt.precio, pt.senia 
                             FROM detalle_trabajos dt
                             JOIN pedido_trabajos pt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos
                             WHERE dt.idDetalleTrabajo = ? AND dt.idActivo = 1";
        $stmt = $MiConexion->prepare($sqlTrabajoCompleto);
        $stmt->bind_param("i", $data['idReferencia']);
        $stmt->execute();
        $trabajo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $idPedidoTrabajos = $trabajo['id_pedido_trabajos'];

        // 8.1 Actualizar estado del trabajo a Entregado (7)
        $sqlUpdateTrabajo = "UPDATE detalle_trabajos 
                           SET idEstadoTrabajo = 7 
                           WHERE idDetalleTrabajo = ?";
        $stmt = $MiConexion->prepare($sqlUpdateTrabajo);
        $stmt->bind_param("i", $data['idReferencia']);
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar estado del trabajo', 500);
        }
        $stmt->close();

        // 8.2 Actualizar seña en el pedido (sumar el monto pagado)
        $sqlUpdateSenia = "UPDATE pedido_trabajos 
                          SET senia = senia + ? 
                          WHERE idPedidoTrabajos = ?";
        $stmt = $MiConexion->prepare($sqlUpdateSenia);
        $stmt->bind_param("di", $data['monto'], $idPedidoTrabajos);
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar la seña del pedido', 500);
        }
        $stmt->close();

        // 8.3 Actualizar estado del pedido padre
        ActualizarEstadoPedido($MiConexion, $idPedidoTrabajos);

        // 8.4 Calcular saldo pendiente del pedido
        $sqlSaldoPedido = "SELECT 
                          SUM(dt.precio) as total_pedido,
                          pt.senia
                          FROM detalle_trabajos dt
                          JOIN pedido_trabajos pt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos
                          WHERE dt.id_pedido_trabajos = ? 
                          AND dt.idActivo = 1
                          AND dt.idEstadoTrabajo <> 7";
                          
        $stmt = $MiConexion->prepare($sqlSaldoPedido);
        $stmt->bind_param("i", $idPedidoTrabajos);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $saldoPendiente = $result['total_pedido'] - ($trabajo['senia'] + $data['monto']);
    }

    // 9. Registrar en caja si hay una caja abierta
    if (!empty($_SESSION['Id_Caja'])) {
        // Mapear tipo de pago (1: Efectivo, 2: Transferencia, etc.)
        $idTipoPago = 1; // Por defecto efectivo
        if (strtoupper($data['metodo']) === 'TRANSFERENCIA') {
            $idTipoPago = 2;
        } elseif (strtoupper($data['metodo']) === 'CHEQUE') {
            $idTipoPago = 3;
        }

        // Determinar tipo de movimiento para caja (1: Egreso, 2: Ingreso)
        $idTipoMovimientoCaja = ($tipoMovimiento === 'DEPOSITO') ? 2 : 1;

        $sqlCaja = "INSERT INTO detalle_caja 
                   (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones)
                   VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $MiConexion->prepare($sqlCaja);
        $obsCaja = substr("$tipoMovimiento - Cliente #{$data['idCliente']} - $observaciones", 0, 255);
        
        $stmt->bind_param(
            "iiiids",
            $_SESSION['Id_Caja'],
            $idTipoPago,
            $idTipoMovimientoCaja,
            $_SESSION['Usuario_Id'],
            $data['monto'],
            $obsCaja
        );
        
        if (!$stmt->execute()) {
            error_log("Error al registrar en caja: " . $stmt->error);
        }
        $stmt->close();
    }

    // Confirmar todas las operaciones
    $MiConexion->commit();

    // 10. Preparar respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'Operación registrada correctamente',
        'nuevoSaldo' => ObtenerSaldoCliente($MiConexion, $data['idCliente']),
        'saldoPedido' => $saldoPendiente,
        'idPedido' => $idPedidoTrabajos
    ];

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($MiConexion)) {
        $MiConexion->rollback();
    }
    
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
    
    error_log('['.date('Y-m-d H:i:s').'] Error en procesar_operacion_ctacte: ' . $e->getMessage());
} finally {
    if (isset($MiConexion)) {
        $MiConexion->close();
    }
}

echo json_encode($response);
exit;