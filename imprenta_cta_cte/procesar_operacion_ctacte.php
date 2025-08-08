<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Error desconocido',
    'saldoPedido' => null
];

try {
    if (empty($_SESSION['Usuario_Id'])) {
        throw new Exception('Debe iniciar sesión para realizar esta acción', 401);
    }

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

    $data['metodo'] = isset($_POST['metodo']) ? filter_input(INPUT_POST, 'metodo', FILTER_SANITIZE_STRING) : 'EFECTIVO';
    $data['observaciones'] = isset($_POST['observaciones']) ? filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING) : '';
    $data['idReferencia'] = isset($_POST['idReferencia']) ? filter_input(INPUT_POST, 'idReferencia', FILTER_VALIDATE_INT) : null;

    // Validación mejorada para pago directo
    if ($data['tipo'] === 'PAGO_DIRECTO' && empty($data['metodo'])) {
        $data['metodo'] = 'SALDO'; // Default cuando se paga solo con saldo
    }

    $MiConexion = ConexionBD();
    if (!$MiConexion) {
        throw new Exception('Error de conexión a la base de datos', 500);
    }

    $MiConexion->begin_transaction();

    $tipoMovimiento = '';
    $esDeposito = false;
    $esPagoDirecto = false;
    $procesarCaja = true; // Variable para controlar el procesamiento de caja

    switch (strtoupper($data['tipo'])) {
        case 'DEPOSITO':
            $tipoMovimiento = 'DEPOSITO';
            $esDeposito = true;
            break;
        case 'PAGO_DIRECTO':
            if (empty($data['idReferencia'])) {
                throw new Exception('Para pagos directos debe seleccionar un trabajo', 400);
            }
            
            // Obtener información del trabajo y validar
            $sqlTrabajo = "SELECT dt.id_pedido_trabajos, dt.precio, dt.idEstadoTrabajo, pt.idCliente
                           FROM detalle_trabajos dt
                           JOIN pedido_trabajos pt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos
                           WHERE dt.idDetalleTrabajo = ? AND dt.idActivo = 1";
            $stmt = $MiConexion->prepare($sqlTrabajo);
            $idRef = $data['idReferencia'];
            $stmt->bind_param("i", $idRef);
            $stmt->execute();
            $trabajo = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$trabajo) {
                throw new Exception('Trabajo no encontrado', 404);
            }
            if ($trabajo['idCliente'] != $data['idCliente']) {
                throw new Exception('El trabajo no pertenece a este cliente', 403);
            }
            if ($trabajo['idEstadoTrabajo'] == 7) {
                throw new Exception('Este trabajo ya fue pagado anteriormente', 400);
            }
            
            // Validar que el monto sea exactamente el precio del trabajo
            $precioTrabajo = floatval($trabajo['precio']);
            if (abs($data['monto'] - $precioTrabajo) > 0.01) {
                throw new Exception("El monto debe ser exactamente $" . number_format($precioTrabajo, 2, ',', '.') . " (precio del trabajo)", 400);
            }

            $saldoCliente = ObtenerSaldoCliente($MiConexion, $data['idCliente']);
            $saldoDisponible = max(0, $saldoCliente); // Solo saldo positivo
            
            // Calcular cuánto se puede pagar con saldo y cuánto con otro método
            $montoUsarSaldo = min($saldoDisponible, $precioTrabajo);
            $montoComplemento = $precioTrabajo - $montoUsarSaldo;
            
            // Validar el monto de complemento si es necesario
            if ($montoComplemento > 0) {
                $montoComplementoRecibido = isset($_POST['montoComplemento']) ? floatval($_POST['montoComplemento']) : 0;
                if (abs($montoComplementoRecibido - $montoComplemento) > 0.01) {
                    throw new Exception('El monto de complemento no coincide con el calculado ($' . number_format($montoComplemento, 2, ',', '.') . ')', 400);
                }
            }

            $tipoMovimiento = 'PAGO_DIRECTO';
            $esPagoDirecto = true;
            $idPedidoTrabajos = $trabajo['id_pedido_trabajos'];
            
            // Construir observaciones detalladas
            if ($montoUsarSaldo > 0 && $montoComplemento > 0) {
                $observaciones = "Pago directo trabajo #{$data['idReferencia']} - Saldo: $" . number_format($montoUsarSaldo, 2, ',', '.') . ", {$data['metodo']}: $" . number_format($montoComplemento, 2, ',', '.') . ". {$data['observaciones']}";
            } elseif ($montoUsarSaldo > 0) {
                $observaciones = "Pago directo trabajo #{$data['idReferencia']} - Pagado completamente con saldo de cuenta corriente. {$data['observaciones']}";
            } else {
                $observaciones = "Pago directo trabajo #{$data['idReferencia']} via {$data['metodo']}. {$data['observaciones']}";
            }
            
            break;
        case 'AJUSTE':
            $tipoAjuste = isset($_POST['tipoAjuste']) ? strtoupper($_POST['tipoAjuste']) : 'A_FAVOR';
            
            // Obtener el saldo actual del cliente
            $saldoActual = ObtenerSaldoCliente($MiConexion, $data['idCliente']);
            
            // Determinar el signo del monto basado en el tipo de ajuste
            if ($tipoAjuste === 'A_FAVOR') {
                // Si el saldo es negativo (deudor), un ajuste a favor debe sumar (acercar a cero)
                // Si el saldo es positivo (acreedor), un ajuste a favor debe sumar (aumentar crédito)
                $montoAjuste = abs($data['monto']);
            } else { // EN_CONTRA
                // Si el saldo es negativo (deudor), un ajuste en contra debe restar (aumentar deuda)
                // Si el saldo es positivo (acreedor), un ajuste en contra debe restar (reducir crédito)
                $montoAjuste = -abs($data['monto']);
            }
            
            $tipoMovimiento = 'AJUSTE';
            $motivo = isset($_POST['motivo']) ? filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING) : 'OTRO';
            $observaciones = "Ajuste ($tipoAjuste) por $motivo. {$data['observaciones']}";
            break;
    }

    if ($esDeposito) {
        // 1. Registrar el depósito completo (aumenta el saldo del cliente)
        $success = ActualizarSaldoCliente(
            $MiConexion, 
            $data['idCliente'], 
            $data['monto'], 
            'DEPOSITO', 
            $_SESSION['Usuario_Id'], 
            null, 
            null, 
            $observaciones
        );
        if (!$success) throw new Exception('Error al registrar el depósito', 500);

        // 2. Obtener trabajos pendientes del cliente (ordenados por antigüedad)
        $trabajosPendientes = Obtener_Trabajos_Pendientes_Por_Antiguedad($MiConexion, $data['idCliente']);
        $saldoRestante = $data['monto']; // Inicializar con el monto depositado
        $trabajosPagados = [];

        // 3. Pagar solo los trabajos que pueden cubrirse COMPLETAMENTE con el saldo
        foreach ($trabajosPendientes as $trabajo) {
            if ($saldoRestante >= $trabajo['PRECIO']) {
                $montoAplicar = $trabajo['PRECIO'];
                $saldoRestante -= $montoAplicar;

                // a. Registrar el movimiento de pago (reduce el saldo)
                $success = ActualizarSaldoCliente(
                    $MiConexion,
                    $data['idCliente'],
                    -$montoAplicar, // Monto negativo (reduce el saldo)
                    'APLICACION_AUTOMATICA',
                    $_SESSION['Usuario_Id'],
                    $trabajo['ID_DETALLE'],
                    'TRABAJO',
                    "Pago automático trabajo #{$trabajo['ID_DETALLE']}"
                );
                if (!$success) throw new Exception('Error al aplicar pago automático', 500);

                // b. Marcar el trabajo como pagado
                $stmt = $MiConexion->prepare("UPDATE detalle_trabajos SET idEstadoTrabajo = 7 WHERE idDetalleTrabajo = ?");
                $stmt->bind_param("i", $trabajo['ID_DETALLE']);
                $stmt->execute();
                $stmt->close();

                // c. Actualizar la seña del pedido (opcional, si lo necesitas para reporting)
                $stmt = $MiConexion->prepare("UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?");
                $stmt->bind_param("di", $montoAplicar, $trabajo['ID_PEDIDO']);
                $stmt->execute();
                $stmt->close();

                // d. Actualizar estado del pedido (si todos sus trabajos están pagados)
                ActualizarEstadoPedido($MiConexion, $trabajo['ID_PEDIDO']);

                $trabajosPagados[] = $trabajo['ID_DETALLE'];
            }
        }

        // 4. Actualizar observaciones del depósito con los trabajos pagados
        if (!empty($trabajosPagados)) {
            $obsTrabajos = "Trabajos pagados: " . implode(", #", $trabajosPagados);
            $observaciones .= " | " . $obsTrabajos;

            $stmt = $MiConexion->prepare("UPDATE movimientos_ctacte SET observaciones = ? WHERE idCliente = ? AND tipo = 'DEPOSITO' ORDER BY fecha DESC LIMIT 1");
            $stmt->bind_param("si", $observaciones, $data['idCliente']);
            $stmt->execute();
            $stmt->close();
        }

        // 5. El saldo restante ya queda como crédito en la cuenta (no se hace nada más)
        $response['saldoRestante'] = $saldoRestante; // Para debug
    } elseif ($esPagoDirecto) {
        // Obtener información del trabajo nuevamente para cálculos
        $sqlTrabajo = "SELECT dt.id_pedido_trabajos, dt.precio, dt.idEstadoTrabajo, pt.idCliente
                       FROM detalle_trabajos dt
                       JOIN pedido_trabajos pt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos
                       WHERE dt.idDetalleTrabajo = ? AND dt.idActivo = 1";
        $stmt = $MiConexion->prepare($sqlTrabajo);
        $idRef = $data['idReferencia'];
        $stmt->bind_param("i", $idRef);
        $stmt->execute();
        $trabajo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $precioTrabajo = floatval($trabajo['precio']);
        $saldoCliente = ObtenerSaldoCliente($MiConexion, $data['idCliente']);
        $saldoDisponible = max(0, $saldoCliente);
        
        $montoUsarSaldo = min($saldoDisponible, $precioTrabajo);
        $montoComplemento = $precioTrabajo - $montoUsarSaldo;
        
        // 1. Si se usa saldo, registrar el movimiento de débito de cuenta corriente
        if ($montoUsarSaldo > 0) {
            $success = ActualizarSaldoCliente(
                $MiConexion,
                $data['idCliente'],
                -$montoUsarSaldo, // Monto negativo (reduce el saldo)
                'PAGO_DIRECTO',
                $_SESSION['Usuario_Id'],
                $data['idReferencia'],
                'TRABAJO',
                "Pago directo trabajo #{$data['idReferencia']} - Parte con saldo cuenta corriente: $" . number_format($montoUsarSaldo, 2, ',', '.')
            );
            if (!$success) throw new Exception('Error al descontar del saldo de cuenta corriente', 500);
        }
        
        // 2. Si hay monto de complemento, registrar el pago externo
        if ($montoComplemento > 0) {
            $stmt = $MiConexion->prepare("INSERT INTO movimientos_ctacte (idCliente, tipo, monto, idUsuario, idReferencia, tipoReferencia, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $idCli = $data['idCliente'];
            $tipoMov = 'PAGO_DIRECTO';
            $montoExt = floatval($montoComplemento);
            $idUsu = $_SESSION['Usuario_Id'];
            $idRef = $data['idReferencia'];
            $tipoRef = 'TRABAJO';
            $obsComplemento = "Pago directo trabajo #{$data['idReferencia']} - Parte con {$data['metodo']}: $" . number_format($montoComplemento, 2, ',', '.');
            $stmt->bind_param("isdiiss", $idCli, $tipoMov, $montoExt, $idUsu, $idRef, $tipoRef, $obsComplemento);
            $stmt->execute();
            $stmt->close();
            
            // Registrar en caja solo el monto del complemento (no el del saldo)
            if (isset($_SESSION['Id_Caja']) && is_numeric($_SESSION['Id_Caja'])) {
                $idTipoPago = 1;
                if (strtoupper($data['metodo']) === 'TRANSFERENCIA') $idTipoPago = 2;
                elseif (strtoupper($data['metodo']) === 'CHEQUE') $idTipoPago = 3;

                $idTipoMovimientoCaja = 2; // Entrada de dinero
                $sqlCaja = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $MiConexion->prepare($sqlCaja);
                if (!$stmt) throw new Exception("Error en prepare() de caja: " . $MiConexion->error, 500);

                $idCaja = intval($_SESSION['Id_Caja']);
                $idUsuario = intval($_SESSION['Usuario_Id']);
                $montoCaja = floatval($montoComplemento); // Solo el complemento va a caja
                $obsCaja = mb_substr("PAGO_DIRECTO - Cliente #{$data['idCliente']} - Trabajo #{$data['idReferencia']} - Complemento via {$data['metodo']}", 0, 255, 'UTF-8');

                $stmt->bind_param("iiiids", $idCaja, $idTipoPago, $idTipoMovimientoCaja, $idUsuario, $montoCaja, $obsCaja);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // 3. Marcar el trabajo como pagado
        $stmt = $MiConexion->prepare("UPDATE detalle_trabajos SET idEstadoTrabajo = 7 WHERE idDetalleTrabajo = ?");
        $stmt->bind_param("i", $idRef);
        $stmt->execute();
        $stmt->close();

        // 4. Actualizar la seña del pedido
        $stmt = $MiConexion->prepare("UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?");
        $stmt->bind_param("di", $precioTrabajo, $idPedidoTrabajos); // Suma el precio total
        $stmt->execute();
        $stmt->close();

        // 5. Actualizar estado del pedido
        ActualizarEstadoPedido($MiConexion, $idPedidoTrabajos);
        
        // No procesar el movimiento de caja general aquí porque ya se procesó arriba solo para el complemento
        $procesarCaja = false; // Evitar que se procese caja al final del script
    } else {
        // Para ajustes, usar el monto ajustado calculado arriba
        $montoFinal = isset($montoAjuste) ? $montoAjuste : $data['monto'];
        $success = ActualizarSaldoCliente($MiConexion, $data['idCliente'], $montoFinal, $tipoMovimiento, $_SESSION['Usuario_Id'], null, null, $observaciones);
        if (!$success) throw new Exception('Error al registrar el ajuste', 500);
    }

    // Solo procesar caja si no es pago directo (porque ya se procesó arriba) o si no se procesó arriba
    if ($procesarCaja && isset($_SESSION['Id_Caja']) && is_numeric($_SESSION['Id_Caja'])) {
        $idTipoPago = 1;
        if (strtoupper($data['metodo']) === 'TRANSFERENCIA') $idTipoPago = 2;
        elseif (strtoupper($data['metodo']) === 'CHEQUE') $idTipoPago = 3;

        $idTipoMovimientoCaja = ($tipoMovimiento === 'DEPOSITO') ? 2 : 1;
        $sqlCaja = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $MiConexion->prepare($sqlCaja);
        if (!$stmt) throw new Exception("Error en prepare() de caja: " . $MiConexion->error, 500);

        $idCaja = intval($_SESSION['Id_Caja']);
        $idUsuario = intval($_SESSION['Usuario_Id']);
        $montoCaja = floatval($data['monto']);
        $obsCaja = mb_substr("$tipoMovimiento - Cliente #{$data['idCliente']} - $observaciones", 0, 255, 'UTF-8');

        $stmt->bind_param("iiiids", $idCaja, $idTipoPago, $idTipoMovimientoCaja, $idUsuario, $montoCaja, $obsCaja);
        $stmt->execute();
        $stmt->close();
    }

    $MiConexion->commit();

    $response = [
        'success' => true,
        'message' => 'Operación registrada correctamente',
        'nuevoSaldo' => ObtenerSaldoCliente($MiConexion, $data['idCliente']),
        'tipoOperacion' => $tipoMovimiento
    ];

} catch (Exception $e) {
    if (isset($MiConexion)) $MiConexion->rollback();

    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];

    error_log('[' . date('Y-m-d H:i:s') . '] Error en procesar_operacion_ctacte: ' . $e->getMessage());
} finally {
    if (isset($MiConexion)) $MiConexion->close();
}

echo json_encode($response);
exit;
?>