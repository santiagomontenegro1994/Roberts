<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

if (!empty($_GET['ID_EMPRESA'])) {
    $id = $_GET['ID_EMPRESA'];
    $sql = "UPDATE empresas SET idActivo = 1 WHERE idEmpresa = '$id'";
    
    if (mysqli_query($MiConexion, $sql)) {
        $_SESSION['Mensaje'] = "Empresa reactivada correctamente.";
        $_SESSION['Estilo'] = "success";
    } else {
        $_SESSION['Mensaje'] = "Error al reactivar empresa.";
        $_SESSION['Estilo'] = "danger";
    }
}

header('Location: listados_empresas.php');
exit;
?>