<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';
require_once '../libreria/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$MiConexion = ConexionBD();
$tipo = $_GET['tipo'] ?? 'todos';

switch ($tipo) {
    case 'pendientes':
        $titulo = "Trabajos Pendientes";
        $ListadoPedidos = Listar_Pedidos_Trabajo_Pendientes($MiConexion);
        break;
    case 'listos':
        $titulo = "Trabajos Listos para Entrega";
        $ListadoPedidos = Listar_Pedidos_Trabajo_Con_Detalle_Estado($MiConexion, 6);
        break;
    case 'impresos':
        $titulo = "Trabajos Impresos/En Proceso";
        $ListadoPedidos = Listar_Pedidos_Trabajo_Con_Detalle_Estado_Proveedor(
            $MiConexion, 
            [4, 5],
            [8, 10, 11, 12, 13, 14, 16]
        );
        break;
    default:
        $titulo = "Todos los Trabajos";
        $ListadoPedidos = Listar_Pedidos_Trabajos_Detallado_Completo($MiConexion);
        break;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0; font-size: 14px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        table th, table td { padding: 6px; text-align: left; border: 1px solid #ddd; }
        table th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 15px; font-size: 11px; text-align: center; color: #666; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p>Generado el <?= date('d/m/Y H:i') ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Fecha Pedido</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Trabajo</th>
                <th>Descripción</th>
                <th>Proveedor</th>
                <th>Estado</th>
                <th class="nowrap">Entrega Prometida</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ListadoPedidos as $pedido): 
                $pedidoId = $pedido['ID'] ?? 'N/A';
                $fechaPedido = $pedido['FECHA'] ?? 'No especificada';
                $clienteNombre = htmlspecialchars(($pedido['CLIENTE_N'] ?? '') . ' ' . ($pedido['CLIENTE_A'] ?? ''));
                $telefono = htmlspecialchars($pedido['TELEFONO'] ?? '');
                
                if (!empty($pedido['TRABAJOS'])) {
                    foreach ($pedido['TRABAJOS'] as $trabajo): 
                        $trabajoNombre = htmlspecialchars($trabajo['DENOMINACION'] ?? 'No especificado');
                        $descripcion = htmlspecialchars($trabajo['DESCRIPCION'] ?? '');
                        $proveedor = htmlspecialchars($trabajo['PROVEEDOR'] ?? 'No asignado');
                        $estado = htmlspecialchars($trabajo['ESTADO'] ?? 'No especificado');
                        $fechaHoraEntrega = htmlspecialchars($trabajo['FECHA_ENTREGA'] ?? 'No especificada');
            ?>
                    <tr>
                        <td><?= $pedidoId ?></td>
                        <td><?= $fechaPedido ?></td>
                        <td><?= $clienteNombre ?></td>
                        <td><?= $telefono ?></td>
                        <td><?= $trabajoNombre ?></td>
                        <td><?= $descripcion ?></td>
                        <td><?= $proveedor ?></td>
                        <td><?= $estado ?></td>
                        <td class="nowrap"><?= $fechaHoraEntrega ?></td>
                    </tr>
            <?php endforeach; 
                } else { ?>
                <tr>
                    <td><?= $pedidoId ?></td>
                    <td><?= $fechaPedido ?></td>
                    <td><?= $clienteNombre ?></td>
                    <td><?= $telefono ?></td>
                    <td colspan="6">Sin trabajos registrados</td>
                </tr>
            <?php } ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Imprenta Roberts - Laprida 25, Villa Allende - Tel: 351 3525107
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
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream("informe_".strtolower(str_replace(' ', '_', $titulo))."_".date('Ymd').".pdf", ["Attachment" => true]);
?>