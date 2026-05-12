<?php
session_start();
require_once '../funciones/conexion.php';

function verificarSesionApp() {
    $MiConexion = ConexionBD();

    // 1. Recuperar sesión vía Cookie si no está activa en PHP
    if (empty($_SESSION['Usuario_Nombre']) && isset($_COOKIE['token_jefes_roberts'])) {
        $token = mysqli_real_escape_string($MiConexion, $_COOKIE['token_jefes_roberts']);
        $query = mysqli_query($MiConexion, "SELECT * FROM usuarios WHERE token_sesion = '$token' AND idActivo = 1");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $usuario = mysqli_fetch_assoc($query);
            $_SESSION['Usuario_Id'] = $usuario['idUsuario'];
            $_SESSION['Usuario_Nombre'] = $usuario['nombre'];
            $_SESSION['Usuario_Apellido'] = $usuario['apellido'];
            $_SESSION['Usuario_Nivel'] = $usuario['idNivel'];
            $_SESSION['Usuario_Tipo'] = $usuario['idTipoUsuario']; // Aseguramos traer el tipo
        } else {
            setcookie('token_jefes_roberts', '', time() - 3600, '/');
        }
    }

    // 2. Si no hay sesión, al login
    if (empty($_SESSION['Usuario_Nombre'])) {
        header('Location: login.php');
        exit;
    }

    // 3. SEGURIDAD: Si hay sesión, pero NO es Admin (Nivel 1), lo echamos
    if ($_SESSION['Usuario_Nivel'] != 1) {
        header('Location: logout.php');
        exit;
    }
}
?>