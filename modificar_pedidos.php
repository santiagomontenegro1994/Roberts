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

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Datos del Pedido</h5>

                <!-- Mostrar mensajes de éxito o error -->
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <!-- Formulario para modificar el pedido -->
                <form method='post'>
                    <input type='hidden' name="IdPedido" value="<?php echo $DatosPedidoActual['ID_PEDIDO']; ?>" />

                    <!-- Datos del encabezado del pedido -->
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Cliente</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="<?php echo $DatosPedidoActual['CLIENTE']; ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Fecha</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="<?php echo $DatosPedidoActual['FECHA']; ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Seña</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="<?php echo $DatosPedidoActual['SENIA']; ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Descuento</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="<?php echo $DatosPedidoActual['DESCUENTO']; ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Precio Total</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="<?php echo $DatosPedidoActual['PRECIO_TOTAL']; ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Estado</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="<?php echo $DatosPedidoActual['ESTADO']; ?>" readonly>
                        </div>
                    </div>

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
