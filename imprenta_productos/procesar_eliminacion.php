<?php
session_start();

// 1. Seguridad básica: verificar que haya sesión activa
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// 2. Recibir datos del formulario
$id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$password_ingresada = $_POST['password'] ?? '';
$idUsuario = $_SESSION['Usuario_ID']; 

// 3. Obtener la clave guardada (formato MD5)
$sql_user = "SELECT clave FROM usuarios WHERE idUsuario = '$idUsuario'";
$res_user = mysqli_query($MiConexion, $sql_user);
$user = mysqli_fetch_assoc($res_user);

// 4. Comparación MD5 (la forma en que tu app guarda las claves)
$clave_ingresada_md5 = md5($password_ingresada);

if ($user && $user['clave'] == $clave_ingresada_md5) {
    
    // Contraseña correcta: proceder a eliminar
    // Usamos transacción para asegurar que se borre todo o nada
    mysqli_begin_transaction($MiConexion);
    
    try {
        // Borrar primero los hijos (imágenes) para mantener integridad
        mysqli_query($MiConexion, "DELETE FROM productos_imagenes WHERE id_producto = '$id_producto'");
        
        // Borrar el producto
        mysqli_query($MiConexion, "DELETE FROM productos WHERE id = '$id_producto'");
        
        mysqli_commit($MiConexion);
        
        $_SESSION['Mensaje'] = "Producto eliminado correctamente.";
        $_SESSION['Estilo'] = "success";
    } catch (Exception $e) {
        mysqli_rollback($MiConexion);
        $_SESSION['Mensaje'] = "Error al intentar eliminar: " . $e->getMessage();
        $_SESSION['Estilo'] = "danger";
    }
} else {
    // Contraseña incorrecta
    $_SESSION['Mensaje'] = "Error: Contraseña incorrecta.";
    $_SESSION['Estilo'] = "danger";
}

// 5. Redirección de vuelta al inventario
header("Location: inventario.php");
exit;
?>