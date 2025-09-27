<?php
require_once __DIR__ . '/config_facturacion.php';

$config = ConfigFacturacion();

$apiUrl = $config['API_URL'] . "/facturacion/nuevo";

// Datos de ejemplo (ajustalos a tus clientes reales)
$data = [
    "apitoken"  => $config['API_TOKEN'],
    "apikey"    => $config['API_KEY'],
    "usertoken" => $config['USER_TOKEN'],

    "cliente" => [
        "documento_tipo"   => "DNI",
        "condicion_iva"    => "CF",
        "domicilio"        => "Av Sta Fe 123",
        "condicion_pago"   => "201",
        "documento_nro"    => "30111222333",
        "razon_social"     => "Cliente de Prueba",
        "provincia"        => "2",
        "email"            => "cliente@test.com",
        "envia_por_mail"   => "N",
        "rg5329"           => "N"
    ],

    "comprobante" => [
        "rubro"       => "Servicios web",
        "tipo"        => "FACTURA B",
        "numero"      => 1,
        "operacion"   => "V",
        "detalle"     => [
            [
                "cantidad" => 1,
                "producto" => [
                    "descripcion" => "Hosting página web",
                    "codigo"      => 37,
                    "alicuota"    => 21,
                    "precio_unitario_sin_iva" => 1000.00
                ]
            ]
        ],
        "fecha"       => date("d/m/Y"),
        "vencimiento" => date("d/m/Y", strtotime("+10 days")),
        "total"       => 1210.00,
        "moneda"      => "PES",
        "punto_venta" => (int) $config['PTO_VTA'],
        "tributos"    => []
    ]
];

// Enviar request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorCurl = curl_error($ch);
curl_close($ch);

// Manejo de errores de conexión
if ($errorCurl) {
    die("❌ Error de conexión con la API: " . $errorCurl);
}

// Decodificar respuesta
$result = json_decode($response, true);

// Mostrar según la API
if ($httpcode === 200 || $httpcode === 201) {
    if (isset($result['id'])) {
        echo "✅ Factura creada correctamente. ID: " . $result['id'];
    } elseif (isset($result['cae'])) {
        echo "✅ Factura autorizada por AFIP. CAE: " . $result['cae'];
    } else {
        echo "⚠️ Respuesta recibida pero sin ID/CAE:<br>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
} else {
    echo "❌ Error al crear la factura (HTTP $httpcode):<br>";
    echo "<pre>" . print_r($result, true) . "</pre>";
}
?>