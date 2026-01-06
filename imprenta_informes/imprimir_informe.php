<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once '../libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$mesReporte = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anioReporte = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMes = $nombresMeses[str_pad($mesReporte, 2, "0", STR_PAD_LEFT)];

// --- CONSULTAS ROBUSTAS (COALESCE para evitar NULL) ---

// 1. Datos Totales
$sqlTotales = "
    SELECT 
        -- Ingresos Operativos (Entradas sin ID 14 ni 15)
        COALESCE(SUM(CASE WHEN tm.es_entrada = 1 AND dc.idTipoMovimiento NOT IN (14, 15) THEN dc.monto ELSE 0 END), 0) as ing_op,
        
        -- Diferencias
        COALESCE(SUM(CASE WHEN dc.idTipoMovimiento = 15 THEN dc.monto ELSE 0 END), 0) as dif_pos,
        COALESCE(SUM(CASE WHEN dc.idTipoMovimiento = 14 THEN dc.monto ELSE 0 END), 0) as dif_neg,
        
        -- Egresos Caja (Salidas sin ID 9 ni 14)
        COALESCE(SUM(CASE WHEN tm.es_salida = 1 AND dc.idTipoMovimiento NOT IN (9, 14) THEN dc.monto ELSE 0 END), 0) as egr_caja,
        
        -- Medios de Pago
        COALESCE(SUM(CASE WHEN dc.idTipoPago IN (3, 13, 23) THEN dc.monto ELSE 0 END), 0) as banco,
        COALESCE(SUM(CASE WHEN dc.idTipoPago = 22 THEN dc.monto ELSE 0 END), 0) as mp,
        COALESCE(SUM(CASE WHEN dc.idTipoPago = 1 AND tm.es_entrada = 1 AND dc.idTipoMovimiento NOT IN (14, 15) THEN dc.monto ELSE 0 END), 0) as efec_op
        
    FROM detalle_caja dc
    JOIN caja c ON dc.idCaja = c.idCaja
    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
";
$qTotales = mysqli_query($MiConexion, $sqlTotales);
$dCaja = mysqli_fetch_assoc($qTotales);

// 2. Retiros
$sqlRet = "SELECT COALESCE(SUM(monto), 0) as total FROM retiros WHERE MONTH(fecha) = '$mesReporte' AND YEAR(fecha) = '$anioReporte'";
$dRet = mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlRet));

// CÁLCULOS
$totalIngresos = floatval($dCaja['ing_op']) + floatval($dCaja['dif_pos']) - floatval($dCaja['dif_neg']);
$totalEgresos = floatval($dCaja['egr_caja']) + floatval($dRet['total']);
$gananciaNeta = $totalIngresos - $totalEgresos;

$totalBanco = floatval($dCaja['banco']);
$totalMP = floatval($dCaja['mp']);
$totalEfectivo = floatval($dCaja['efec_op']) + floatval($dCaja['dif_pos']) - floatval($dCaja['dif_neg']);

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
        .bg-neg { background-color: #6c757d; }
        
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
            $filasTabla = [];

            // 1. Movimientos de Caja (Excluyendo ID 9 totalmente)
            $sqlDetalleCaja = "
                SELECT tm.denominacion, tm.es_entrada, tm.es_salida, dc.idTipoMovimiento, SUM(dc.monto) as subtotal
                FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
                AND dc.idTipoMovimiento != 9 
                GROUP BY tm.denominacion, tm.es_entrada, tm.es_salida, dc.idTipoMovimiento
            ";
            $qCaja = mysqli_query($MiConexion, $sqlDetalleCaja);
            while($r = mysqli_fetch_assoc($qCaja)){
                // Diferencia Negativa (ID 14) -> Mostramos como ingreso negativo
                if ($r['idTipoMovimiento'] == 14) {
                     $filasTabla[] = [
                         'concepto' => $r['denominacion'], 
                         'tipo' => 'Ajuste Ingreso', 
                         'clase' => 'bg-neg', 
                         'monto' => -1 * abs($r['subtotal']) 
                     ];
                } 
                elseif ($r['es_entrada'] == 1) {
                    $filasTabla[] = [
                        'concepto' => $r['denominacion'], 
                        'tipo' => 'Ingreso', 
                        'clase' => 'bg-in', 
                        'monto' => $r['subtotal']
                    ];
                }
                elseif ($r['es_salida'] == 1) {
                    $filasTabla[] = [
                        'concepto' => $r['denominacion'], 
                        'tipo' => 'Egreso', 
                        'clase' => 'bg-out', 
                        'monto' => $r['subtotal']
                    ];
                }
            }

            // 2. Movimientos de Retiros
            $sqlDetalleRetiros = "
                SELECT tm.denominacion, SUM(r.monto) as subtotal
                FROM retiros r
                JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                WHERE MONTH(r.fecha) = '$mesReporte' AND YEAR(r.fecha) = '$anioReporte'
                GROUP BY tm.denominacion
            ";
            $qRetiros = mysqli_query($MiConexion, $sqlDetalleRetiros);
            while($r = mysqli_fetch_assoc($qRetiros)){
                $filasTabla[] = ['concepto' => $r['denominacion'], 'tipo' => 'Retiro', 'clase' => 'bg-ret', 'monto' => $r['subtotal']];
            }

            // Ordenar por magnitud (absoluto)
            usort($filasTabla, function($a, $b) { return abs($b['monto']) <=> abs($a['monto']); });

            if (count($filasTabla) > 0) {
                foreach($filasTabla as $row) { 
                    $montoItem = $row['monto'];
                    $porcentaje = ($totalIngresos > 0) ? (abs($montoItem) / $totalIngresos) * 100 : 0;
            ?>
            <tr>
                <td><?php echo $row['concepto']; ?></td>
                <td>
                    <span class="badge <?php echo $row['clase']; ?>"><?php echo $row['tipo']; ?></span>
                </td>
                <td class="text-right">
                    <?php echo '$ ' . number_format($montoItem, 2, ',', '.'); ?>
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