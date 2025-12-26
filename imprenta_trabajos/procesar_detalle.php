<?php
// --- ACTIVAR ERRORES PARA VERIFICAR EN HOSTINGER ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Validar sesión
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Obtener ID Pedido (Soporta POST y GET)
$idPedido = $_REQUEST['IdPedido'] ?? $_REQUEST['ID_PEDIDO'] ?? 0;

// Validar datos mínimos
$accion = $_REQUEST['accion'] ?? '';
$conexion = ConexionBD();

if (!$conexion || empty($accion) || empty($idPedido)) {
    $_SESSION['Mensaje'] = 'Error: Faltan datos obligatorios (Acción o ID Pedido).';
    $_SESSION['Estilo'] = 'danger';
    // Intentar volver aunque sea sin ID
    if($idPedido) header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
    else echo "Error crítico: No se recibió ID de pedido.";
    exit;
}

// Preparar datos para la función
$datos = [
    'idDetalle' => $_REQUEST['idDetalle'] ?? 0,
    'id_pedido_trabajos' => $idPedido,
    'idTrabajo' => $_POST['idTrabajo'] ?? 0,
    'precio' => $_POST['precio'] ?? 0,
    'fechaEntrega' => $_POST['fechaEntrega'] ?? '',
    'horaEntrega' => $_POST['horaEntrega'] ?? '',
    'descripcion' => $_POST['descripcion'] ?? '',
    'idProveedor' => $_POST['idProveedor'] ?? 0,
    'idEstadoTrabajo' => $_POST['idEstadoTrabajo'] ?? 0,
    'facturado' => isset($_POST['facturado']) ? 1 : 0,
    'idTipoFactura' => $_POST['idTipoFactura'] ?? null,
    'numeroFactura' => $_POST['numeroFactura'] ?? null
];

// EJECUTAR
$resultado = Procesar_Detalle_Trabajo($conexion, $accion, $datos);

if ($resultado) {
    $_SESSION['Mensaje'] = 'Cambios guardados correctamente.';
    $_SESSION['Estilo'] = 'success';
} else {
    // Si falla, probablemente quedó un error en el log de errores de PHP
    $_SESSION['Mensaje'] = 'Error al guardar. Verifica los datos ingresados.';
    $_SESSION['Estilo'] = 'danger';
}

// VOLVER
header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido&t=".time());
exit;
?>