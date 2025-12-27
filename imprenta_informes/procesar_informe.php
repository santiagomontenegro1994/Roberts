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

// --- FUNCIONES ---

function obtenerDatosMes($conexion, $m, $a) {
    // 1. Totales Generales
    $sql = "SELECT 
                SUM(CASE WHEN tm.es_entrada = 1 THEN dc.monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tm.es_salida = 1 THEN dc.monto ELSE 0 END) as egresos,
                -- Desglose por método de pago (ajustar según tus ID de tipo movimiento o descripciones si es necesario)
                SUM(CASE WHEN tm.denominacion LIKE '%Banco%' AND tm.es_entrada=1 THEN dc.monto ELSE 0 END) as banco,
                SUM(CASE WHEN tm.denominacion LIKE '%MercadoPago%' AND tm.es_entrada=1 THEN dc.monto ELSE 0 END) as mp,
                SUM(CASE WHEN tm.denominacion LIKE '%Efectivo%' AND tm.es_entrada=1 THEN dc.monto ELSE 0 END) as efectivo
            FROM detalle_caja dc
            INNER JOIN caja c ON dc.idCaja = c.idCaja
            INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
            WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a'";
            
    $query = mysqli_query($conexion, $sql);
    $totales = mysqli_fetch_assoc($query);

    // 2. Lista de Gastos (Detalle)
    $sqlGastos = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_salida = 1
                  GROUP BY tm.denominacion ORDER BY monto DESC";
    $qGastos = mysqli_query($conexion, $sqlGastos);
    $listaGastos = [];
    while($row = mysqli_fetch_assoc($qGastos)) $listaGastos[] = $row;

    // 3. Lista de Ingresos (Detalle para el nuevo desplegable)
    $sqlIngresos = "SELECT tm.denominacion as concepto, SUM(dc.monto) as monto
                  FROM detalle_caja dc
                  JOIN caja c ON dc.idCaja = c.idCaja
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  WHERE MONTH(c.Fecha) = '$m' AND YEAR(c.Fecha) = '$a' AND tm.es_entrada = 1
                  GROUP BY tm.denominacion ORDER BY monto DESC";
    $qIngresos = mysqli_query($conexion, $sqlIngresos);
    $listaIngresos = [];
    while($row = mysqli_fetch_assoc($qIngresos)) $listaIngresos[] = $row;

    return [
        'banco' => floatval($totales['banco']),
        'mp' => floatval($totales['mp']),
        'efectivo' => floatval($totales['efectivo']),
        'totalIngresos' => floatval($totales['ingresos']), // Ventas Totales
        'totalGastos' => floatval($totales['egresos']),
        'desgloseGastos' => $listaGastos,
        'desgloseIngresos' => $listaIngresos
    ];
}

// Ejecutamos
$datosActual = obtenerDatosMes($MiConexion, $mes, $anio);
$datosPrevio = obtenerDatosMes($MiConexion, $mesAnt, $anioAnt);

echo json_encode([
    'ok' => true,
    'datos' => $datosActual,
    'previo' => $datosPrevio
]);
?>