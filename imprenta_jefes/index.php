<?php
require_once 'auth_jefes.php';
verificarSesionApp();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard | App Jefes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .top-navbar { background: #fff; padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
        
        .main-menu { padding: 30px 20px; }
        .menu-btn { 
            background: #fff; border: none; border-radius: 16px; padding: 25px 20px; 
            margin-bottom: 20px; display: flex; align-items: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); transition: transform 0.2s;
            text-decoration: none; color: #333;
        }
        .menu-btn:active { transform: scale(0.98); }
        
        .btn-icon { 
            width: 60px; height: 60px; border-radius: 15px; 
            display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-right: 15px; 
        }
        .icon-blue { background: #e7f1ff; color: #0d6efd; }
        .icon-green { background: #e8f8f5; color: #198754; }
        .icon-purple { background: #f3e8ff; color: #6f42c1; }
        
        .btn-text h5 { margin: 0; font-weight: bold; font-size: 1.1rem; }
        .btn-text p { margin: 0; font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div>
        <h5 class="m-0 fw-bold">Hola, <?= htmlspecialchars($_SESSION['Usuario_Nombre']) ?></h5>
        <small class="text-muted">Gráfica Roberts</small>
    </div>
    <a href="logout.php" class="text-danger text-decoration-none" style="font-size: 1.5rem;"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="main-menu">
    
    <a href="presupuesto.php" class="menu-btn w-100">
        <div class="btn-icon icon-blue"><i class="bi bi-calculator-fill"></i></div>
        <div class="btn-text text-start">
            <h5>Generar Presupuesto</h5>
            <p>Cotizador y lista de precios (PDF)</p>
        </div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
    </a>

    <a href="carga_rapida.php" class="menu-btn w-100">
        <div class="btn-icon icon-green"><i class="bi bi-lightning-charge-fill"></i></div>
        <div class="btn-text text-start">
            <h5>Carga Rápida</h5>
            <p>Ingresar trabajo simplificado</p>
        </div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
    </a>

    <a href="facturar.php" class="menu-btn w-100">
        <div class="btn-icon icon-purple"><i class="bi bi-receipt"></i></div>
        <div class="btn-text text-start">
            <h5>Hacer Factura</h5>
            <p>Emisión rápida</p>
        </div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
    </a>

</div>

<?php
// Si llegas a crear el archivo logout.php, solo tiene que llevar esto:
/*
session_start();
session_destroy();
setcookie('token_jefes_roberts', '', time() - 3600, '/');
header('Location: login.php');
*/
?>
</body>
</html>