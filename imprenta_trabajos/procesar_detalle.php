<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Validar sesión y permisos
if (empty($_SESSION['Usuario_Nombre'])) {
    $_SESSION['Mensaje'] = 'Acceso no autorizado';
    $_SESSION['Estilo'] = 'danger';
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Obtener datos del formulario
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
$idDetalle = $_GET['id'] ?? $_POST['idDetalle'] ?? 0;

// Obtener ID del pedido - para eliminación viene por GET
$idPedido = $_GET['ID_PEDIDO'] ?? $_POST['IdPedido'] ?? 0;

// Validar acción y IDs
if (!in_array($accion, ['agregar', 'editar', 'eliminar'])) {
    $_SESSION['Mensaje'] = 'Acción no válida';
    $_SESSION['Estilo'] = 'danger';
    header('Location: listados_pedidos_trabajos.php');
    exit;
}

// Validación específica para eliminación
if ($accion === 'eliminar') {
    if ($idDetalle <= 0 || $idPedido <= 0) {
        $_SESSION['Mensaje'] = 'Parámetros inválidos para eliminación';
        $_SESSION['Estilo'] = 'danger';
        header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
        exit;
    }
}

$conexion = ConexionBD();
if (!$conexion) {
    $_SESSION['Mensaje'] = 'Error de conexión a la base de datos';
    $_SESSION['Estilo'] = 'danger';
    header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
    exit;
}

// Preparar datos para la función
$datos = [
    'idDetalle' => $idDetalle,
    'idTrabajo' => $_POST['idTrabajo'] ?? 0,
    'precio' => $_POST['precio'] ?? 0,
    'fechaEntrega' => $_POST['fechaEntrega'] ?? '',
    'horaEntrega' => $_POST['horaEntrega'] ?? '',
    'descripcion' => $_POST['descripcion'] ?? '',
    'idProveedor' => $_POST['idProveedor'] ?? 0,
    'idEstadoTrabajo' => $_POST['idEstadoTrabajo'] ?? 0
];

// Para acciones de agregar, necesitamos el ID del pedido
if ($accion === 'agregar') {
    $datos['id_pedido_trabajos'] = $idPedido;
}

// Procesar la acción
$resultado = Procesar_Detalle_Trabajo($conexion, $accion, $datos);

if ($resultado) {
    $_SESSION['Mensaje'] = 'Operación realizada correctamente';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] = 'Error al procesar el detalle: ' . ($conexion->error ?? 'Error desconocido');
    $_SESSION['Estilo'] = 'danger';
}

// Redireccionar de vuelta a la página del pedido
header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
exit;
?>