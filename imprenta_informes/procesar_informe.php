<?php
// procesar_informe.php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión expirada']);
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// Recibimos el periodo por GET (formato YYYY-MM) o usamos el actual
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');
list($anio, $mes) = explode('-', $periodo);

// Calculamos el mes anterior
$fechaObj = new DateTime($periodo . '-01');
$fechaObj->modify('-1 month');
$mesAnt = $fechaObj->format('m');
$anioAnt = $fechaObj->format('Y');

// --- FUNCIONES ---\

function obtenerDatosMes($conexion, $m, $a) {
    // 1. Totales Generales y Contadores Específicos
    // Banco: idTipoPago 3, 13, 23
    // MP: idTipoPago 22
    // Efectivo: idTipoMovimiento 9
    
    $sql = "SELECT 
                SUM(CASE WHEN tm.es_entrada = 1 THEN dc.monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tm.es_salida = 1 THEN dc.monto ELSE 0 END) as egresos,
                
                -- Contadores Específicos
                SUM(CASE WHEN dc.idTipoPago IN (3, 13, 23) THEN dc.monto ELSE 0 END) as banco,
                SUM(CASE WHEN dc.idTipoPago = 22 THEN dc.monto ELSE 0 END) as mp,
                SUM(CASE WHEN dc.idTipoMovimiento = 9 THEN dc.monto ELSE 0 END) as efectivo
            FROM detalle_caja dc
            JOIN caja c ON dc.idCaja = c.idCaja
            JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
            WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'";

    $query = mysqli_query($conexion, $sql);
    $totales = mysqli_fetch_assoc($query);

    // Asegurar que no sean nulos
    $totalIngresos = floatval($totales['ingresos']);
    $totalEgresos = floatval($totales['egresos']);

    // 2. Lista de Gastos (Egresos)
    $sqlGastos = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_salida = 1
                  GROUP BY tm.denominacion ORDER BY monto DESC";
    $qGastos = mysqli_query($conexion, $sqlGastos);
    $listaGastos = [];
    while($row = mysqli_fetch_assoc($qGastos)) {
        // Calcular porcentaje respecto al TOTAL DE INGRESOS
        $montoItem = floatval($row['monto']);
        $porc = ($totalIngresos > 0) ? ($montoItem / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
        $listaGastos[] = $row;
    }

    // 3. Lista de Ingresos
    $sqlIngresos = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_entrada = 1
                  GROUP BY tm.denominacion ORDER BY monto DESC";
    $qIngresos = mysqli_query($conexion, $sqlIngresos);
    $listaIngresos = [];
    while($row = mysqli_fetch_assoc($qIngresos)) {
        // Calcular porcentaje respecto al TOTAL DE INGRESOS
        $montoItem = floatval($row['monto']);
        $porc = ($totalIngresos > 0) ? ($montoItem / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
        $listaIngresos[] = $row;
    }

    return [
        'banco' => floatval($totales['banco']),
        'mp' => floatval($totales['mp']),
        'efectivo' => floatval($totales['efectivo']),
        'totalIngresos' => $totalIngresos,
        'totalGastos' => $totalEgresos,
        'desgloseGastos' => $listaGastos,
        'desgloseIngresos' => $listaIngresos
    ];
}

// Obtenemos datos
$actual = obtenerDatosMes($MiConexion, $mes, $anio);
$anterior = obtenerDatosMes($MiConexion, $mesAnt, $anioAnt);

// Calculamos variaciones (lógica visual para flechitas)
$varIngresos = ($anterior['totalIngresos'] > 0) 
    ? (($actual['totalIngresos'] - $anterior['totalIngresos']) / $anterior['totalIngresos']) * 100 
    : 100;

$varGastos = ($anterior['totalGastos'] > 0) 
    ? (($actual['totalGastos'] - $anterior['totalGastos']) / $anterior['totalGastos']) * 100 
    : 100;

$gananciaActual = $actual['totalIngresos'] - $actual['totalGastos'];
$gananciaAnt = $anterior['totalIngresos'] - $anterior['totalGastos'];
$varGanancia = ($gananciaAnt != 0) 
    ? (($gananciaActual - $gananciaAnt) / abs($gananciaAnt)) * 100 
    : 100;

// Devolvemos JSON
echo json_encode([
    'ok' => true,
    'actual' => $actual,
    'variaciones' => [
        'ingresos' => round($varIngresos, 1),
        'gastos' => round($varGastos, 1),
        'ganancia' => round($varGanancia, 1)
    ]
]);
?>