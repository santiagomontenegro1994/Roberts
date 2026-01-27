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

    $data['metodo'] = isset($_POST['metodo']) ? filter_input(INPUT_POST, 'metodo', FILTER_VALIDATE_INT) : 1;
    $data['observaciones'] = isset($_POST['observaciones']) ? filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING) : '';
    $data['idReferencia'] = isset($_POST['idReferencia']) ? filter_input(INPUT_POST, 'idReferencia', FILTER_VALIDATE_INT) : null;
    $data['usarSaldo'] = isset($_POST['usarSaldo']) ? filter_var($_POST['usarSaldo'], FILTER_VALIDATE_BOOLEAN) : false;
    $data['montoComplemento'] = isset($_POST['montoComplemento']) ? floatval($_POST['montoComplemento']) : 0;

    $MiConexion = ConexionBD();
    if (!$MiConexion) {
        throw new Exception('Error de conexión a la base de datos', 500);
    }

    // --- NUEVO: Obtener datos del cliente para usar el nombre en la caja ---
    $datosCliente = Obtener_Cliente_Por_ID($MiConexion, $data['idCliente']);
    $nombreCompletoCliente = $datosCliente ? ($datosCliente['NOMBRE'] . ' ' . $datosCliente['APELLIDO']) : 'Cliente #' . $data['idCliente'];

    // Validar método de pago (excluyendo Cta Cte ID 18)
    $tiposPagoEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
    $tiposPagoPermitidos = array_column(array_filter($tiposPagoEntrada, function($tipo) {
        return $tipo['idTipoPago'] != 18; 
    }), 'idTipoPago');

    if (!in_array($data['metodo'], $tiposPagoPermitidos)) {
        throw new Exception('Método de pago no válido', 400);
    }

    $MiConexion->begin_transaction();

    $tipoMovimiento = '';
    $esDeposito = false;
    $esPagoDirecto = false;
    $procesarCaja = true;

    switch (strtoupper($data['tipo'])) {
        case 'DEPOSITO':
            $tipoMovimiento = 'DEPOSITO';
            $esDeposito = true;
            $observaciones = "Depósito via " . ObtenerNombreTipoPago($MiConexion, $data['metodo']) . ". " . $data['observaciones'];
            break;
            
        case 'PAGO_DIRECTO':
            if (empty($data['idReferencia'])) {
                throw new Exception('Para pagos directos debe seleccionar un trabajo', 400);
            }
            
            // Obtener información del trabajo
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
            
            $precioTrabajo = floatval($trabajo['precio']);
            $saldoCliente = ObtenerSaldoCliente($MiConexion, $data['idCliente']);
            $saldoDisponible = max(0, $saldoCliente);
            
            if (abs($data['monto'] - $precioTrabajo) > 0.01) {
                throw new Exception("El monto debe ser exactamente $" . number_format($precioTrabajo, 2, ',', '.') . " (precio del trabajo)", 400);
            }

            $montoUsarSaldo = $data['usarSaldo'] ? min($saldoDisponible, $precioTrabajo) : 0;
            $montoComplemento = $precioTrabajo - $montoUsarSaldo;
            
            if ($montoComplemento > 0) {
                if (abs($data['montoComplemento'] - $montoComplemento) > 0.01) {
                    throw new Exception('El monto de complemento no coincide con el calculado', 400);
                }
            }

            $tipoMovimiento = 'PAGO_DIRECTO';
            $esPagoDirecto = true;
            $idPedidoTrabajos = $trabajo['id_pedido_trabajos'];
            
            $nombreMetodoPago = ObtenerNombreTipoPago($MiConexion, $data['metodo']);
            if ($montoUsarSaldo > 0 && $montoComplemento > 0) {
                $observaciones = "Pago directo pedido #{$idPedidoTrabajos} - Saldo: $" . number_format($montoUsarSaldo, 2, ',', '.') . ", {$nombreMetodoPago}: $" . number_format($montoComplemento, 2, ',', '.') . ". " . $data['observaciones'];
            } elseif ($montoUsarSaldo > 0) {
                $observaciones = "Pago directo pedido #{$idPedidoTrabajos} - Pagado completamente con saldo de cuenta corriente. " . $data['observaciones'];
            } else {
                $observaciones = "Pago directo pedido #{$idPedidoTrabajos} via {$nombreMetodoPago}. " . $data['observaciones'];
            }
            break;
            
        case 'AJUSTE':
            $tipoAjuste = isset($_POST['tipoAjuste']) ? strtoupper($_POST['tipoAjuste']) : 'A_FAVOR';
            $saldoActual = ObtenerSaldoCliente($MiConexion, $data['idCliente']);
            
            if ($tipoAjuste === 'A_FAVOR') {
                $montoAjuste = abs($data['monto']);
            } else {
                $montoAjuste = -abs($data['monto']);
            }
            
            $tipoMovimiento = 'AJUSTE';
            $motivo = isset($_POST['motivo']) ? filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING) : 'OTRO';
            $observaciones = "Ajuste ($tipoAjuste) por $motivo. " . $data['observaciones'];
            break;
    }

    if ($esDeposito) {
        // --- PASO 1: Registrar el depósito (Esto aumenta el saldo en la BD) ---
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

        // --- PASO 2: Obtener el NUEVO saldo total disponible ---
        // Aquí está la clave: Consultamos la BD después del depósito para ver cuánto hay realmente en total.
        $saldoTotalCliente = ObtenerSaldoCliente($MiConexion, $data['idCliente']);
        
        // Solo podemos usar dinero si el saldo es positivo.
        // (Si el cliente debía $50k y depositó $20k, su saldo es -$30k, no puede pagar trabajos específicos aún).
        $saldoParaPagar = max(0, $saldoTotalCliente);

        // --- PASO 3: Obtener trabajos pendientes ---
        $trabajosPendientes = Obtener_Trabajos_Pendientes_Por_Antiguedad($MiConexion, $data['idCliente']);
        
        $saldoRestante = $saldoParaPagar; // Usamos el TOTAL disponible, no solo el depósito
        $trabajosPagados = [];

        // --- PASO 4: Bucle de Pago Inteligente ---
        foreach ($trabajosPendientes as $trabajo) {
            // Verificamos si nos alcanza para este trabajo específico
            if ($saldoRestante >= $trabajo['PRECIO']) {
                $montoAplicar = $trabajo['PRECIO'];
                
                // Descontamos de nuestro contador local
                $saldoRestante -= $montoAplicar;

                // a. Registrar el movimiento de pago (reduce el saldo en la BD)
                $obsPagoAutomatico = "Pago automático pedido #{$trabajo['ID_PEDIDO']}";

                $success = ActualizarSaldoCliente(
                    $MiConexion,
                    $data['idCliente'],
                    -$montoAplicar, // Restamos
                    'APLICACION_AUTOMATICA',
                    $_SESSION['Usuario_Id'],
                    $trabajo['ID_DETALLE'],
                    'TRABAJO',
                    $obsPagoAutomatico
                );
                if (!$success) throw new Exception('Error al aplicar pago automático', 500);

                // b. Marcar el trabajo como pagado
                $stmt = $MiConexion->prepare("UPDATE detalle_trabajos SET idEstadoTrabajo = 7 WHERE idDetalleTrabajo = ?");
                $stmt->bind_param("i", $trabajo['ID_DETALLE']);
                $stmt->execute();
                $stmt->close();

                // c. Actualizar la seña del pedido
                $stmt = $MiConexion->prepare("UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?");
                $stmt->bind_param("di", $montoAplicar, $trabajo['ID_PEDIDO']);
                $stmt->execute();
                $stmt->close();

                // d. Actualizar estado del pedido
                ActualizarEstadoPedido($MiConexion, $trabajo['ID_PEDIDO']);

                $trabajosPagados[] = $trabajo['ID_PEDIDO']; 
            }
            // SI NO ALCANZA ($saldoRestante < precio), EL BUCLE CONTINÚA AL SIGUIENTE
            // (No hay un 'else break', por lo que intentará pagar el siguiente trabajo más barato si lo hay)
        }

        // 5. Actualizar observaciones del depósito
        if (!empty($trabajosPagados)) {
            $obsTrabajos = "Trabajos pagados: " . implode(", #", $trabajosPagados);
            $observaciones .= " | " . $obsTrabajos;

            $stmt = $MiConexion->prepare("UPDATE movimientos_ctacte SET observaciones = ? WHERE idCliente = ? AND tipo = 'DEPOSITO' ORDER BY fecha DESC LIMIT 1");
            $stmt->bind_param("si", $observaciones, $data['idCliente']);
            $stmt->execute();
            $stmt->close();
        }

        // Devolvemos el saldo restante real en la cuenta
        $response['saldoRestante'] = $saldoRestante; 

    } elseif ($esPagoDirecto) {
        // ... (Lógica de Pago Directo sin cambios, ya estaba correcta en la versión anterior) ...
        if ($montoUsarSaldo > 0) {
            $success = ActualizarSaldoCliente(
                $MiConexion,
                $data['idCliente'],
                -$montoUsarSaldo,
                'PAGO_DIRECTO',
                $_SESSION['Usuario_Id'],
                $data['idReferencia'],
                'TRABAJO',
                "Pago directo pedido #{$idPedidoTrabajos} - Parte con saldo cuenta corriente: $" . number_format($montoUsarSaldo, 2, ',', '.')
            );
            if (!$success) throw new Exception('Error al descontar del saldo de cuenta corriente', 500);
        }
        
        if ($montoComplemento > 0) {
            $stmt = $MiConexion->prepare("INSERT INTO movimientos_ctacte (idCliente, tipo, monto, idUsuario, idReferencia, tipoReferencia, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $idCli = $data['idCliente'];
            $tipoMov = 'PAGO_DIRECTO';
            $montoExt = floatval($montoComplemento);
            $idUsu = $_SESSION['Usuario_Id'];
            $idRef = $data['idReferencia'];
            $tipoRef = 'TRABAJO';
            $obsComplemento = "Pago directo pedido #{$idPedidoTrabajos} via " . ObtenerNombreTipoPago($MiConexion, $data['metodo']);
            $stmt->bind_param("isdiiss", $idCli, $tipoMov, $montoExt, $idUsu, $idRef, $tipoRef, $obsComplemento);
            $stmt->execute();
            $stmt->close();
            
            if (isset($_SESSION['Id_Caja']) && is_numeric($_SESSION['Id_Caja'])) {
                $idTipoPago = $data['metodo']; 
                $idTipoMovimientoCaja = 2;
                
                $sqlCaja = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $MiConexion->prepare($sqlCaja);
                if (!$stmt) throw new Exception("Error en prepare() de caja: " . $MiConexion->error, 500);

                $idCaja = intval($_SESSION['Id_Caja']);
                $idUsuario = intval($_SESSION['Usuario_Id']);
                $montoCaja = floatval($montoComplemento);
                $obsCaja = mb_substr("PAGO_DIRECTO - Cliente #{$data['idCliente']} - Pedido #{$idPedidoTrabajos} via " . ObtenerNombreTipoPago($MiConexion, $data['metodo']), 0, 255, 'UTF-8');

                $stmt->bind_param("iiiids", $idCaja, $idTipoPago, $idTipoMovimientoCaja, $idUsuario, $montoCaja, $obsCaja);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $stmt = $MiConexion->prepare("UPDATE detalle_trabajos SET idEstadoTrabajo = 7 WHERE idDetalleTrabajo = ?");
        $stmt->bind_param("i", $idRef);
        $stmt->execute();
        $stmt->close();

        $stmt = $MiConexion->prepare("UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?");
        $stmt->bind_param("di", $precioTrabajo, $idPedidoTrabajos);
        $stmt->execute();
        $stmt->close();

        ActualizarEstadoPedido($MiConexion, $idPedidoTrabajos);
        
        $procesarCaja = false;
    } else {
        $montoFinal = isset($montoAjuste) ? $montoAjuste : $data['monto'];
        $success = ActualizarSaldoCliente($MiConexion, $data['idCliente'], $montoFinal, $tipoMovimiento, $_SESSION['Usuario_Id'], null, null, $observaciones);
        if (!$success) throw new Exception('Error al registrar el ajuste', 500);
    }

    if ($procesarCaja && isset($_SESSION['Id_Caja']) && is_numeric($_SESSION['Id_Caja'])) {
        $idTipoPago = $data['metodo']; 
        $idTipoMovimientoCaja = ($tipoMovimiento === 'DEPOSITO') ? 2 : 1;
        
        $sqlCaja = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $MiConexion->prepare($sqlCaja);
        if (!$stmt) throw new Exception("Error en prepare() de caja: " . $MiConexion->error, 500);

        $idCaja = intval($_SESSION['Id_Caja']);
        $idUsuario = intval($_SESSION['Usuario_Id']);
        $montoCaja = floatval($data['monto']);
        $obsCaja = mb_substr("$tipoMovimiento - Cliente: $nombreCompletoCliente - $observaciones", 0, 255, 'UTF-8');

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