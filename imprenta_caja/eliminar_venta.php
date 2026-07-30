<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();
require_once '../funciones/imprenta.php';

$idDetalleCaja = (int)$_GET['idDetalleCaja'];
$idUsuario = isset($_SESSION['Usuario_Id']) ? (int)$_SESSION['Usuario_Id'] : 0;

// Reponer stock si la venta a eliminar tenía insumos asociados
AnularMovimientosStockVenta($MiConexion, $idDetalleCaja, $idUsuario);

if (Anular_DetalleCaja($MiConexion, $idDetalleCaja)) {
    $_SESSION['Mensaje'] = 'El detalle seleccionado se ha eliminado correctamente.';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] = 'No se pudo eliminar el detalle.';
    $_SESSION['Estilo'] = 'warning';
}

header('Location: planilla_caja.php');
exit;

?>