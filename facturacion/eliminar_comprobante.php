<?php
require_once __DIR__ . '/config_facturacion.php';

$config = ConfigFacturacion();

$idFactura = $_GET['id'] ?? null;
if (!$idFactura) die("❌ Falta el parámetro id del comprobante");

$apiUrl = $config['API_URL'] . "/facturas/" . $idFactura;
$apiKey = $config['API_KEY'];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
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

$result = json_decode($response, true);

if ($httpcode === 200 || $httpcode === 204) {
    echo "✅ Comprobante eliminado correctamente.";
} else {
    echo "❌ Error al eliminar el comprobante.<br>";
    if (isset($result['error'])) echo "➡️ " . $result['error'] . "<br>";
    if (isset($result['errors'])) echo "<pre>" . print_r($result['errors'], true) . "</pre>";
    echo "<hr>Respuesta completa:<br><pre>" . htmlspecialchars($response) . "</pre>";
}
?>