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

// Asegurar Zona Horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');
list($anio, $mes) = explode('-', $periodo);

$fechaObj = new DateTime($periodo . '-01');
$fechaObj->modify('-1 month');
$mesAnt = $fechaObj->format('m');
$anioAnt = $fechaObj->format('Y');

// --- FUNCIÓN PRINCIPAL ---

function obtenerDatosMes($conexion, $m, $a) {
    // 1. OBTENER TOTALES DE CAJA (TABLA detalle_caja)
    
    // A. Ingresos Brutos (Entradas normales)
    $sqlEntradas = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                    JOIN caja c ON dc.idCaja = c.idCaja
                    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                    AND tm.es_entrada = 1 
                    AND dc.idTipoMovimiento != 15"; 
    $qEntradas = mysqli_query($conexion, $sqlEntradas);
    $rowEntradas = mysqli_fetch_assoc($qEntradas);
    $ingresosCaja = floatval($rowEntradas['total']);

    // B. Diferencias de Caja (Positiva 15, Negativa 14)
    $sqlDifPos = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 15";
    $difPositiva = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifPos))['total']);

    $sqlDifNeg = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 14";
    $difNegativa = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifNeg))['total']);

    // C. Egresos de Caja (Salidas normales)
    // ESTO ESTABA BIEN: Ya filtrabas ID 9 y 14 aquí
    $sqlSalidasCaja = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                       JOIN caja c ON dc.idCaja = c.idCaja
                       JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                       WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                       AND tm.es_salida = 1
                       AND dc.idTipoMovimiento NOT IN (14, 9)"; 
    $gastosCaja = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlSalidasCaja))['total']);

    // 2. OBTENER TOTALES DE RETIROS CONTABLES (TABLA retiros)
    // ---> ¡AQUÍ ESTABA EL ERROR! Faltaba ignorar ID 9 en los RETIROS <---
    $sqlRetiros = "SELECT SUM(monto) as total FROM retiros 
                   WHERE MONTH(fecha) = '$m' AND YEAR(fecha) = '$a'
                   AND idTipoMovimiento != 9";
    $montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlRetiros))['total']);


    // 3. CÁLCULO DE TOTALES FINALES
    $totalIngresos = $ingresosCaja + $difPositiva - $difNegativa;
    $totalEgresos = $gastosCaja + $montoRetiros;
    
    // Calculamos ganancia para enviarla lista al front
    $gananciaNeta = $totalIngresos - $totalEgresos;


    // 4. DETALLES TARJETAS
    
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
    $sqlEfecEnt = "SELECT SUM(dc.monto) as monto FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                   WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoPago = 1 AND tm.es_entrada = 1 AND dc.idTipoMovimiento NOT IN (14, 15)";
    $montoEntEfec = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlEfecEnt))['monto']);
    
    $totalEfectivo = $montoEntEfec + $difPositiva - $difNegativa;

    $detallesEfectivo = [];
    if($montoEntEfec > 0) $detallesEfectivo[] = ['concepto' => 'Ingresos Operativos', 'monto' => $montoEntEfec];
    if($difPositiva > 0) $detallesEfectivo[] = ['concepto' => 'Diferencia a Favor', 'monto' => $difPositiva];
    if($difNegativa > 0) $detallesEfectivo[] = ['concepto' => 'Diferencia en Contra', 'monto' => $difNegativa * -1];

    foreach($detallesEfectivo as &$item) {
        $base = ($totalEfectivo != 0) ? $totalEfectivo : 1;
        $item['porcentaje'] = number_format((abs($item['monto']) / abs($base)) * 100, 1) . '%';
    }

    // 5. LISTAS DE DESGLOSE (INGRESOS Y EGRESOS)

    // Lista Ingresos
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

    // ---> AGREGADO: Mostrar la Diferencia Negativa en el listado restando <---
    if($difNegativa > 0) {
        $listaIngresos[] = [
            'concepto' => 'Diferencia de Caja (Faltante)',
            'monto' => floatval($difNegativa) * -1 // Negativo
        ];
    }
    
    // Recalcular porcentajes
    foreach($listaIngresos as &$row) {
        $porc = ($totalIngresos > 0) ? ($row['monto'] / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
    }

    // Lista Gastos
    $listaGastos = [];
    
    // a. Gastos desde Caja (excluyendo CF y ID 14)
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

    // b. Gastos desde Retiros
    // ---> CORRECCIÓN AQUÍ: Filtrar ID 9 también en el listado de retiros <---
    $sqlListaRetiros = "SELECT tm.denominacion as concepto, SUM(r.monto) as monto
                        FROM retiros r
                        JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                        WHERE MONTH(r.fecha) = '$m' AND YEAR(r.fecha) = '$a'
                        AND r.idTipoMovimiento != 9
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
        'ganancia' => $gananciaNeta, // Enviamos ganancia lista
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