<?php
    session_start();
    if (empty($_SESSION['Usuario_Nombre']) ) {
        header('Location: ../core/cerrarsesion.php');
        exit;
    }
    
    require_once '../funciones/conexion.php';
    $MiConexion = ConexionBD();
   

    require_once '../funciones/imprenta.php';

    if ( Anular_Pedidos_Trabajo($MiConexion , $_GET['ID_PEDIDO']) != false ) {
        $_SESSION['Mensaje'].='Se ha eliminado el pedido seleccionado.';
        $_SESSION['Estilo']='success';
    }else {
        $_SESSION['Mensaje'].='No se pudo borrar el pedido. <br /> ';
        $_SESSION['Estilo']='warning';
    }
    
   
    header('Location: ../imprenta_trabajos/listados_pedidos_trabajos.php');
    exit;
?>