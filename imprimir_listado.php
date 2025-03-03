<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: cerrarsesion.php');
    exit;
}

require_once 'funciones/conexion.php';
$MiConexion = ConexionBD();

// Función para obtener los detalles de los pedidos según el estado
function obtenerDetallesPorEstado($conexion, $estado) {
    // Consulta para librosleas
    $query_leas = "
        SELECT 
            dp.*, 
            pl.idCliente, 
            pl.fecha, 
            pl.precioTotal, 
            pl.senia, 
            pl.descuento, 
            c.nombre, 
            c.apellido, 
            c.telefono,
            leas.titulo AS titulo_libro,
            prov.nombre AS nombre_proveedor
        FROM detalle_pedido dp
        JOIN pedido_libros pl ON dp.id_pedido_libros = pl.idPedidoLibros
        JOIN clientes c ON pl.idCliente = c.idCliente
        JOIN librosleas leas ON dp.idLibro = leas.idLibros
        JOIN proveedores prov ON dp.idProveedor = prov.idProveedor
        WHERE dp.idEstado = ?
    ";

    // Consulta para librossbs
    $query_sbs = "
        SELECT 
            dp.*, 
            pl.idCliente, 
            pl.fecha, 
            pl.precioTotal, 
            pl.senia, 
            pl.descuento, 
            c.nombre, 
            c.apellido, 
            c.telefono,
            sbs.titulo AS titulo_libro,
            prov.nombre AS nombre_proveedor
        FROM detalle_pedido dp
        JOIN pedido_libros pl ON dp.id_pedido_libros = pl.idPedidoLibros
        JOIN clientes c ON pl.idCliente = c.idCliente
        JOIN librossbs sbs ON dp.idLibro = sbs.idLibros
        JOIN proveedores prov ON dp.idProveedor = prov.idProveedor
        WHERE dp.idEstado = ?
    ";

    // Consulta para libros
    $query_libros = "
        SELECT 
            dp.*, 
            pl.idCliente, 
            pl.fecha, 
            pl.precioTotal, 
            pl.senia, 
            pl.descuento, 
            c.nombre, 
            c.apellido, 
            c.telefono,
            libros.titulo AS titulo_libro,
            prov.nombre AS nombre_proveedor
        FROM detalle_pedido dp
        JOIN pedido_libros pl ON dp.id_pedido_libros = pl.idPedidoLibros
        JOIN clientes c ON pl.idCliente = c.idCliente
        JOIN libros ON dp.idLibro = libros.idLibros
        JOIN proveedores prov ON dp.idProveedor = prov.idProveedor
        WHERE dp.idEstado = ?
    ";

    // Ejecutar las tres consultas y combinar los resultados
    $resultados = [];

    // Consulta para librosleas
    $stmt = $conexion->prepare($query_leas);
    $stmt->bind_param("i", $estado);
    $stmt->execute();
    $resultados = array_merge($resultados, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    // Consulta para librossbs
    $stmt = $conexion->prepare($query_sbs);
    $stmt->bind_param("i", $estado);
    $stmt->execute();
    $resultados = array_merge($resultados, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    // Consulta para libros
    $stmt = $conexion->prepare($query_libros);
    $stmt->bind_param("i", $estado);
    $stmt->execute();
    $resultados = array_merge($resultados, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    return $resultados;
}

// Obtener los detalles de los pedidos para cada estado
$detallesParaPedir = obtenerDetallesPorEstado($MiConexion, 1);
$detallesPedido = obtenerDetallesPorEstado($MiConexion, 2);
$detallesRecibido = obtenerDetallesPorEstado($MiConexion, 3);

// Empiezo a guardar el contenido en una variable
ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Pedidos</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ccc; }
        .header, .footer { text-align: center; margin: 20px 0; }
        .details { margin: 20px 0; }
        .details div { margin: 5px 0; }
        .text-end { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Listado de Pedidos</h2>
        </div>

        <!-- Pedidos para pedir (Estado 1) -->
        <div class="details">
            <h3>Pedidos para Pedir (Estado 1)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Fecha</th>
                        <th>Libro</th>
                        <th>Proveedor</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detallesParaPedir as $detalle) { ?>
                        <tr>
                            <td><?php echo $detalle['nombre'] . ' ' . $detalle['apellido']; ?></td>
                            <td><?php echo $detalle['telefono']; ?></td>
                            <td><?php echo $detalle['fecha']; ?></td>
                            <td><?php echo $detalle['titulo_libro']; ?></td>
                            <td><?php echo $detalle['nombre_proveedor']; ?></td>
                            <td>$<?php echo number_format($detalle['precio_pedido'], 2); ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Pedidos pedidos (Estado 2) -->
        <div class="details">
            <h3>Pedidos Pedidos (Estado 2)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Fecha</th>
                        <th>Libro</th>
                        <th>Proveedor</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detallesPedido as $detalle) { ?>
                        <tr>
                            <td><?php echo $detalle['nombre'] . ' ' . $detalle['apellido']; ?></td>
                            <td><?php echo $detalle['telefono']; ?></td>
                            <td><?php echo $detalle['fecha']; ?></td>
                            <td><?php echo $detalle['titulo_libro']; ?></td>
                            <td><?php echo $detalle['nombre_proveedor']; ?></td>
                            <td>$<?php echo number_format($detalle['precio_pedido'], 2); ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Pedidos recibidos (Estado 3) -->
        <div class="details">
            <h3>Pedidos Recibidos (Estado 3)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Fecha</th>
                        <th>Libro</th>
                        <th>Proveedor</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detallesRecibido as $detalle) { ?>
                        <tr>
                            <td><?php echo $detalle['nombre'] . ' ' . $detalle['apellido']; ?></td>
                            <td><?php echo $detalle['telefono']; ?></td>
                            <td><?php echo $detalle['fecha']; ?></td>
                            <td><?php echo $detalle['titulo_libro']; ?></td>
                            <td><?php echo $detalle['nombre_proveedor']; ?></td>
                            <td>$<?php echo number_format($detalle['precio_pedido'], 2); ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
// Termino de guardar el contenido en una variable
$html = ob_get_clean();

// Creo la variable dompdf
require_once 'libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
$dompdf = new Dompdf();

// Activo las opciones para poder generar el PDF con imágenes
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnable' => true));
$dompdf->setOptions($options);

// Le paso el $html en el que guardamos toda la lista
$dompdf->loadHtml($html);

// Seteo el papel en A4 vertical
$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

// Le indico el nombre del archivo y le doy true para que descargue
$dompdf->stream("listado_pedidos.pdf", array("Attachment" => true));
?>