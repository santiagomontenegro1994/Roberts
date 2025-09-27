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

$ch = curl_init($apiUrl . "/" . $idFactura . "/pdf");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode === 200) {
    // Guardar el PDF
    $filePath = __DIR__ . "/factura_" . $idFactura . ".pdf";
    file_put_contents($filePath, $response);
    echo "📄 PDF regenerado y guardado en: $filePath";
} else {
    echo "❌ Error al regenerar el PDF: " . $response;
}
?>