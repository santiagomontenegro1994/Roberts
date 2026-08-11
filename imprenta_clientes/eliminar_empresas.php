<?php
    session_start();
    if (empty($_SESSION['Usuario_Nombre']) ) {
        header('Location: ../core/cerrarsesion.php');
        exit;
    }
    
    require_once '../funciones/conexion.php';
    $MiConexion = ConexionBD();
    require_once '../funciones/imprenta.php';

    if ( Anular_Empresa($MiConexion, $_GET['ID_EMPRESA']) != false ) {
        $_SESSION['Mensaje'] = 'Se ha eliminado la empresa seleccionada';
        $_SESSION['Estilo'] = 'success';
    } else {
        $_SESSION['Mensaje'] = 'No se pudo borrar la empresa.';
        $_SESSION['Estilo'] = 'warning';
    }
    
    header('Location: listados_empresas.php');
    exit;
?>