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

// --- LÓGICA DE DATOS ---
$mesReporte = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anioReporte = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMes = $nombresMeses[str_pad($mesReporte, 2, "0", STR_PAD_LEFT)];

// 1. CÁLCULO DE TOTALES (Lógica corregida)

// A. Datos de CAJA
$sqlCaja = "
    SELECT 
        -- Ingresos Normales (Entradas excluyendo Dif Positiva 15 por si acaso, se suma aparte si se desea, o todo junto)
        SUM(CASE WHEN tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 THEN dc.monto ELSE 0 END) as ingresos_caja,
        
        -- Dif Positiva (15)
        SUM(CASE WHEN dc.idTipoMovimiento = 15 THEN dc.monto ELSE 0 END) as dif_positiva,

        -- Dif Negativa (14)
        SUM(CASE WHEN dc.idTipoMovimiento = 14 THEN dc.monto ELSE 0 END) as dif_negativa,
        
        -- Egresos Caja (Salidas excluyendo ID 14 y Caja Fuerte)
        SUM(CASE WHEN tm.es_salida = 1 AND dc.idTipoMovimiento != 14 AND tm.denominacion NOT LIKE '%Caja Fuerte%' THEN dc.monto ELSE 0 END) as egresos_caja,
        
        -- Desglose Medios Pago
        SUM(CASE WHEN dc.idTipoPago IN (3, 13, 23) THEN dc.monto ELSE 0 END) as banco,
        SUM(CASE WHEN dc.idTipoPago = 22 THEN dc.monto ELSE 0 END) as mp,
        SUM(CASE WHEN dc.idTipoPago = 1 AND tm.es_entrada = 1 AND dc.idTipoMovimiento NOT IN (14, 15) THEN dc.monto ELSE 0 END) as efectivo_puro

    FROM detalle_caja dc
    JOIN caja c ON dc.idCaja = c.idCaja
    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'";

$dataCaja = mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlCaja));

// B. Datos de RETIROS (Tabla 'retiros')
$sqlRetiros = "SELECT SUM(monto) as total FROM retiros WHERE MONTH(fecha) = '$mesReporte' AND YEAR(fecha) = '$anioReporte'";
$dataRetiros = mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlRetiros));
$totalRetirosContables = floatval($dataRetiros['total']);

// C. Consolidación de Totales
$totalIngresos = floatval($dataCaja['ingresos_caja']) + floatval($dataCaja['dif_positiva']) - floatval($dataCaja['dif_negativa']);
$totalEgresos = floatval($dataCaja['egresos_caja']) + $totalRetirosContables;
$gananciaNeta = $totalIngresos - $totalEgresos;

// Totales medios de pago (Efectivo ajustado con diferencias)
$totalBanco = floatval($dataCaja['banco']);
$totalMP = floatval($dataCaja['mp']);
$totalEfectivo = floatval($dataCaja['efectivo_puro']) + floatval($dataCaja['dif_positiva']) - floatval($dataCaja['dif_negativa']);

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Financiero - <?php echo $nombreMes . ' ' . $anioReporte; ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #666; }
        
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
        .bg-ret { background-color: #fd7e14; } /* Color naranja para retiros */
        
        .porcentaje { color: #888; font-size: 10px; margin-left: 5px; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Informe Financiero</h1>
        <p>Período: <?php echo $nombreMes . ' de ' . $anioReporte; ?></p>
        <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

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
                <span class="label">Efectivo (Neto)</span>
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
            // Preparamos los datos unificados para la tabla
            $filasTabla = [];

            // 1. Movimientos de Caja
            $sqlDetalleCaja = "
                SELECT tm.denominacion, tm.es_entrada, tm.es_salida, dc.idTipoMovimiento, SUM(dc.monto) as subtotal
                FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
                -- Excluir Caja Fuerte y Dif Negativa (14) de este listado, ya que el 14 restó al total y CF no va
                AND dc.idTipoMovimiento != 14 
                AND tm.denominacion NOT LIKE '%Caja Fuerte%'
                GROUP BY tm.denominacion, tm.es_entrada, tm.es_salida, dc.idTipoMovimiento
            ";
            $qCaja = mysqli_query($MiConexion, $sqlDetalleCaja);
            while($r = mysqli_fetch_assoc($qCaja)){
                $tipo = ($r['es_entrada'] == 1) ? 'Ingreso' : 'Egreso';
                $clase = ($r['es_entrada'] == 1) ? 'bg-in' : 'bg-out';
                $filasTabla[] = ['concepto' => $r['denominacion'], 'tipo' => $tipo, 'clase' => $clase, 'monto' => $r['subtotal']];
            }

            // 2. Movimientos de Retiros (Se consideran Egresos)
            $sqlDetalleRetiros = "
                SELECT tm.denominacion, SUM(r.monto) as subtotal
                FROM retiros r
                JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(r.fecha) = '$mesReporte' AND YEAR(r.fecha) = '$anioReporte'
                GROUP BY tm.denominacion
            ";
            $qRetiros = mysqli_query($MiConexion, $sqlDetalleRetiros);
            while($r = mysqli_fetch_assoc($qRetiros)){
                // Agregamos a la lista
                $filasTabla[] = ['concepto' => $r['denominacion'], 'tipo' => 'Retiro', 'clase' => 'bg-ret', 'monto' => $r['subtotal']];
            }

            // Ordenar por monto descendente para que se vea ordenado
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