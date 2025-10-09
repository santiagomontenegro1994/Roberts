<?php
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Datos de prueba para la factura
$datosFactura = [
    "cliente" => [
        "documento_tipo" => "DNI",
        "condicion_iva"  => "CF",
        "domicilio"      => "Av Sta Fe 23132",
        "condicion_pago" => "201",
        "documento_nro"  => "111132333",
        "razon_social"   => "Juan Pedro KJL",
        "provincia"      => "2",
        "email"          => "email@dominio.com",
        "envia_por_mail" => "N",
        "rg5329"         => "N"
    ],
    "comprobante" => [
        "rubro"               => "Servicios web",
        "tipo"                => "FACTURA B",
        "numero"              => 2134,
        "operacion"           => "V",
        "detalle"             => [
            [
                "cantidad"               => 1,
                "afecta_stock"           => "S",
                "actualiza_precio"       => "S",
                "bonificacion_porcentaje"=> 0,
                "producto"               => [
                    "descripcion"            => "Hosting pagina web",
                    "codigo"                 => 37,
                    "lista_precios"          => "standard",
                    "leyenda"                => "",
                    "unidad_bulto"           => 1,
                    "alicuota"               => 21,
                    "actualiza_precio"       => "S",
                    "rg5329"                 => "N",
                    "precio_unitario_sin_iva"=> 114.88
                ]
            ]
        ],
        "fecha"                => date('d/m/Y'),
        "vencimiento"          => date('d/m/Y', strtotime('+30 days')),
        "rubro_grupo_contable" => "Servicios",
        "total"                => 139.0,
        "cotizacion"           => 1,
        "moneda"               => "PES",
        "punto_venta"          => (int)getenv('FACTURACION_PTO_VENTA'),
        "tributos"             => []
    ]
];

// URL correcta para crear factura
$urlApi = "https://api.tusfacturas.app/api/v2/facturacion/nuevo";


// Crear factura
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $urlApi,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($datosFactura),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: ".getenv('FACTURACION_API_KEY'),
        "apitoken: ".getenv('FACTURACION_API_TOKEN'),
        "usertoken: ".getenv('FACTURACION_USER_TOKEN')
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// Manejo de errores cURL
if ($response === false) {
    die("❌ Error cURL: $err\nInformación adicional: " . print_r($info, true));
}

// Mostrar respuesta completa cruda
echo "<h2>Respuesta completa cruda del servidor:</h2><pre>";
var_dump($response);
echo "</pre>";

// Decodificar JSON
$res = json_decode($response, true);
if ($res === null) {
    die("❌ Error: No se pudo decodificar JSON. Respuesta recibida: $response");
}

// Verificar errores de la API
if (isset($res['error']) && $res['error'] === 'S') {
    echo "<h2>⚠️ Error al crear la factura:</h2><pre>";
    print_r($res['errores']);
    echo "</pre>";
    exit;
}

// Validar que exista el id de la factura
if (!isset($res['factura']['id'])) {
    die("❌ Error: La factura no fue creada correctamente. Respuesta: " . print_r($res, true));
}

echo "<h2>✅ Factura creada correctamente:</h2><pre>";
print_r($res['factura']);
echo "</pre>";

// Descargar PDF de la factura
$idFactura = $res['factura']['id'];  // usar 'uuid' si tu cuenta lo requiere
$urlPdf = getenv('FACTURACION_API_URL') . "/pdf/$idFactura";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $urlPdf,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: ".getenv('FACTURACION_API_KEY'),
        "apitoken: ".getenv('FACTURACION_API_TOKEN'),
        "usertoken: ".getenv('FACTURACION_USER_TOKEN')
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$pdfContent = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($pdfContent === false) {
    die("❌ Error cURL al descargar PDF: $err");
}

// Guardar PDF en disco
$fileName = "Factura_{$idFactura}.pdf";
file_put_contents($fileName, $pdfContent);

echo "<h2>✅ PDF descargado:</h2>";
echo "<p><a href='$fileName' target='_blank'>$fileName</a></p>";
?>