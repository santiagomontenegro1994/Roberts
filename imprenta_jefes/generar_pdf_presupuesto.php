<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) || empty($_GET['id'])) {
    die("Acceso denegado.");
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

$idPresupuesto = (int)$_GET['id'];
$query = mysqli_query($MiConexion, "SELECT * FROM presupuestos_historial WHERE idPresupuesto = $idPresupuesto");

if (!$query || mysqli_num_rows($query) == 0) {
    die("Presupuesto no encontrado.");
}

$presupuesto = mysqli_fetch_assoc($query);
$cliente = htmlspecialchars($presupuesto['cliente_nombre']);
$items = json_decode($presupuesto['items_json'], true);

// Separamos la fecha en Día, Mes y Año para los cuadritos
$timestamp = strtotime($presupuesto['fecha']);
$dia = date('d', $timestamp);
$mes = date('m', $timestamp);
$anio = date('Y', $timestamp);

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto #<?php echo $idPresupuesto; ?></title>
    <style>
        /* Fuentes y colores generales basados en tu diseño */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            margin: -10px;
        }
        
        .color-magenta { color: #c45b9b; }
        .bg-magenta { background-color: #c45b9b; color: white; }
        .bg-gris { background-color: #e6e6e6; }
        
        table { width: 100%; border-collapse: collapse; }
        
        /* Cajas de la fecha */
        .caja-fecha {
            background-color: white;
            color: #333;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-left: 5px;
            font-size: 12px;
        }

        /* Tabla principal de items */
        .tabla-items {
            margin-top: 20px;
            border-top: 2px solid #c45b9b;
            border-bottom: 2px solid #c45b9b;
        }
        .tabla-items th {
            background-color: #d18ab8; /* Rosa más suave para el encabezado */
            color: white;
            font-style: italic;
            font-weight: bold;
            padding: 8px;
            text-align: center;
        }
        .tabla-items td {
            padding: 10px;
            border-bottom: 1px solid #eccbe0; /* Lineas rosas tenues entre filas */
            vertical-align: middle;
        }
        .tabla-items .col-borde {
            border-right: 1px solid #eccbe0;
        }
        
        /* Redondeos simulados para los campos del cliente */
        .campo-cliente {
            background-color: #e3e3e3;
            padding: 3px 10px;
            border-radius: 12px; /* Dompdf soporta border-radius básico */
            display: block;
            margin-bottom: 4px;
            min-height: 12px;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <td width="35%" style="vertical-align: top;">
                <img src="../assets/img/Logo1.png" style="max-width: 180px;">
            </td>
            
            <td width="35%" style="vertical-align: top; font-size: 9px; line-height: 1.3;">
                <strong>ROBERTS GRAFICA SAS</strong><br>
                CUIT 30-71902925-2<br>
                Laprida 25 - Villa Allende Córdoba<br><br>
                @robertsgrafica<br>
                www.robertsgrafica.com
            </td>
            
            <td width="30%" style="vertical-align: top; text-align: right;">
                <div style="background-color: #c45b9b; padding: 6px 10px; border-radius: 6px; display: inline-block;">
                    <span style="color: white; font-weight: bold; margin-right: 5px; font-size: 11px;">Fecha:</span>
                    <span class="caja-fecha"><?php echo $dia; ?></span>
                    <span class="caja-fecha"><?php echo $mes; ?></span>
                    <span class="caja-fecha"><?php echo $anio; ?></span>
                </div>
            </td>
        </tr>
    </table>

    <br><br>

    <table style="width: 80%;">
        <tr>
            <td width="15%" style="font-size: 10px;">RAZÓN SOCIAL:</td>
            <td width="85%"><span class="campo-cliente" style="font-weight: bold;"><?php echo $cliente; ?></span></td>
        </tr>
        <tr>
            <td style="font-size: 10px;">Domicilio:</td>
            <td><span class="campo-cliente"></span></td>
        </tr>
        <tr>
            <td style="font-size: 10px;">Teléfono:</td>
            <td><span class="campo-cliente"></span></td>
        </tr>
        <tr>
            <td style="font-size: 10px;">Atención:</td>
            <td><span class="campo-cliente"></span></td>
        </tr>
    </table>

    <table class="tabla-items">
        <thead>
            <tr>
                <th width="5%" class="col-borde">N°</th>
                <th width="60%" class="col-borde">TIPO DE SERVICIO</th>
                <th width="15%" class="col-borde">VALOR UNIT</th>
                <th width="20%">VALOR TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $num = 1;
            foreach ($items as $item): 
            ?>
            <tr>
                <td class="col-borde" style="text-align: center; font-weight: bold; font-style: italic;"><?php echo $num; ?></td>
                <td class="col-borde">
                    <span style="font-weight: bold;"><?php echo htmlspecialchars($item['descripcion']); ?></span>
                </td>
                <td class="col-borde" style="text-align: center; font-weight: bold;">
                    $<?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?>.-
                </td>
                <td style="text-align: center; font-weight: bold; font-size: 12px;">
                    $<?php echo number_format($item['precio_total'], 2, ',', '.'); ?>.-
                </td>
            </tr>
            <?php 
            $num++;
            endforeach; 
            ?>
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 5px; font-weight: bold; font-style: italic; font-size: 10px;">
        Precios con iva incluido.
    </div>

    <br>

    <div style="background-color: #d18ab8; color: white; padding: 4px 10px; font-weight: bold; font-size: 13px; margin-bottom: 10px;">
        OBSERVACIONES / ACLARACIONES
    </div>
    
    <div style="font-size: 10px; font-weight: bold; line-height: 1.6; padding-left: 5px;">
        1. Precio cotizado en pesos argentinos.<br>
        2. Forma de pago 50% de seña cuando se aprueba el trabajo, el saldo cuando se entrega el trabajo.<br>
        3. El presupuesto tiene una validez de 7 días.<br>
        4. Los trabajos se retiran de nuestro local, solo será en el local del cliente si está acordado.
    </div>

    <br><br><br><br>

    <table style="margin-top: 40px;">
        <tr>
            <td width="50%" style="text-align: center;">
                <br>
                <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 10px;">FIRMA</div>
            </td>
            <td width="50%" style="text-align: center;">
                <br><br><br>
                <div style="font-weight: bold; font-size: 11px;">Rodolfo Regalado</div>
                <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 10px;">ACLARACIÓN</div>
            </td>
        </tr>
    </table>

</body>
</html>
<?php
$html = ob_get_clean();

require_once '../libreria/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true); // Vital para que cargue la imagen del logo
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Mostrar el PDF en el navegador para WhatsApp (no forzar descarga automática en celulares)
$dompdf->stream("Presupuesto_Roberts_" . str_replace(' ', '_', $cliente) . ".pdf", array("Attachment" => false));
?>