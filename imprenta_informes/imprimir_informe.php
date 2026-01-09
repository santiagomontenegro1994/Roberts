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

// --- DATOS BÁSICOS ---
$mesReporte = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anioReporte = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMes = $nombresMeses[str_pad($mesReporte, 2, "0", STR_PAD_LEFT)];

// --- LÓGICA IDÉNTICA A PROCESAR_INFORME.PHP ---

// 1. DIFERENCIAS
$sqlDifPos = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte' AND dc.idTipoMovimiento = 15";
$difPositiva = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlDifPos))['total']);

$sqlDifNeg = "SELECT SUM(dc.monto) as total FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte' AND dc.idTipoMovimiento = 14";
$difNegativa = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlDifNeg))['total']);

// 2. INGRESOS CAJA (Solo activos y entradas reales)
$sqlEntradas = "SELECT SUM(dc.monto) as total FROM detalle_caja dc
                JOIN caja c ON dc.idCaja = c.idCaja
                JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago 
                WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
                AND tm.es_entrada = 1 
                AND dc.idTipoMovimiento != 15
                AND tp.idActivo = 1"; 
$ingresosCaja = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlEntradas))['total']);

// 3. MEDIOS DE PAGO (Desglosados individualmente como en el procesador)
// Banco
$sqlBanco = "SELECT SUM(dc.monto) as monto FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte' AND dc.idTipoPago IN (3, 13, 23)";
$totalBanco = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlBanco))['monto']);

// MP
$sqlMP = "SELECT SUM(dc.monto) as monto FROM detalle_caja dc JOIN caja c ON dc.idCaja = c.idCaja WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte' AND dc.idTipoPago = 22";
$totalMP = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlMP))['monto']);

// Efectivo (Lógica: Ventas Efvo Activas + Diferencias)
$sqlEfecEnt = "SELECT SUM(dc.monto) as monto FROM detalle_caja dc 
               JOIN caja c ON dc.idCaja = c.idCaja 
               JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
               WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte' 
               AND dc.idTipoPago = 1 AND dc.idTipoMovimiento NOT IN (14, 15) AND tp.idActivo = 1";
$montoEntEfec = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlEfecEnt))['monto']);

// 4. RETIROS
$sqlRetiros = "SELECT SUM(monto) as total FROM retiros 
               WHERE MONTH(fecha) = '$mesReporte' AND YEAR(fecha) = '$anioReporte'
               AND idTipoMovimiento NOT IN (9, 14, 15)"; 
$montoRetiros = floatval(mysqli_fetch_assoc(mysqli_query($MiConexion, $sqlRetiros))['total']);

// --- CÁLCULOS FINALES ---
$totalIngresos = $ingresosCaja + $difPositiva - $difNegativa;
$totalEgresos = $montoRetiros;
$gananciaNeta = $totalIngresos - $totalEgresos;
$totalEfectivo = $montoEntEfec + $difPositiva - $difNegativa;

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        /* ESTILOS DEL ENCABEZADO RESTAURADOS */
        .header-table { width: 100%; border-bottom: 2px solid #444; margin-bottom: 30px; padding-bottom: 10px; }
        .header-logo { width: 50%; vertical-align: middle; }
        .header-logo img { max-width: 180px; max-height: 80px; }
        .header-info { width: 50%; text-align: right; vertical-align: middle; }
        .header-info h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header-info p { margin: 5px 0 0; font-size: 14px; color: #666; }
        
        .resumen-cards { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .resumen-cards td { width: 33%; padding: 15px; text-align: center; background-color: #f9f9f9; border: 1px solid #ddd; }
        .monto { font-size: 16px; font-weight: bold; }
        .medios-pago { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .medios-pago td { border: 1px solid #eee; padding: 10px; text-align: center; }
        .label { font-size: 9px; text-transform: uppercase; color: #777; font-weight: bold; }
        .detalle-table { width: 100%; border-collapse: collapse; }
        .detalle-table th { background-color: #444; color: #fff; padding: 8px; font-size: 10px; text-align: left; }
        .detalle-table td { border-bottom: 1px solid #eee; padding: 7px; }
        .text-right { text-align: right; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 9px; color: #fff; }
        .bg-in { background-color: #28a745; }
        .bg-ret { background-color: #fd7e14; }
        .bg-dif { background-color: #17a2b8; }
        .bg-out { background-color: #dc3545; }
        .porcentaje { color: #888; font-size: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #eee; padding-top: 5px; }
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
                <span class="label">INGRESOS TOTALES</span><br>
                <div class="monto" style="color: #28a745;">$ <?php echo number_format($totalIngresos, 2, ',', '.'); ?></div>
            </td>
            <td>
                <span class="label">EGRESOS TOTALES</span><br>
                <div class="monto" style="color: #dc3545;">$ <?php echo number_format($totalEgresos, 2, ',', '.'); ?></div>
            </td>
            <td>
                <span class="label">GANANCIA NETA</span><br>
                <div class="monto">$ <?php echo number_format($gananciaNeta, 2, ',', '.'); ?></div>
            </td>
        </tr>
    </table>

    <table class="medios-pago">
        <tr>
            <td><span class="label">Banco</span><br>$ <?php echo number_format($totalBanco, 2, ',', '.'); ?></td>
            <td><span class="label">MercadoPago</span><br>$ <?php echo number_format($totalMP, 2, ',', '.'); ?></td>
            <td><span class="label">Efectivo</span><br>$ <?php echo number_format($totalEfectivo, 2, ',', '.'); ?></td>
        </tr>
    </table>

    <h3>Detalle de Movimientos</h3>
    <table class="detalle-table">
        <thead>
            <tr>
                <th>CONCEPTO</th>
                <th>TIPO</th>
                <th class="text-right">MONTO Y %</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $listaFinal = [];

            // A. Ingresos Operativos
            $sqlDetCaja = "SELECT tm.denominacion, SUM(dc.monto) as subtotal
                           FROM detalle_caja dc
                           JOIN caja c ON dc.idCaja = c.idCaja
                           JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                           JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                           WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
                           AND tm.es_entrada = 1 AND dc.idTipoMovimiento != 15 AND tp.idActivo = 1
                           GROUP BY tm.denominacion";
            $qCaja = mysqli_query($MiConexion, $sqlDetCaja);
            while($r = mysqli_fetch_assoc($qCaja)){
                $listaFinal[] = [
                    'concepto' => $r['denominacion'],
                    'tipo' => 'Entrada',
                    'clase' => 'bg-in',
                    'monto' => floatval($r['subtotal'])
                ];
            }

            // B. Diferencias
            if($difPositiva > 0) $listaFinal[] = ['concepto' => 'Diferencia a Favor', 'tipo' => 'Entrada', 'clase' => 'bg-dif', 'monto' => $difPositiva];
            if($difNegativa > 0) $listaFinal[] = ['concepto' => 'Diferencia en Contra', 'tipo' => 'Entrada', 'clase' => 'bg-out', 'monto' => ($difNegativa * -1)];

            // C. Gastos (Retiros)
            $sqlDetRet = "SELECT tm.denominacion, SUM(r.monto) as subtotal
                          FROM retiros r
                          JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                          WHERE MONTH(r.fecha) = '$mesReporte' AND YEAR(r.fecha) = '$anioReporte'
                          AND r.idTipoMovimiento NOT IN (9, 14, 15)
                          GROUP BY tm.denominacion";
            $qRet = mysqli_query($MiConexion, $sqlDetRet);
            while($r = mysqli_fetch_assoc($qRet)){
                $listaFinal[] = [
                    'concepto' => $r['denominacion'],
                    'tipo' => 'Salida',
                    'clase' => 'bg-ret',
                    'monto' => floatval($r['subtotal'])
                ];
            }

            // Ordenar por monto descendente
            usort($listaFinal, function($a, $b) { return abs($b['monto']) <=> abs($a['monto']); });

            foreach($listaFinal as $row) {
                $div = ($totalIngresos != 0) ? $totalIngresos : 1;
                $porc = (abs($row['monto']) / abs($div)) * 100;
            ?>
            <tr>
                <td><?php echo $row['concepto']; ?></td>
                <td><span class="badge <?php echo $row['clase']; ?>"><?php echo $row['tipo']; ?></span></td>
                <td class="text-right">
                    $ <?php echo number_format($row['monto'], 2, ',', '.'); ?>
                    <span class="porcentaje">(<?php echo number_format($porc, 1); ?>%)</span>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="footer">Imprenta Roberts - Documento de Gestión Interna</div>
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