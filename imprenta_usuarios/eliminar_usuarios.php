<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Verificar que no sea el usuario actual
if ($_GET['ID_USUARIO'] == $_SESSION['Usuario_ID']) {
    $_SESSION['Mensaje'] = 'No puedes desactivar tu propio usuario mientras estás logueado.';
    $_SESSION['Estilo'] = 'danger';
    header('Location: ../imprenta_usuarios/listado_usuarios.php');
    exit;
}

if (Desactivar_Usuario($MiConexion, $_GET['ID_USUARIO'])) {
    $_SESSION['Mensaje'] = 'El usuario ha sido desactivado correctamente';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] = 'No se pudo desactivar el usuario. ' . mysqli_error($MiConexion);
    $_SESSION['Estilo'] = 'danger';
}

header('Location: ../imprenta_usuarios/listado_usuarios.php');
exit;
?>