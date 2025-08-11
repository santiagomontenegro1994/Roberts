<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

if (Reactivar_Usuario($MiConexion, $_GET['ID_USUARIO'])) {
    $_SESSION['Mensaje'] = 'El usuario ha sido reactivado correctamente';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] = 'No se pudo reactivar el usuario. ' . mysqli_error($MiConexion);
    $_SESSION['Estilo'] = 'danger';
}

header('Location: ../imprenta_usuarios/listado_usuarios.php');
exit;
?>