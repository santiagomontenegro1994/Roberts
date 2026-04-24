<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, no lo deja entrar
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Voy a necesitar la conexión: incluyo la función de Conexión.
require_once '../funciones/conexion.php';

// Genero una variable para usar mi conexión desde donde me haga falta
$MiConexion = ConexionBD();

// Ahora voy a llamar el script con la función que genera mi listado
require_once '../funciones/imprenta.php';

// --- ADAPTAR ESTAS FUNCIONES SEGÚN TU LÓGICA ---
$DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $_GET['ID_PEDIDO']);
$DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $_GET['ID_PEDIDO']);
// ------------------------------------------------

// Empiezo a guardar el contenido en una variable
ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Trabajo N°</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px; }
        .header { width: 100%; margin-bottom: 20px; }
        .header img { float: left; max-width: 150px; }
        .header-text { float: right; text-align: right; width: 70%; }
        .header-text h2 { color: #333; margin: 0; font-size: 24px; }
        .header-text p { color: #777; margin: 5px 0 0; font-size: 14px; }
        .details { clear: both; margin: 20px 0; }
        .details h3 { color: #555; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        .details div { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; color: #333; }
        table tr:nth-child(even) { background: #f9f9f9; }
        .text-right { text-align: right; }
        .footer p { color: #777; font-style: italic; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Logo a la izquierda -->
            <?php
            $ruta_imagen = '../assets/img/logo.png';
            $tipo_imagen = pathinfo($ruta_imagen, PATHINFO_EXTENSION);
            $datos_imagen = file_get_contents($ruta_imagen);
            $base64_imagen = 'data:image/' . $tipo_imagen . ';base64,' . base64_encode($datos_imagen);
            ?>
            <img src="<?php echo $base64_imagen; ?>" alt="Logo de la empresa">

            <!-- Texto a la derecha -->
            <div class="header-text">
                <h2>Pedido de Trabajo N° <?php echo $_GET['ID_PEDIDO']; ?> </h2>
                <p>Fecha: <span><?php echo $DatosPedidoActual['FECHA']; ?></span></p>
            </div>
        </div>
        <div class="details">
            <h3>Datos del Cliente</h3>
            <div>Nombre: <span><?php echo $DatosPedidoActual['CLIENTE']; ?>, <?php echo $DatosPedidoActual['CLIENTE_A']; ?></span></div>
            <div>Teléfono: <span><?php echo $DatosPedidoActual['TELEFONO']; ?></span></div>
        </div>
        <div class="details">
            <h3>Detalles del Pedido</h3>
            <table>
                <thead>
                    <tr>
                        <th>Trabajo</th>
                        <th>Detalles</th>
                        <th>Fecha Entrega</th>
                        <th>Hora Entrega</th>
                        <th class="text-right">Precio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($DetallesPedido as $detalle) { ?>
                        <tr>
                            <td><?php echo $detalle['TRABAJO']; ?></td>
                            <td><?php echo $detalle['DESCRIPCION']; ?></td>
                            <td><?php echo $detalle['FECHA_ENTREGA']; ?></td>
                            <td><?php echo $detalle['HORA_ENTREGA']; ?></td>
                            <td class="text-right">$<?php echo number_format($detalle['PRECIO'], 2); ?></td>
                            <td><?php echo $detalle['ESTADO']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="details">
            <h3>Precio</h3>
            <?php
                $saldo = $DatosPedidoActual['PRECIO_TOTAL'] - $DatosPedidoActual['SENIA'];
            ?>
            <div>Precio Total: $<span><?php echo number_format($DatosPedidoActual['PRECIO_TOTAL'], 2); ?></span></div>
            <div>Seña: $<span><?php echo number_format($DatosPedidoActual['SENIA'], 2); ?></span></div>
            <div>Saldo: $<span><?php echo number_format($saldo, 2); ?></span></div>
        </div>
        <div class="footer">
            <div class="contact-info">
                <p><strong>Contactos:</strong></p>
                <p>Email: imprentaroberts@gmail.com</p>
                <p>WhatsApp: 351 3525107</p>
                <p>Rivadavia 31 - Villa Allende</p>
            </div>
            <p class="thank-you">Gracias por su pedido</p>
        </div>
    </div>
</body>
</html>

<?php
// Termino de guardar el contenido en una variable
$html = ob_get_clean();

// Creo la variable dompdf
require_once '../libreria/dompdf/autoload.inc.php';
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
$dompdf->stream("comprobante_pedido_trabajo.pdf", array("Attachment" => true));
?>