<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function ConfigFacturacion() {
    return [
        'API_URL'  => $_ENV['FACTURACION_API_URL'],
        'API_KEY'  => $_ENV['FACTURACION_API_KEY'],
        'CUIT'     => $_ENV['FACTURACION_CUIT'],
        'PTO_VTA'  => $_ENV['FACTURACION_PTO_VENTA'],
    ];
}
?>