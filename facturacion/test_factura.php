<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables del .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configuración API desde .env
$apiUrl    = getenv('FACTURACION_API_URL'); // https://www.tusfacturas.app/api/v2/facturacion/nuevo
$apiKey    = getenv('FACTURACION_API_KEY');
$apiToken  = getenv('FACTURACION_API_TOKEN');
$userToken = getenv('FACTURACION_USER_TOKEN');

// Datos de prueba para la factura
$data = [
    "comprobante" => [
        "tipo"        => "FA", // Factura A
        "pto_venta"   => getenv('FACTURACION_PTO_VENTA'),
        "concepto"    => 1, // Productos
        "cliente" => [
            "razon_social" => "Cliente de Prueba",
            "tipo_doc"     => "DNI",
            "nro_doc"      => "30111222",
            "domicilio"    => "Calle Falsa 123",
            "email"        => "cliente@correo.com"
        ],
        "items" => [
            [
                "descripcion" => "Producto de prueba",
                "cantidad"    => 1,
                "precio_unit" => 1000.00,
                "iva_id"      => 5 // 21%
            ]
        ]
    ]
];

// Inicializar cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: $apiKey",
    "apitoken: $apiToken",
    "usertoken: $userToken"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Ejecutar request
$response = curl_exec($ch);

// Manejar errores de cURL
if (curl_errno($ch)) {
    echo "❌ Error de conexión: " . curl_error($ch);
    exit;
}

curl_close($ch);

// Decodificar respuesta
$result = json_decode($response, true);

// Mostrar resultado
if (isset($result['error']) && $result['error'] === 'S') {
    echo "⚠️ Error al crear la factura:\n";
    print_r($result['errores']);
} else {
    echo "✅ Factura creada correctamente\n";
    print_r($result);
}
?>