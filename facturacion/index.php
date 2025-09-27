<?php
// Cargar configuración
require_once __DIR__ . '/config_facturacion.php';
$config = ConfigFacturacion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Facturación</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; }
        h1 { color: #333; }
        form { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 0 5px #ccc; }
        label { display: block; margin-top: 10px; }
        input, button { padding: 5px; margin-top: 5px; }
        button { cursor: pointer; background: #007BFF; color: #fff; border: none; border-radius: 3px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<h1>Panel de Facturación</h1>

<!-- Crear Factura -->
<form action="crear_factura.php" method="POST">
    <h2>Crear Factura</h2>
    <label>Tipo de documento:</label>
    <input type="text" name="doc_tipo" value="80" required>

    <label>Nro de documento:</label>
    <input type="text" name="doc_nro" value="20111111112" required>

    <label>Tipo de comprobante:</label>
    <input type="text" name="cbte_tipo" value="1" required>

    <label>Total:</label>
    <input type="text" name="imp_total" value="1000" required>

    <input type="hidden" name="imp_neto" value="1000">
    <input type="hidden" name="imp_iva" value="0">

    <button type="submit">Crear Factura</button>
</form>

<!-- Consultar Factura -->
<form action="consultar_factura.php" method="GET">
    <h2>Consultar Factura</h2>
    <label>ID de Factura:</label>
    <input type="text" name="id" placeholder="12345" required>
    <button type="submit">Consultar</button>
</form>

<!-- Eliminar Factura -->
<form action="eliminar_comprobante.php" method="POST">
    <h2>Eliminar Factura</h2>
    <label>ID de Factura:</label>
    <input type="text" name="id" placeholder="12345" required>
    <button type="submit">Eliminar</button>
</form>

<!-- Regenerar PDF -->
<form action="regenerar_pdf.php" method="GET">
    <h2>Regenerar PDF</h2>
    <label>ID de Factura:</label>
    <input type="text" name="id" placeholder="12345" required>
    <button type="submit">Regenerar PDF</button>
</form>

</body>
</html>
