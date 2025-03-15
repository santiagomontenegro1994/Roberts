<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, no lo deja entrar
    header('Location: cerrarsesion.php');
    exit;
}

// Voy a necesitar la conexión: incluyo la función de Conexión.
require_once 'funciones/conexion.php';

// Genero una variable para usar mi conexión desde donde me haga falta
$MiConexion = ConexionBD();

// Ahora voy a llamar el script con la función que genera mi listado
require_once 'funciones/select_general.php';

// Obtener los datos del pedido y sus detalles si se pasa el ID por GET
$DatosPedidoActual = Datos_Pedidos($MiConexion, $_GET['ID_PEDIDO']);
$DetallesPedido = Detalles_Pedido($MiConexion, $_GET['ID_PEDIDO']);

// Empiezo a guardar el contenido en una variable
ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pedido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header, .footer {
            text-align: center;
            margin: 20px 0;
        }
        .header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .details {
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
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #f5f5f5;
        }
        .status {
            font-weight: bold;
        }
        .status.entregado {
            color: green;
        }
        .status.no-entregado {
            color: red;
        }
        .footer p {
            color: #777;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Comprobante de Pedido</h2>
            <p>Fecha: <span id="fecha"><?php echo $DatosPedidoActual['FECHA'] ?></span></p>
        </div>
        <div class="details">
            <h3>Datos del Cliente</h3>
            <div>Nombre: <span id="nombreCliente"><?php echo $DatosPedidoActual['CLIENTE'] ?>, <?php echo $DatosPedidoActual['CLIENTE_A'] ?></span></div>
            <div>Teléfono: <span id="telefonoCliente"><?php echo $DatosPedidoActual['TELEFONO'] ?></span></div>
        </div>

        <div class="details">
            <h3>Detalles del Pedido</h3>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Editorial</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($DetallesPedido as $detalle) { ?>
                        <tr>
                            <td><?php echo $detalle['LIBRO_T']; ?></td>
                            <td><?php echo $detalle['LIBRO_E']; ?></td>
                            <td>$<?php echo number_format($detalle['PRECIO'], 2); ?></td>
                            <td><?php echo $detalle['CANTIDAD']; ?></td>
                            <td>
                                <?php if ($detalle['ESTADO'] == 4) { ?>
                                    <span class="status entregado">Entregado</span>
                                <?php } else { ?>
                                    <span class="status no-entregado">No Entregado</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="details">
            <h3>Precio</h3>
            <?php
                // Calcula el monto del descuento
                $monto_descuento = ($DatosPedidoActual['PRECIO_TOTAL'] * $DatosPedidoActual['DESCUENTO']) / 100;
                $saldo = ($DatosPedidoActual['PRECIO_TOTAL'] - $monto_descuento) - $DatosPedidoActual['SENIA'];
            ?>
            <div>Precio Total: $<span id="precioTotal"><?php echo number_format($DatosPedidoActual['PRECIO_TOTAL'], 2); ?></span></div>
            <div>Descuento: %<span id="descuento"><?php echo $DatosPedidoActual['DESCUENTO']; ?></span></div>
            <div>Seña: $<span id="sena"><?php echo number_format($DatosPedidoActual['SENIA'], 2); ?></span></div>
            <div>Saldo: $<span id="saldo"><?php echo number_format($saldo, 2); ?></span></div>
        </div>
        <div class="footer">
            <p>Gracias por su compra</p>
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
$dompdf->stream("comprobante_pedido.pdf", array("Attachment" => true));
?>