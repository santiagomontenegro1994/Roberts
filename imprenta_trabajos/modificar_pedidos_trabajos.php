<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

$DatosPedidoActual = array();
$DetallesPedido = array();

if (!empty($_POST['BotonModificarPedido'])) {
    Validar_Pedido_Trabajo();
    
    if (empty($_SESSION['Mensaje'])) {
        if (Modificar_Pedido_Trabajo($MiConexion) != false) {
            $_SESSION['Mensaje'] = "El pedido se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listados_pedidos_trabajo.php');
            exit;
        }
    } else {
        $_SESSION['Estilo'] = 'warning';
        $DatosPedidoActual['idPedidoTrabajos'] = !empty($_POST['IdPedido']) ? $_POST['IdPedido'] : '';
        $DatosPedidoActual['idCliente'] = !empty($_POST['Cliente']) ? $_POST['Cliente'] : '';
        $DatosPedidoActual['fecha'] = !empty($_POST['Fecha']) ? $_POST['Fecha'] : '';
        $DatosPedidoActual['precioTotal'] = !empty($_POST['PrecioTotal']) ? $_POST['PrecioTotal'] : '';
        $DatosPedidoActual['senia'] = !empty($_POST['Senia']) ? $_POST['Senia'] : '';
        $DatosPedidoActual['idEstado'] = !empty($_POST['Estado']) ? $_POST['Estado'] : '';
    }
} else if (!empty($_GET['ID_PEDIDO'])) {
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $_GET['ID_PEDIDO']);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $_GET['ID_PEDIDO']);
}

ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Pedidos de Trabajo</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item">Pedidos</li>
                <li class="breadcrumb-item active">Modificar Pedido</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Modificar Pedido #<?php echo $DatosPedidoActual['ID']; ?></h5>

                <form method='post'>
                    <?php if (!empty($_SESSION['Mensaje'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                            <?php echo $_SESSION['Mensaje']; ?>
                        </div>
                    <?php } ?>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Cliente</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="Cliente" id="cliente">
                                <?php 
                                $clientes = Listar_Clientes($MiConexion);
                                foreach ($clientes as $cliente) {
                                    $selected = ($cliente['ID_CLIENTE'] == $DatosPedidoActual['CLIENTE_ID']) ? 'selected' : '';
                                    echo "<option value='{$cliente['ID_CLIENTE']}' $selected>{$cliente['NOMBRE']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Fecha</label>
                        <div class="col-sm-10">
                            <input type="date" class="form-control" name="Fecha" 
                                   value="<?php echo $DatosPedidoActual['FECHA']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Precio Total</label>
                        <div class="col-sm-10">
                            <input type="number" step="0.01" class="form-control" name="PrecioTotal" 
                                   value="<?php echo $DatosPedidoActual['PRECIO_TOTAL']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Seña</label>
                        <div class="col-sm-10">
                            <input type="number" step="0.01" class="form-control" name="Senia" 
                                   value="<?php echo $DatosPedidoActual['SENIA']; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Estado</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="Estado">
                                <?php 
                                $estados = Datos_Estados_Trabajo($MiConexion);
                                foreach ($estados as $estado) {
                                    $selected = ($estado['ID_ESTADO'] == $DatosPedidoActual['idEstado']) ? 'selected' : '';
                                    echo "<option value='{$estado['ID_ESTADO']}' $selected>{$estado['DESCRIPCION']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <input type='hidden' name="IdPedido" value="<?php echo $DatosPedidoActual['ID']; ?>" />
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarPedido">Guardar Cambios</button>
                        <a href="listados_pedidos_trabajos.php" class="btn btn-success">Volver al listado</a>
                    </div>
                </form>

                <hr>

                <h5 class="card-title">Detalles del Pedido</h5>
                
                <!-- Botón para agregar nuevo detalle -->
                <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#agregarDetalleModal">
                    Agregar Detalle
                </button>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Trabajo</th>
                                <th>Proveedor</th>
                                <th>Fecha Entrega</th>
                                <th>Hora Entrega</th>
                                <th>Precio</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($DetallesPedido as $detalle) { ?>
                                <tr>
                                    <td><?php echo $detalle['TRABAJO']; ?></td>
                                    <td><?php echo $detalle['PROVEEDOR']; ?></td>
                                    <td><?php echo $detalle['FECHA_ENTREGA']; ?></td>
                                    <td><?php echo $detalle['HORA_ENTREGA']; ?></td>
                                    <td><?php echo $detalle['PRECIO']; ?></td>
                                    <td><?php echo $detalle['DESCRIPCION']; ?></td>
                                    <td><?php echo $detalle['ESTADO']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="editarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)">
                                            Editar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="eliminarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal para agregar detalle -->
<div class="modal fade" id="agregarDetalleModal" tabindex="-1" aria-labelledby="agregarDetalleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarDetalleModalLabel">Agregar Detalle de Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="procesar_detalle.php?accion=agregar">
                <div class="modal-body">
                    <input type="hidden" name="idPedido" value="<?php echo $DatosPedidoActual['ID']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Trabajo</label>
                        <select class="form-select" name="idTrabajo" required>
                            <?php 
                            $trabajos = Listar_Trabajos($MiConexion);
                            foreach ($trabajos as $trabajo) {
                                echo "<option value='{$trabajo['ID']}'>{$trabajo['NOMBRE']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" name="idProveedor" required>
                            <?php 
                            $proveedores = Listar_Proveedores($MiConexion);
                            foreach ($proveedores as $proveedor) {
                                echo "<option value='{$proveedor['ID_PROVEEDOR']}'>{$proveedor['NOMBRE']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha Entrega</label>
                        <input type="date" class="form-control" name="fechaEntrega" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hora Entrega</label>
                        <input type="time" class="form-control" name="horaEntrega" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio</label>
                        <input type="number" step="0.01" class="form-control" name="precio" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="idEstadoTrabajo" required>
                            <?php 
                            $estadosTrabajo = Listar_Estados_Trabajo($MiConexion);
                            foreach ($estadosTrabajo as $estado) {
                                echo "<option value='{$estado['ID_ESTADO_TRABAJO']}'>{$estado['DESCRIPCION']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar detalle -->
<div class="modal fade" id="editarDetalleModal" tabindex="-1" aria-labelledby="editarDetalleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarDetalleModalLabel">Editar Detalle de Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="procesar_detalle.php?accion=editar">
                <div class="modal-body" id="contenidoEditarDetalle">
                    <!-- Contenido cargado dinámicamente por JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarDetalle(idDetalle) {
    fetch(`obtener_detalle.php?id=${idDetalle}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('contenidoEditarDetalle').innerHTML = data;
            var myModal = new bootstrap.Modal(document.getElementById('editarDetalleModal'));
            myModal.show();
        });
}

function eliminarDetalle(idDetalle) {
    if (confirm('¿Estás seguro de que deseas eliminar este detalle?')) {
        window.location.href = `procesar_detalle.php?accion=eliminar&id=${idDetalle}&idPedido=<?php echo $DatosPedidoActual['idPedidoTrabajos']; ?>`;
    }
}
</script>

<?php
    $_SESSION['Mensaje'] = '';
    require ('../shared/footer.inc.php');
?>