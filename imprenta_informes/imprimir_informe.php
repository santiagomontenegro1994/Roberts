<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// Incluir la librería Dompdf
require_once '../libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// --- LÓGICA DE DATOS ---
// Recibimos mes y año por GET, o usamos el actual por defecto
$mesReporte = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anioReporte = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Array de meses para texto
$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMes = $nombresMeses[str_pad($mesReporte, 2, "0", STR_PAD_LEFT)];

// Reutilizamos la lógica de consulta (pégala aquí o inclúyela)
$sql = "
    SELECT 
        SUM(CASE WHEN tm.es_entrada = 1 THEN dc.monto ELSE 0 END) as total_entradas,
        SUM(CASE WHEN tm.es_salida = 1 THEN dc.monto ELSE 0 END) as total_salidas,
        COUNT(dc.idDetalleCaja) as cantidad_movimientos
    FROM detalle_caja dc
    INNER JOIN caja c ON dc.idCaja = c.idCaja
    INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
";
$query = mysqli_query($MiConexion, $sql);
$resumen = mysqli_fetch_assoc($query);

$ingresos = $resumen['total_entradas'] ?? 0;
$gastos = $resumen['total_salidas'] ?? 0;
$ganancia = $ingresos - $gastos;

// Consulta opcional: Desglose por tipo de movimiento (para llenar la tabla)
$sqlDetalle = "
    SELECT tm.denominacion, 
           SUM(dc.monto) as subtotal,
           tm.es_entrada
    FROM detalle_caja dc
    INNER JOIN caja c ON dc.idCaja = c.idCaja
    INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
    GROUP BY tm.denominacion, tm.es_entrada
    ORDER BY subtotal DESC
";
$queryDetalle = mysqli_query($MiConexion, $sqlDetalle);

ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Mensual - <?php echo $nombreMes; ?></title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; color: #333; margin: 0; padding: 20px; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .header img { max-width: 150px; float: left; }
        .header-text { text-align: right; float: right; }
        .header-text h1 { margin: 0; font-size: 22px; text-transform: uppercase; color: #444; }
        .header-text p { margin: 5px 0 0; color: #777; font-size: 14px; }
        
        .resumen-cards { width: 100%; margin-bottom: 30px; display: table; }
        .card { display: table-cell; width: 30%; background: #f4f4f4; padding: 15px; border-radius: 5px; text-align: center; border: 1px solid #ddd; }
        .card h3 { margin: 0 0 10px; font-size: 14px; color: #666; text-transform: uppercase; }
        .card .numero { font-size: 20px; font-weight: bold; color: #333; }
        .spacer { display: table-cell; width: 5%; } /* Espacio entre cards */

        .green { color: #27ae60 !important; }
        .red { color: #c0392b !important; }
        
        .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; color: #444; }
        
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table th { background: #eee; text-align: left; padding: 10px; border-bottom: 2px solid #ddd; font-weight: bold; color: #555; }
        table td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        
        .footer { margin-top: 50px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <?php
            $ruta_imagen = '../assets/img/logo.png';
            if(file_exists($ruta_imagen)){
                $tipo_imagen = pathinfo($ruta_imagen, PATHINFO_EXTENSION);
                $datos_imagen = file_get_contents($ruta_imagen);
                $base64_imagen = 'data:image/' . $tipo_imagen . ';base64,' . base64_encode($datos_imagen);
                echo '<img src="'.$base64_imagen.'" alt="Logo">';
            } else {
                echo '<h2>IMPRENTA ROBERTS</h2>';
            }
        ?>
        <div class="header-text">
            <h1>Informe Económico</h1>
            <p>Período: <strong><?php echo $nombreMes . ' ' . $anioReporte; ?></strong></p>
            <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>

    <div class="resumen-cards">
        <div class="card">
            <h3>Ingresos</h3>
            <div class="numero green">$ <?php echo number_format($ingresos, 2, ',', '.'); ?></div>
        </div>
        <div class="spacer"></div>
        <div class="card">
            <h3>Egresos</h3>
            <div class="numero red">$ <?php echo number_format($gastos, 2, ',', '.'); ?></div>
        </div>
        <div class="spacer"></div>
        <div class="card" style="background: #e8f5e9; border-color: #c8e6c9;">
            <h3>Ganancia Neta</h3>
            <div class="numero <?php echo ($ganancia >= 0) ? 'green' : 'red'; ?>">
                $ <?php echo number_format($ganancia, 2, ',', '.'); ?>
            </div>
        </div>
    </div>

    <div class="section-title">Desglose de Movimientos</div>
    
    <table>
        <thead>
            <tr>
                <th>Concepto / Tipo de Movimiento</th>
                <th>Tipo</th>
                <th class="text-right">Monto Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if(mysqli_num_rows($queryDetalle) > 0) {
                while($row = mysqli_fetch_assoc($queryDetalle)) { 
                    $tipo = ($row['es_entrada'] == 1) ? 'Ingreso' : 'Egreso';
                    $claseColor = ($row['es_entrada'] == 1) ? 'green' : 'red';
            ?>
            <tr>
                <td><?php echo $row['denominacion']; ?></td>
                <td><span style="font-size: 10px; padding: 2px 5px; border-radius: 3px; background: #eee; color: #555;"><?php echo $tipo; ?></span></td>
                <td class="text-right <?php echo $claseColor; ?>">
                    $ <?php echo number_format($row['subtotal'], 2, ',', '.'); ?>
                </td>
            </tr>
            <?php 
                } 
            } else {
                echo '<tr><td colspan="3" style="text-align:center">No hay movimientos registrados en este período.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="footer">
        Imprenta Roberts - Sistema de Gestión Interno <br>
    </div>

</body>
</html>

<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnable' => true));
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Informe_".$nombreMes."_".$anioReporte.".pdf", array("Attachment" => false)); // false para abrir en navegador, true para descargar
?>