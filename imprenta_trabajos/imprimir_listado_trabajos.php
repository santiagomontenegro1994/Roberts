<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Obtener los detalles de los trabajos para cada lista
$detallesPendientes = obtenerDetallesTrabajoPorEstados($MiConexion, [1, 2]); // 1: Pendiente, 2: Diseño Empezado
$detallesEnProceso = obtenerDetallesTrabajoPorEstados($MiConexion, [3, 5]); // 3: Muestra Enviada, 5: Enviado
$detallesEnTaller = obtenerDetallesTrabajoPorEstados($MiConexion, [4]); // 4: En Taller
$detallesListos = obtenerDetallesTrabajoPorEstados($MiConexion, [6]); // 6: Listo

// Empiezo a guardar el contenido en una variable
ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Trabajos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .container { max-width: 100%; margin: auto; padding: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin: 20px 0; }
        h3 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
        .table-pendiente { background-color: #f8d7da; }
        .table-proceso { background-color: #fff3cd; }
        .table-taller { background-color: #cce5ff; }
        .table-listo { background-color: #d4edda; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Listado de Trabajos de Imprenta</h2>
            <p>Fecha de Emisión: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <!-- Trabajos Pendientes (Estados 1 y 2) -->
        <div class="details">
            <h3>Trabajos Pendientes</h3>
            <table class="table-pendiente">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Tipo Trabajo</th>
                        <th>Descripción</th>
                        <th>Proveedor</th>
                        <th>Fecha Entrega</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detallesPendientes)): ?>
                        <tr><td colspan="8">No hay trabajos pendientes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($detallesPendientes as $detalle): ?>
                            <tr>
                                <td><?php echo $detalle['idPedidoTrabajos']; ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_cliente'] . ' ' . $detalle['apellido_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['tipo_trabajo']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_proveedor']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($detalle['fechaEntrega'])); ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_estado']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Trabajos en Proceso (Estados 3 y 5) -->
        <div class="details">
            <h3>Trabajos en Proceso (Muestras y Enviados)</h3>
            <table class="table-proceso">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Tipo Trabajo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Fecha Cambio</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detallesEnProceso)): ?>
                        <tr><td colspan="7">No hay trabajos en proceso.</td></tr>
                    <?php else: ?>
                        <?php foreach ($detallesEnProceso as $detalle): ?>
                            <tr>
                                <td><?php echo $detalle['idPedidoTrabajos']; ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_cliente'] . ' ' . $detalle['apellido_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['tipo_trabajo']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_estado']); ?></td>
                                <td><?php echo $detalle['fecha_cambio_estado'] ? date('d/m/Y H:i', strtotime($detalle['fecha_cambio_estado'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($detalle['usuario_cambio_estado'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Trabajos en Taller (Estado 4) -->
        <div class="details">
            <h3>Trabajos en Taller</h3>
            <table class="table-taller">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Tipo Trabajo</th>
                        <th>Descripción</th>
                        <th>Proveedor</th>
                        <th>Fecha Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detallesEnTaller)): ?>
                        <tr><td colspan="6">No hay trabajos en taller.</td></tr>
                    <?php else: ?>
                        <?php foreach ($detallesEnTaller as $detalle): ?>
                            <tr>
                                <td><?php echo $detalle['idPedidoTrabajos']; ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_cliente'] . ' ' . $detalle['apellido_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['tipo_trabajo']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_proveedor']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($detalle['fechaEntrega'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Trabajos Listos (Estado 6) -->
        <div class="details">
            <h3>Trabajos Listos para Entregar</h3>
            <table class="table-listo">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Tipo Trabajo</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                     <?php if (empty($detallesListos)): ?>
                        <tr><td colspan="6">No hay trabajos listos para entregar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($detallesListos as $detalle): ?>
                            <tr>
                                <td><?php echo $detalle['idPedidoTrabajos']; ?></td>
                                <td><?php echo htmlspecialchars($detalle['nombre_cliente'] . ' ' . $detalle['apellido_cliente']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['tipo_trabajo']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                                <td>$<?php echo number_format($detalle['precio'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>

<?php
// Termino de guardar el contenido en una variable
$html = ob_get_clean();

// Incluyo la librería Dompdf
require_once '../libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Configuro Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Cargo el HTML
$dompdf->loadHtml($html);

// Seteo el papel en A4 vertical
$dompdf->setPaper('A4', 'portrait');

// Renderizo el PDF
$dompdf->render();

// Envío el PDF al navegador para descarga
$dompdf->stream("listado_trabajos_imprenta.pdf", array("Attachment" => true));
?>