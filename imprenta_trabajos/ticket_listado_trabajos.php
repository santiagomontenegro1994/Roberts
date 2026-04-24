<?php
session_start();

// Configurar zona horaria
date_default_timezone_set('America/Argentina/Cordoba');

if (empty($_SESSION['Usuario_Nombre'])) { 
    die("Acceso denegado."); 
}

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';
$MiConexion = ConexionBD();

// Consultas a la base de datos para traer los listados
$detallesPendientes = obtenerDetallesTrabajoPorEstados($MiConexion, [1, 2]); // Pendiente, Diseño
$detallesEnTaller = obtenerDetallesTrabajoPorEstados($MiConexion, [4]); // En Taller
$detallesListos = obtenerDetallesTrabajoPorEstados($MiConexion, [6]); // Listos

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Trabajos</title>
    <style>
        /* ESTE ES EXACTAMENTE EL MISMO CSS DEL TICKET DE PEDIDO */
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
        th, td { text-align: left; padding: 2px 0; border-bottom: 1px dotted #ccc; }
        th.right, td.right { text-align: right; }
        
        /* Titulos de las secciones */
        .seccion-titulo {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            background-color: #000;
            color: #fff;
            padding: 2px;
        }
    </style>
</head>
<body>

    <div class="ticket-header text-center">
        <h2>GRÁFICA ROBERTS</h2>
        <p>Laprida 25, Villa Allende</p>
        <div class="separador"></div>
        <p class="bold">LISTADO DE PRODUCCIÓN</p>
        <p>Emisión: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <?php 
    // Agrupamos las secciones para imprimirlas
    $secciones = [
        'PENDIENTES / DISEÑO' => $detallesPendientes,
        'EN TALLER' => $detallesEnTaller,
        'LISTOS P/ ENTREGAR' => $detallesListos
    ];

    foreach ($secciones as $titulo => $datos): 
        if (!empty($datos)):
    ?>
        <div class="separador"></div>
        <div class="text-center seccion-titulo"><?php echo $titulo; ?></div>
        
        <table>
            <thead>
                <tr>
                    <th width="25%">Orden</th>
                    <th width="75%">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $d): ?>
                <tr>
                    <td style="vertical-align: top;">
                        <span class="bold">#<?php echo $d['idPedidoTrabajos']; ?></span>
                    </td>
                    <td>
                        <span class="bold"><?php echo htmlspecialchars($d['tipo_trabajo']); ?></span><br>
                        <?php echo htmlspecialchars($d['nombre_cliente'] . ' ' . $d['apellido_cliente']); ?><br>
                        <?php if(!empty($d['descripcion'])) { ?>
                            <span style="font-size: 10px; font-style: italic;">
                                <?php echo htmlspecialchars($d['descripcion']); ?>
                            </span>
                        <?php } ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php 
        endif;
    endforeach; 
    ?>

    <div class="separador"></div>
    <div class="text-center">
        <p>-- FIN DEL LISTADO --</p>
        <br><br><br>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>