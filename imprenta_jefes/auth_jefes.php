<?php
session_start();
require_once '../funciones/conexion.php';

function verificarSesionApp() {
    $MiConexion = ConexionBD();

    // 1. Si no hay sesión activa en PHP, pero el celular tiene la Cookie guardada...
    if (empty($_SESSION['Usuario_Nombre']) && isset($_COOKIE['token_jefes_roberts'])) {
        $token = mysqli_real_escape_string($MiConexion, $_COOKIE['token_jefes_roberts']);
        
        // Buscamos a quién le pertenece este token
        $query = mysqli_query($MiConexion, "SELECT * FROM usuarios WHERE token_sesion = '$token' AND idActivo = 1");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $usuario = mysqli_fetch_assoc($query);
            // ¡Lo encontramos! Le armamos la sesión automáticamente
            $_SESSION['Usuario_Id'] = $usuario['idUsuario'];
            $_SESSION['Usuario_Nombre'] = $usuario['nombre'];
            $_SESSION['Usuario_Apellido'] = $usuario['apellido'];
            $_SESSION['Usuario_Nivel'] = $usuario['idNivel'];
        } else {
            // El token es viejo o inválido, borramos la cookie
            setcookie('token_jefes_roberts', '', time() - 3600, '/');
        }
    }

    // 2. Si después de revisar todo, sigue sin haber sesión, lo mandamos al Login
    if (empty($_SESSION['Usuario_Nombre'])) {
        header('Location: login.php');
        exit;
    }
}
?>