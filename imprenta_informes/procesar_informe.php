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
    
    // 1. OBTENER TOTALES DE INGRESOS (SOLO ENTRADAS REALES DE CAJA)
    
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

    // *** CAMBIO: Se eliminaron las consultas de Diferencia Positiva (ID 15) y Negativa (ID 14) ***
    // $difPositiva = ... (ELIMINADO)
    // $difNegativa = ... (ELIMINADO)

    // *** CAMBIO: Se eliminó el cálculo de 'Gastos de Caja' ($gastosCaja). 
    // La instrucción es que las salidas sean SOLO retiros. ***

    
    // 2. OBTENER TOTALES DE RETIROS (NUEVA LÓGICA DE SALIDAS)
    
    // *** CAMBIO: Se agrega 'AND idTipoMovimiento != 9' para excluir Caja Fuerte ***
    $sqlRetiros = "SELECT SUM(monto) as total FROM retiros 
                   WHERE MONTH(fecha) = '$m' AND YEAR(fecha) = '$a'
                   AND idTipoMovimiento != 9"; 
    $montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($conexion, $sqlRetiros))['total']);


    // 3. CÁLCULO DE TOTALES FINALES

    // *** CAMBIO: Ingresos es solo lo que entra en caja (sin sumar/restar diferencias) ***
    $totalIngresos = $ingresosCaja; 
    
    // *** CAMBIO: Egresos es AHORA SOLO el monto de retiros (sin gastos de caja) ***
    $totalEgresos = $montoRetiros;


    // 4. DETALLES TARJETAS SUPERIORES
    
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
    
    // *** CAMBIO: El total efectivo ya no suma/resta diferencias ***
    $totalEfectivo = $montoEntEfec;

    $detallesEfectivo = [];
    if($montoEntEfec > 0) $detallesEfectivo[] = ['concepto' => 'Ingresos Operativos', 'monto' => $montoEntEfec];
    // *** CAMBIO: Se eliminaron los items de Diferencia a Favor/Contra en el desglose ***

    foreach($detallesEfectivo as &$item) {
        $base = ($totalEfectivo != 0) ? $totalEfectivo : 1;
        $item['porcentaje'] = number_format((abs($item['monto']) / abs($base)) * 100, 1) . '%';
    }

    // 5. LISTAS DE DESGLOSE (INGRESOS Y EGRESOS)

    // Lista Ingresos (Se mantiene igual, solo caja)
    $sqlListaIng = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                    FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_entrada = 1
                    GROUP BY tm.denominacion ORDER BY monto DESC";
    $qListaIng = mysqli_query($conexion, $sqlListaIng);
    $listaIngresos = [];
    while($row = mysqli_fetch_assoc($qListaIng)) {
        $monto = floatval($row['monto']);
        $porc = ($totalIngresos > 0) ? ($monto / $totalIngresos) * 100 : 0;
        $row['porcentaje'] = number_format($porc, 1) . '%';
        $listaIngresos[] = $row;
    }

    // Lista Gastos (AHORA SOLO RETIROS)
    $listaGastos = [];
    
    // *** CAMBIO: Se eliminó la consulta de $sqlListaGastosCaja (salidas desde caja) ***
    // *** CAMBIO: Solo se consulta la tabla retiros, excluyendo ID 9 (Caja Fuerte) ***

    $sqlListaRetiros = "SELECT tm.denominacion as concepto, SUM(r.monto) as monto
                        FROM retiros r
                        JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                        WHERE MONTH(r.fecha) = '$m' AND YEAR(r.fecha) = '$a'
                        AND r.idTipoMovimiento != 9
                        GROUP BY tm.denominacion";
    
    $qGR = mysqli_query($conexion, $sqlListaRetiros);
    while($row = mysqli_fetch_assoc($qGR)){
        // Ya no necesitamos lógica de fusión porque es la única fuente de gastos
        $listaGastos[] = ['concepto' => $row['concepto'], 'monto' => floatval($row['monto'])];
    }

    usort($listaGastos, function($a, $b) { return $b['monto'] <=> $a['monto']; });
    
    // Calcular porcentajes sobre el total de egresos (que ahora son solo retiros)
    foreach($listaGastos as &$row) {
        // Nota: calculamos % sobre Total Ingresos (habitual para ver impacto) o Total Gastos?
        // El código original lo hacía sobre Total INGRESOS. Lo mantengo así.
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