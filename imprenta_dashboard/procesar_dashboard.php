<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión expirada']);
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

$fechaInicio = isset($_GET['fechaInicio']) ? $_GET['fechaInicio'] : date('Y-m-01');
$fechaFin = isset($_GET['fechaFin']) ? $_GET['fechaFin'] : date('Y-m-d');

$fIni = $fechaInicio . " 00:00:00";
$fFin = $fechaFin . " 23:59:59";

// ==========================================
// 1. CÁLCULO DE TOTALES GENERALES DEL PERÍODO
// ==========================================

// Diferencias
$qDifPos = mysqli_query($MiConexion, "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE c.Fecha BETWEEN '$fIni' AND '$fFin' AND dc.idTipoMovimiento = 15");
$difPositiva = floatval(mysqli_fetch_assoc($qDifPos)['total']);

$qDifNeg = mysqli_query($MiConexion, "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE c.Fecha BETWEEN '$fIni' AND '$fFin' AND dc.idTipoMovimiento = 14");
$difNegativa = floatval(mysqli_fetch_assoc($qDifNeg)['total']);

// Ingresos Brutos
$sqlEntradas = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago 
                WHERE c.Fecha BETWEEN '$fIni' AND '$fFin'
                AND tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 AND tp.idActivo = 1";
$ingresosCaja = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlEntradas))['total']);

// Retiros (Gastos)
$sqlRetiros = "SELECT SUM(monto) as total FROM retiros WHERE fecha BETWEEN '$fIni' AND '$fFin' AND idTipoMovimiento NOT IN (9, 14, 15)";
$montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlRetiros))['total']);

$totalIngresos = $ingresosCaja + $difPositiva - $difNegativa;
$totalEgresos = $montoRetiros;
$gananciaNeta = $totalIngresos - $totalEgresos;

// ==========================================
// 2. DESGLOSE POR RUBRO
// ==========================================

// Rubros Ingresos
$listaIngresos = [];
$sqlListaIng = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto FROM detalle_caja dc 
                JOIN caja c ON dc.idCaja = c.idCaja 
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                WHERE c.Fecha BETWEEN '$fIni' AND '$fFin' 
                AND tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 AND tp.idActivo = 1 
                GROUP BY tm.denominacion ORDER BY monto DESC";
$qListaIng = mysqli_query($MiConexion, $sqlListaIng);
while($row = mysqli_fetch_assoc($qListaIng)) {
    $monto = floatval($row['monto']);
    $porc = ($totalIngresos > 0) ? ($monto / $totalIngresos) * 100 : 0;
    $listaIngresos[] = ['concepto' => $row['concepto'], 'monto' => $monto, 'porcentaje' => number_format($porc, 1) . '%'];
}
if ($difPositiva > 0) {
    $listaIngresos[] = ['concepto' => 'Diferencia a Favor', 'monto' => $difPositiva, 'porcentaje' => number_format(($totalIngresos > 0 ? ($difPositiva / $totalIngresos) * 100 : 0), 1) . '%'];
}
if ($difNegativa > 0) {
    $listaIngresos[] = ['concepto' => 'Diferencia en Contra', 'monto' => $difNegativa * -1, 'porcentaje' => number_format(($totalIngresos > 0 ? (($difNegativa * -1) / $totalIngresos) * 100 : 0), 1) . '%'];
}

// Rubros Gastos
$listaGastos = [];
$sqlListaRetiros = "SELECT tm.denominacion as concepto, SUM(r.monto) as monto FROM retiros r
                    JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE r.fecha BETWEEN '$fIni' AND '$fFin' AND r.idTipoMovimiento NOT IN (9, 14, 15) 
                    GROUP BY tm.denominacion ORDER BY monto DESC";
$qGR = mysqli_query($MiConexion, $sqlListaRetiros);
while($row = mysqli_fetch_assoc($qGR)){
    $monto = floatval($row['monto']);
    $porc = ($totalIngresos > 0) ? ($monto / $totalIngresos) * 100 : 0;
    $listaGastos[] = ['concepto' => $row['concepto'], 'monto' => $monto, 'porcentaje' => number_format($porc, 1) . '%'];
}

// ==========================================
// 3. DATOS PARA LOS GRÁFICOS (Evolución diaria)
// ==========================================
$fechasArray = [];
$ventasPorDia = [];
$gastosPorDia = [];

$periodo = new DatePeriod(new DateTime($fechaInicio), new DateInterval('P1D'), (new DateTime($fechaFin))->modify('+1 day'));
foreach ($periodo as $fecha) {
    $f = $fecha->format('Y-m-d');
    $fechasArray[] = $f;
    $ventasPorDia[$f] = 0;
    $gastosPorDia[$f] = 0;
}

// Ventas por día
$sqlVentasDia = "SELECT DATE(c.Fecha) as dia, 
                 SUM(CASE WHEN tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 AND tp.idActivo = 1 THEN dc.monto ELSE 0 END) as ingresos,
                 SUM(CASE WHEN dc.idTipoMovimiento = 15 THEN dc.monto ELSE 0 END) as dif_pos,
                 SUM(CASE WHEN dc.idTipoMovimiento = 14 THEN dc.monto ELSE 0 END) as dif_neg
                 FROM detalle_caja dc
                 JOIN caja c ON dc.idCaja = c.idCaja
                 JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE c.Fecha BETWEEN '$fIni' AND '$fFin'
                 GROUP BY DATE(c.Fecha)";
$qVentasDia = mysqli_query($MiConexion, $sqlVentasDia);
while($row = mysqli_fetch_assoc($qVentasDia)) {
    $dia = $row['dia'];
    if(isset($ventasPorDia[$dia])) {
        $ventasPorDia[$dia] = floatval($row['ingresos']) + floatval($row['dif_pos']) - floatval($row['dif_neg']);
    }
}

// Gastos por día
$sqlGastosDia = "SELECT DATE(fecha) as dia, SUM(monto) as total FROM retiros 
                 WHERE fecha BETWEEN '$fIni' AND '$fFin' AND idTipoMovimiento NOT IN (9, 14, 15) GROUP BY DATE(fecha)";
$qGastosDia = mysqli_query($MiConexion, $sqlGastosDia);
while($row = mysqli_fetch_assoc($qGastosDia)) {
    $dia = $row['dia'];
    if(isset($gastosPorDia[$dia])) {
        $gastosPorDia[$dia] = floatval($row['total']);
    }
}

// ==========================================
// 4. FILTRAR DÍAS SIN ACTIVIDAD (ELIMINA LOS CERO)
// ==========================================
$finalLabels = [];
$finalVentas = [];
$finalGastos = [];

foreach ($fechasArray as $f) {
    $v = $ventasPorDia[$f];
    $g = $gastosPorDia[$f];
    
    // Si el día tuvo ventas o gastos, lo incluimos
    if ($v != 0 || $g != 0) {
        $finalLabels[] = $f;
        $finalVentas[] = $v;
        $finalGastos[] = $g;
    }
}

echo json_encode([
    'ok' => true,
    'totales' => [
        'ingresos' => $totalIngresos,
        'gastos' => $totalEgresos,
        'ganancia' => $gananciaNeta
    ],
    'rubros' => [
        'ingresos' => $listaIngresos,
        'gastos' => $listaGastos
    ],
    'graficos' => [
        'labels' => $finalLabels,
        'ventas' => $finalVentas,
        'gastos' => $finalGastos
    ]
]);
?>