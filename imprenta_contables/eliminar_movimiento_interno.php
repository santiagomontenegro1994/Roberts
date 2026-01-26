<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// Recibir ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Borrar de la tabla de transferencias
    $stmt = $MiConexion->prepare("DELETE FROM movimientos_internos WHERE idMovimientoInterno = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['Mensaje'] = 'Transferencia eliminada correctamente.';
        $_SESSION['Estilo'] = 'success';
    } else {
        $_SESSION['Mensaje'] = 'Error al eliminar: ' . $stmt->error;
        $_SESSION['Estilo'] = 'danger';
    }
    $stmt->close();
} else {
    $_SESSION['Mensaje'] = 'ID inválido.';
    $_SESSION['Estilo'] = 'warning';
}

header('Location: movimientos_contables.php');
exit;
?>