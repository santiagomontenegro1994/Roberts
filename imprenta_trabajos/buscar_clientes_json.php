<?php
// Archivo: buscar_clientes_json.php
require_once '../funciones/conexion.php';
session_start();

// Si no hay sesión o búsqueda, devolver array vacío
if (empty($_SESSION['Usuario_Nombre']) || empty($_GET['q'])) exit('[]');

$busqueda = $_GET['q'];
$conexion = ConexionBD();

// Busca por Nombre, Apellido o Teléfono
// Y FILTRA para que idActivo NO sea 2
$sql = "SELECT idCliente, nombre, apellido, telefono 
        FROM clientes 
        WHERE (nombre LIKE ? OR apellido LIKE ? OR telefono LIKE ?) 
        AND idActivo != 2 
        LIMIT 10";

// Preparamos los comodines para el LIKE
$param = "%$busqueda%";
$stmt = $conexion->prepare($sql);

// "sss" significa que pasamos 3 strings (nombre, apellido, telefono)
$stmt->bind_param("sss", $param, $param, $param);

$stmt->execute();
$result = $stmt->get_result();

$clientes = [];
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($clientes);
?>