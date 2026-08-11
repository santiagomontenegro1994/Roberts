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
    // Si es una petición AJAX, responder con JSON en lugar de redirigir con HTML
    if ($accion === 'cambiar_estado_rapido') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
        exit;
    }

    $_SESSION['Mensaje'] = 'Error: Faltan datos obligatorios (Acción o ID Pedido).';
    $_SESSION['Estilo'] = 'danger';
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
    'idEstadoTrabajo' => $_REQUEST['idEstadoTrabajo'] ?? 0, // Usamos _REQUEST para asegurar compatibilidad con AJAX y Formularios
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
    $_SESSION['Mensaje'] = 'Error al guardar. Verifica los datos ingresados.';
    $_SESSION['Estilo'] = 'danger';
}

// --- RESPUESTA ESPECÍFICA PARA AJAX (CAMBIO RÁPIDO DE ESTADO) ---
if ($accion === 'cambiar_estado_rapido') {
    header('Content-Type: application/json');
    if ($resultado) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false]);
    }
    exit;
}
// -----------------------------------------------------------------

// VOLVER (Para los formularios tradicionales de agregar, editar y eliminar)
header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido&t=".time());
exit;
?>