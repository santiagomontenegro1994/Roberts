<?php
// Configuración de zona horaria y tiempo límite
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(300); 
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';
require_once '../libreria/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$MiConexion = ConexionBD();

// Recibir Filtros
$filtros = [];
$filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
$filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';
$filtros['tipo_movimiento'] = $_GET['tipo_movimiento'] ?? '';
$filtros['metodo_pago'] = $_GET['metodo_pago'] ?? '';

// Obtener TODOS los datos
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, 0, 999999);

// Inicializar contadores
$sumaEntradas = 0;
$sumaSalidas = 0;
$sumaContables = 0;

// Preparar Logo
$ruta_imagen = __DIR__ . '/../assets/img/logo.png'; 
if (file_exists($ruta_imagen)) {
    $mime_type = mime_content_type($ruta_imagen);
    $datos_imagen = file_get_contents($ruta_imagen);
    $base64_imagen = 'data:' . $mime_type . ';base64,' . base64_encode($datos_imagen);
    $logo_html = '<img src="' . $base64_imagen . '" alt="Logo" style="max-width: 150px; height: auto;">';
} else {
    $logo_html = '<h3 style="color:#333;">Imprenta Roberts</h3>';
}

$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 0; padding: 0; }
        .container { width: 100%; padding: 10px; }
        .header { width: 100%; margin-bottom: 20px; display: table; }
        .logo-box { display: table-cell; vertical-align: middle; width: 40%; }
        .info-box { display: table-cell; vertical-align: middle; width: 60%; text-align: right; }
        h2 { margin: 0; color: #333; font-size: 18px; }
        p { margin: 2px 0; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; color: #333; font-weight: bold; text-align: center; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 4px; border-radius: 3px; color: #fff; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .bg-success { background-color: #198754; color: #fff; }
        .bg-danger { background-color: #dc3545; color: #fff; }
        .bg-secondary { background-color: #6c757d; color: #fff; }
        
        .totales-box { margin-top: 20px; width: 50%; float: right; }
        .totales-table td { padding: 4px; border: none; border-bottom: 1px solid #eee; }
        .total-label { font-weight: bold; color: #555; }
        .total-value { font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-box">
                ' . $logo_html . '
            </div>
            <div class="info-box">
                <h2>Reporte de Movimientos Contables</h2>
                <p>Fecha de emisión: ' . date("d/m/Y H:i") . '</p>
                <p>Usuario: ' . $_SESSION['Usuario_Nombre'] . '</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="12%">Fecha</th>
                    <th width="10%">Tipo</th>
                    <th width="35%">Detalle</th>
                    <th width="15%">Usuario</th>
                    <th width="13%">Método</th>
                    <th width="15%">Monto</th>
                </tr>
            </thead>
            <tbody>';

if (count($movimientos) > 0) {
    foreach ($movimientos as $m) {
        
        if ($m['es_entrada']) {
            $sumaEntradas += $m['monto'];
            $tipoHtml = '<span class="badge bg-success">ENTRADA</span>';
        } elseif ($m['es_salida']) {
            $sumaSalidas += $m['monto'];
            $tipoHtml = '<span class="badge bg-danger">SALIDA</span>';
        } else {
            $sumaContables += $m['monto'];
            $tipoHtml = '<span class="badge bg-secondary">CONTABLE</span>';
        }

        $html .= '<tr>
                    <td class="text-center">' . date("d/m/Y", strtotime($m['fecha'])) . '</td>
                    <td class="text-center">' . $tipoHtml . '</td>
                    <td>' . htmlspecialchars(substr($m['detalle'], 0, 50)) . '</td>
                    <td>' . htmlspecialchars(substr($m['usuario'], 0, 15)) . '</td>
                    <td class="text-center">' . htmlspecialchars($m['metodo_pago']) . '</td>
                    <td class="text-right">$' . number_format($m['monto'], 2, ',', '.') . '</td>
                  </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" class="text-center">No hay datos para mostrar.</td></tr>';
}

// CÁLCULO FINAL CORREGIDO
$resultadoPeriodo = $sumaEntradas - $sumaSalidas - $sumaContables;

$html .= '  </tbody>
        </table>
        
        <div class="totales-box">
            <table class="totales-table">
                <tr>
                    <td class="total-label text-right">Total Entradas:</td>
                    <td class="total-value" style="color:#198754;">$ ' . number_format($sumaEntradas, 2, ',', '.') . '</td>
                </tr>
                <tr>
                    <td class="total-label text-right">Total Salidas:</td>
                    <td class="total-value" style="color:#dc3545;">$ ' . number_format($sumaSalidas, 2, ',', '.') . '</td>
                </tr>
                <tr>
                    <td class="total-label text-right">Total Mov. Contables:</td>
                    <td class="total-value" style="color:#6c757d;">$ ' . number_format($sumaContables, 2, ',', '.') . '</td>
                </tr>
                <tr>
                    <td class="total-label text-right" style="border-top: 2px solid #333; font-size:12px;">Resultado del Período (E - S - C):</td>
                    <td class="total-value" style="border-top: 2px solid #333; font-size:12px;">$ ' . number_format($resultadoPeriodo, 2, ',', '.') . '</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>';

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_" . date('Ymd_Hi') . ".pdf", array("Attachment" => true));
?>