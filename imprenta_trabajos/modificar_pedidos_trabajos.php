<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();
if (!$MiConexion) {
    die("Error de conexión a la base de datos");
}

$DatosPedidoActual = array();
$DetallesPedido = array();
$IdPedidoParaJs = 'null';

// --- MODIFICAR PEDIDO COMPLETO ---
if (!empty($_POST['BotonModificarPedido'])) {
    if (Modificar_Pedido_Trabajo($MiConexion)) {
        $_SESSION['Mensaje'] = "El pedido se ha modificado correctamente.";
        $_SESSION['Estilo'] = 'success';
        header('Location: listados_pedidos_trabajo.php');
        exit;
    } else {
        $_SESSION['Estilo'] = 'warning';
        $DatosPedidoActual['ID'] = $_POST['IdPedido'];
        $tempData = Datos_Pedido_Trabajo($MiConexion, $_POST['IdPedido']);
        $DatosPedidoActual = array_merge($DatosPedidoActual, $tempData);
        $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $_POST['IdPedido']);
    }
}

// --- MODIFICAR SOLO LA SEÑA ---
else if (!empty($_POST['BotonModificarSenia'])) {
    $idPedido = (int)($_POST['IdPedido'] ?? 0);
    $nuevaSenia = (float)($_POST['Senia'] ?? 0);
    
    // Obtener datos actuales del pedido
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $precioTotal = (float)($DatosPedidoActual['PRECIO_TOTAL'] ?? 0);
    
        // Actualizar la seña
        if (Modificar_Senia_Pedido($MiConexion, $idPedido, $nuevaSenia)) {
            $_SESSION['Mensaje'] = "Seña actualizada correctamente.";
            $_SESSION['Estilo'] = 'success';
            header("Location: modificar_pedidos_trabajos.php?ID_PEDIDO=$idPedido");
            exit;
        } else {
            $_SESSION['Mensaje'] = "Error al actualizar la seña. ".$MiConexion->error;
            $_SESSION['Estilo'] = 'danger';
        }
    
    // Recargar datos después de intentar modificar
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}
// --- CARGAR PEDIDO EXISTENTE ---
else if (!empty($_GET['ID_PEDIDO'])) {
    $idPedido = (int)$_GET['ID_PEDIDO'];
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    
    if (empty($DatosPedidoActual)) {
        $_SESSION['Mensaje'] = "El pedido solicitado no existe.";
        $_SESSION['Estilo'] = 'danger';
        header('Location: listados_pedidos_trabajo.php');
        exit;
    }
    
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}

// Configurar variables para la vista
if (!empty($DatosPedidoActual['ID'])) {
    $IdPedidoParaJs = $DatosPedidoActual['ID'];
    $saldoInicial = (float)($DatosPedidoActual['PRECIO_TOTAL'] ?? 0) - (float)($DatosPedidoActual['SENIA'] ?? 0);
} else {
    $saldoInicial = 0;
}

ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Modificar Pedido de Trabajo</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menú</a></li>
                <li class="breadcrumb-item"><a href="listados_pedidos_trabajo.php">Pedidos</a></li>
                <li class="breadcrumb-item active">Modificar Pedido</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['Mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <form method='post'>
            <!-- SECCIÓN 1 -->
            <div class="card mb-2 mt-2">
                <div class="card-body py-2 px-3">
                    <h5 class="card-title pb-1 mb-2">Datos del Pedido N°: <?php echo htmlspecialchars($DatosPedidoActual['ID'] ?? ''); ?></h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cliente:</label>
                            <p class="form-control-static mb-0"><?php echo htmlspecialchars($DatosPedidoActual['CLIENTE'] ?? ''); ?> <?php echo htmlspecialchars($DatosPedidoActual['CLIENTE_A'] ?? ''); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label fw-bold">Fecha:</label>
                            <p class="form-control-static mb-0"><?php echo htmlspecialchars($DatosPedidoActual['FECHA'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2 -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Trabajos del Pedido</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarDetalleModal">
                            <i class="bi bi-plus-circle"></i> Agregar Trabajo
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Trabajo</th>
                                    <th>Descripción</th>
                                    <th>Fecha Entrega</th>
                                    <th class="text-end">Precio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($DetallesPedido)) {
                                    foreach ($DetallesPedido as $detalle) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($detalle['TRABAJO']); ?></td>
                                            <td><?php echo htmlspecialchars($detalle['DESCRIPCION']); ?></td>
                                            <td><?php echo date("d/m/Y", strtotime($detalle['FECHA_ENTREGA'])); ?></td>
                                            <td class="text-end">$<?php echo number_format($detalle['PRECIO'], 2, ',', '.'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning me-2" onclick="editarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php }
                                } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No hay trabajos registrados en este pedido</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3 - Resumen de Precios -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resumen de Precios</h5>
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Total:</label>
                            <p id="precioTotal" class="form-control-static fs-5 text-success mb-0" 
                            data-value="<?php echo htmlspecialchars($DatosPedidoActual['PRECIO_TOTAL'] ?? 0); ?>">
                                $<?php echo number_format($DatosPedidoActual['PRECIO_TOTAL'] ?? 0, 2, ',', '.'); ?>
                            </p>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="senia" class="form-label fw-bold">Seña ($)</label>
                            <input type="number" step="0.01" min="0" 
                                class="form-control <?php echo (!empty($_SESSION['Estilo'])) && $_SESSION['Estilo'] == 'warning' ? 'is-invalid' : ''; ?>" 
                                name="Senia" id="senia"
                                value="<?php echo htmlspecialchars($DatosPedidoActual['SENIA'] ?? 0); ?>">
                            <?php if (!empty($_SESSION['Estilo']) && $_SESSION['Estilo'] == 'warning') { ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($_SESSION['Mensaje']); ?>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Saldo a Pagar:</label>
                            <p id="saldo" class="form-control-static fw-bold fs-5 <?php echo ($saldoInicial > 0) ? 'text-danger' : 'text-success'; ?> mb-0">
                                $<?php echo number_format($saldoInicial, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 d-flex justify-content-end">
                            <input type="hidden" name="IdPedido" value="<?php echo htmlspecialchars($DatosPedidoActual['ID'] ?? ''); ?>">
                            <button type="submit" class="btn btn-success me-2" name="BotonModificarSenia" value="1">
                                <i class="bi bi-cash-coin me-2"></i>Actualizar Seña
                            </button>
                            <a href="listados_pedidos_trabajos.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</main>

<!-- Modal para agregar detalle -->
<div class="modal fade" id="agregarDetalleModal" tabindex="-1" aria-labelledby="agregarDetalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarDetalleModalLabel">Agregar Trabajo al Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Contenido del modal para agregar detalle -->
                <form id="formAgregarDetalle" action="procesar_detalle.php" method="post">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="idPedido" value="<?php echo $IdPedidoParaJs; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="trabajo" class="form-label">Trabajo</label>
                            <select class="form-select" id="trabajo" name="trabajo" required>
                                <option value="" selected disabled>Seleccione un trabajo</option>
                                <!-- Opciones de trabajos -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="precio" class="form-label">Precio</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fechaEntrega" class="form-label">Fecha de Entrega</label>
                            <input type="date" class="form-control" id="fechaEntrega" name="fechaEntrega" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Trabajo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar detalle -->
<div class="modal fade" id="editarDetalleModal" tabindex="-1" aria-labelledby="editarDetalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarDetalleModalLabel">Editar Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="contenidoEditarDetalle">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar que la seña no sea mayor que el total ni negativa
    const seniaInput = document.getElementById('senia');
    
    seniaInput.addEventListener('change', function() {
        const total = parseFloat(document.getElementById('precioTotal').dataset.value) || 0;
        const senia = parseFloat(this.value) || 0;
        
        if (senia > total) {
            alert('La seña no puede ser mayor que el total del pedido');
            this.value = total.toFixed(2);
        } else if (senia < 0) {
            alert('La seña no puede ser negativa');
            this.value = '0.00';
        }
    });
});

function editarDetalle(id) {
    fetch(`obtener_detalle.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('contenidoEditarDetalle').innerHTML = data;
            new bootstrap.Modal(document.getElementById('editarDetalleModal')).show();
        });
}

function eliminarDetalle(id) {
    if (confirm('¿Está seguro que desea eliminar este trabajo?')) {
        window.location.href = `procesar_detalle.php?accion=eliminar&id=${id}&ID_PEDIDO=<?php echo $IdPedidoParaJs; ?>`;
    }
}
</script>

<?php
$_SESSION['Mensaje'] = '';
$_SESSION['Estilo'] = '';
require('../shared/footer.inc.php');
?>