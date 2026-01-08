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
    
    // 1. OBTENER DIFERENCIAS DE CAJA
    $sqlDifPos = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 15";
    $difPositiva = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifPos))['total']);

    $sqlDifNeg = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 14";
    $difNegativa = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifNeg))['total']);


    // 2. OBTENER INGRESOS BRUTOS DE CAJA (Con filtro idActivo = 1)
    $sqlEntradas = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                    JOIN caja c ON dc.idCaja = c.idCaja
                    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago 
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                    AND tm.es_entrada = 1 
                    AND dc.idTipoMovimiento != 15
                    AND tp.idActivo = 1"; 
    $qEntradas = mysqli_query($conexion, $sqlEntradas);
    $rowEntradas = mysqli_fetch_assoc($qEntradas);
    $ingresosCaja = floatval($rowEntradas['total']);

    
    // 3. OBTENER TOTALES DE RETIROS
    $sqlRetiros = "SELECT SUM(monto) as total FROM retiros 
                   WHERE MONTH(fecha) = '$m' AND YEAR(fecha) = '$a'
                   AND idTipoMovimiento NOT IN (9, 14, 15)"; 
    $montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlRetiros))['total']);


    // 4. CÁLCULO DE TOTALES FINALES

    // Ventas Totales = (Ventas Reales Activas) + (Sobra Plata) - (Falta Plata)
    $totalIngresos = $ingresosCaja + $difPositiva - $difNegativa;
    
    // Salidas Totales = Solo Retiros
    $totalEgresos = $montoRetiros;


    // 5. DETALLES MEDIOS DE PAGO
    
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
    $sqlEfecEnt = "SELECT SUM(dc.monto) as monto FROM detalle_caja dc 
                   JOIN caja c ON dc.idCaja = c.idCaja 
                   JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                   JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                   WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                   AND dc.idTipoPago = 1 
                   AND tm.es_entrada = 1 
                   AND dc.idTipoMovimiento NOT IN (14, 15)
                   AND tp.idActivo = 1"; 
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

    // 6. LISTAS DE DESGLOSE (INGRESOS Y EGRESOS)

    // Lista Ingresos (Datos de base de datos)
    $sqlListaIng = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                    FROM detalle_caja dc 
                    JOIN caja c ON dc.idCaja = c.idCaja 
                    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                    AND tm.es_entrada = 1
                    AND dc.idTipoMovimiento != 15 
                    AND tp.idActivo = 1
                    GROUP BY tm.denominacion ORDER BY monto DESC";
    $qListaIng = mysqli_query($conexion, $sqlListaIng);
    $listaIngresos = [];
    while($row = mysqli_fetch_assoc($qListaIng)) {
        // Guardamos solo los datos, calcularemos porcentaje al final
        $listaIngresos[] = ['concepto' => $row['concepto'], 'monto' => floatval($row['monto'])];
    }

    // *** MODIFICACIÓN VISUAL AQUÍ: Agregar Diferencia Neta a la lista ***
    $diferenciaNeta = $difPositiva - $difNegativa;
    if ($diferenciaNeta != 0) {
        $listaIngresos[] = [
            'concepto' => 'Diferencia de Caja', // Nombre del Item
            'monto' => $diferenciaNeta
        ];
    }

    // Reordenar por monto (para que el más alto quede arriba, incluso si es la diferencia)
    usort($listaIngresos, function($a, $b) { return $b['monto'] <=> $a['monto']; });

    // Calcular Porcentajes Finales
    foreach($listaIngresos as &$row) {
        $porc = ($totalIngresos != 0) ? ($row['monto'] / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
    }

    // Lista Gastos (SOLO RETIROS)
    $listaGastos = [];
    $sqlListaRetiros = "SELECT tm.denominacion as concepto, SUM(r.monto) as monto
                        FROM retiros r
                        JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                        WHERE MONTH(r.fecha) = '$m' AND YEAR(r.fecha) = '$a'
                        AND r.idTipoMovimiento NOT IN (9, 14, 15)
                        GROUP BY tm.denominacion";
    
    $qGR = mysqli_query($conexion, $sqlListaRetiros);
    while($row = mysqli_fetch_assoc($qGR)){
        $listaGastos[] = ['concepto' => $row['concepto'], 'monto' => floatval($row['monto'])];
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