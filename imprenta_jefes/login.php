<?php 
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/login.php';

$MiConexion = ConexionBD();
$MiConexion->set_charset("utf8mb4"); 

date_default_timezone_set('America/Argentina/Cordoba');

$error = '';

// Si el usuario presiona el botón de Iniciar Sesión
if (!empty($_POST['BotonLogin'])) {
    
    // Llamamos a tu función exacta del sistema principal
    $UsuarioLogueado = DatosLogin($_POST['user'], $_POST['password'], $MiConexion);

    if (!empty($UsuarioLogueado)) {
        // Guardamos las sesiones EXACTAMENTE igual que en tu app grande
        $_SESSION['Usuario_Nombre'] = $UsuarioLogueado['NOMBRE'];
        $_SESSION['Usuario_Apellido'] = $UsuarioLogueado['APELLIDO'];
        $_SESSION['Usuario_Nivel'] = $UsuarioLogueado['NIVEL'];
        $_SESSION['Usuario_Id'] = $UsuarioLogueado['ID'];
        $_SESSION['Usuario_Tipo'] = $UsuarioLogueado['TIPO_USUARIO'];
        $_SESSION['Id_Caja'] = $UsuarioLogueado['ID_CAJA'];
        $_SESSION['Mensaje'] = '';
        $_SESSION['Estilo'] = '';

        // --- SISTEMA DE SESIÓN PERMANENTE (APP JEFES) ---
        if (isset($_POST['recordarme'])) {
            $token = bin2hex(random_bytes(32)); // Creamos una llave única
            $idUsuario = $UsuarioLogueado['ID'];
            // Guardamos la llave en la base de datos
            mysqli_query($MiConexion, "UPDATE usuarios SET token_sesion = '$token' WHERE idUsuario = $idUsuario");
            // Le guardamos la llave al celular por 90 días
            setcookie('token_jefes_roberts', $token, time() + (86400 * 90), '/'); 
        }

        // Lo mandamos al menú principal del celular
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceso Jefes | Roberts</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-login { width: 100%; max-width: 400px; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .logo-box { width: 80px; height: 80px; background: #fff; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: -40px auto 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .logo-box img { max-width: 90%; max-height: 90%; }
        .form-control { border-radius: 10px; padding: 12px 15px; }
        .btn-login { border-radius: 10px; padding: 12px; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="card card-login pt-4">
        <div class="logo-box">
            <img src="../assets/img/Logo1.png" alt="Logo">
        </div>
        <div class="card-body px-4 pb-4">
            <h4 class="text-center fw-bold mb-1">App Jefes</h4>
            <p class="text-center text-muted small mb-4">Gráfica Roberts</p>

            <?php if ($error): ?>
                <div class="alert alert-danger small py-2 rounded-3 text-center"><i class="bi bi-exclamation-circle me-1"></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted ms-1">Usuario</label>
                    <input type="text" name="user" class="form-control" required placeholder="Ingrese su Usuario">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted ms-1">Contraseña</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" checked style="transform: scale(1.2); margin-top: 3px;">
                    <label class="form-check-label ms-2" for="recordarme">Mantener sesión iniciada</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-login" name="BotonLogin" value="Login">INGRESAR</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>