<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Validar sesión
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Obtener datos y normalizar nombres
$accion = $_REQUEST['accion'] ?? '';
$idDetalle = $_REQUEST['idDetalle'] ?? 0;
// A veces viene como IdPedido, a veces como ID_PEDIDO, aseguramos ambos:
$idPedido = $_REQUEST['IdPedido'] ?? $_REQUEST['ID_PEDIDO'] ?? 0;

$conexion = ConexionBD();

// Validaciones básicas
if (!$conexion || empty($accion) || empty($idPedido)) {
    $_SESSION['Mensaje'] = 'Error: Datos incompletos o fallo de conexión.';
    $_SESSION['Estilo'] = 'danger';
    header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
    exit;
}

// Preparar array de datos
$facturado = isset($_POST['facturado']) ? 1 : 0; // Checkbox suele no enviarse si no está marcado

$datos = [
    'idDetalle' => $idDetalle,
    'id_pedido_trabajos' => $idPedido,
    'idTrabajo' => $_POST['idTrabajo'] ?? 0,
    'precio' => $_POST['precio'] ?? 0,
    'fechaEntrega' => $_POST['fechaEntrega'] ?? '',
    'horaEntrega' => $_POST['horaEntrega'] ?? '',
    'descripcion' => $_POST['descripcion'] ?? '',
    'idProveedor' => $_POST['idProveedor'] ?? 0,
    'idEstadoTrabajo' => $_POST['idEstadoTrabajo'] ?? 0,
    'facturado' => $facturado,
    'idTipoFactura' => $_POST['idTipoFactura'] ?? null,
    'numeroFactura' => $_POST['numeroFactura'] ?? null
];

// Ejecutar Función
$resultado = Procesar_Detalle_Trabajo($conexion, $accion, $datos);

if ($resultado) {
    $_SESSION['Mensaje'] = 'Operación realizada correctamente';
    $_SESSION['Estilo'] = 'success';
} else {
    $_SESSION['Mensaje'] = 'Hubo un error al procesar la solicitud.';
    $_SESSION['Estilo'] = 'danger';
}

// Volver al pedido
header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido&t=".time());
exit;
?>