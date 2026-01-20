<?php
// Archivo: procesar_cambio_cliente.php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

if (empty($_SESSION['Usuario_Nombre'])) { header('Location: ../core/cerrarsesion.php'); exit; }

$idPedido = $_POST['IdPedido'] ?? 0;
$idCliente = $_POST['idClienteNuevo'] ?? 0;

if ($idPedido > 0 && $idCliente > 0) {
    $conexion = ConexionBD();
    if (Cambiar_Cliente_Pedido($conexion, $idPedido, $idCliente)) {
        $_SESSION['Mensaje'] = "Cliente actualizado correctamente.";
        $_SESSION['Estilo'] = 'success';
    } else {
        $_SESSION['Mensaje'] = "Error al actualizar el cliente.";
        $_SESSION['Estilo'] = 'danger';
    }
}

// Volver al pedido
header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
exit;
?>