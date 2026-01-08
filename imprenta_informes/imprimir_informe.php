<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// --- CONFIGURACIÓN ZONA HORARIA ARGENTINA ---
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Incluir la librería Dompdf
require_once '../libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// --- DATOS BÁSICOS ---
$mesReporte = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anioReporte = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMes = $nombresMeses[str_pad($mesReporte, 2, "0", STR_PAD_LEFT)];

// --- CONSULTAS SQL (Sincronizadas con procesar_informe) ---

// 1. DATOS DE CAJA + FILTRO ACTIVOS
$sqlCaja = "
    SELECT 
        -- Ingresos Operativos (Solo activos, sin diferencias)
        SUM(CASE WHEN tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 THEN dc.monto ELSE 0 END) as ingresos_reales,
        
        -- Desglose Medios Pago (Solo activos)
        SUM(CASE WHEN dc.idTipoPago IN (3, 13, 23) THEN dc.monto ELSE 0 END) as banco,
        SUM(CASE WHEN dc.idTipoPago = 22 THEN dc.monto ELSE 0 END) as mp,
        
        -- Efectivo Base (Solo activos, sin diferencias)
        SUM(CASE WHEN dc.idTipoPago = 1 AND tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 THEN dc.monto ELSE 0 END) as efectivo_puro,

        -- Diferencias (Para el cálculo neto)
        SUM(CASE WHEN dc.idTipoMovimiento = 15 THEN dc.monto ELSE 0 END) as dif_positiva,
        SUM(CASE WHEN dc.idTipoMovimiento = 14 THEN dc.monto ELSE 0 END) as dif_negativa

    FROM detalle_caja dc
    JOIN caja c ON dc.idCaja = c.idCaja
    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
    AND tp.idActivo = 1"; // FILTRO CRÍTICO

$dataCaja = mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlCaja));

// 2. DATOS DE RETIROS (Salidas Puras)
$sqlRetiros = "SELECT SUM(monto) as total FROM retiros 
               WHERE MONTH(fecha) = '$mesReporte' AND YEAR(fecha) = '$anioReporte' 
               AND idTipoMovimiento NOT IN (9, 14, 15)";
$dataRetiros = mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlRetiros));

// --- CÁLCULOS MATEMÁTICOS ---

$diferenciaNeta = floatval($dataCaja['dif_positiva']) - floatval($dataCaja['dif_negativa']);

// Ventas Totales = Operativo + Diferencia Neta
$totalIngresos = floatval($dataCaja['ingresos_reales']) + $diferenciaNeta;

// Egresos = Solo Retiros
$totalEgresos = floatval($dataRetiros['total']);

// Ganancia Neta
$gananciaNeta = $totalIngresos - $totalEgresos;

// Medios de Pago
$totalBanco = floatval($dataCaja['banco']);
$totalMP = floatval($dataCaja['mp']);

// Efectivo Real = Operativo + Diferencia Neta
$totalEfectivo = floatval($dataCaja['efectivo_puro']) + $diferenciaNeta;

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Financiero - <?php echo $nombreMes . ' ' . $anioReporte; ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header-table { width: 100%; border-bottom: 2px solid #444; margin-bottom: 30px; padding-bottom: 10px; }
        .header-logo { width: 50%; vertical-align: middle; }
        .header-logo img { max-width: 180px; max-height: 80px; }
        .header-info { width: 50%; text-align: right; vertical-align: middle; }
        .header-info h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header-info p { margin: 5px 0 0; font-size: 14px; color: #666; }
        
        .resumen-cards { width: 100%; margin-bottom: 30px; }
        .resumen-cards td { width: 33%; padding: 10px; text-align: center; background-color: #f8f9fa; border: 1px solid #ddd; }
        .resumen-cards h3 { margin: 0 0 5px; font-size: 14px; color: #555; }
        .resumen-cards .monto { font-size: 18px; font-weight: bold; color: #000; }
        
        .medios-pago { margin-bottom: 30px; width: 100%; border-collapse: collapse; }
        .medios-pago td { border: 1px solid #eee; padding: 8px; text-align: center; width: 33%; }
        .medios-pago .label { font-weight: bold; display: block; margin-bottom: 4px; font-size: 10px; text-transform: uppercase; color: #777; }
        
        .detalle-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .detalle-table th { background-color: #333; color: #fff; padding: 10px; text-align: left; font-size: 11px; }
        .detalle-table td { border-bottom: 1px solid #eee; padding: 8px 10px; }
        .text-right { text-align: right; }
        
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 9px; color: #fff; text-transform: uppercase; }
        .bg-in { background-color: #28a745; }
        .bg-out { background-color: #dc3545; } 
        .bg-ret { background-color: #fd7e14; }
        .bg-dif { background-color: #17a2b8; }
        
        .porcentaje { color: #888; font-size: 10px; margin-left: 5px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-logo">
                <?php
                    $ruta_imagen = '../assets/img/logo.png';
                    if(file_exists($ruta_imagen)){
                        $tipo = pathinfo($ruta_imagen, PATHINFO_EXTENSION);
                        $dataImg = file_get_contents($ruta_imagen);
                        $base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($dataImg);
                        echo '<img src="'.$base64.'" alt="Logo">';
                    } else {
                        echo '<h2>IMPRENTA ROBERTS</h2>';
                    }
                ?>
            </td>
            <td class="header-info">
                <h1>Informe Financiero</h1>
                <p>Período: <?php echo $nombreMes . ' de ' . $anioReporte; ?></p>
                <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
            </td>
        </tr>
    </table>

    <table class="resumen-cards">
        <tr>
            <td>
                <h3>INGRESOS TOTALES</h3>
                <div class="monto" style="color: #28a745;">
                    $ <?php echo number_format($totalIngresos, 2, ',', '.'); ?>
                </div>
            </td>
            <td>
                <h3>EGRESOS TOTALES</h3>
                <div class="monto" style="color: #dc3545;">
                    $ <?php echo number_format($totalEgresos, 2, ',', '.'); ?>
                </div>
            </td>
            <td>
                <h3>GANANCIA NETA</h3>
                <div class="monto">
                    $ <?php echo number_format($gananciaNeta, 2, ',', '.'); ?>
                </div>
            </td>
        </tr>
    </table>

    <table class="medios-pago">
        <tr>
            <td>
                <span class="label">Banco</span>
                $ <?php echo number_format($totalBanco, 2, ',', '.'); ?>
            </td>
            <td>
                <span class="label">MercadoPago</span>
                $ <?php echo number_format($totalMP, 2, ',', '.'); ?>
            </td>
            <td>
                <span class="label">Efectivo (Real)</span>
                $ <?php echo number_format($totalEfectivo, 2, ',', '.'); ?>
            </td>
        </tr>
    </table>

    <h3>Detalle de Movimientos</h3>
    <table class="detalle-table">
        <thead>
            <tr>
                <th>CONCEPTO</th>
                <th>TIPO</th>
                <th class="text-right">MONTO Y % (s/Ingresos)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $agrupados = [];

            // 1. Ingresos Operativos (Caja) - Solo activos
            $sqlDetalleCaja = "
                SELECT tm.denominacion, tm.es_entrada, SUM(dc.monto) as subtotal
                FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
                AND tm.es_entrada = 1
                AND dc.idTipoMovimiento NOT IN (14, 15, 9) 
                AND tp.idActivo = 1
                GROUP BY tm.denominacion, tm.es_entrada
            ";
            $qCaja = mysqli_query($MiConexion, $sqlDetalleCaja);
            
            while($r = mysqli_fetch_assoc($qCaja)){
                $nombre = mb_strtoupper(trim($r['denominacion']), 'UTF-8');
                if($nombre == 'SUELDOS') $nombre = 'SUELDO';
                if($nombre == 'INSUMOS') $nombre = 'INSUMO';

                if(!isset($agrupados[$nombre])) {
                    $agrupados[$nombre] = [
                        'concepto' => ucfirst(strtolower($nombre)), 
                        'tipo' => 'Entrada',
                        'clase' => 'bg-in', 
                        'monto' => 0
                    ];
                }
                $agrupados[$nombre]['monto'] += $r['subtotal'];
            }

            // 2. AGREGAR DIFERENCIA DE CAJA NETA (Manual)
            if($diferenciaNeta != 0) {
                // Se agrega a la lista como un ítem más
                $agrupados['DIFERENCIA_CAJA'] = [
                    'concepto' => 'Diferencia de Caja',
                    'tipo' => 'Entrada', // Se clasifica como entrada porque ajusta ventas
                    'clase' => 'bg-dif', 
                    'monto' => $diferenciaNeta
                ];
            }

            // 3. Salidas (Retiros)
            $sqlDetalleRetiros = "
                SELECT tm.denominacion, SUM(r.monto) as subtotal
                FROM retiros r
                JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(r.fecha) = '$mesReporte' AND YEAR(r.fecha) = '$anioReporte'
                AND r.idTipoMovimiento NOT IN (9, 14, 15)
                GROUP BY tm.denominacion
            ";
            $qRetiros = mysqli_query($MiConexion, $sqlDetalleRetiros);
            
            while($r = mysqli_fetch_assoc($qRetiros)){
                $nombre = mb_strtoupper(trim($r['denominacion']), 'UTF-8');
                if($nombre == 'SUELDOS') $nombre = 'SUELDO';
                if($nombre == 'INSUMOS') $nombre = 'INSUMO';

                if(!isset($agrupados[$nombre])) {
                    $agrupados[$nombre] = [
                        'concepto' => ucfirst(strtolower($nombre)), 
                        'tipo' => 'Salida',
                        'clase' => 'bg-ret', 
                        'monto' => 0
                    ];
                } else {
                    $agrupados[$nombre]['monto'] += $r['subtotal'];
                    $agrupados[$nombre]['tipo'] = 'Salida';
                    $agrupados[$nombre]['clase'] = 'bg-ret';
                }
            }

            // Ordenar
            $filasTabla = array_values($agrupados);
            usort($filasTabla, function($a, $b) { return $b['monto'] <=> $a['monto']; });

            if (count($filasTabla) > 0) {
                foreach($filasTabla as $row) { 
                    $montoItem = $row['monto'];
                    $porcentaje = ($totalIngresos > 0) ? ($montoItem / $totalIngresos) * 100 : 0;
            ?>
            <tr>
                <td><?php echo $row['concepto']; ?></td>
                <td>
                    <span class="badge <?php echo $row['clase']; ?>"><?php echo $row['tipo']; ?></span>
                </td>
                <td class="text-right">
                    $ <?php echo number_format($montoItem, 2, ',', '.'); ?>
                    <span class="porcentaje">(%<?php echo number_format($porcentaje, 1); ?>)</span>
                </td>
            </tr>
            <?php 
                } 
            } else {
                echo '<tr><td colspan="3" style="text-align:center; padding: 20px;">No hay movimientos registrados.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="footer">
        Imprenta Roberts - Sistema de Gestión Interno <br>
        Documento confidencial para uso administrativo.
    </div>

</body>
</html>

<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Informe_" . $nombreMes . "_" . $anioReporte . ".pdf", array("Attachment" => false));
?>