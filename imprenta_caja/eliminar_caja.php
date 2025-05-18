<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Llamar a la función para eliminar una caja
if (Anular_Caja($MiConexion, $_GET['idCaja']) != false) {
    $_SESSION['Mensaje'] .= 'Se ha eliminado la caja seleccionada.';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] .= 'No se pudo borrar la caja. <br />';
    $_SESSION['Estilo'] = 'warning';
}

// Redirigir al listado de cajas
header('Location: listados_caja.php');
exit;
?>