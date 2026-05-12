<?php
session_start();
date_default_timezone_set('America/Argentina/Cordoba'); 
require_once '../funciones/conexion.php';

header('Content-Type: application/json');

if (empty($_SESSION['Usuario_Id'])) {
    echo json_encode(['success' => false, 'error' => 'No hay sesión activa']);
    exit;
}

$MiConexion = ConexionBD();

$cliente = mysqli_real_escape_string($MiConexion, $_POST['cliente'] ?? 'Cliente Gral.');
$total = floatval($_POST['total'] ?? 0);
$items_json = mysqli_real_escape_string($MiConexion, $_POST['items'] ?? '[]');
$idUsuario = $_SESSION['Usuario_Id'];

// Atrapamos la fecha que eligieron en el celular, o usamos la de hoy si falla
$fecha = mysqli_real_escape_string($MiConexion, $_POST['fecha'] ?? date('Y-m-d'));
// Le agregamos la hora actual para que la base de datos (DATETIME) no se queje
$fecha_hora = $fecha . ' ' . date('H:i:s'); 

// Guardamos en la tabla
$sql = "INSERT INTO presupuestos_historial (cliente_nombre, fecha, total, items_json, idUsuario) 
        VALUES ('$cliente', '$fecha_hora', $total, '$items_json', $idUsuario)";

if (mysqli_query($MiConexion, $sql)) {
    $idInsertado = mysqli_insert_id($MiConexion);
    echo json_encode(['success' => true, 'id_presupuesto' => $idInsertado]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($MiConexion)]);
}
?>