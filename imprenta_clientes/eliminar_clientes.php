<?php
    session_start();
    if (empty($_SESSION['Usuario_Nombre']) ) {
        header('Location: ../core/cerrarsesion.php');
        exit;
    }
    
    require_once '../funciones/conexion.php';
    $MiConexion = ConexionBD();
   

    require_once '../funciones/select_general.php';

    if ( Anular_Cliente($MiConexion , $_GET['ID_CLIENTE']) != false ) {
        $_SESSION['Mensaje'].='Se ha eliminado la consulta seleccionada';
        $_SESSION['Estilo']='success';
    }else {
        $_SESSION['Mensaje'].='No se pudo borrar la consulta. <br /> ';
        $_SESSION['Estilo']='warning';
    }
    
   
    header('Location: ../imprenta_clientes/listados_clientes.php');
    exit;
?>