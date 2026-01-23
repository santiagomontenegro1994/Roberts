<?php
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

// Headers para descarga Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Movimientos_" . date('Y-m-d') . ".xls");
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
    </style>
</head>
<body>
    <h3>Reporte de Movimientos Contables</h3>
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
        
        $entrada = ($m['es_entrada'] == 1) ? '$ ' . number_format($m['monto'], 2, ',', '.') : '-';
        $salida  = ($m['es_salida'] == 1)  ? '$ ' . number_format($m['monto'], 2, ',', '.') : '-';
        
        $colorEntrada = ($m['es_entrada'] == 1) ? "class='text-success'" : "";
        $colorSalida  = ($m['es_salida'] == 1)  ? "class='text-danger'" : "";
        
        $tipoTexto = 'Contable';
        if($m['es_entrada']) $tipoTexto = 'Entrada';
        if($m['es_salida']) $tipoTexto = 'Salida';

        echo "<tr>
                <td>{$fechaF}</td>
                <td>{$m['usuario']}</td>
                <td>{$m['detalle']}</td>
                <td>{$tipoTexto}</td>
                <td>{$m['metodo_pago']}</td>
                <td $colorEntrada>{$entrada}</td>
                <td $colorSalida>{$salida}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No se encontraron registros.</td></tr>";
}

echo "</tbody></table></body></html>";
exit;
?>