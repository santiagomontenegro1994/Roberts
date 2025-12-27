<?php
// procesar_informe.php
header('Content-Type: application/json');
require_once '../funciones/conexion.php';

// Verificar sesión si es necesario
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$MiConexion = ConexionBD();

// Obtener el periodo enviado por GET (formato "YYYY-MM")
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');

// Calcular fechas de inicio y fin del mes actual
$fechaInicio = $periodo . '-01';
$fechaFin = date("Y-m-t", strtotime($fechaInicio));

// Calcular fechas del mes ANTERIOR (para comparar)
$periodoPrevio = date("Y-m", strtotime($periodo . " -1 month"));
$fechaInicioPrev = $periodoPrevio . '-01';
$fechaFinPrev = date("Y-m-t", strtotime($fechaInicioPrev));

/* ==========================================================================
    IMPORTANTE: ZONA DE CONSULTAS SQL
    Necesito que me pases tu BD para hacer esto real.
    Por ahora, usaré DATOS DE EJEMPLO para que veas la interfaz funcionando.
 ==========================================================================
*/

// --- ESTRUCTURA DE RESPUESTA ---
$respuesta = [
    'ok' => true,
    'periodo' => $periodo,
    'datos' => [ // Mes seleccionado
        'banco' => 0,
        'mp' => 0,
        'efectivo' => 0,
        'totalGastos' => 0,
        'desgloseGastos' => [] 
    ],
    'previo' => [ // Mes anterior (solo totales para calcular %)
        'banco' => 0,
        'mp' => 0,
        'efectivo' => 0
    ]
];

// ------------------------------------------------------------------------
// AQUÍ HARÍAMOS LAS CONSULTAS REALES (Te dejo la estructura comentada)
// ------------------------------------------------------------------------

/*
// EJEMPLO DE CÓMO SERÍA LA CONSULTA REAL (Ajustar a tus tablas)
function obtenerVentasPorMetodo($conexion, $fInicio, $fFin, $metodo) {
    $sql = "SELECT SUM(monto) as total FROM ventas 
            WHERE fecha BETWEEN '$fInicio' AND '$fFin' 
            AND metodo_pago = '$metodo'";
    $res = mysqli_query($conexion, $sql);
    $fila = mysqli_fetch_assoc($res);
    return $fila['total'] ? floatval($fila['total']) : 0;
}

$respuesta['datos']['banco'] = obtenerVentasPorMetodo($MiConexion, $fechaInicio, $fechaFin, 'Banco');
$respuesta['datos']['mp'] = obtenerVentasPorMetodo($MiConexion, $fechaInicio, $fechaFin, 'MercadoPago');
// ...etc
*/

// ------------------------------------------------------------------------
// DATOS SIMULADOS (BORRAR ESTO CUANDO TENGAS LA BD CONECTADA)
// ------------------------------------------------------------------------

// Simulamos que los datos varían según el mes para probar el selector
$seed = crc32($periodo); // Semilla basada en el mes
srand($seed);

$respuesta['datos']['banco'] = rand(150000, 300000);
$respuesta['datos']['mp'] = rand(100000, 250000);
$respuesta['datos']['efectivo'] = rand(50000, 120000);

// Simulamos gastos detallados
$respuesta['datos']['desgloseGastos'] = [
    ['concepto' => 'Sueldos Empleados', 'monto' => rand(80000, 90000)],
    ['concepto' => 'Alquiler Local', 'monto' => 45000],
    ['concepto' => 'Servicios (Luz/Internet)', 'monto' => rand(12000, 18000)],
    ['concepto' => 'Insumos de Impresión', 'monto' => rand(20000, 50000)],
    ['concepto' => 'Cafetería / Varios', 'monto' => rand(2000, 5000)]
];

// Sumar gastos
$totalGastos = 0;
foreach($respuesta['datos']['desgloseGastos'] as $g) {
    $totalGastos += $g['monto'];
}
$respuesta['datos']['totalGastos'] = $totalGastos;

// Datos del mes previo (Simulados un poco más bajos para ver variación positiva)
$respuesta['previo']['banco'] = $respuesta['datos']['banco'] * 0.9;
$respuesta['previo']['mp'] = $respuesta['datos']['mp'] * 0.95;
$respuesta['previo']['efectivo'] = $respuesta['datos']['efectivo'] * 1.1; // Efectivo bajó en el actual

// ------------------------------------------------------------------------
// FIN DATOS SIMULADOS
// ------------------------------------------------------------------------

echo json_encode($respuesta);
?>