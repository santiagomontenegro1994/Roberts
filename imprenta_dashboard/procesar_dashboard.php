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
$qDifPos = mysqli_query($MiConexion, "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE c.Fecha BETWEEN '$fIni' AND '$fFin' AND dc.idTipoMovimiento = 15");
$difPositiva = floatval(mysqli_fetch_assoc($qDifPos)['total']);

$qDifNeg = mysqli_query($MiConexion, "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE c.Fecha BETWEEN '$fIni' AND '$fFin' AND dc.idTipoMovimiento = 14");
$difNegativa = floatval(mysqli_fetch_assoc($qDifNeg)['total']);

$sqlEntradas = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago 
                WHERE c.Fecha BETWEEN '$fIni' AND '$fFin'
                AND tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 AND tp.idActivo = 1";
$ingresosCaja = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlEntradas))['total']);

$sqlRetiros = "SELECT SUM(monto) as total FROM retiros WHERE fecha BETWEEN '$fIni' AND '$fFin' AND idTipoMovimiento NOT IN (9, 14, 15)";
$montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlRetiros))['total']);

$totalIngresos = $ingresosCaja + $difPositiva - $difNegativa;
$totalEgresos = $montoRetiros;
$gananciaNeta = $totalIngresos - $totalEgresos;

// ==========================================
// 2. DESGLOSE POR RUBRO
// ==========================================
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
    // Se envía 'subitems' vacío para mantener estructura compatible
    $listaIngresos[] = ['concepto' => $row['concepto'], 'monto' => $monto, 'porcentaje' => number_format($porc, 1) . '%', 'subitems' => []];
}
if ($difPositiva > 0) {
    $listaIngresos[] = ['concepto' => 'Diferencia a Favor', 'monto' => $difPositiva, 'porcentaje' => number_format(($totalIngresos > 0 ? ($difPositiva / $totalIngresos) * 100 : 0), 1) . '%', 'subitems' => []];
}
if ($difNegativa > 0) {
    $listaIngresos[] = ['concepto' => 'Diferencia en Contra', 'monto' => $difNegativa * -1, 'porcentaje' => number_format(($totalIngresos > 0 ? (($difNegativa * -1) / $totalIngresos) * 100 : 0), 1) . '%', 'subitems' => []];
}

// ---------------- DESGLOSE DE GASTOS CON SUBITEMS ----------------
$gastosAgrupados = [];
// NOTA: Si tu columna no se llama "detalle", cambiá "r.detalle" por "r.descripcion"
$sqlListaRetiros = "SELECT tm.denominacion as concepto, r.detalle, SUM(r.monto) as monto 
                    FROM retiros r
                    JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE r.fecha BETWEEN '$fIni' AND '$fFin' AND r.idTipoMovimiento NOT IN (9, 14, 15) 
                    GROUP BY tm.denominacion, r.detalle";
$qGR = mysqli_query($MiConexion, $sqlListaRetiros);

while($row = mysqli_fetch_assoc($qGR)){
    $concepto = $row['concepto'];
    $detalle = empty(trim($row['detalle'] ?? '')) ? 'Varios / Sin detalle' : trim($row['detalle']);
    $monto = floatval($row['monto']);
    
    if(!isset($gastosAgrupados[$concepto])) {
        $gastosAgrupados[$concepto] = ['monto_total' => 0, 'detalles' => []];
    }
    
    $gastosAgrupados[$concepto]['monto_total'] += $monto;
    $gastosAgrupados[$concepto]['detalles'][] = [
        'nombre' => $detalle,
        'monto' => $monto
    ];
}

$listaGastos = [];
foreach($gastosAgrupados as $concepto => $datos) {
    $montoCat = $datos['monto_total'];
    $porcCat = ($totalIngresos > 0) ? ($montoCat / $totalIngresos) * 100 : 0;
    
    $subItems = [];
    foreach($datos['detalles'] as $det) {
        $porcSub = ($montoCat > 0) ? ($det['monto'] / $montoCat) * 100 : 0;
        $subItems[] = [
            'nombre' => $det['nombre'],
            'monto' => $det['monto'],
            'porcentaje' => number_format($porcSub, 1) . '%'
        ];
    }
    
    // Ordenar subitems de mayor a menor gasto
    usort($subItems, function($a, $b) { return $b['monto'] <=> $a['monto']; });

    $listaGastos[] = [
        'concepto' => $concepto, 
        'monto' => $montoCat, 
        'porcentaje' => number_format($porcCat, 1) . '%',
        'subitems' => $subItems
    ];
}

// Ordenar rubros principales de mayor a menor
usort($listaGastos, function($a, $b) { return $b['monto'] <=> $a['monto']; });


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
// 4. AGRUPACIÓN INTELIGENTE Y FILTRADO
// ==========================================
$dias_dif = (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400;
$agrupar_mensual = ($dias_dif > 92);
$agrupar_semanal = ($dias_dif > 31 && !$agrupar_mensual);

$finalLabels = [];
$finalVentas = [];
$finalGastos = [];

if ($agrupar_mensual) {
    $tempDatos = [];
    foreach ($fechasArray as $f) {
        $mesAnio = substr($f, 0, 7) . '-01'; 
        if (!isset($tempDatos[$mesAnio])) $tempDatos[$mesAnio] = ['v' => 0, 'g' => 0];
        $tempDatos[$mesAnio]['v'] += $ventasPorDia[$f];
        $tempDatos[$mesAnio]['g'] += $gastosPorDia[$f];
    }
    foreach ($tempDatos as $lbl => $t) {
        if ($t['v'] != 0 || $t['g'] != 0) {
            $finalLabels[] = $lbl;
            $finalVentas[] = $t['v'];
            $finalGastos[] = $t['g'];
        }
    }
} else if ($agrupar_semanal) {
    $tempSemanas = [];
    foreach ($fechasArray as $f) {
        $dateObj = new DateTime($f);
        $lunes = clone $dateObj;
        if ($lunes->format('w') != 1) $lunes->modify('last monday');
        
        $label = $lunes->format('Y-m-d');
        if (!isset($tempSemanas[$label])) $tempSemanas[$label] = ['v' => 0, 'g' => 0];
        $tempSemanas[$label]['v'] += $ventasPorDia[$f];
        $tempSemanas[$label]['g'] += $gastosPorDia[$f];
    }
    foreach ($tempSemanas as $lbl => $totales) {
        if ($totales['v'] != 0 || $totales['g'] != 0) {
            $finalLabels[] = $lbl;
            $finalVentas[] = $totales['v'];
            $finalGastos[] = $totales['g'];
        }
    }
} else {
    foreach ($fechasArray as $f) {
        $v = $ventasPorDia[$f];
        $g = $gastosPorDia[$f];
        if ($v != 0 || $g != 0) {
            $finalLabels[] = $f;
            $finalVentas[] = $v;
            $finalGastos[] = $g;
        }
    }
}

echo json_encode([
    'ok' => true,
    'agrupado_semanal' => $agrupar_semanal,
    'agrupado_mensual' => $agrupar_mensual,
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