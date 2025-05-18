<?php
session_start();

// Verificar si el usuario está logueado
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Verificar si se recibió un idCaja válido
if (!empty($_GET['idCaja'])) {
    $_SESSION['Id_Caja'] = $_GET['idCaja']; // Asignar el idCaja a la sesión
    $_SESSION['Mensaje'] = 'Caja seleccionada correctamente.';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] = 'Error: No se pudo seleccionar la caja.';
    $_SESSION['Estilo'] = 'danger';
}

// Redirigir de vuelta al listado de cajas
header('Location: listados_caja.php');
exit;
?>