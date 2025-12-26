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
$TiposPagosEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
$TiposPagosSalida = Listar_Tipos_Pagos_Salida($MiConexion);
$TiposFactura = Listar_Tipos_Factura($MiConexion);

$DatosPedidoActual = array();
$DetallesPedido = array();
$IdPedidoParaJs = 'null';

// --- LOGICA DE CARGA DE DATOS ---
if (!empty($_GET['ID_PEDIDO'])) {
    $idPedido = (int)$_GET['ID_PEDIDO'];
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}
// Si viene por POST (recarga tras editar seña, etc)
else if (!empty($_POST['IdPedido'])) {
    $idPedido = (int)$_POST['IdPedido'];
    
    // Lógica de Seña (si se envió el formulario de seña)
    if (!empty($_POST['BotonModificarSenia'])) {
        $monto = (float)($_POST['montoOperacion'] ?? 0);
        $metodo = $_POST['metodoPago'] ?? null;
        $esReduccion = ($_POST['esReduccion'] ?? '0') === '1';
        
        $res = Modificar_Senia_Pedido($MiConexion, $idPedido, $monto, $metodo, $esReduccion);
        if($res['success']) {
            $_SESSION['Mensaje'] = "Seña actualizada"; $_SESSION['Estilo'] = 'success';
        } else {
            $_SESSION['Mensaje'] = $res['error']; $_SESSION['Estilo'] = 'danger';
        }
    }
    
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}

if (!empty($DatosPedidoActual['ID'])) {
    $IdPedidoParaJs = $DatosPedidoActual['ID'];
    $saldoInicial = (float)($DatosPedidoActual['PRECIO_TOTAL'] ?? 0) - (float)($DatosPedidoActual['SENIA'] ?? 0);
} else {
    // Si no hay pedido cargado, volver
    header('Location: listados_pedidos_trabajo.php');
    exit;
}

function obtenerIconoMetodoPago($nombre) { /* ... tu logica de iconos ... */ return 'bi-coin'; }
ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Modificar Pedido #<?php echo $IdPedidoParaJs; ?></h1>
    </div>

    <section class="section">
        <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['Mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php $_SESSION['Mensaje']=''; $_SESSION['Estilo']=''; ?>
        <?php } ?>

        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-6">
                        <h5><?php echo htmlspecialchars($DatosPedidoActual['CLIENTE'] . ' ' . $DatosPedidoActual['CLIENTE_A']); ?></h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($DatosPedidoActual['FECHA']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                    <h5 class="card-title m-0">Trabajos</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#agregarDetalleModal">
                        <i class="bi bi-plus-lg"></i> Agregar Trabajo
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Trabajo</th>
                                <th>Estado</th>
                                <th>Descripción</th>
                                <th>Entrega</th>
                                <th class="text-end">Precio</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($DetallesPedido)) { 
                                foreach ($DetallesPedido as $detalle) { 
                                    list($Title, $Color) = ColorDeFilaTrabajo($detalle['ESTADO_ID']); 
                            ?>
                            <tr class="<?php echo $Color; ?>">
                                <td><?php echo htmlspecialchars($detalle['TRABAJO']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['ESTADO']); ?></td>
                                <td><?php echo htmlspecialchars($detalle['DESCRIPCION']); ?></td>
                                <td><?php echo date("d/m", strtotime($detalle['FECHA_ENTREGA'])); ?></td>
                                <td class="text-end">$<?php echo number_format($detalle['PRECIO'], 2, ',', '.'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" onclick="editarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-xs btn-danger" onclick="eliminarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php } } else { ?>
                            <tr><td colspan="6" class="text-center">Sin trabajos</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Pagos y Señas</h5>
                <form method="post">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Total Pedido:</label>
                            <div class="fs-4 text-primary">$<?php echo number_format($DatosPedidoActual['PRECIO_TOTAL'], 2); ?></div>
                        </div>
                        <div class="col-md-4">
                            <label>Seña Actual:</label>
                            <div class="fs-4 text-success">$<?php echo number_format($DatosPedidoActual['SENIA'], 2); ?></div>
                        </div>
                        <div class="col-md-4 text-end">
                            <input type="hidden" name="IdPedido" value="<?php echo $IdPedidoParaJs; ?>">
                            <input type="hidden" name="BotonModificarSenia" value="1">
                            <input type="hidden" name="montoOperacion" id="inputMontoOperacion" value="0">
                            <input type="hidden" name="metodoPago" id="inputMetodoPago">
                            <input type="hidden" name="esReduccion" id="inputEsReduccion" value="0">

                            <button type="button" class="btn btn-success" id="btnAgregarSenia">
                                <i class="bi bi-plus-circle"></i> Seña
                            </button>
                            <button type="button" class="btn btn-danger" id="btnQuitarSenia" 
                                    data-senia-actual="<?php echo $DatosPedidoActual['SENIA']; ?>">
                                <i class="bi bi-dash-circle"></i> Seña
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="agregarDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAgregar" action="procesar_detalle.php" method="post">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="IdPedido" value="<?php echo $IdPedidoParaJs; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Trabajo</label>
                            <select class="form-select" name="idTrabajo" required>
                                <?php foreach (Datos_Trabajos($MiConexion) as $t): ?>
                                    <option value="<?php echo $t['idTipoTrabajo']; ?>"><?php echo $t['denominacion']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Estado</label>
                            <select class="form-select" name="idEstadoTrabajo" required>
                                <?php foreach (Datos_Estados_Trabajo($MiConexion) as $e): ?>
                                    <option value="<?php echo $e['idEstado']; ?>"><?php echo $e['denominacion']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Precio</label>
                            <input type="number" class="form-control" name="precio" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label>Fecha Entrega</label>
                            <input type="date" class="form-control" name="fechaEntrega" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Descripción / Detalles</label>
                        <input type="text" class="form-control" name="descripcion">
                    </div>
                    
                    <input type="hidden" name="idProveedor" value="1"> 

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editarDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoEditarDetalle">
                </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eliminarDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar Trabajo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Seguro que deseas eliminar este ítem?</p>
                <p class="small text-danger">Se recalculará el total del pedido.</p>
                
                <form action="procesar_detalle.php" method="POST">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="idDetalle" id="idDetalleEliminar">
                    <input type="hidden" name="IdPedido" value="<?php echo $IdPedidoParaJs; ?>">
                    
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="agregarSeniaModal" tabindex="-1">...</div>
<div class="modal fade" id="quitarSeniaModal" tabindex="-1">...</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aquí va tu lógica de botones de SEÑA que ya tenías y funcionaba...
    // (ConfigurarMetodosPago, btnAgregarSenia.addEventListener, etc.)
});

// --- FUNCIONES GLOBALES PARA LA TABLA ---

// 1. Abrir Modal Editar
window.editarDetalle = function(id) {
    fetch(`obtener_detalle.php?id=${id}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('contenidoEditarDetalle').innerHTML = html;
            new bootstrap.Modal(document.getElementById('editarDetalleModal')).show();
            // Aquí puedes re-inicializar validaciones de facturación si es necesario
        });
};

// 2. Abrir Modal Eliminar (ESTO FALTABA)
window.eliminarDetalle = function(id) {
    document.getElementById('idDetalleEliminar').value = id;
    new bootstrap.Modal(document.getElementById('eliminarDetalleModal')).show();
};
</script>

<?php require('../shared/footer.inc.php'); ?>
</body>
</html>