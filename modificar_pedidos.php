<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado
require_once 'funciones/conexion.php';
require_once 'funciones/select_general.php';
$MiConexion = ConexionBD();

// Array para almacenar los datos del pedido y sus detalles
$DatosPedidoActual = array();
$DetallesPedido = array();

if (!empty($_POST['BotonModificarPedido1'])) {
    echo '<script>
        console.log("El botón Modificar Pedido 1 fue presionado.");
        </script>';
    // Validar y procesar la modificación del estado de los detalles
    if (Modificar_Detalles_Pedido($MiConexion, $_POST)) {
        $_SESSION['Mensaje'] = "El pedido se ha modificado correctamente!";
        $_SESSION['Estilo'] = 'success';
        header('Location: listados_pedidos.php');
        exit;
    } else {
        $_SESSION['Mensaje'] = "Error al modificar el pedido.";
        $_SESSION['Estilo'] = 'danger';
    }
} else if (!empty($_GET['ID_PEDIDO'])) {
    // Obtener los datos del pedido y sus detalles si se pasa el ID por GET
    $DatosPedidoActual = Datos_Pedidos($MiConexion, $_GET['ID_PEDIDO']);
    $DetallesPedido = Detalles_Pedido($MiConexion, $_GET['ID_PEDIDO']);
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Modificar Pedido</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
                <li class="breadcrumb-item">Pedidos</li>
                <li class="breadcrumb-item active">Modificar Pedido</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    
            
                <h5 class="card-title">Datos del Pedido</h5>

                <!-- Mostrar mensajes de éxito o error -->
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>
                <!-- Nuevo encabezado -->
    <div class="section">
        <div class="card">
            <div class="card-body">
                <div class="details">
                    <div class="card-title">Nombre: <span id="nombreCliente" class="text-dark fs-5"><?php echo $DatosPedidoActual['CLIENTE'] ?></span></div>
                </div>
                <div>   
                    <div class="card-title">Fecha: <span id="fecha" class="text-dark fs-5"><?php echo $DatosPedidoActual['FECHA'] ?></span></div>
                </div>
                
                <div class="details">
                    <h3 class="card-title">Precio</h3>
                    <?php
                    // Calcula el monto del descuento
                    $monto_descuento = ($DatosPedidoActual['PRECIO_TOTAL'] * $DatosPedidoActual['DESCUENTO']) / 100;
                    $saldo = ($DatosPedidoActual['PRECIO_TOTAL'] - $monto_descuento)-$DatosPedidoActual['SENIA']?>
                    <div>Precio Total: $<span id="precioTotal"><?php echo $DatosPedidoActual['PRECIO_TOTAL'] ?></span></div>
                    <div>Descuento: %<span id="sena"><?php echo $DatosPedidoActual['DESCUENTO'] ?></span></div>
                    <div>Seña: $<span id="sena"><?php echo $DatosPedidoActual['SENIA'] ?></span></div>
                    <div>Saldo: $<span id="saldo"><?php echo $saldo ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <!-- Formulario para modificar el pedido -->
                <form method='post'>
                    <input type='hidden' name="IdPedido" value="<?php echo $DatosPedidoActual['ID_PEDIDO']; ?>" />
                    <!-- Detalles del pedido -->
                    <h5 class="card-title">Detalles del Pedido</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Libro</th>
                                <th>Precio Unitario</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($DetallesPedido as $detalle) { ?>
                                <tr>
                                    <td><?php echo $detalle['LIBRO']; ?></td>
                                    <td><?php echo $detalle['PRECIO']; ?></td>
                                    <td><?php echo $detalle['CANTIDAD']; ?></td>
                                    <td>
                                        <select name="estado_detalle[<?php echo $detalle['ID_DETALLE']; ?>]" class="form-control">
                                            <option value="1" <?php echo ($detalle['ESTADO'] == 1) ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="2" <?php echo ($detalle['ESTADO'] == 2) ? 'selected' : ''; ?>>Enviado</option>
                                            <option value="3" <?php echo ($detalle['ESTADO'] == 3) ? 'selected' : ''; ?>>Entregado</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- Botones de acción -->
                    <div class="text-center">
                      <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarPedido1">Guardar Cambios</button>
                        <a href="listados_pedidos.php" class="btn btn-success btn-info">Volver al Listado</a>
                    </div>
                </form><!-- End Horizontal Form -->
            </div>
        </div>
    </section>
</main><!-- End #main -->

<?php
$_SESSION['Mensaje'] = '';
require('footer.inc.php'); // Incluir footer
?>

</body>

</html>
