<?php
require_once __DIR__ . '/../vendor/autoload.php'; // ajusta la ruta según tu proyecto

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function ConexionBD() {
    $Host = $_ENV['DB_HOST'];
    $User = $_ENV['DB_USER'];
    $Password = $_ENV['DB_PASSWORD'];
    $BaseDeDatos = $_ENV['DB_NAME'];

    $linkConexion = mysqli_connect($Host, $User, $Password, $BaseDeDatos);

    if ($linkConexion) {
        return $linkConexion;
    } else {
        die('No se pudo establecer la conexión: ' . mysqli_connect_error());
    }
}
?>
