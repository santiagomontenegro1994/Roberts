<?php
// Configuración de zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// Recibir Filtros
$filtros = [];
$filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
$filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';
$filtros['tipo_movimiento'] = $_GET['tipo_movimiento'] ?? '';
$filtros['metodo_pago'] = $_GET['metodo_pago'] ?? '';

// Obtener datos
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, 0, 999999);

// Inicializar contadores
$sumaEntradas = 0;
$sumaSalidas = 0;
$sumaContables = 0;
$sumaTransferencias = 0; // Solo estadístico

// Headers para descarga Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_" . date('Y-m-d_Hi') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta charset='UTF-8'>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #0d6efd; color: white; border: 1px solid #000; padding: 5px; }
        td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .text-success { color: #198754; font-weight: bold; }
        .text-danger { color: #dc3545; font-weight: bold; }
        .text-info { color: #0dcaf0; font-weight: bold; } /* Color para transferencias */
        .text-muted { color: #6c757d; font-weight: bold; }
        .total-row { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #000; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h3>Reporte de Movimientos Contables</h3>
    <p>Fecha emisión: " . date('d/m/Y H:i') . "</p>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Detalle</th>
                <th>Tipo</th>
                <th>Método Pago</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Mov. Contable</th> 
                <th>Transferencia</th> </tr>
        </thead>
        <tbody>";

if (count($movimientos) > 0) {
    foreach ($movimientos as $m) {
        $fechaF = date('d/m/Y', strtotime($m['fecha']));
        
        $entrada = '-';
        $salida = '-';
        $contable = '-';
        $transferencia = '-'; // Variable para la celda
        $estilo = '';

        // DETECTAR TIPO DE MOVIMIENTO
        // Prioridad 1: Es Transferencia (Interno)
        if ((isset($m['origen']) && $m['origen'] === 'transferencia') || (isset($m['tipo']) && $m['tipo'] === 'Transferencia')) {
            $transferencia = '$ ' . number_format($m['monto'], 2, ',', '.');
            $estilo = "class='text-info'";
            $tipoTexto = 'Mov. Interno';
            $sumaTransferencias += $m['monto'];
            // IMPORTANTE: No sumamos a Entradas ni Salidas para no afectar el total
            
        } elseif ($m['es_entrada']) {
            $entrada = '$ ' . number_format($m['monto'], 2, ',', '.');
            $estilo = "class='text-success'";
            $tipoTexto = 'Entrada';
            $sumaEntradas += $m['monto'];
            
        } elseif ($m['es_salida']) {
            $salida = '$ ' . number_format($m['monto'], 2, ',', '.');
            $estilo = "class='text-danger'";
            $tipoTexto = 'Salida';
            $sumaSalidas += $m['monto'];
            
        } else {
            // Contable puro
            $contable = '$ ' . number_format($m['monto'], 2, ',', '.');
            $tipoTexto = 'Contable';
            $estilo = "class='text-muted'";
            $sumaContables += $m['monto'];
        }

        echo "<tr>
                <td>{$fechaF}</td>
                <td>{$m['usuario']}</td>
                <td>{$m['detalle']}</td>
                <td>{$tipoTexto}</td>
                <td>{$m['metodo_pago']}</td>
                <td " . ($m['es_entrada'] ? $estilo : '') . ">{$entrada}</td>
                <td " . ($m['es_salida'] ? $estilo : '') . ">{$salida}</td>
                <td " . (!$m['es_entrada'] && !$m['es_salida'] && $tipoTexto !== 'Mov. Interno' ? $estilo : '') . ">{$contable}</td>
                <td " . ($tipoTexto === 'Mov. Interno' ? $estilo : '') . ">{$transferencia}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='9'>No se encontraron registros.</td></tr>";
}

// CÁLCULO FINAL (Solo Entradas - Salidas - Contables). Transferencias se ignoran.
$resultadoPeriodo = $sumaEntradas - $sumaSalidas - $sumaContables;

echo "</tbody>
      <tfoot>
        <tr><td colspan='9'></td></tr>
        
        <tr class='total-row'>
            <td colspan='5' class='text-right'>TOTALES POR COLUMNA:</td>
            <td class='text-success'>$ " . number_format($sumaEntradas, 2, ',', '.') . "</td>
            <td class='text-danger'>$ " . number_format($sumaSalidas, 2, ',', '.') . "</td>
            <td class='text-muted'>$ " . number_format($sumaContables, 2, ',', '.') . "</td>
            <td class='text-info'>$ " . number_format($sumaTransferencias, 2, ',', '.') . "</td>
        </tr>

        <tr class='total-row' style='background-color:#e2e3e5;'>
            <td colspan='5' class='text-right'>RESULTADO FINAL (Entradas - Salidas - Contables):</td>
            <td colspan='4' style='text-align:center; font-size:1.1em;'>$ " . number_format($resultadoPeriodo, 2, ',', '.') . "</td>
        </tr>
      </tfoot>
    </table>
</body>
</html>";
exit;
?>