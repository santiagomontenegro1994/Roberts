<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pedido de Libro</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; }
        .header, .footer { text-align: center; margin: 20px 0; }
        .details { margin: 20px 0; }
        .details div { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Comprobante de Pedido de Libro</h2>
            <p>Fecha: <span id="fecha">01/01/2025</span></p>
        </div>
        <div class="details">
            <h3>Datos del Cliente</h3>
            <div>Nombre: <span id="nombreCliente">Juan Pérez</span></div>
            <div>Dirección: <span id="direccionCliente">Calle Falsa 123</span></div>
            <div>Teléfono: <span id="telefonoCliente">+54 351 1234567</span></div>
            <div>Email: <span id="emailCliente">juan.perez@example.com</span></div>
        </div>
        <div class="details">
            <h3>Datos del Libro</h3>
            <div>Título: <span id="tituloLibro">El Gran Libro</span></div>
            <div>Autor: <span id="autorLibro">Autor Famoso</span></div>
            <div>ISBN: <span id="isbnLibro">123-4567890123</span></div>
        </div>
        <div class="details">
            <h3>Precio</h3>
            <div>Precio Total: $<span id="precioTotal">1000.00</span></div>
            <div>Seña: $<span id="sena">200.00</span></div>
            <div>Saldo: $<span id="saldo">800.00</span></div>
        </div>
        <div class="footer">
            <p>Gracias por su compra</p>
        </div>
    </div>
</body>
</html>
