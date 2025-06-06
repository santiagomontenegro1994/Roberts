<?php
    session_start();
    if (empty($_SESSION['Usuario_Nombre'])) {
        header('Location: ../core/cerrarsesion.php');
        exit;
    }
    
    require_once '../funciones/conexion.php';
    $MiConexion = ConexionBD();

    require_once '../funciones/imprenta.php';

    if (Anular_Venta($MiConexion, $_GET['idDetalleCaja']) != false) {
        $_SESSION['Mensaje'] .= 'Se ha eliminado la venta seleccionada.';
        $_SESSION['Estilo'] = 'success';
    } else {
        $_SESSION['Mensaje'] .= 'No se pudo borrar la venta. <br />';
        $_SESSION['Estilo'] = 'warning';
    }

    header('Location: planilla_caja.php');
    exit;
?>