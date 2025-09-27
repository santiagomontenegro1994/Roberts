<?php
require_once __DIR__ . '/config_facturacion.php';

$config = ConfigFacturacion();

$apiUrl = $config['API_URL'] . "/facturas";
$apiKey = $config['API_KEY'];

// ID de la factura recibido por GET o POST
$idFactura = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$idFactura) {
    die("❌ Debes indicar un ID de factura con ?id= o por POST.");
}

$ch = curl_init($apiUrl . "/" . $idFactura);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode === 200) {
    $result = json_decode($response, true);
    echo "📄 Datos de la factura:<br>";
    echo "<pre>" . print_r($result, true) . "</pre>";
} else {
    echo "❌ Error al consultar la factura: " . $response;
}
?>