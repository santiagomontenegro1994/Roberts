<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado

//require ('barraLateral.inc.php'); //incluir barra lateral

require_once 'funciones/conexion.php';
require_once 'funciones/select_general.php';
$MiConexion = ConexionBD();

// Array para almacenar los datos del pedido y sus detalles
$DatosPedidoActual = array();
$DetallesPedido = array();
$proveedores = Listar_Proveedores($MiConexion);

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
                <div class="d-flex justify-content-between align-items-end w-100">
                    <div class="card-title">Nombre: <span id="nombreCliente" class="text-dark fs-5"><?php echo $DatosPedidoActual['CLIENTE'] ?>, <?php echo $DatosPedidoActual['CLIENTE_A'] ?></span></div>
   
                    <div class="card-title">Fecha de Pedido: <span id="fecha" class="text-dark fs-5"><?php echo $DatosPedidoActual['FECHA'] ?></span></div>
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Titulo</th>
                                <th>Editorial</th>
                                <th>Precio</th>
                                <th>Cant.</th>
                                <th>Estado</th>
                                <th>Proveedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($DetallesPedido as $detalle) { 
                                // Método para pintar las filas
                                list($Title, $Color) = ColorDeFila($detalle['ESTADO']); ?>
                                <tr class="<?php echo $Color; ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?php echo $Title; ?>">
                                    <td><?php echo $detalle['LIBRO_T']; ?></td>
                                    <td><?php echo $detalle['LIBRO_E']; ?></td>
                                    <td><?php echo $detalle['PRECIO']; ?></td>
                                    <td><?php echo $detalle['CANTIDAD']; ?></td>
                                    <td>
                                        <select name="estado_detalle[<?php echo $detalle['ID_DETALLE']; ?>]" class="form-control">
                                            <option value="1" <?php echo ($detalle['ESTADO'] == 1) ? 'selected' : ''; ?>>ENTREGADO</option>
                                            <option value="2" <?php echo ($detalle['ESTADO'] == 2) ? 'selected' : ''; ?>>RECIBIDO</option>
                                            <option value="3" <?php echo ($detalle['ESTADO'] == 3) ? 'selected' : ''; ?>>PEDIDO</option>
                                            <option value="4" <?php echo ($detalle['ESTADO'] == 4) ? 'selected' : ''; ?>>P/PEDIR</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="proveedor_detalle[<?php echo $detalle['ID_DETALLE']; ?>]" class="form-control">
                                            <?php foreach ($proveedores as $proveedor) { ?>
                                                <option value="<?php echo $proveedor['ID_PROVEEDOR']; ?>" 
                                                    <?php echo ($detalle['idProveedor'] == $proveedor['ID_PROVEEDOR']) ? 'selected' : ''; ?>>
                                                    <?php echo $proveedor['NOMBRE']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <!-- Botones de acción -->
                    <div class="text-center">
                      <button type="submit" class="btn btn-primary btn-sm" value="Modificar" name="BotonModificarPedido1">Guardar Cambios</button>
                        <a href="listados_pedidos.php" class="btn btn-success btn-info btn-sm">Volver al Listado</a>
                    </div>
                </form><!-- End Horizontal Form -->
            </div>
        </div>
    </section>

    <div class="section">
        <div class="card">
            <div class="card-footer text-end">
                <div class="details">
                    <table class="table w-auto ms-auto"> <!-- w-auto ajusta el ancho -->
                        <tr>
                            <td class="card-title">Precio Total:</td>
                            <td class="text-dark fs-5">$<?php echo $DatosPedidoActual['PRECIO_TOTAL'] ?></td>
                        </tr>
                        <tr>
                            <td class="card-title">Descuento:</td>
                            <td class="text-dark fs-5">%<?php echo $DatosPedidoActual['DESCUENTO'] ?></td>
                        </tr>
                        <tr>
                            <td class="card-title">Seña:</td>
                            <td class="text-dark fs-5">$<?php echo $DatosPedidoActual['SENIA'] ?></td>
                        </tr>
                        <tr>
                        <?php
                            // Calcula el monto del descuento
                            $monto_descuento = ($DatosPedidoActual['PRECIO_TOTAL'] * $DatosPedidoActual['DESCUENTO']) / 100;
                            $saldo = ($DatosPedidoActual['PRECIO_TOTAL'] - $monto_descuento)-$DatosPedidoActual['SENIA']?>
                            <td class="card-title">Saldo:</td>
                            <td class="text-dark fs-5">$<?php echo $saldo ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>


</main><!-- End #main -->

<?php
$_SESSION['Mensaje'] = '';
require('footer.inc.php'); // Incluir footer
?>

</body>

</html>
