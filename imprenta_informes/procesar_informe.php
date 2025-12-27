<?php
// procesar_informe.php
session_start();

// Validar sesión
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// --- 1. FUNCIONES DE CÁLCULO ---

/**
 * Obtiene Ingresos, Egresos y Neto para un mes y año específicos
 */
function obtenerTotalesMes($conexion, $mes, $anio) {
    // Usamos la relación entre detalle_caja -> caja (para la fecha) -> tipo_movimiento (para saber si suma o resta)
    // NOTA: Asumo que en tu tabla 'tipo_movimiento', es_entrada=1 suma y es_salida=1 resta.
    $sql = "
        SELECT 
            SUM(CASE WHEN tm.es_entrada = 1 THEN dc.monto ELSE 0 END) as total_entradas,
            SUM(CASE WHEN tm.es_salida = 1 THEN dc.monto ELSE 0 END) as total_salidas
        FROM detalle_caja dc
        INNER JOIN caja c ON dc.idCaja = c.idCaja
        INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
        WHERE MONTH(c.Fecha) = '$mes' AND YEAR(c.Fecha) = '$anio'
    ";
    
    $query = mysqli_query($conexion, $sql);
    $data = mysqli_fetch_assoc($query);
    
    // Devolvemos un array con los 3 valores clave, asegurando que no sean null (0 por defecto)
    $entradas = $data['total_entradas'] ?? 0;
    $salidas  = $data['total_salidas'] ?? 0;
    
    return [
        'ingresos' => $entradas,
        'gastos'   => $salidas,
        'neto'     => $entradas - $salidas
    ];
}

/**
 * Calcula el porcentaje de crecimiento o decrecimiento
 */
function calcularVariacion($actual, $anterior) {
    if ($anterior == 0) {
        // Si el mes anterior fue 0:
        // - Si el actual es positivo, es un aumento del 100% (simbólico).
        // - Si el actual es 0, no hubo cambio (0%).
        return ($actual > 0) ? 100 : 0;
    }
    return (($actual - $anterior) / $anterior) * 100;
}

// --- 2. LOGICA DE FECHAS ---

// Fechas actuales
$mesActual = date('m');
$anioActual = date('Y');

// Fechas del mes pasado
$mesAnterior = date('m', strtotime('-1 month'));
$anioAnterior = date('Y', strtotime('-1 month'));

// Nombre del mes para mostrar en el título
$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMesActual = $nombresMeses[$mesActual];

// --- 3. EJECUCIÓN DE CONSULTAS ---

// A. Obtener datos financieros
$datosMesActual   = obtenerTotalesMes($MiConexion, $mesActual, $anioActual);
$datosMesAnterior = obtenerTotalesMes($MiConexion, $mesAnterior, $anioAnterior);

// B. Calcular porcentajes de variación
// Para Salidas (Gastos)
$varGastos = calcularVariacion($datosMesActual['gastos'], $datosMesAnterior['gastos']);
// Para Ganancia Neta
$varNeto   = calcularVariacion($datosMesActual['neto'], $datosMesAnterior['neto']);

// --- 4. PREPARACIÓN DE ESTILOS VISUALES (Colores e Iconos) ---

// Lógica Visual para GASTOS:
// Si los gastos suben (positivo), es "malo" (rojo). Si bajan, es "bueno" (verde).
if ($varGastos > 0) {
    $colorGastos = '#dc3545'; // Rojo
    $iconoGastos = '&#9650;'; // Flecha arriba
} elseif ($varGastos < 0) {
    $colorGastos = '#28a745'; // Verde
    $iconoGastos = '&#9660;'; // Flecha abajo
} else {
    $colorGastos = '#6c757d'; // Gris (igual)
    $iconoGastos = '=';
}

// Lógica Visual para GANANCIA NETA:
// Si la ganancia sube, es "bueno" (verde). Si baja, es "malo" (rojo).
if ($varNeto > 0) {
    $colorNeto = '#28a745'; // Verde
    $iconoNeto = '&#9650;'; 
} elseif ($varNeto < 0) {
    $colorNeto = '#dc3545'; // Rojo
    $iconoNeto = '&#9660;'; 
} else {
    $colorNeto = '#6c757d'; 
    $iconoNeto = '=';
}

// (Opcional) Guardamos los valores formateados en variables para imprimir fácil
$txtGastosActual = number_format($datosMesActual['gastos'], 2, ',', '.');
$txtNetoActual   = number_format($datosMesActual['neto'], 2, ',', '.');
$txtVarGastos    = number_format(abs($varGastos), 1); // Valor absoluto para mostrar % sin el signo menos duplicado
$txtVarNeto      = number_format(abs($varNeto), 1);

?>