<?php
    session_start();
    if (empty($_SESSION['Usuario_Nombre']) ) {
        header('Location: cerrarsesion.php');
        exit;
    }
    
    require_once 'funciones/conexion.php';
    $MiConexion = ConexionBD();
   

    require_once 'funciones/select_general.php';

    if ( Eliminar_Libro($MiConexion , $_GET['ID_LIBRO']) != false ) {
        $_SESSION['Mensaje'].='Se ha eliminado el libro seleccionado';
        $_SESSION['Estilo']='success';
    }else {
        $_SESSION['Mensaje'].='No se pudo borrar el libro. <br /> ';
        $_SESSION['Estilo']='warning';
    }
    
   
    header('Location: listados_libros.php');
    exit;
?>