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
    // 1. TOTALES GENERALES
    // NOTA: Se quitó la exclusión del ID 15 en ingresos para que sume la dif. caja positiva
    $sqlTotales = "SELECT 
                SUM(CASE WHEN tm.es_entrada = 1 THEN dc.monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tm.es_salida = 1 AND dc.idTipoMovimiento != 14 THEN dc.monto ELSE 0 END) as egresos
            FROM detalle_caja dc
            JOIN caja c ON dc.idCaja = c.idCaja
            JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
            WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'";

    $query = mysqli_query($conexion, $sqlTotales);
    $totales = mysqli_fetch_assoc($query);
    
    $totalIngresos = floatval($totales['ingresos']);
    $totalEgresos = floatval($totales['egresos']);

    // 2. CÁLCULO TARJETAS SUPERIORES (BANCO, MP, EFECTIVO)
    
    // A) BANCO (IDs 3, 13, 23)
    $sqlBanco = "SELECT tp.denominacion, SUM(dc.monto) as monto
                 FROM detalle_caja dc
                 JOIN caja c ON dc.idCaja = c.idCaja
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                 AND dc.idTipoPago IN (3, 13, 23)
                 GROUP BY tp.denominacion";
    $qBanco = mysqli_query($conexion, $sqlBanco);
    $detallesBanco = [];
    $totalBanco = 0;
    while($row = mysqli_fetch_assoc($qBanco)){
        $monto = floatval($row['monto']);
        $totalBanco += $monto;
        $detallesBanco[] = ['concepto' => $row['denominacion'], 'monto' => $monto];
    }
    // Calcular porcentajes internos del banco
    foreach($detallesBanco as &$item) {
        $item['porcentaje'] = ($totalBanco > 0) ? number_format(($item['monto'] / $totalBanco) * 100, 1) . '%' : '0%';
    }

    // B) MERCADOPAGO (ID 22)
    $sqlMP = "SELECT tp.denominacion, SUM(dc.monto) as monto
                 FROM detalle_caja dc
                 JOIN caja c ON dc.idCaja = c.idCaja
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                 AND dc.idTipoPago = 22
                 GROUP BY tp.denominacion";
    $qMP = mysqli_query($conexion, $sqlMP);
    $detallesMP = [];
    $totalMP = 0;
    while($row = mysqli_fetch_assoc($qMP)){
        $monto = floatval($row['monto']);
        $totalMP += $monto;
        $detallesMP[] = ['concepto' => $row['denominacion'], 'monto' => $monto];
    }
    foreach($detallesMP as &$item) {
        $item['porcentaje'] = ($totalMP > 0) ? number_format(($item['monto'] / $totalMP) * 100, 1) . '%' : '0%';
    }

    // C) EFECTIVO (Lógica compleja solicitada)
    // 1. Entradas Efectivo (TipoPago 1)
    // 2. Dif Caja Positiva (Mov 15)
    // 3. Restar Dif Caja Negativa (Mov 14)
    
    $detallesEfectivo = [];
    $totalEfectivo = 0;

    // C.1 Entradas Reales en Efectivo (Excluyendo diferencias para no duplicar si hubiera error en IDs)
    $sqlEfecEntradas = "SELECT 'Ingresos Operativos' as concepto, SUM(dc.monto) as monto
                        FROM detalle_caja dc
                        JOIN caja c ON dc.idCaja = c.idCaja
                        JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                        WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                        AND dc.idTipoPago = 1 
                        AND tm.es_entrada = 1
                        AND dc.idTipoMovimiento NOT IN (14, 15)";
    $resEnt = mysqli_fetch_assoc(mysqli_query($conexion, $sqlEfecEntradas));
    $montoEnt = floatval($resEnt['monto']);

    // C.2 Dif Positiva (Mov 15)
    $sqlDifPos = "SELECT 'Diferencia a Favor' as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                  AND dc.idTipoMovimiento = 15";
    $resPos = mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifPos));
    $montoPos = floatval($resPos['monto']);

    // C.3 Dif Negativa (Mov 14) - Se restará
    $sqlDifNeg = "SELECT 'Diferencia en Contra' as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'
                  AND dc.idTipoMovimiento = 14";
    $resNeg = mysqli_fetch_assoc(mysqli_query($conexion, $sqlDifNeg));
    $montoNeg = floatval($resNeg['monto']);

    // Calcular Total Efectivo Neto
    $totalEfectivo = $montoEnt + $montoPos - $montoNeg;

    // Armar array detalles
    if($montoEnt > 0) $detallesEfectivo[] = ['concepto' => 'Ingresos/Cobros', 'monto' => $montoEnt];
    if($montoPos > 0) $detallesEfectivo[] = ['concepto' => 'Sobrantes de Caja', 'monto' => $montoPos];
    if($montoNeg > 0) $detallesEfectivo[] = ['concepto' => 'Faltantes de Caja', 'monto' => $montoNeg * -1]; // Mostrar negativo visualmente

    // Calcular porcentajes sobre el total neto de efectivo (cuidado con división por cero)
    foreach($detallesEfectivo as &$item) {
        // Para el porcentaje usamos valor absoluto para que tenga sentido visual
        $base = ($totalEfectivo != 0) ? $totalEfectivo : 1;
        $item['porcentaje'] = number_format((abs($item['monto']) / abs($base)) * 100, 1) . '%';
    }


    // 3. LISTAS DEL BALANCE (INGRESOS Y EGRESOS DETALLADOS)
    // Ingresos: Incluimos todo (incluso Dif Caja ID 15 si se desea ver en lista general)
    $sqlIngresos = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                  AND tm.es_entrada = 1 
                  GROUP BY tm.denominacion ORDER BY monto DESC";
    $qIngresos = mysqli_query($conexion, $sqlIngresos);
    $listaIngresos = [];
    while($row = mysqli_fetch_assoc($qIngresos)) {
        $montoItem = floatval($row['monto']);
        $porc = ($totalIngresos > 0) ? ($montoItem / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
        $listaIngresos[] = $row;
    }

    // Egresos (Mantenemos exclusión de ID 14 si se considera ajuste de caja y no gasto operativo, 
    // o se quita el filtro si quieres ver el faltante como gasto)
    $sqlGastos = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' 
                  AND tm.es_salida = 1 
                  AND dc.idTipoMovimiento != 14 
                  GROUP BY tm.denominacion ORDER BY monto DESC";
    $qGastos = mysqli_query($conexion, $sqlGastos);
    $listaGastos = [];
    while($row = mysqli_fetch_assoc($qGastos)) {
        $montoItem = floatval($row['monto']);
        $porc = ($totalIngresos > 0) ? ($montoItem / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
        $listaGastos[] = $row;
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