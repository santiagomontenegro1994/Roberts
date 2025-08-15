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

// Configuramos cada caso por separado
switch ($tipo) {
    case 'pendientes':
        $titulo = "Trabajos Pendientes";
        $ListadoPedidos = Listar_Pedidos_Trabajo_Pendientes($MiConexion);
        break;
        
    case 'listos':
        $titulo = "Trabajos Listos para Entrega";
        $ListadoPedidos = Listar_Pedidos_Listos_Entregar($MiConexion, 6);
        break;
        
    case 'impresos':
        $titulo = "Trabajos en Taller";
        $ListadoPedidos = Listar_Trabajos_En_Taller($MiConexion);
        break;  
    default:
        break;
}

ob_start();

// Cargamos el logo solo una vez
$ruta_imagen = '../assets/img/logo.png';
$logo_base64 = '';
if (file_exists($ruta_imagen)) {
    $tipo_imagen = pathinfo($ruta_imagen, PATHINFO_EXTENSION);
    $datos_imagen = file_get_contents($ruta_imagen);
    $logo_base64 = 'data:image/' . $tipo_imagen . ';base64,' . base64_encode($datos_imagen);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 15px;
            background: #f9f9f9;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }
        .header { 
            width: 100%; 
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        .header img {
            max-width: 150px;
            margin-right: 20px;
        }
        .header-text {
            flex: 1;
        }
        .header h1 { 
            margin: 0; 
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        .header p { 
            margin: 8px 0; 
            font-size: 16px; 
            color: #555;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            font-size: 14px;
            line-height: 1.5;
        }
        table th { 
            padding: 10px 8px; 
            text-align: left; 
            border: 1px solid #ddd;
            background-color: #f2f2f2; 
            font-weight: bold;
            font-size: 15px;
            color: #333;
        }
        table td { 
            padding: 10px 8px; 
            text-align: left; 
            border: 1px solid #ddd;
            vertical-align: top;
        }
        table tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        .text-right { 
            text-align: right; 
        }
        .footer { 
            margin-top: 25px; 
            font-size: 13px; 
            text-align: center; 
            color: #555;
        }
        .nowrap { 
            white-space: nowrap; 
        }
        .descripcion {
            min-width: 200px;
            max-width: 300px;
            word-wrap: break-word;
        }
        .contact-info {
            margin-top: 15px;
            font-size: 12px;
        }
        .thank-you {
            font-style: italic;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (!empty($logo_base64)): ?>
                <img src="<?= $logo_base64 ?>" alt="Logo Roberts">
            <?php endif; ?>
            <div class="header-text">
                <h1><?= htmlspecialchars($titulo) ?></h1>
                <p>Generado el <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
        
        <?php if ($tipo == 'impresos'): ?>
            <!-- Vista específica para taller -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Trabajo</th>
                        <th class="descripcion">Descripción</th>
                        <th class="nowrap">Entrega Prometida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ListadoPedidos)): ?>
                        <?php foreach ($ListadoPedidos as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['ID'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['CLIENTE'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['TRABAJO'] ?? '') ?></td>
                                <td class="descripcion"><?= htmlspecialchars($item['DESCRIPCION'] ?? '') ?></td>
                                <td class="nowrap"><?= htmlspecialchars($item['ENTREGA_PROMETIDA'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 15px;">
                                No se encontraron trabajos en taller
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
        <?php elseif ($tipo == 'pendientes' || $tipo == 'listos'): ?>
            <!-- Vista para pendientes y listos (misma estructura) -->
            <table>
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Fecha Pedido</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Trabajo</th>
                        <th class="descripcion">Descripción</th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                        <th class="nowrap">Entrega Prometida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ListadoPedidos)): ?>
                        <?php foreach ($ListadoPedidos as $pedido): ?>
                            <?php if (!empty($pedido['TRABAJOS'])): ?>
                                <?php foreach ($pedido['TRABAJOS'] as $trabajo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pedido['ID'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($pedido['FECHA'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($pedido['CLIENTE_N'] ?? '') ?> <?= htmlspecialchars($pedido['CLIENTE_A'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($pedido['TELEFONO'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($trabajo['DENOMINACION'] ?? '') ?></td>
                                        <td class="descripcion"><?= htmlspecialchars($trabajo['DESCRIPCION'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($trabajo['PROVEEDOR'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($trabajo['ESTADO'] ?? '') ?></td>
                                        <td class="nowrap"><?= htmlspecialchars($trabajo['FECHA_ENTREGA'] ?? '') ?> <?= htmlspecialchars($trabajo['HORA_ENTREGA'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><?= htmlspecialchars($pedido['ID'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($pedido['FECHA'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($pedido['CLIENTE_N'] ?? '') ?> <?= htmlspecialchars($pedido['CLIENTE_A'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($pedido['TELEFONO'] ?? '') ?></td>
                                    <td colspan="5">Sin trabajos registrados</td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 15px;">
                                No se encontraron trabajos <?= $tipo == 'pendientes' ? 'pendientes' : 'listos para entregar' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
        <?php endif; ?>
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