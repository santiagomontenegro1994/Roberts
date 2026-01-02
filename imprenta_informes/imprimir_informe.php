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

// 1. CONSULTA DE TOTALES GENERALES Y CONTADORES
// EXCLUYENDO DIFERENCIA DE CAJA (15 en Ingresos, 14 en Egresos)
$sqlTotales = "
    SELECT 
        SUM(CASE WHEN tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 THEN dc.monto ELSE 0 END) as total_ingresos,
        SUM(CASE WHEN tm.es_salida = 1 AND dc.idTipoMovimiento != 14 THEN dc.monto ELSE 0 END) as total_egresos,
        SUM(CASE WHEN dc.idTipoPago IN (3, 13, 23) THEN dc.monto ELSE 0 END) as banco,
        SUM(CASE WHEN dc.idTipoPago = 22 THEN dc.monto ELSE 0 END) as mp,
        SUM(CASE WHEN dc.idTipoMovimiento = 9 THEN dc.monto ELSE 0 END) as efectivo
    FROM detalle_caja dc
    JOIN caja c ON dc.idCaja = c.idCaja
    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'";

$queryTotales = mysqli_query($MiConexion, $sqlTotales);
$dataTotales = mysqli_fetch_assoc($queryTotales);

$totalIngresos = $dataTotales['total_ingresos'] ?? 0;
$totalEgresos = $dataTotales['total_egresos'] ?? 0;
$gananciaNeta = $totalIngresos - $totalEgresos;

// Captura de buffering para HTML
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
                <span class="label">Banco (Transferencias)</span>
                $ <?php echo number_format($dataTotales['banco'], 2, ',', '.'); ?>
            </td>
            <td>
                <span class="label">MercadoPago</span>
                $ <?php echo number_format($dataTotales['mp'], 2, ',', '.'); ?>
            </td>
            <td>
                <span class="label">Efectivo (Trabajos)</span>
                $ <?php echo number_format($dataTotales['efectivo'], 2, ',', '.'); ?>
            </td>
        </tr>
    </table>

    <h3>Detalle de Movimientos Agrupados</h3>
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
            // Consultamos el desglose agrupado EXCLUYENDO DIF DE CAJA (14 y 15)
            $sqlDetalle = "
                SELECT tm.denominacion, tm.es_entrada, SUM(dc.monto) as subtotal
                FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
                AND dc.idTipoMovimiento NOT IN (14, 15)
                GROUP BY tm.denominacion, tm.es_entrada
                ORDER BY tm.es_entrada DESC, subtotal DESC
            ";
            
            $queryDetalle = mysqli_query($MiConexion, $sqlDetalle);
            
            if (mysqli_num_rows($queryDetalle) > 0) {
                while($row = mysqli_fetch_assoc($queryDetalle)) { 
                    $tipoTxt = ($row['es_entrada'] == 1) ? 'Ingreso' : 'Egreso';
                    $badgeClass = ($row['es_entrada'] == 1) ? 'bg-in' : 'bg-out';
                    
                    // Cálculo de porcentaje sobre el TOTAL DE INGRESOS
                    $montoItem = $row['subtotal'];
                    $porcentaje = ($totalIngresos > 0) ? ($montoItem / $totalIngresos) * 100 : 0;
            ?>
            <tr>
                <td><?php echo $row['denominacion']; ?></td>
                <td>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $tipoTxt; ?></span>
                </td>
                <td class="text-right">
                    $ <?php echo number_format($montoItem, 2, ',', '.'); ?>
                    <span class="porcentaje">(%<?php echo number_format($porcentaje, 1); ?>)</span>
                </td>
            </tr>
            <?php 
                } 
            } else {
                echo '<tr><td colspan="3" style="text-align:center; padding: 20px;">No hay movimientos registrados en este período.</td></tr>';
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