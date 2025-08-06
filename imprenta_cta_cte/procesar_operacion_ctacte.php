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

    $MiConexion = ConexionBD();
    if (!$MiConexion) {
        throw new Exception('Error de conexión a la base de datos', 500);
    }

    $MiConexion->begin_transaction();

    $tipoMovimiento = '';
    $esDeposito = false;
    $esPagoDirecto = false;

    switch (strtoupper($data['tipo'])) {
        case 'DEPOSITO':
            $tipoMovimiento = 'DEPOSITO';
            $esDeposito = true;
            break;
        case 'PAGO_DIRECTO':
            if (empty($data['idReferencia'])) {
                throw new Exception('Para pagos directos debe seleccionar un trabajo', 400);
            }
            $tipoMovimiento = 'PAGO_DIRECTO';
            $esPagoDirecto = true;
            break;
        case 'AJUSTE':
            $tipoMovimiento = 'AJUSTE';
            break;
        default:
            throw new Exception('Tipo de operación no válido', 400);
    }

    $observaciones = '';
    $idPedidoTrabajos = null;

    if ($tipoMovimiento === 'DEPOSITO') {
        $observaciones = "Depósito via {$data['metodo']}. {$data['observaciones']}";
    } elseif ($tipoMovimiento === 'PAGO_DIRECTO') {
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
        if (abs($data['monto'] - $trabajo['precio']) > 0.01) {
            throw new Exception('El monto debe ser exactamente $' . number_format($trabajo['precio'], 2, ',', '.'), 400);
        }
        if ($trabajo['idEstadoTrabajo'] == 7) {
            throw new Exception('Este trabajo ya fue pagado anteriormente', 400);
        }

        $idPedidoTrabajos = $trabajo['id_pedido_trabajos'];
        $observaciones = "Pago directo trabajo #{$data['idReferencia']}. {$data['observaciones']}";
    } elseif ($tipoMovimiento === 'AJUSTE') {
        $motivo = isset($_POST['motivo']) ? filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING) : 'OTRO';
        $observaciones = "Ajuste por $motivo. {$data['observaciones']}";
    }

    if ($esDeposito) {
        $success = ActualizarSaldoCliente($MiConexion, $data['idCliente'], $data['monto'], 'DEPOSITO', $_SESSION['Usuario_Id'], null, null, $observaciones);
        if (!$success) throw new Exception('Error al registrar el depósito', 500);

        $trabajosPendientes = Obtener_Trabajos_Pendientes_Por_Antiguedad($MiConexion, $data['idCliente']);
        $saldoRestante = $data['monto'];
        $trabajosPagados = [];

        foreach ($trabajosPendientes as $trabajo) {
            if ($saldoRestante <= 0) break;

            $montoAplicar = min($saldoRestante, $trabajo['PRECIO']);
            $saldoRestante -= $montoAplicar;

            $success = ActualizarSaldoCliente($MiConexion, $data['idCliente'], $montoAplicar, 'APLICACION_AUTOMATICA', $_SESSION['Usuario_Id'], $trabajo['ID_DETALLE'], 'TRABAJO', "Aplicación automática de saldo a trabajo #{$trabajo['ID_DETALLE']}");
            if (!$success) throw new Exception('Error al aplicar saldo a trabajos', 500);

            $stmt = $MiConexion->prepare("UPDATE detalle_trabajos SET idEstadoTrabajo = 7 WHERE idDetalleTrabajo = ?");
            $idDetalle = $trabajo['ID_DETALLE'];
            $stmt->bind_param("i", $idDetalle);
            $stmt->execute();
            $stmt->close();

            $stmt = $MiConexion->prepare("UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?");
            $montoAplicarF = floatval($montoAplicar);
            $idPedido = intval($trabajo['ID_PEDIDO']);
            $stmt->bind_param("di", $montoAplicarF, $idPedido);
            $stmt->execute();
            $stmt->close();

            ActualizarEstadoPedido($MiConexion, $trabajo['ID_PEDIDO']);
            $trabajosPagados[] = $trabajo['ID_DETALLE'];
        }

        if (!empty($trabajosPagados)) {
            $obsTrabajos = "Trabajos pagados automáticamente: " . implode(", #", $trabajosPagados);
            $observaciones .= " | " . $obsTrabajos;

            $stmt = $MiConexion->prepare("UPDATE movimientos_ctacte SET observaciones = ? WHERE idCliente = ? AND tipo = 'DEPOSITO' ORDER BY fecha DESC LIMIT 1");
            $obsUpdate = $observaciones;
            $idCli = $data['idCliente'];
            $stmt->bind_param("si", $obsUpdate, $idCli);
            $stmt->execute();
            $stmt->close();
        }

        $response['trabajosPagados'] = $trabajosPagados;
        $response['saldoRestante'] = $saldoRestante;

    } elseif ($esPagoDirecto) {
        $stmt = $MiConexion->prepare("INSERT INTO movimientos_ctacte (idCliente, tipo, monto, idUsuario, idReferencia, tipoReferencia, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $idCli = $data['idCliente'];
        $tipoMov = $tipoMovimiento;
        $monto = floatval($data['monto']);
        $idUsu = $_SESSION['Usuario_Id'];
        $idRef = $data['idReferencia'];
        $tipoRef = 'TRABAJO';
        $obs = $observaciones;
        $stmt->bind_param("isdiiss", $idCli, $tipoMov, $monto, $idUsu, $idRef, $tipoRef, $obs);
        $stmt->execute();
        $stmt->close();

        $stmt = $MiConexion->prepare("UPDATE detalle_trabajos SET idEstadoTrabajo = 7 WHERE idDetalleTrabajo = ?");
        $stmt->bind_param("i", $idRef);
        $stmt->execute();
        $stmt->close();

        $stmt = $MiConexion->prepare("UPDATE pedido_trabajos SET senia = senia + ? WHERE idPedidoTrabajos = ?");
        $stmt->bind_param("di", $monto, $idPedidoTrabajos);
        $stmt->execute();
        $stmt->close();

        ActualizarEstadoPedido($MiConexion, $idPedidoTrabajos);

    } else {
        $success = ActualizarSaldoCliente($MiConexion, $data['idCliente'], $data['monto'], $tipoMovimiento, $_SESSION['Usuario_Id'], null, null, $observaciones);
        if (!$success) throw new Exception('Error al registrar el ajuste', 500);
    }

    if (isset($_SESSION['Id_Caja']) && is_numeric($_SESSION['Id_Caja'])) {
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
