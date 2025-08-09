<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';
require_once '../libreria/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Validar ID cliente
$idCliente = isset($_GET['idCliente']) ? intval($_GET['idCliente']) : 0;
if ($idCliente <= 0) {
    die("Cliente inválido");
}

$MiConexion = ConexionBD();
if (!$MiConexion) {
    die("Error de conexión a la base de datos");
}

// Obtener datos del cliente
$SQLCliente = "SELECT idCliente, nombre, apellido, telefono FROM clientes WHERE idCliente = ?";
$stmtCliente = $MiConexion->prepare($SQLCliente);
$stmtCliente->bind_param("i", $idCliente);
$stmtCliente->execute();
$resultCliente = $stmtCliente->get_result();
$DatosCliente = $resultCliente->fetch_assoc();
$stmtCliente->close();

if (!$DatosCliente) {
    die("No se encontró el cliente");
}

// Obtener saldo actual de la cuenta corriente
$SQLSaldo = "SELECT saldo FROM saldos_clientes WHERE idCliente = ?";
$stmtSaldo = $MiConexion->prepare($SQLSaldo);
$stmtSaldo->bind_param("i", $idCliente);
$stmtSaldo->execute();
$resultSaldo = $stmtSaldo->get_result();
$SaldoCliente = $resultSaldo->fetch_assoc();
$saldo = $SaldoCliente ? floatval($SaldoCliente['saldo']) : 0;
$stmtSaldo->close();

// Obtener trabajos pendientes en cuenta corriente (SOLO idEstado = 8)
$SQLTrabajos = "SELECT 
                dt.idDetalleTrabajo,
                dt.descripcion,
                dt.precio,
                dt.fechaEntrega,
                tt.denominacion AS tipoTrabajo,
                pt.fecha AS fechaPedido
            FROM detalle_trabajos dt
            JOIN pedido_trabajos pt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos
            JOIN tipo_trabajo tt ON dt.idTrabajo = tt.idTipoTrabajo
            WHERE pt.idCliente = ?
            AND dt.idActivo = 1
            AND dt.idEstadoTrabajo = 8
            ORDER BY dt.fechaEntrega ASC";

$stmtTrabajos = $MiConexion->prepare($SQLTrabajos);
$stmtTrabajos->bind_param("i", $idCliente);
$stmtTrabajos->execute();
$resultTrabajos = $stmtTrabajos->get_result();

$trabajosPendientes = [];
$totalPendiente = 0;
while ($row = $resultTrabajos->fetch_assoc()) {
    $trabajosPendientes[] = $row;
    $totalPendiente += floatval($row['precio']);
}
$stmtTrabajos->close();

// Preparar el logo con base64 embebido
$ruta_imagen = __DIR__ . '/../assets/img/logo.png'; // Ajustá la ruta según donde esté el logo
if (file_exists($ruta_imagen)) {
    $mime_type = mime_content_type($ruta_imagen);
    $datos_imagen = file_get_contents($ruta_imagen);
    $base64_imagen = 'data:' . $mime_type . ';base64,' . base64_encode($datos_imagen);
    $logo_html = '<img src="' . $base64_imagen . '" alt="Logo de la empresa" style="max-width: 150px; height: auto;">';
} else {
    $logo_html = '<p><strong>Logo no encontrado</strong></p>';
}

// HTML del PDF
$html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta Corriente</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f9f9f9; 
            margin: 0; 
            padding: 0; 
        }
        .container { 
            max-width: 800px; 
            margin: 20px auto; 
            padding: 20px; 
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
        }
        .header { 
            width: 100%; 
            margin-bottom: 20px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-text { 
            text-align: right; 
        }
        .header-text h2 { 
            color: #333; 
            margin: 0; 
            font-size: 24px; 
        }
        .header-text p { 
            color: #777; 
            margin: 5px 0 0; 
            font-size: 14px; 
        }
        .details { 
            clear: both; 
            margin: 20px 0; 
        }
        .details h3 { 
            color: #555; 
            border-bottom: 2px solid #ddd; 
            padding-bottom: 5px; 
        }
        .details div { 
            margin: 10px 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            font-size: 14px; 
        }
        table th, table td { 
            padding: 8px; 
            text-align: left; 
            border: 1px solid #ddd; 
        }
        table th { 
            background: #f8f9fa; 
            font-weight: bold; 
            color: #333; 
        }
        table tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        .text-right { 
            text-align: right; 
        }
        .text-danger { 
            color: #dc3545; 
        }
        .text-success { 
            color: #28a745; 
        }
        .saldo-box { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-top: 20px; 
            border-radius: 5px;
            background: #f8f9fa;
        }
        .saldo-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .saldo-row:last-child {
            border-bottom: none;
        }
        .saldo-title { 
            font-weight: bold; 
            color: #555;
        }
        .saldo-value { 
            text-align: right; 
            font-weight: bold;
        }
        .saldo-total { 
            font-size: 16px; 
            border-top: 2px solid #ddd; 
            padding-top: 10px; 
            margin-top: 10px; 
        }
        .footer { 
            margin-top: 30px;
            text-align: center;
            color: #777; 
            font-style: italic; 
        }
        .footer p {
            margin: 5px 0;
        }
        .thank-you {
            margin-top: 15px;
            font-weight: bold;
            font-size: 16px;
        }
        .contact-info {
            margin-bottom: 15px;
        }
        .contact-info p {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            ' . $logo_html . '
            <div class="header-text">
                <h2>Estado de Cuenta Corriente</h2>
                <p>Fecha: ' . date("d/m/Y") . '</p>
            </div>
        </div>
        
        <div class="details">
            <h3>Datos del Cliente</h3>
            <div><strong>Nombre:</strong> ' . htmlspecialchars($DatosCliente['nombre'] . ' ' . $DatosCliente['apellido']) . '</div>
            <div><strong>Teléfono:</strong> ' . htmlspecialchars($DatosCliente['telefono']) . '</div>
        </div>
        
        <div class="details">
            <h3>Trabajos en Cuenta Corriente</h3>';

if (count($trabajosPendientes) > 0) {
    $html .= '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Fecha Pedido</th>
                <th>Fecha Entrega</th>
                <th class="text-right">Precio</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($trabajosPendientes as $trabajo) {
        $html .= '<tr>
            <td>' . htmlspecialchars($trabajo['idDetalleTrabajo']) . '</td>
            <td>' . htmlspecialchars($trabajo['tipoTrabajo']) . '</td>
            <td>' . htmlspecialchars(substr($trabajo['descripcion'], 0, 50)) . (strlen($trabajo['descripcion']) > 50 ? '...' : '') . '</td>
            <td>' . date("d/m/Y", strtotime($trabajo['fechaPedido'])) . '</td>
            <td>' . date("d/m/Y", strtotime($trabajo['fechaEntrega'])) . '</td>
            <td class="text-right">$' . number_format($trabajo['precio'], 2, ',', '.') . '</td>
        </tr>';
    }

    $html .= '</tbody>
    </table>';
} else {
    $html .= '<p>No hay trabajos en cuenta corriente</p>';
}

// Resumen de saldos
$saldoProyectado = $saldo - $totalPendiente;

$html .= '
        <div class="saldo-box">
            <div class="saldo-row">
                <span class="saldo-title">Total trabajos en CC:</span>
                <span class="saldo-value">$' . number_format($totalPendiente, 2, ',', '.') . '</span>
            </div>
            <div class="saldo-row">
                <span class="saldo-title">Saldo en cuenta:</span>
                <span class="saldo-value ' . ($saldo >= 0 ? 'text-success' : 'text-danger') . '">
                    $' . number_format(abs($saldo), 2, ',', '.') . ' (' . ($saldo >= 0 ? 'A favor' : 'Deudor') . ')
                </span>
            </div>
            <div class="saldo-row saldo-total">
                <span class="saldo-title">Saldo proyectado:</span>
                <span class="saldo-value ' . ($saldoProyectado >= 0 ? 'text-success' : 'text-danger') . '">
                    $' . number_format(abs($saldoProyectado), 2, ',', '.') . ' (' . ($saldoProyectado >= 0 ? 'A favor' : 'Deudor') . ')
                </span>
            </div>
        </div>
        
        <div class="footer">
            <div class="contact-info">
                <p><strong>Contactos:</strong></p>
                <p>Email: imprentaroberts@gmail.com</p>
                <p>WhatsApp: 351 3525107</p>
                <p>Laprida 25 - Villa Allende</p>
            </div>
            <p class="thank-you">Gracias por confiar en nosotros</p>
        </div>
    </div>
</body>
</html>';

// Generar PDF
$dompdf = new Dompdf();

// Activar opciones para imágenes remotas (aunque acá no haría falta por base64, no molesta)
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nombre del archivo con nombre del cliente
$nombreArchivo = "Estado_CtaCte_" . str_replace(' ', '_', $DatosCliente['nombre'] . '_' . $DatosCliente['apellido']) . ".pdf";

// Descargar PDF
$dompdf->stream($nombreArchivo, array("Attachment" => true));
