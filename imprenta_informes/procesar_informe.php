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

$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');
list($anio, $mes) = explode('-', $periodo);

$fechaObj = new DateTime($periodo . '-01');
$fechaObj->modify('-1 month');
$mesAnt = $fechaObj->format('m');
$anioAnt = $fechaObj->format('Y');

// --- FUNCIÓN PRINCIPAL ---

function obtenerDatosMes($conexion, $m, $a) {
    // 1. OBTENER TOTALES DE CAJA (TABLA detalle_caja)
    
    // A. Ingresos Brutos (Entradas normales, excluyendo diferencias positivas ID 15 para no duplicar si se procesan aparte)
    $sqlEntradas = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                    JOIN caja c ON dc.idCaja = c.idCaja
                    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                    AND tm.es_entrada = 1 
                    AND dc.idTipoMovimiento != 15"; 
    $qEntradas = mysqli_query($conexion, $sqlEntradas);
    $rowEntradas = mysqli_fetch_assoc($qEntradas);
    $ingresosCaja = floatval($rowEntradas['total']);

    // B. Diferencias de Caja
    // Positiva (ID 15) -> Suma a Ingresos
    $sqlDifPos = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 15";
    $difPositiva = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifPos))['total']);

    // Negativa (ID 14) -> SE RESTARÁ DE INGRESOS (No es gasto)
    $sqlDifNeg = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 14";
    $difNegativa = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifNeg))['total']);

    // C. Egresos de Caja 
    // IMPORTANTE: Excluimos ID 14 (porque va a ingresos restando) y ID 9 (Caja Fuerte, ignorar totalmente)
    $sqlSalidasCaja = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                       JOIN caja c ON dc.idCaja = c.idCaja
                       JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                       WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                       AND tm.es_salida = 1
                       AND dc.idTipoMovimiento NOT IN (14, 9)"; 
    $gastosCaja = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlSalidasCaja))['total']);

    // 2. OBTENER TOTALES DE RETIROS CONTABLES (TABLA retiros)
    $sqlRetiros = "SELECT SUM(monto) as total FROM retiros 
                   WHERE MONTH(fecha) = '$m' AND YEAR(fecha) = '$a'";
    $montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlRetiros))['total']);


    // 3. CÁLCULO DE TOTALES FINALES
    // Total Ingresos = Operativos + Sobrantes - Faltantes
    $totalIngresos = $ingresosCaja + $difPositiva - $difNegativa;
    
    // Total Egresos = Gastos Operativos (sin CF ni dif) + Retiros
    $totalEgresos = $gastosCaja + $montoRetiros;


    // 4. DETALLES MEDIOS DE PAGO
    
    // A) BANCO
    $sqlBanco = "SELECT tp.denominacion as concepto, SUM(dc.monto) as monto
                 FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoPago IN (3, 13, 23)
                 GROUP BY tp.denominacion";
    $qBanco = mysqli_query($conexion, $sqlBanco);
    $detallesBanco = []; $totalBanco = 0;
    while($row = mysqli_fetch_assoc($qBanco)){ $totalBanco += $row['monto']; $detallesBanco[] = $row; }
    foreach($detallesBanco as &$item) { $item['porcentaje'] = ($totalBanco > 0) ? number_format(($item['monto'] / $totalBanco) * 100, 1) . '%' : '0%'; }

    // B) MERCADOPAGO
    $sqlMP = "SELECT tp.denominacion as concepto, SUM(dc.monto) as monto
              FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
              WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoPago = 22
              GROUP BY tp.denominacion";
    $qMP = mysqli_query($conexion, $sqlMP);
    $detallesMP = []; $totalMP = 0;
    while($row = mysqli_fetch_assoc($qMP)){ $totalMP += $row['monto']; $detallesMP[] = $row; }
    foreach($detallesMP as &$item) { $item['porcentaje'] = ($totalMP > 0) ? number_format(($item['monto'] / $totalMP) * 100, 1) . '%' : '0%'; }

    // C) EFECTIVO
    // Efectivo puro entrada
    $sqlEfecEnt = "SELECT SUM(dc.monto) as monto FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                   WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoPago = 1 AND tm.es_entrada = 1 AND dc.idTipoMovimiento NOT IN (14, 15)";
    $montoEntEfec = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlEfecEnt))['monto']);
    
    // El efectivo total real incluye las diferencias
    $totalEfectivo = $montoEntEfec + $difPositiva - $difNegativa;

    $detallesEfectivo = [];
    if($montoEntEfec > 0) $detallesEfectivo[] = ['concepto' => 'Ingresos Operativos', 'monto' => $montoEntEfec];
    if($difPositiva > 0) $detallesEfectivo[] = ['concepto' => 'Diferencia a Favor', 'monto' => $difPositiva];
    if($difNegativa > 0) $detallesEfectivo[] = ['concepto' => 'Diferencia en Contra', 'monto' => $difNegativa * -1]; // Mostrar negativo

    foreach($detallesEfectivo as &$item) {
        $base = ($totalEfectivo != 0) ? $totalEfectivo : 1;
        $item['porcentaje'] = number_format((abs($item['monto']) / abs($base)) * 100, 1) . '%';
    }

    // 5. LISTAS DE DESGLOSE

    // A. Lista Ingresos (Incluye la resta de dif negativa visualmente)
    $sqlListaIng = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                    FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_entrada = 1
                    GROUP BY tm.denominacion ORDER BY monto DESC";
    $qListaIng = mysqli_query($conexion, $sqlListaIng);
    $listaIngresos = [];
    while($row = mysqli_fetch_assoc($qListaIng)) {
        $row['monto'] = floatval($row['monto']);
        $listaIngresos[] = $row;
    }
    
    // Insertar Diferencia Negativa en la lista de INGRESOS (restando)
    if ($difNegativa > 0) {
        $listaIngresos[] = [
            'concepto' => 'Diferencia de Caja (Faltante)',
            'monto' => -$difNegativa // Negativo
        ];
    }
    
    // Calcular porcentajes de ingresos sobre el Total Neto
    foreach($listaIngresos as &$row) {
        $porc = ($totalIngresos > 0) ? ($row['monto'] / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
    }


    // B. Lista Gastos (Excluir ID 9 y ID 14)
    $listaGastos = [];
    
    // Gastos Caja
    $sqlListaGastosCaja = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                           FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                           WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                           AND tm.es_salida = 1 
                           AND dc.idTipoMovimiento NOT IN (14, 9) 
                           GROUP BY tm.denominacion";
    $qGC = mysqli_query($conexion, $sqlListaGastosCaja);
    while($row = mysqli_fetch_assoc($qGC)){
        $listaGastos[] = $row;
    }

    // Gastos Retiros
    $sqlListaRetiros = "SELECT tm.denominacion as concepto, SUM(r.monto) as monto
                        FROM retiros r
                        JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                        WHERE MONTH(r.fecha) = '$m' AND YEAR(r.fecha) = '$a'
                        GROUP BY tm.denominacion";
    $qGR = mysqli_query($conexion, $sqlListaRetiros);
    while($row = mysqli_fetch_assoc($qGR)){
        $encontrado = false;
        foreach($listaGastos as &$existente) {
            if($existente['concepto'] == $row['concepto']) {
                $existente['monto'] += floatval($row['monto']);
                $encontrado = true;
                break;
            }
        }
        if(!$encontrado) {
            $listaGastos[] = ['concepto' => $row['concepto'], 'monto' => floatval($row['monto'])];
        }
    }

    usort($listaGastos, function($a, $b) { return $b['monto'] <=> $a['monto']; });
    
    foreach($listaGastos as &$row) {
        $porc = ($totalIngresos > 0) ? ($row['monto'] / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
    }

    return [
        'banco' => $totalBanco,
        'detallesBanco' => $detallesBanco,
        'mp' => $totalMP,
        'detallesMP' => $detallesMP,
        'efectivo' => $totalEfectivo,
        'detallesEfectivo' => $detallesEfectivo,
        'totalIngresos' => $totalIngresos,
        'totalGastos' => $totalEgresos,
        'desgloseIngresos' => $listaIngresos,
        'desgloseGastos' => $listaGastos
    ];
}

$actual = obtenerDatosMes($MiConexion, $mes, $anio);
$anterior = obtenerDatosMes($MiConexion, $mesAnt, $anioAnt);

echo json_encode([
    'ok' => true,
    'datos' => $actual,
    'previo' => $anterior
]);
?>