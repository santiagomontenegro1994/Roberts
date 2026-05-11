<?php
session_start();
require_once '../funciones/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $MiConexion = ConexionBD();
    // Ajusta 'email' y 'clave' según cómo se llamen tus columnas en la tabla usuarios
    $user = mysqli_real_escape_string($MiConexion, $_POST['usuario']);
    $pass = mysqli_real_escape_string($MiConexion, $_POST['clave']); 

    $query = mysqli_query($MiConexion, "SELECT * FROM usuarios WHERE email = '$user' AND clave = '$pass' AND idActivo = 1");

    if ($query && mysqli_num_rows($query) > 0) {
        $datos = mysqli_fetch_assoc($query);
        
        $_SESSION['Usuario_Id'] = $datos['idUsuario'];
        $_SESSION['Usuario_Nombre'] = $datos['nombre'];
        $_SESSION['Usuario_Apellido'] = $datos['apellido'];
        $_SESSION['Usuario_Nivel'] = $datos['idNivel'];

        // Si tildó "Recordarme", generamos el Token Inmortal (dura 90 días)
        if (isset($_POST['recordarme'])) {
            $token = bin2hex(random_bytes(32)); // Clave de 64 caracteres
            mysqli_query($MiConexion, "UPDATE usuarios SET token_sesion = '$token' WHERE idUsuario = " . $datos['idUsuario']);
            setcookie('token_jefes_roberts', $token, time() + (86400 * 90), '/'); // 86400 = 1 día
        }

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-login { width: 100%; max-width: 400px; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .logo-box { width: 80px; height: 80px; background: #0d6efd; color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: -40px auto 20px; box-shadow: 0 5px 15px rgba(13,110,253,0.3); }
        .form-control { border-radius: 10px; padding: 12px 15px; }
        .btn-login { border-radius: 10px; padding: 12px; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="card card-login pt-4">
        <div class="logo-box">
            <i class="bi bi-briefcase-fill"></i>
        </div>
        <div class="card-body px-4 pb-4">
            <h4 class="text-center fw-bold mb-1">App Jefes</h4>
            <p class="text-center text-muted small mb-4">Gráfica Roberts</p>

            <?php if ($error): ?>
                <div class="alert alert-danger small py-2 rounded-3 text-center"><i class="bi bi-exclamation-circle me-1"></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted ms-1">Usuario / Email</label>
                    <input type="text" name="usuario" class="form-control" required placeholder="Tu correo">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted ms-1">Contraseña</label>
                    <input type="password" name="clave" class="form-control" required placeholder="••••••••">
                </div>
                
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" checked style="transform: scale(1.2); margin-top: 3px;">
                    <label class="form-check-label ms-2" for="recordarme">Mantener sesión iniciada</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-login">INGRESAR</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>