<?php
require_once __DIR__ . '/config_facturacion.php';

$config = ConfigFacturacion();

$apiUrl = $config['API_URL'] . "/facturas";
$apiKey = $config['API_KEY'];

// Ejemplo de datos (puedes recibirlos por $_POST)
$data = [
    "cuit"        => $config['CUIT'],
    "pto_vta"     => $config['PTO_VTA'],
    "concepto"    => 1,
    "doc_tipo"    => $_POST['doc_tipo'] ?? 80,
    "doc_nro"     => $_POST['doc_nro'] ?? "20111111112",
    "cbte_tipo"   => $_POST['cbte_tipo'] ?? 1,
    "cbte_nro"    => 0,
    "imp_total"   => $_POST['imp_total'] ?? 1000.00,
    "imp_neto"    => $_POST['imp_neto'] ?? 1000.00,
    "imp_iva"     => $_POST['imp_iva'] ?? 0,
    "moneda_id"   => "PES",
    "moneda_ctz"  => 1
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorCurl = curl_error($ch);
curl_close($ch);

if ($errorCurl) {
    die("❌ Error de conexión con la API: " . $errorCurl);
}

$result = json_decode($response, true);

if ($httpcode === 200 || $httpcode === 201) {
    echo "✅ Factura creada correctamente. ID: " . ($result['id'] ?? 'No recibido');
} else {
    echo "❌ Error al crear la factura.<br>";
    if (isset($result['error'])) echo "➡️ " . $result['error'] . "<br>";
    if (isset($result['errors'])) echo "<pre>" . print_r($result['errors'], true) . "</pre>";
    echo "<hr>Respuesta completa:<br><pre>" . htmlspecialchars($response) . "</pre>";
}
?>