<?php
session_start();

// Configurar zona horaria local
date_default_timezone_set('America/Argentina/Cordoba');

if (empty($_SESSION['Usuario_Nombre']) || empty($_GET['id'])) {
    die("Acceso denegado.");
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();
$idDetalle = (int)$_GET['id'];

// Obtener datos del movimiento
$query = "SELECT dc.*, tp.denominacion as metodo, tm.denominacion as tipo_movimiento, u.nombre as usuario, u.apellido as usuario_apellido
          FROM detalle_caja dc
          JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
          JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
          JOIN usuarios u ON dc.idUsuario = u.idUsuario
          WHERE dc.idDetalleCaja = $idDetalle";
$rs = mysqli_query($MiConexion, $query);
$retiro = mysqli_fetch_assoc($rs);

if (!$retiro) die("No se encontró el retiro.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Retiro</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 70mm;
            margin: 0 auto;
            padding: 5mm 0;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .separador { border-top: 1px dashed #000; margin: 5px 0; }
        .ticket-header h2 { margin: 0; font-size: 16px; }
        .ticket-header p { margin: 2px 0; }
        .monto-total { font-size: 18px; margin: 10px 0; }
    </style>
</head>
<body>

    <div class="ticket-header text-center">
        <h2>GRÁFICA ROBERTS</h2>
        <p>Rivadavia 31, Villa Allende</p>
        <div class="separador"></div>
        <p>COMPROBANTE DE RETIRO N° <?php echo str_pad($retiro['idDetalleCaja'], 6, '0', STR_PAD_LEFT); ?></p>
        <p>Fecha: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="separador"></div>

    <p><span class="bold">Concepto:</span> <?php echo $retiro['tipo_movimiento']; ?></p>
    <p><span class="bold">Extraído de:</span> <?php echo $retiro['metodo']; ?></p>
    <?php if (!empty($retiro['observaciones'])): ?>
        <p><span class="bold">Detalle:</span> <?php echo htmlspecialchars($retiro['observaciones']); ?></p>
    <?php endif; ?>

    <div class="separador"></div>

    <div class="text-center monto-total bold">
        MONTO: $<?php echo number_format($retiro['monto'], 2, ',', '.'); ?>
    </div>

    <div class="separador"></div>

    <div class="text-center">
        <p>Registrado por: <?php echo $retiro['usuario'] . ' ' . $retiro['usuario_apellido']; ?></p>
        <p>*** COPIA CONTROL ***</p>
        <br><br><br>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>