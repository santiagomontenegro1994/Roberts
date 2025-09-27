<?php
require_once __DIR__ . '/config_facturacion.php';

$config = ConfigFacturacion();

$idFactura = $_GET['id'] ?? null;
if (!$idFactura) die("❌ Falta el parámetro id de la factura");

$apiUrl = $config['API_URL'] . "/facturas/" . $idFactura . "/pdf";
$apiKey = $config['API_KEY'];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorCurl = curl_error($ch);
curl_close($ch);

if ($errorCurl) {
    die("❌ Error de conexión con la API: " . $errorCurl);
}

if ($httpcode === 200) {
    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=factura_$idFactura.pdf");
    echo $response;
} else {
    $result = json_decode($response, true);
    echo "❌ Error al regenerar PDF.<br>";
    if (isset($result['error'])) echo "➡️ " . $result['error'] . "<br>";
    if (isset($result['errors'])) echo "<pre>" . print_r($result['errors'], true) . "</pre>";
    echo "<hr>Respuesta completa:<br><pre>" . htmlspecialchars($response) . "</pre>";
}
?>