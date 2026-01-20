<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

if (!empty($_GET['ID_CLIENTE'])) {
    $id = $_GET['ID_CLIENTE'];
    
    // Actualizamos el estado a 1 (Activo)
    $sql = "UPDATE clientes SET idActivo = 1 WHERE idCliente = '$id'";
    
    if (mysqli_query($MiConexion, $sql)) {
        $_SESSION['Mensaje'] = "Cliente reactivado correctamente.";
        $_SESSION['Estilo'] = "success";
    } else {
        $_SESSION['Mensaje'] = "Error al reactivar cliente.";
        $_SESSION['Estilo'] = "danger";
    }
}

// Volvemos al listado, forzando ver los inactivos para que el usuario vea que desapareció de la lista de borrados, 
// o lo mandamos a la lista normal. Lo mandaré a la lista normal para que vea que ya está disponible.
header('Location: listados_clientes.php');
exit;
?>