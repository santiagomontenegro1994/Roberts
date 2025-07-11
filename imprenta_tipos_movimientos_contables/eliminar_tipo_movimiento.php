<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

if (Anular_Tipo_Movimiento($MiConexion, $_GET['idTipoMovimiento']) != false) {
    $_SESSION['Mensaje'] .= 'Se ha eliminado el tipo de movimiento seleccionado.';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] .= 'No se pudo borrar el tipo de movimiento. <br />';
    $_SESSION['Estilo'] = 'warning';
}

header('Location: listados_tipos_movimientos.php');
exit;
?>