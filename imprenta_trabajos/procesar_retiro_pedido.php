<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

if (empty($_SESSION['Usuario_Nombre']) || empty($_POST['idPedido'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

$MiConexion = ConexionBD();
$idPedido = intval($_POST['idPedido']);
$metodoPago = $_POST['metodoPago'];

try {
    // 1. Obtener detalles del pedido
    $detalles = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
    
    // 2. Determinar acciones según método de pago
    if ($metodoPago == '18') { // ID de cuenta corriente
        // Solo cambiar estados a Cuenta Corriente (8)
        $nuevoEstado = 8;
        $mensaje = "Pedido marcado como Cuenta Corriente";
    } else {
        // Para otros métodos: cambiar estados a Entregado (7) y actualizar seña
        $nuevoEstado = 7;
        
        // Calcular el saldo a pagar (precio total - seña actual)
        $precioTotal = 0;
        foreach ($detalles as $detalle) {
            $precioTotal += $detalle['PRECIO'];
        }
        
        $sqlSenia = "SELECT senia FROM pedido_trabajos WHERE idPedidoTrabajos = $idPedido";
        $resultSenia = mysqli_query($MiConexion, $sqlSenia);
        $seniaActual = mysqli_fetch_assoc($resultSenia)['senia'] ?? 0;
        $montoAPagar = $precioTotal - $seniaActual;
        
        // Actualizar la seña al precio total
        if (!Marcar_Pedido_Como_Pagado($MiConexion, $idPedido)) {
            throw new Exception("Error al actualizar la seña");
        }
        
        // Registrar el movimiento en caja solo si hay monto a pagar
        if ($montoAPagar > 0) {
            // Obtener el tipo de movimiento para "Trabajo"
            $idTipoMovimiento = 3;
            
            // Verificar que la caja esté abierta
            if (empty($_SESSION['Id_Caja'])) {
                throw new Exception("No hay una caja abierta para registrar el pago");
            }
            
            $sqlInsertMovimiento = "INSERT INTO detalle_caja 
                                  (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones)
                                  VALUES (
                                      '".$_SESSION['Id_Caja']."',
                                      '$metodoPago',
                                      '$idTipoMovimiento',
                                      '".$_SESSION['Usuario_Id']."',
                                      '$montoAPagar',
                                      'Pago del pedido #$idPedido'
                                  )";
            
            if (!mysqli_query($MiConexion, $sqlInsertMovimiento)) {
                throw new Exception("Error al registrar el pago en caja: ".mysqli_error($MiConexion));
            }
        }
        
        $mensaje = "Pedido retirado y marcado como pagado";
    }
    
    // 3. Actualizar todos los detalles (solo los no entregados)
    foreach ($detalles as $detalle) {
        if ($detalle['ESTADO_ID'] != 7) { // No modificar ya entregados
            Actualizar_Estado_Detalle($MiConexion, $detalle['ID_DETALLE'], $nuevoEstado);
        }
    }
    
    // 4. Actualizar estado general del pedido
    ActualizarEstadoPedido($MiConexion, $idPedido);
    
    $_SESSION['Mensaje'] = $mensaje;
    $_SESSION['Estilo'] = 'success';
    
} catch (Exception $e) {
    $_SESSION['Mensaje'] = "Error al retirar el pedido: " . $e->getMessage();
    $_SESSION['Estilo'] = 'danger';
}

header("Location: listados_pedidos_trabajos.php");
exit;
?>