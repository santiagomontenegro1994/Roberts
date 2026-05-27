<?php
session_start();

// 1. Seguridad básica: verificar que haya sesión activa
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// DEFINIMOS LA RUTA FÍSICA ABSOLUTA PARA BORRAR LAS IMÁGENES
$ruta_servidor_img = "/home/u922707138/domains/robertsgrafica.com/public_html/img/";

// 2. Recibir datos del formulario (Aseguramos que sean del tipo correcto para evitar inyecciones)
$id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
$password_ingresada = $_POST['password'] ?? '';
$idUsuario = (int)$_SESSION['Usuario_ID']; 

// 3. Obtener la clave guardada (formato MD5)
$sql_user = "SELECT clave FROM usuarios WHERE idUsuario = $idUsuario";
$res_user = mysqli_query($MiConexion, $sql_user);
$user = mysqli_fetch_assoc($res_user);

// 4. Comparación MD5
$clave_ingresada_md5 = md5($password_ingresada);

if ($user && $user['clave'] == $clave_ingresada_md5) {
    
    // Contraseña correcta: proceder a eliminar
    mysqli_begin_transaction($MiConexion);
    
    try {
        // --- PASO A: BORRAR LOS ARCHIVOS FÍSICOS DEL SERVIDOR ---
        
        // 1. Buscar y borrar la imagen principal del producto
        $res_prod = mysqli_query($MiConexion, "SELECT imagen FROM productos WHERE id = $id_producto");
        if ($prod = mysqli_fetch_assoc($res_prod)) {
            if (!empty($prod['imagen']) && $prod['imagen'] != 'productos/sin-imagen.jpg') {
                $ruta_img_principal = $ruta_servidor_img . $prod['imagen'];
                if (file_exists($ruta_img_principal)) {
                    unlink($ruta_img_principal);
                }
            }
        }

        // 2. Buscar y borrar las imágenes de las variantes
        $res_var = mysqli_query($MiConexion, "SELECT nombre_imagen FROM productos_imagenes WHERE id_producto = $id_producto");
        while ($var = mysqli_fetch_assoc($res_var)) {
            if (!empty($var['nombre_imagen'])) {
                $ruta_img_var = $ruta_servidor_img . $var['nombre_imagen'];
                if (file_exists($ruta_img_var)) {
                    unlink($ruta_img_var);
                }
            }
        }

        // --- PASO B: BORRAR REGISTROS DE LA BASE DE DATOS ---
        
        // Borrar primero los hijos para mantener integridad
        mysqli_query($MiConexion, "DELETE FROM productos_imagenes WHERE id_producto = $id_producto");
        mysqli_query($MiConexion, "DELETE FROM producto_categoria WHERE id_producto = $id_producto"); // Limpiamos la tabla puente
        
        // Borrar el producto padre
        mysqli_query($MiConexion, "DELETE FROM productos WHERE id = $id_producto");
        
        mysqli_commit($MiConexion);
        
        $_SESSION['Mensaje'] = "Producto e imágenes eliminados correctamente.";
        $_SESSION['Estilo'] = "success";
    } catch (Exception $e) {
        mysqli_rollback($MiConexion);
        $_SESSION['Mensaje'] = "Error al intentar eliminar: " . $e->getMessage();
        $_SESSION['Estilo'] = "danger";
    }
} else {
    // Contraseña incorrecta
    $_SESSION['Mensaje'] = "Error: Contraseña incorrecta. No se borró el producto.";
    $_SESSION['Estilo'] = "danger";
}

// 5. Redirección de vuelta al inventario
header("Location: inventario.php");
exit;
?>