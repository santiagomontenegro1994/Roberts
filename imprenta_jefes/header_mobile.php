<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roberts Jefes</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="apple-touch-icon" href="../assets/img/Logo1.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Estilos globales para la versión móvil */
        body { 
            background-color: #f0f2f5; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-tap-highlight-color: transparent; /* Quita el recuadro azul al tocar botones en Android/iOS */
        }
        
        .card-custom {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Ajustes para que la pantalla completa de la PWA no choque con la barra de estado */
        @supports (padding-top: env(safe-area-inset-top)) {
            .top-navbar {
                padding-top: calc(15px + env(safe-area-inset-top));
            }
        }
    </style>
</head>
<body>