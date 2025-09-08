<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Recibir correctamente el ID desde la URL
$idRetiro = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idRetiro > 0) {
    $resultado = Eliminar_Movimiento_Contable($MiConexion, $idRetiro);
    if ($resultado === true) {
        $_SESSION['Mensaje'] = 'Se ha eliminado el movimiento contable seleccionado.';
        $_SESSION['Estilo'] = 'success';
    } else {
        $_SESSION['Mensaje'] = 'No se pudo eliminar el movimiento contable. Error: ' . $resultado;
        $_SESSION['Estilo'] = 'warning';
    }
} else {
    $_SESSION['Mensaje'] = 'ID de movimiento contable no válido.';
    $_SESSION['Estilo'] = 'warning';
}

header('Location: movimientos_contables.php');
exit;

?>
