<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();
require_once '../funciones/imprenta.php';

$idDetalleCaja = $_GET['idDetalleCaja'];

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