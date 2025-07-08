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
    'Efectivo' => ['entrada' => 0, 'salida' => 0],
    'Transferencia' => ['entrada' => 0, 'salida' => 0],
    'Tarjeta' => ['entrada' => 0, 'salida' => 0],
    'totalRetiros' => 0
];

while ($fila = $resultadoDetalleCaja->fetch_assoc()) {
    $detalles[] = $fila;
    
    // Calcular totales
    if ($fila['es_entrada']) {
        $totales[$fila['metodoPago']]['entrada'] += $fila['monto'];
    } else {
        $totales[$fila['metodoPago']]['salida'] += $fila['monto'];
        $totales['totalRetiros'] += $fila['monto'];
    }
}

$filaCaja = $resultadoCaja->fetch_assoc();
$cajaInicial = (float)$filaCaja['cajaInicial'];
$totalEfectivo = (float)$totales['Efectivo']['entrada'];
$totalTransferencia = (float)$totales['Transferencia']['entrada'];
$totalTarjeta = (float)$totales['Tarjeta']['entrada'];
$totalRetiros = (float)$totales['totalRetiros'];
$cajaEfectivoActual = $totalEfectivo - $totalRetiros + $cajaInicial;

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
            font-size: 12px;
            margin: 0;
            padding: 15px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 120px;
            max-height: 60px;
        }
        .header-info {
            text-align: right;
        }
        .header-info h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        table th {
            background: #f5f5f5;
            padding: 6px;
            border: 1px solid #ddd;
            font-weight: bold;
            text-align: left;
        }
        table td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .entrada {
            color: #28a745;
        }
        .salida {
            color: #dc3545;
        }
        .totals-container {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            border: 1px solid #ddd;
            padding: 8px;
            background: #f8f9fa;
        }
        .total-item {
            flex: 1;
            text-align: center;
            padding: 0 5px;
        }
        .total-item:not(:last-child) {
            border-right: 1px solid #eee;
        }
        .total-label {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .total-value {
            font-size: 12px;
        }
        .final-total {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            font-size: 14px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php
            $ruta_imagen = '../assets/img/logo.png';
            if (file_exists($ruta_imagen)) {
                $tipo_imagen = pathinfo($ruta_imagen, PATHINFO_EXTENSION);
                $datos_imagen = file_get_contents($ruta_imagen);
                $base64_imagen = 'data:image/' . $tipo_imagen . ';base64,' . base64_encode($datos_imagen);
                echo '<img class="logo" src="'.$base64_imagen.'" alt="Logo">';
            }
            ?>
            <div class="header-info">
                <h2>Planilla de Caja N° <?php echo $idCaja; ?></h2>
                <p>Fecha: <?php echo date('d/m/Y', strtotime($filaCaja['Fecha'])); ?></p>
                <p>Caja Inicial: $<?php echo number_format($cajaInicial, 2); ?></p>
            </div>
        </div>

        <table>
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
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td class="<?php echo $fila['es_entrada'] ? 'entrada' : 'salida'; ?>">
                            <?php echo $fila['es_entrada'] ? 'Entrada' : 'Salida'; ?>
                        </td>
                        <td><?php echo $fila['metodoPago']; ?></td>
                        <td><?php echo $fila['detalle']; ?></td>
                        <td><?php echo $fila['usuario']; ?></td>
                        <td class="text-right <?php echo $fila['es_entrada'] ? 'entrada' : 'salida'; ?>">
                            $<?php echo number_format($fila['monto'], 2); ?>
                        </td>
                        <td><?php echo $fila['observaciones']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- SECCIÓN DE TOTALES EN HORIZONTAL -->
        <div class="totals-container">
            <div class="total-item">
                <div class="total-label">Ing. Efectivo</div>
                <div class="total-value">$<?php echo number_format($totalEfectivo, 2); ?></div>
            </div>
            <div class="total-item">
                <div class="total-label">Ing. Transferencia</div>
                <div class="total-value">$<?php echo number_format($totalTransferencia, 2); ?></div>
            </div>
            <div class="total-item">
                <div class="total-label">Ing. Tarjeta</div>
                <div class="total-value">$<?php echo number_format($totalTarjeta, 2); ?></div>
            </div>
            <div class="total-item">
                <div class="total-label">Retiros</div>
                <div class="total-value">$<?php echo number_format($totalRetiros, 2); ?></div>
            </div>
        </div>

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

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("planilla_caja_".$idCaja.".pdf", array("Attachment" => true));
?>