<?php
session_start();
if (empty($_SESSION['Usuario_Nombre']) || empty($_GET['id'])) {
    die("Acceso denegado.");
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();
$idDetalle = (int)$_GET['id'];

// Obtener datos del movimiento
$query = "SELECT dc.*, tp.denominacion as metodo, tm.denominacion as tipo_movimiento, u.nombre as usuario
          FROM detalle_caja dc
          JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
          JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
          JOIN usuarios u ON dc.idUsuario = u.idUsuario
          WHERE dc.idDetalleCaja = $idDetalle";
$rs = mysqli_query($MiConexion, $query);
$venta = mysqli_fetch_assoc($rs);

if (!$venta) die("No se encontró la venta.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Venta</title>
    <style>
        /* Estilos específicos para Impresora Térmica (58mm u 80mm) */
        @page { margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 70mm; /* Cambiar a 50mm si tu impresora es de 58mm, 70mm es para 80mm */
            margin: 0 auto;
            padding: 5mm 0;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
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
        <p>Tel: 351 3525107</p>
        <div class="separador"></div>
        <p>TICKET DE VENTA N° <?php echo str_pad($venta['idDetalleCaja'], 6, '0', STR_PAD_LEFT); ?></p>
        <p>Fecha: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="separador"></div>

    <p><span class="bold">Concepto:</span> <?php echo $venta['tipo_movimiento']; ?></p>
    <p><span class="bold">Medio Pago:</span> <?php echo $venta['metodo']; ?></p>
    <?php if (!empty($venta['observaciones'])): ?>
        <p><span class="bold">Obs:</span> <?php echo htmlspecialchars($venta['observaciones']); ?></p>
    <?php endif; ?>

    <div class="separador"></div>

    <div class="text-center monto-total bold">
        TOTAL: $<?php echo number_format($venta['monto'], 2, ',', '.'); ?>
    </div>

    <div class="separador"></div>

    <div class="text-center">
        <p>Atendido por: <?php echo $venta['usuario']; ?></p>
        <p>¡Gracias por su compra!</p>
        <br><br><br> </div>

    <script>
        // En cuanto el ticket cargue, le ordenamos imprimir
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>