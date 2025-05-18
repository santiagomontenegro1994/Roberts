<?php
    session_start();
    if (empty($_SESSION['Usuario_Nombre']) ) {
        header('Location: cerrarsesion.php');
        exit;
    }
    
    require_once 'funciones/conexion.php';
    $MiConexion = ConexionBD();
   

    require_once 'funciones/select_general.php';
    echo '<script>console.log("entre");</script>';
    if ( Anular_Pedido($MiConexion , $_GET['ID_PEDIDO']) != false ) {
        $_SESSION['Mensaje'].='Se ha eliminado el pedido seleccionado';
        $_SESSION['Estilo']='success';
    }else {
        $_SESSION['Mensaje'].='No se pudo anular el pedido. <br /> ';
        $_SESSION['Estilo']='warning';
    }
   
    header('Location: listados_pedidos.php');
    exit;
?>