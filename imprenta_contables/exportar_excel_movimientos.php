<?php
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

// Headers
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
        .text-muted { color: #6c757d; font-weight: bold; }
        .total-row { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #000; }
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
            </tr>
        </thead>
        <tbody>";

if (count($movimientos) > 0) {
    foreach ($movimientos as $m) {
        $fechaF = date('d/m/Y', strtotime($m['fecha']));
        
        $entrada = '-';
        $salida = '-';
        $estilo = '';

        if ($m['es_entrada']) {
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
                <td $estilo>{$entrada}</td>
                <td $estilo>{$salida}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No se encontraron registros.</td></tr>";
}

// CÁLCULO FINAL CORREGIDO
$resultadoPeriodo = $sumaEntradas - $sumaSalidas - $sumaContables;

echo "</tbody>
      <tfoot>
        <tr><td colspan='7'></td></tr>
        <tr class='total-row'>
            <td colspan='5' style='text-align:right;'>TOTAL ENTRADAS:</td>
            <td class='text-success'>$ " . number_format($sumaEntradas, 2, ',', '.') . "</td>
            <td></td>
        </tr>
        <tr class='total-row'>
            <td colspan='5' style='text-align:right;'>TOTAL SALIDAS:</td>
            <td></td>
            <td class='text-danger'>$ " . number_format($sumaSalidas, 2, ',', '.') . "</td>
        </tr>
        <tr class='total-row'>
            <td colspan='5' style='text-align:right;'>TOTAL MOV. CONTABLES:</td>
            <td colspan='2' class='text-muted'>$ " . number_format($sumaContables, 2, ',', '.') . "</td>
        </tr>
        <tr class='total-row' style='background-color:#e2e3e5;'>
            <td colspan='5' style='text-align:right;'>RESULTADO (E - S - C):</td>
            <td colspan='2'>$ " . number_format($resultadoPeriodo, 2, ',', '.') . "</td>
        </tr>
      </tfoot>
    </table>
</body>
</html>";
exit;
?>