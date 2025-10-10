<?php
session_start();

// Configurar zona horaria Argentina
date_default_timezone_set('America/Argentina/Cordoba');

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();
$idCaja = (int)$_GET['idCaja'];

// Obtener datos básicos de la caja
$queryCaja = "SELECT c.idCaja, c.Fecha, c.cajaInicial
              FROM caja c
              WHERE c.idCaja = ?";
$stmtCaja = $MiConexion->prepare($queryCaja);
$stmtCaja->bind_param("i", $idCaja);
$stmtCaja->execute();
$resultadoCaja = $stmtCaja->get_result();

if ($resultadoCaja->num_rows === 0) {
    die('No se encontró la caja seleccionada');
}

// Obtener detalles completos de movimientos
$queryDetalles = "SELECT dc.*, 
                  tp.denominacion AS metodoPago, 
                  tm.denominacion AS tipoMovimiento, 
                  tm.es_entrada,
                  u.nombre AS usuario,
                  tm.denominacion AS detalle
                  FROM detalle_caja dc
                  JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                  JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                  JOIN usuarios u ON dc.idUsuario = u.idUsuario
                  WHERE dc.idCaja = ?
                  ORDER BY dc.idDetalleCaja";
$stmtDetalles = $MiConexion->prepare($queryDetalles);
$stmtDetalles->bind_param("i", $idCaja);
$stmtDetalles->execute();
$resultadoDetalleCaja = $stmtDetalles->get_result();

$detalles = [];
$totales = [
    'totalRetiros' => 0,
    'totalRetirosCajaFuerte' => 0
];
$metodosUsados = [];

while ($fila = $resultadoDetalleCaja->fetch_assoc()) {
    $detalles[] = $fila;

    $metodo = $fila['metodoPago'];
    if (!isset($totales[$metodo])) {
        $totales[$metodo] = ['entrada' => 0, 'salida' => 0];
    }

    if ($fila['es_entrada']) {
        $totales[$metodo]['entrada'] += $fila['monto'];
    } else {
        if (strpos($fila['tipoMovimiento'], 'Caja Fuerte') !== false) {
            $totales['totalRetirosCajaFuerte'] += $fila['monto'];
        } else {
            $totales['totalRetiros'] += $fila['monto'];
        }
    }

    if (!in_array($metodo, $metodosUsados)) {
        $metodosUsados[] = $metodo;
    }
}

// Ordenar alfabéticamente los métodos de pago usados
sort($metodosUsados);

$filaCaja = $resultadoCaja->fetch_assoc();
$cajaInicial = (float)$filaCaja['cajaInicial'];

$totalEfectivo = isset($totales['Efectivo']['entrada']) ? (float)$totales['Efectivo']['entrada'] : 0;
$totalRetiros = (float)$totales['totalRetiros'];
$totalRetirosCajaFuerte = (float)$totales['totalRetirosCajaFuerte'];
$cajaEfectivoActual = $totalEfectivo - $totalRetiros - $totalRetirosCajaFuerte + $cajaInicial;

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla de Caja N° <?php echo $idCaja; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 2mm;
        }
        .container {
            width: 100%;
            max-width: 100%;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 5mm;
            border-bottom: 0.5pt solid #ddd;
            padding-bottom: 2mm;
        }
        .logo-cell {
            display: table-cell;
            width: 20%;
        }
        .header-info {
            display: table-cell;
            width: 80%;
            text-align: right;
            vertical-align: top;
        }
        .logo {
            max-width: 30mm;
            max-height: 15mm;
        }
        .header-info h2 {
            margin: 0 0 1mm 0;
            font-size: 12pt;
        }
        .header-info p {
            margin: 1mm 0;
            font-size: 9pt;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
            font-size: 8pt;
        }
        table.data th {
            background: #f5f5f5;
            padding: 1.5mm;
            border: 0.5pt solid #ddd;
            font-weight: bold;
            text-align: left;
        }
        table.data td {
            padding: 1.5mm;
            border: 0.5pt solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .entrada {
            color: #28a745;
        }
        .salida {
            background-color: #dc3545;
            color: white !important;
        }
        table.totals {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 8pt;
        }
        table.totals td {
            padding: 2mm 1mm;
            border: 0.5pt solid #ddd;
            text-align: center;
            background: #f8f9fa;
        }
        .total-label {
            font-weight: bold;
            font-size: 8pt;
        }
        .total-amount {
            font-weight: bold;
            font-size: 9pt;
        }
        .final-total {
            text-align: center;
            margin: 3mm 0;
            padding: 2mm;
            background: #f8f9fa;
            border: 0.5pt solid #ddd;
            font-size: 10pt;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 7pt;
            color: #777;
            border-top: 0.5pt solid #ddd;
            padding-top: 2mm;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-cell">
                <?php
                $ruta_imagen = '../assets/img/logo.png';
                if (file_exists($ruta_imagen)) {
                    $tipo_imagen = pathinfo($ruta_imagen, PATHINFO_EXTENSION);
                    $datos_imagen = file_get_contents($ruta_imagen);
                    $base64_imagen = 'data:image/' . $tipo_imagen . ';base64,' . base64_encode($datos_imagen);
                    echo '<img class="logo" src="'.$base64_imagen.'" alt="Logo">';
                }
                ?>
            </div>
            <div class="header-info">
                <h2>Planilla de Caja N° <?php echo $idCaja; ?></h2>
                <p>Fecha: <?php echo date('d/m/Y', strtotime($filaCaja['Fecha'])); ?></p>
                <p>Caja Inicial: $<?php echo number_format($cajaInicial, 2); ?></p>
            </div>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th width="5%">N°</th>
                    <th width="10%">Tipo</th>
                    <th width="15%">Método</th>
                    <th width="25%">Detalle</th>
                    <th width="15%">Usuario</th>
                    <th width="10%">Monto</th>
                    <th width="20%">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $index => $fila): ?>
                    <tr class="<?php echo $fila['es_entrada'] ? '' : 'salida'; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $fila['es_entrada'] ? 'Entrada' : 'Salida'; ?></td>
                        <td><?php echo $fila['metodoPago']; ?></td>
                        <td><?php echo $fila['detalle']; ?></td>
                        <td><?php echo $fila['usuario']; ?></td>
                        <td class="text-right">$<?php echo number_format($fila['monto'], 2); ?></td>
                        <td><?php echo $fila['observaciones']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- TABLA DE TOTALES DINÁMICA -->
        <table class="totals">
            <tr>
                <?php foreach ($metodosUsados as $metodo): ?>
                    <td>
                        <div class="total-label"><?php echo htmlspecialchars($metodo); ?></div>
                        <div class="total-amount">$<?php echo number_format($totales[$metodo]['entrada'], 2); ?></div>
                    </td>
                <?php endforeach; ?>
                <td>
                    <div class="total-label">Retiros</div>
                    <div class="total-amount">$<?php echo number_format($totalRetiros, 2); ?></div>
                </td>
                <td>
                    <div class="total-label">Caja Fuerte</div>
                    <div class="total-amount">$<?php echo number_format($totalRetirosCajaFuerte, 2); ?></div>
                </td>
            </tr>
        </table>

        <div class="final-total">
            Efectivo Total en Caja: $<?php echo number_format($cajaEfectivoActual, 2); ?>
        </div>

        <div class="footer">
            <p>Impreso el <?php echo date('d/m/Y H:i'); ?> por <?php echo $_SESSION['Usuario_Nombre']; ?></p>
            <p>Imprenta Roberts - Laprida 25, Villa Allende - Tel: 351 3525107</p>
        </div>
    </div>
</body>
</html>

<?php
$html = ob_get_clean();

require_once '../libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
$dompdf = new Dompdf();

$options = $dompdf->getOptions();
$options->set(array('isRemoteEnable' => true));
$dompdf->setOptions($options);

$dompdf->set_option('margin_top', '5mm');
$dompdf->set_option('margin_right', '5mm');
$dompdf->set_option('margin_bottom', '5mm');
$dompdf->set_option('margin_left', '5mm');

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("planilla_caja_".$idCaja.".pdf", array("Attachment" => true));
?>
