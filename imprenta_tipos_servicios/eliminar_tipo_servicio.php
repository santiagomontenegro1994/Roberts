<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Cambiar de Anular_Tipo_Pago a Anular_Tipo_Servicio
if (Anular_Tipo_Servicio($MiConexion, $_GET['idTipoServicio']) != false) {
    $_SESSION['Mensaje'] .= 'Se ha eliminado el tipo de servicio seleccionado.';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] .= 'No se pudo borrar el tipo de servicio. <br />';
    $_SESSION['Estilo'] = 'warning';
}

// Cambiar la redirección al listado de tipos de servicios
header('Location: listados_tipos_servicios.php');
exit;
?>