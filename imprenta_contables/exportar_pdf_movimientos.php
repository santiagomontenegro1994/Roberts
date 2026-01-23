<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// ---------------------------------------------------------
// 1. INCLUSIONES Y CONEXIÓN
// ---------------------------------------------------------
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';
// Ruta específica de DOMPDF solicitada
require_once '../libreria/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$MiConexion = ConexionBD();

// ---------------------------------------------------------
// 2. RECIBIR FILTROS (Igual que en movimientos_contables.php)
// ---------------------------------------------------------
$filtros = [];
$filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
$filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';
$filtros['tipo_movimiento'] = $_GET['tipo_movimiento'] ?? '';
$filtros['metodo_pago'] = $_GET['metodo_pago'] ?? '';

// Obtener TODOS los datos (sin paginación para el PDF)
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, 0, 999999);

// ---------------------------------------------------------
// 3. PREPARAR LOGO (Lógica copiada de tu ejemplo)
// ---------------------------------------------------------
$ruta_imagen = __DIR__ . '/../assets/img/logo.png'; 
if (file_exists($ruta_imagen)) {
    $mime_type = mime_content_type($ruta_imagen);
    $datos_imagen = file_get_contents($ruta_imagen);
    $base64_imagen = 'data:' . $mime_type . ';base64,' . base64_encode($datos_imagen);
    $logo_html = '<img src="' . $base64_imagen . '" alt="Logo" style="max-width: 150px; height: auto;">';
} else {
    $logo_html = '<p><strong>Imprenta Roberts</strong></p>';
}

// ---------------------------------------------------------
// 4. CONSTRUCCIÓN DEL HTML
// ---------------------------------------------------------
$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos Contables</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #fff; 
            margin: 0; 
            padding: 0; 
            font-size: 12px;
        }
        .container { 
            width: 100%;
            margin: 0 auto; 
            padding: 10px; 
        }
        .header { 
            width: 100%; 
            margin-bottom: 20px; 
            display: table; /* Para simular flex en PDF viejos */
        }
        .logo-box {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }
        .header-text { 
            display: table-cell;
            vertical-align: middle;
            text-align: right; 
            width: 50%;
        }
        .header-text h2 { 
            color: #333; 
            margin: 0; 
            font-size: 20px; 
        }
        .header-text p { 
            color: #777; 
            margin: 5px 0 0; 
            font-size: 12px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
        }
        table th, table td { 
            padding: 6px; 
            text-align: left; 
            border: 1px solid #ddd; 
        }
        table th { 
            background: #f8f9fa; 
            font-weight: bold; 
            color: #333; 
            text-align: center;
        }
        table tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        
        .footer { 
            margin-top: 30px;
            text-align: center;
            color: #777; 
            font-style: italic; 
            font-size: 10px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            color: #fff;
            font-size: 10px;
        }
        .bg-success { background-color: #198754; color: #fff; }
        .bg-danger { background-color: #dc3545; color: #fff; }
        .bg-secondary { background-color: #6c757d; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-box">
                ' . $logo_html . '
            </div>
            <div class="header-text">
                <h2>Movimientos Contables</h2>
                <p>Fecha de emisión: ' . date("d/m/Y H:i") . '</p>
                <p>Generado por: ' . $_SESSION['Usuario_Nombre'] . '</p>
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
        
        // Determinar tipo visual
        $tipoHtml = '';
        if ($m['es_entrada']) {
            $tipoHtml = '<span class="badge bg-success">ENTRADA</span>';
        } elseif ($m['es_salida']) {
            $tipoHtml = '<span class="badge bg-danger">SALIDA</span>';
        } else {
            $tipoHtml = '<span class="badge bg-secondary">CONTABLE</span>';
        }

        $html .= '<tr>
                    <td class="text-center">' . date("d/m/Y", strtotime($m['fecha'])) . '</td>
                    <td class="text-center">' . $tipoHtml . '</td>
                    <td>' . htmlspecialchars(substr($m['detalle'], 0, 60)) . '</td>
                    <td>' . htmlspecialchars(substr($m['usuario'], 0, 15)) . '</td>
                    <td class="text-center">' . htmlspecialchars($m['metodo_pago']) . '</td>
                    <td class="text-right">$' . number_format($m['monto'], 2, ',', '.') . '</td>
                  </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" class="text-center">No hay movimientos con los filtros seleccionados.</td></tr>';
}

$html .= '  </tbody>
        </table>

        <div class="footer">
            <p>Imprenta Roberts - Laprida 25 - Villa Allende</p>
        </div>
    </div>
</body>
</html>';

// ---------------------------------------------------------
// 5. GENERAR PDF (Dompdf)
// ---------------------------------------------------------
$dompdf = new Dompdf();

// Opciones
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Vertical suele ser mejor para listas largas
$dompdf->render();

// Descargar
$nombreArchivo = "Movimientos_" . date('Ymd_His') . ".pdf";
$dompdf->stream($nombreArchivo, array("Attachment" => true));
?>