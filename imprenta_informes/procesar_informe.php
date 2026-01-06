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

// Evitar errores de JSON
mysqli_set_charset($MiConexion, "utf8mb4");

$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');
list($anio, $mes) = explode('-', $periodo);

$fechaObj = new DateTime($periodo . '-01');
$fechaObj->modify('-1 month');
$mesAnt = $fechaObj->format('m');
$anioAnt = $fechaObj->format('Y');

// --- FUNCIÓN PRINCIPAL ---

function obtenerDatosMes($conexion, $m, $a) {
    
    // 1. INGRESOS: Entradas normales (excluyendo ID 15 y 14)
    // Usamos COALESCE para que si es null devuelva 0
    $sqlEntradas = "SELECT COALESCE(SUM(dc.monto), 0) as total 
                    FROM detalle_caja dc
                    JOIN caja c ON dc.idCaja = c.idCaja
                    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                    AND tm.es_entrada = 1 
                    AND dc.idTipoMovimiento NOT IN (14, 15)"; 
    $qEntradas = mysqli_query($conexion, $sqlEntradas);
    $ingresosOperativos = floatval(mysqli_fetch_assoc($qEntradas)['total']);

    // 2. DIFERENCIAS (ID 15 Positiva, ID 14 Negativa)
    $sqlDifPos = "SELECT COALESCE(SUM(dc.monto), 0) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 15";
    $difPositiva = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifPos))['total']);

    $sqlDifNeg = "SELECT COALESCE(SUM(dc.monto), 0) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoMovimiento = 14";
    $difNegativa = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifNeg))['total']);

    // 3. EGRESOS DE CAJA
    // Excluir ID 9 (Caja Fuerte) y ID 14 (Dif Negativa, que va a ingresos restando)
    $sqlSalidas = "SELECT COALESCE(SUM(dc.monto), 0) as total 
                   FROM detalle_caja dc
                   JOIN caja c ON dc.idCaja = c.idCaja
                   JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                   WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                   AND tm.es_salida = 1
                   AND dc.idTipoMovimiento NOT IN (9, 14)"; 
    $gastosCaja = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlSalidas))['total']);

    // 4. RETIROS (Tabla externa)
    $sqlRetiros = "SELECT COALESCE(SUM(monto), 0) as total FROM retiros 
                   WHERE MONTH(fecha) = '$m' AND YEAR(fecha) = '$a'";
    $montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlRetiros))['total']);


    // --- CÁLCULOS FINALES ---
    
    // Total Ingresos = (Ventas Reales + Sobrantes) - Faltantes
    $totalIngresos = $ingresosOperativos + $difPositiva - $difNegativa;
    
    // Total Egresos = Gastos de Caja (sin caja fuerte) + Retiros
    $totalEgresos = $gastosCaja + $montoRetiros;


    // 5. MEDIOS DE PAGO (Contadores)
    
    // Banco (3, 13, 23)
    $sqlBanco = "SELECT tp.denominacion as concepto, SUM(dc.monto) as monto
                 FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoPago IN (3, 13, 23)
                 GROUP BY tp.denominacion";
    $qBanco = mysqli_query($conexion, $sqlBanco);
    $detallesBanco = []; $totalBanco = 0;
    while($row = mysqli_fetch_assoc($qBanco)){ 
        $totalBanco += floatval($row['monto']); 
        $detallesBanco[] = $row; 
    }
    // Calcular porcentajes
    foreach($detallesBanco as &$item) { $item['porcentaje'] = ($totalBanco > 0) ? number_format(($item['monto'] / $totalBanco) * 100, 1) . '%' : '0%'; }

    // MercadoPago (22)
    $sqlMP = "SELECT tp.denominacion as concepto, SUM(dc.monto) as monto
              FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
              WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND dc.idTipoPago = 22
              GROUP BY tp.denominacion";
    $qMP = mysqli_query($conexion, $sqlMP);
    $detallesMP = []; $totalMP = 0;
    while($row = mysqli_fetch_assoc($qMP)){ 
        $totalMP += floatval($row['monto']); 
        $detallesMP[] = $row; 
    }
    foreach($detallesMP as &$item) { $item['porcentaje'] = ($totalMP > 0) ? number_format(($item['monto'] / $totalMP) * 100, 1) . '%' : '0%'; }

    // Efectivo (1)
    $sqlEfec = "SELECT COALESCE(SUM(dc.monto), 0) as monto FROM detalle_caja dc 
                JOIN caja c ON dc.idCaja = c.idCaja 
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                AND dc.idTipoPago = 1 AND tm.es_entrada = 1 AND dc.idTipoMovimiento NOT IN (14, 15)";
    $montoEntEfec = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlEfec))['monto']);
    
    // Total Efectivo Neto = Efectivo Entrante + Sobrante - Faltante
    $totalEfectivo = $montoEntEfec + $difPositiva - $difNegativa;

    $detallesEfectivo = [];
    if($montoEntEfec > 0) $detallesEfectivo[] = ['concepto' => 'Ingresos Operativos', 'monto' => $montoEntEfec];
    if($difPositiva > 0) $detallesEfectivo[] = ['concepto' => 'Diferencia a Favor', 'monto' => $difPositiva];
    if($difNegativa > 0) $detallesEfectivo[] = ['concepto' => 'Diferencia en Contra', 'monto' => $difNegativa * -1];

    foreach($detallesEfectivo as &$item) {
        $base = ($totalEfectivo != 0) ? $totalEfectivo : 1;
        $item['porcentaje'] = number_format((abs($item['monto']) / abs($base)) * 100, 1) . '%';
    }

    // 6. LISTADOS DE DESGLOSE

    // A. INGRESOS
    $listaIngresos = [];
    // Ingresos Normales
    $sqlListIng = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                   FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                   WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_entrada = 1 AND dc.idTipoMovimiento != 14
                   GROUP BY tm.denominacion ORDER BY monto DESC";
    $qLI = mysqli_query($conexion, $sqlListIng);
    while($row = mysqli_fetch_assoc($qLI)) {
        $row['monto'] = floatval($row['monto']);
        $listaIngresos[] = $row;
    }
    // Agregar Dif Negativa restando visualmente
    if ($difNegativa > 0) {
        $listaIngresos[] = [
            'concepto' => 'Diferencia de Caja (Faltante)',
            'monto' => -$difNegativa
        ];
    }
    // Porcentajes Ingresos
    foreach($listaIngresos as &$row) {
        $porc = ($totalIngresos > 0) ? ($row['monto'] / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
    }


    // B. GASTOS (Excluyendo ID 9 y 14)
    $listaGastos = [];
    
    // Gastos desde Caja
    $sqlListGasCaja = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                       FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                       WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                       AND tm.es_salida = 1 
                       AND dc.idTipoMovimiento NOT IN (9, 14)
                       GROUP BY tm.denominacion";
    $qLGC = mysqli_query($conexion, $sqlListGasCaja);
    while($row = mysqli_fetch_assoc($qLGC)){
        $listaGastos[] = $row;
    }

    // Gastos desde Retiros
    $sqlListRet = "SELECT tm.denominacion as concepto, SUM(r.monto) as monto
                   FROM retiros r JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                   WHERE MONTH(r.fecha) = '$m' AND YEAR(r.fecha) = '$a'
                   GROUP BY tm.denominacion";
    $qLR = mysqli_query($conexion, $sqlListRet);
    while($row = mysqli_fetch_assoc($qLR)){
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
], JSON_UNESCAPED_UNICODE);
?>