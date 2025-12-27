<?php
// Incluimos la lógica de negocio antes de imprimir nada
require_once 'procesar_informe.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Informes - Imprenta Roberts</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        
        .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-container h1 { color: #333; margin: 0; font-size: 24px; }
        .btn-print { background-color: #007bff; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; transition: background 0.3s; }
        .btn-print:hover { background-color: #0056b3; }

        /* Contenedor de Tarjetas */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }

        /* Estilo de Tarjeta */
        .card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 5px solid transparent; }
        
        .card h3 { margin: 0 0 10px 0; color: #888; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .card .amount { font-size: 32px; font-weight: bold; color: #333; margin-bottom: 5px; }
        
        /* Estilos de Variación (Porcentajes) */
        .variation { font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; }
        .variation span { margin-left: 5px; font-weight: normal; color: #999; }

        /* Colores específicos de borde para tarjetas */
        .card.expenses { border-left-color: #dc3545; } /* Rojo */
        .card.profit { border-left-color: #28a745; }   /* Verde */
        .card.income { border-left-color: #17a2b8; }   /* Azul */

    </style>
</head>
<body>

    <div class="header-container">
        <div>
            <h1>Informe Financiero</h1>
            <p style="color: #666; margin: 5px 0 0;">Período: <?php echo $nombreMesActual . ' ' . $anioActual; ?></p>
        </div>
        <a href="imprimir_informe.php" target="_blank" class="btn-print">
            Imprimir Informe PDF
        </a>
    </div>

    <div class="dashboard-grid">
        
        <div class="card income">
            <h3>Ingresos Totales</h3>
            <div class="amount">$ <?php echo number_format($datosMesActual['ingresos'], 2, ',', '.'); ?></div>
            <div class="variation" style="color: #6c757d;">
                <small>Total facturado este mes</small>
            </div>
        </div>

        <div class="card expenses">
            <h3>Salidas Totales</h3>
            <div class="amount">$ <?php echo $txtGastosActual; ?></div>
            
            <div class="variation" style="color: <?php echo $colorGastos; ?>">
                <?php echo $iconoGastos . ' ' . $txtVarGastos; ?>%
                <span>respecto al mes anterior</span>
            </div>
        </div>

        <div class="card profit">
            <h3>Ganancia Neta</h3>
            <div class="amount">$ <?php echo $txtNetoActual; ?></div>
            
            <div class="variation" style="color: <?php echo $colorNeto; ?>">
                <?php echo $iconoNeto . ' ' . $txtVarNeto; ?>%
                <span>respecto al mes anterior</span>
            </div>
        </div>

    </div>

    <div style="margin-top: 40px;">
        <h3 style="color: #444; border-bottom: 2px solid #ddd; padding-bottom: 10px;">Resumen Rápido</h3>
        <p style="color: #666;">Para ver el detalle completo de movimientos, por favor descargue el PDF.</p>
    </div>

</body>
</html>