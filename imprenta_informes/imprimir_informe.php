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
$mesReporte = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anioReporte = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$nombresMeses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombreMes = $nombresMeses[str_pad($mesReporte, 2, "0", STR_PAD_LEFT)];

// 1. CONSULTA DE TOTALES GENERALES Y POR MEDIO DE PAGO
// Usamos SUM con IF o CASE para sacar todo en una sola consulta eficiente
$sqlTotales = "
    SELECT 
        SUM(CASE WHEN tm.es_entrada = 1 THEN dc.monto ELSE 0 END) as total_entradas,
        SUM(CASE WHEN tm.es_salida = 1 THEN dc.monto ELSE 0 END) as total_salidas,
        -- Desglose específico (Ajusta los LIKE si tus nombres en BD son distintos)
        SUM(CASE WHEN tm.denominacion LIKE '%Banco%' AND tm.es_entrada=1 THEN dc.monto ELSE 0 END) as banco,
        SUM(CASE WHEN tm.denominacion LIKE '%MercadoPago%' AND tm.es_entrada=1 THEN dc.monto ELSE 0 END) as mp,
        SUM(CASE WHEN tm.denominacion LIKE '%Efectivo%' AND tm.es_entrada=1 THEN dc.monto ELSE 0 END) as efectivo
    FROM detalle_caja dc
    INNER JOIN caja c ON dc.idCaja = c.idCaja
    INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
";
$queryTotales = mysqli_query($MiConexion, $sqlTotales);
$resumen = mysqli_fetch_assoc($queryTotales);

$ingresos = $resumen['total_entradas'] ?? 0;
$gastos = $resumen['total_salidas'] ?? 0;
$ganancia = $ingresos - $gastos;

$valBanco = $resumen['banco'] ?? 0;
$valMP = $resumen['mp'] ?? 0;
$valEfectivo = $resumen['efectivo'] ?? 0;

// 2. CONSULTA DETALLADA (LISTA)
$sqlDetalle = "
    SELECT tm.denominacion, 
           SUM(dc.monto) as subtotal,
           tm.es_entrada
    FROM detalle_caja dc
    INNER JOIN caja c ON dc.idCaja = c.idCaja
    INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
    WHERE MONTH(c.Fecha) = '$mesReporte' AND YEAR(c.Fecha) = '$anioReporte'
    GROUP BY tm.denominacion, tm.es_entrada
    ORDER BY tm.es_entrada DESC, subtotal DESC
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
        body { font-family: 'Helvetica', Arial, sans-serif; color: #333; margin: 0; padding: 30px; }
        
        /* HEADER CON MARGEN PARA EVITAR SOLAPAMIENTO */
        .header { 
            width: 100%; 
            border-bottom: 2px solid #333; 
            padding-bottom: 20px; 
            margin-bottom: 50px; /* Aumentado para separar del contenido */
            overflow: hidden; /* Importante para limpiar los floats */
        }
        .header img { max-width: 180px; float: left; margin-top: 10px; }
        .header-text { text-align: right; float: right; margin-top: 10px; }
        .header-text h1 { margin: 0; font-size: 24px; text-transform: uppercase; color: #444; }
        .header-text p { margin: 5px 0 0; color: #777; font-size: 14px; }
        
        /* SISTEMA DE TARJETAS (Dompdf prefiere tablas a flexbox) */
        .row-cards { width: 100%; border-spacing: 15px 0; margin-bottom: 30px; margin-left: -15px; }
        .card-cell { 
            width: 33.33%; 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            text-align: center; 
            border: 1px solid #e9ecef; 
        }
        
        /* Estilos específicos para medios de pago */
        .card-banco { border-top: 4px solid #0d6efd; } /* Azul */
        .card-mp { border-top: 4px solid #0dcaf0; }    /* Celeste/Cian */
        .card-efectivo { border-top: 4px solid #198754; } /* Verde */

        .card h3 { margin: 0 0 10px; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .card .numero { font-size: 18px; font-weight: bold; color: #333; }
        
        /* Estilos Balance General */
        .balance-section { margin-top: 40px; margin-bottom: 20px; }
        .card-ingreso .numero { color: #198754; }
        .card-egreso .numero { color: #dc3545; }
        .card-neto .numero { color: #333; }
        
        /* Títulos de sección */
        .section-title { font-size: 14px; font-weight: bold; color: #555; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; }

        /* TABLA */
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 10px; }
        table th { background: #eee; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; font-weight: bold; color: #555; }
        table td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .badge { padding: 3px 6px; border-radius: 4px; color: white; font-size: 10px; }
        .bg-in { background-color: #198754; }
        .bg-out { background-color: #dc3545; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <?php
            // Ajusta la ruta a tu logo si es necesario
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
        <div class="header-text">
            <h1>Informe Económico</h1>
            <p>Período: <strong><?php echo $nombreMes . ' ' . $anioReporte; ?></strong></p>
            <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>

    <div class="section-title">Desglose de Ingresos por Medio</div>
    <table class="row-cards">
        <tr>
            <td class="card-cell card-banco">
                <h3>Banco</h3>
                <div class="numero">$ <?php echo number_format($valBanco, 2, ',', '.'); ?></div>
            </td>
            <td class="card-cell card-mp">
                <h3>MercadoPago</h3>
                <div class="numero">$ <?php echo number_format($valMP, 2, ',', '.'); ?></div>
            </td>
            <td class="card-cell card-efectivo">
                <h3>Efectivo</h3>
                <div class="numero">$ <?php echo number_format($valEfectivo, 2, ',', '.'); ?></div>
            </td>
        </tr>
    </table>

    <div class="section-title" style="margin-top: 20px;">Balance General</div>
    <table class="row-cards">
        <tr>
            <td class="card-cell card-ingreso">
                <h3>Ingresos Totales</h3>
                <div class="numero">$ <?php echo number_format($ingresos, 2, ',', '.'); ?></div>
            </td>
            <td class="card-cell card-egreso">
                <h3>Egresos Totales</h3>
                <div class="numero">$ <?php echo number_format($gastos, 2, ',', '.'); ?></div>
            </td>
            <td class="card-cell card-neto" style="background-color: #e8f5e9;">
                <h3>Ganancia Neta</h3>
                <div class="numero" style="color: <?php echo ($ganancia >= 0) ? '#198754' : '#dc3545'; ?>;">
                    $ <?php echo number_format($ganancia, 2, ',', '.'); ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title" style="margin-top: 30px;">Detalle de Movimientos Agrupados</div>
    
    <table>
        <thead>
            <tr>
                <th>Concepto / Tipo de Movimiento</th>
                <th style="width: 100px;">Tipo</th>
                <th class="text-right" style="width: 150px;">Monto Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if(mysqli_num_rows($queryDetalle) > 0) {
                while($row = mysqli_fetch_assoc($queryDetalle)) { 
                    $tipoTxt = ($row['es_entrada'] == 1) ? 'Ingreso' : 'Egreso';
                    $badgeClass = ($row['es_entrada'] == 1) ? 'bg-in' : 'bg-out';
            ?>
            <tr>
                <td><?php echo $row['denominacion']; ?></td>
                <td>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $tipoTxt; ?></span>
                </td>
                <td class="text-right">
                    $ <?php echo number_format($row['subtotal'], 2, ',', '.'); ?>
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

// Opciones para permitir imágenes externas y mejorar renderizado
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnable' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Informe_".$nombreMes."_".$anioReporte.".pdf", array("Attachment" => false));
?>