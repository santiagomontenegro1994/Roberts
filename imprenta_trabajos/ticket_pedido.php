<?php
session_start();

// Configurar zona horaria
date_default_timezone_set('America/Argentina/Cordoba');

if (empty($_SESSION['Usuario_Nombre']) || empty($_GET['id'])) {
    die("Acceso denegado.");
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();
$idPedido = (int)$_GET['id'];

// 1. Obtener datos principales del pedido y del cliente
$queryPedido = "SELECT p.*, c.nombre as cliente_nom, c.apellido as cliente_ape, c.telefono, u.nombre as usuario 
                FROM pedido_trabajos p
                JOIN clientes c ON p.idCliente = c.idCliente
                JOIN usuarios u ON p.idUsuario = u.idUsuario
                WHERE p.idPedidoTrabajos = $idPedido";
$rsPedido = mysqli_query($MiConexion, $queryPedido);
$pedido = mysqli_fetch_assoc($rsPedido);

if (!$pedido) die("No se encontró el pedido.");

// 2. Obtener el detalle de los trabajos (para listarlos en el ticket)
$queryDetalles = "SELECT dt.*, tt.denominacion as tipo_trabajo 
                  FROM detalle_trabajos dt
                  JOIN tipo_trabajo tt ON dt.idTrabajo = tt.idTipoTrabajo
                  WHERE dt.id_pedido_trabajos = $idPedido";
$rsDetalles = mysqli_query($MiConexion, $queryDetalles);

// Cálculos matemáticos de la base de datos
$total = floatval($pedido['precio_total'] ?? 0);
$senia = floatval($pedido['senia'] ?? 0);
$saldo = $total - $senia;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Pedido</title>
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
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .separador { border-top: 1px dashed #000; margin: 5px 0; }
        .ticket-header h2 { margin: 0; font-size: 16px; }
        .ticket-header p { margin: 2px 0; }
        
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 5px; }
        th, td { text-align: left; padding: 2px 0; }
        th.right, td.right { text-align: right; }
        
        .totales-box { margin-top: 10px; font-size: 13px; }
        .saldo-pendiente { font-size: 16px; margin-top: 5px; border: 1px solid #000; padding: 3px; }
    </style>
</head>
<body>

    <div class="ticket-header text-center">
        <h2>GRÁFICA ROBERTS</h2>
        <p>Laprida 25, Villa Allende</p>
        <div class="separador"></div>
        <p>ORDEN DE TRABAJO N° <?php echo str_pad($idPedido, 6, '0', STR_PAD_LEFT); ?></p>
        <p>Fecha: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="separador"></div>

    <p><span class="bold">Cliente:</span> <?php echo htmlspecialchars($pedido['cliente_nom'] . ' ' . $pedido['cliente_ape']); ?></p>
    <p><span class="bold">Teléfono:</span> <?php echo htmlspecialchars($pedido['telefono']); ?></p>

    <div class="separador"></div>
    <p class="bold text-center">DETALLE DEL TRABAJO</p>
    
    <table>
        <thead>
            <tr>
                <th>Trabajo / Descripción</th>
                <th class="right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = mysqli_fetch_assoc($rsDetalles)): ?>
            <tr>
                <td>
                    <span class="bold"><?php echo htmlspecialchars($item['tipo_trabajo']); ?></span><br>
                    <?php echo htmlspecialchars($item['descripcion']); ?>
                </td>
                <td class="right" style="vertical-align: bottom;">
                    $<?php echo number_format($item['precio'], 2, ',', '.'); ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="separador"></div>

    <div class="totales-box">
        <table style="font-size: 13px;">
            <tr>
                <td class="bold">TOTAL PEDIDO:</td>
                <td class="right bold">$<?php echo number_format($total, 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>SEÑA ABONADA:</td>
                <td class="right">-$<?php echo number_format($senia, 2, ',', '.'); ?></td>
            </tr>
        </table>
        
        <div class="text-center saldo-pendiente bold">
            RESTA ABONAR: $<?php echo number_format($saldo, 2, ',', '.'); ?>
        </div>
    </div>

    <div class="separador"></div>

    <div class="text-center">
        <p>Atendido por: <?php echo $pedido['usuario']; ?></p>
        <p>Guarde este comprobante para<br>retirar su trabajo.</p>
        <br><br><br>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>