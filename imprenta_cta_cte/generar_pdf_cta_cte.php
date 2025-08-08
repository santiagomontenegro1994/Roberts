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

// Obtener trabajos pendientes en cuenta corriente
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
            AND dt.idEstadoTrabajo IN (SELECT idEstado FROM estado_trabajo WHERE denominacion NOT LIKE '%PAGADO%')
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

// Logo en base64
$LogoPath = '../imagenes/logo.png';
$LogoBase64 = '';
if (file_exists($LogoPath)) {
    $LogoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($LogoPath));
}

// HTML del PDF
$html = '<html>
<head>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
.header { display: flex; align-items: center; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
.header img { height: 60px; margin-right: 20px; }
.header h1 { font-size: 20px; margin: 0; }
.table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.table th, .table td { border: 1px solid #000; padding: 5px; text-align: left; }
.table th { background-color: #f0f0f0; }
.total { margin-top: 20px; font-weight: bold; font-size: 14px; }
.saldo-box { border: 1px solid #000; padding: 10px; margin-top: 20px; }
.saldo-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
.saldo-title { font-weight: bold; }
.saldo-value { text-align: right; }
.saldo-total { font-size: 14px; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; }
.text-danger { color: #dc3545; }
.text-success { color: #28a745; }
</style>
</head>
<body>

<div class="header">
    <img src="' . $LogoBase64 . '">
    <div>
        <h1>Estado de Cuenta Corriente</h1>
        <p>Fecha de emisión: ' . date("d/m/Y") . '</p>
    </div>
</div>

<p><strong>Cliente:</strong> ' . htmlspecialchars($DatosCliente['nombre'] . ' ' . $DatosCliente['apellido']) . '</p>
<p><strong>Teléfono:</strong> ' . htmlspecialchars($DatosCliente['telefono']) . '</p>

<h3>Trabajos Pendientes</h3>';

if (count($trabajosPendientes) > 0) {
    $html .= '<table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Fecha Pedido</th>
                <th>Fecha Entrega</th>
                <th>Precio</th>
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
            <td>$ ' . number_format($trabajo['precio'], 2, ',', '.') . '</td>
        </tr>';
    }

    $html .= '</tbody>
    </table>';
} else {
    $html .= '<p>No hay trabajos pendientes</p>';
}

// Resumen de saldos
$saldoProyectado = $saldo - $totalPendiente;

$html .= '
<div class="saldo-box">
    <div class="saldo-row">
        <span class="saldo-title">Total trabajos pendientes:</span>
        <span class="saldo-value">$ ' . number_format($totalPendiente, 2, ',', '.') . '</span>
    </div>
    <div class="saldo-row">
        <span class="saldo-title">Saldo en cuenta:</span>
        <span class="saldo-value ' . ($saldo >= 0 ? 'text-success' : 'text-danger') . '">
            $ ' . number_format(abs($saldo), 2, ',', '.') . ' (' . ($saldo >= 0 ? 'A favor' : 'Deudor') . ')
        </span>
    </div>
    <div class="saldo-row saldo-total">
        <span class="saldo-title">Saldo proyectado:</span>
        <span class="saldo-value ' . ($saldoProyectado >= 0 ? 'text-success' : 'text-danger') . '">
            $ ' . number_format(abs($saldoProyectado), 2, ',', '.') . ' (' . ($saldoProyectado >= 0 ? 'A favor' : 'Deudor') . ')
        </span>
    </div>
</div>

</body>
</html>';

// Generar PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Estado_CtaCte_" . $DatosCliente['nombre'] . "_" . $DatosCliente['apellido'] . ".pdf", ["Attachment" => false]);