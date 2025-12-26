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

// Listas para los selectores
$TiposPagosEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
$TiposPagosSalida = Listar_Tipos_Pagos_Salida($MiConexion);
$TiposFactura = Listar_Tipos_Factura($MiConexion);

$DatosPedidoActual = array();
$DetallesPedido = array();
$IdPedidoParaJs = 'null';

// --- LOGICA PRINCIPAL: CARGA Y MODIFICACION DE SEÑA ---

// Caso A: Se envió el formulario para Modificar Seña
if (!empty($_POST['BotonModificarSenia'])) {
    $idPedido = (int)$_POST['IdPedido'];
    $montoOperacion = (float)($_POST['montoOperacion'] ?? 0);
    $metodoPago = $_POST['metodoPago'] ?? null;
    $esReduccion = ($_POST['esReduccion'] ?? '0') === '1';

    if ($montoOperacion > 0) {
        $resultado = Modificar_Senia_Pedido($MiConexion, $idPedido, $montoOperacion, $metodoPago, $esReduccion);
        if (!$resultado['success']) {
            $_SESSION['Mensaje'] = "Error: " . $resultado['error'];
            $_SESSION['Estilo'] = 'danger';
        } else {
            $_SESSION['Mensaje'] = "Seña actualizada correctamente.";
            $_SESSION['Estilo'] = 'success';
        }
    }
    // Recargar datos para mostrar actualizados
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}
// Caso B: Carga normal por GET
else if (!empty($_GET['ID_PEDIDO'])) {
    $idPedido = (int)$_GET['ID_PEDIDO'];
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}
// Caso C: Retorno de error (POST sin acción específica)
else if (!empty($_POST['IdPedido'])) {
    $idPedido = (int)$_POST['IdPedido'];
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $DetallesPedido = Detalles_Pedido_Trabajo($MiConexion, $idPedido);
}

// Validar que exista el pedido
if (empty($DatosPedidoActual['ID'])) {
    // Si no se encontró, redirigir
    header('Location: listados_pedidos_trabajo.php');
    exit;
}

$IdPedidoParaJs = $DatosPedidoActual['ID'];
$saldoInicial = (float)($DatosPedidoActual['PRECIO_TOTAL'] ?? 0) - (float)($DatosPedidoActual['SENIA'] ?? 0);

ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Modificar Pedido #<?php echo $IdPedidoParaJs; ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="listados_pedidos_trabajo.php">Pedidos</a></li>
                <li class="breadcrumb-item active">Modificar</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['Mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php $_SESSION['Mensaje'] = ''; $_SESSION['Estilo'] = ''; ?>
        <?php } ?>

        <form method="post" id="formPrincipal">
            
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="fw-bold">Cliente:</label>
                            <span><?php echo htmlspecialchars($DatosPedidoActual['CLIENTE'] . ' ' . $DatosPedidoActual['CLIENTE_A']); ?></span>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="fw-bold">Fecha:</label>
                            <span><?php echo htmlspecialchars($DatosPedidoActual['FECHA']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                        <h5 class="card-title m-0">Trabajos del Pedido</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#agregarDetalleModal">
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
                                        <button type="button" class="btn btn-xs btn-warning me-1" onclick="editarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger" onclick="eliminarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php } } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No hay trabajos registrados</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resumen de Cuenta</h5>
                    <div class="row align-items-end">
                        <div class="col-md-4 text-center">
                            <label class="fw-bold">Total</label>
                            <div class="fs-4 text-primary">$<?php echo number_format($DatosPedidoActual['PRECIO_TOTAL'] ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-md-4 text-center">
                            <label class="fw-bold">Seña Pagada</label>
                            <div class="fs-4 text-success">$<?php echo number_format($DatosPedidoActual['SENIA'] ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-md-4 text-center">
                            <label class="fw-bold">Saldo Restante</label>
                            <div class="fs-4 <?php echo ($saldoInicial > 0) ? 'text-danger' : 'text-success'; ?>">
                                $<?php echo number_format($saldoInicial, 2, ',', '.'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 d-flex justify-content-end">
                            <input type="hidden" name="IdPedido" value="<?php echo htmlspecialchars($DatosPedidoActual['ID']); ?>">
                            <input type="hidden" name="BotonModificarSenia" value="1">
                            <input type="hidden" name="metodoPago" id="inputMetodoPago">
                            <input type="hidden" name="esReduccion" id="inputEsReduccion" value="0">
                            <input type="hidden" name="montoOperacion" id="inputMontoOperacion" value="0">
                            
                            <button type="button" class="btn btn-success me-2" id="btnAgregarSenia"
                                data-pedido-id="<?php echo htmlspecialchars($DatosPedidoActual['ID']); ?>"
                                data-senia-actual="<?php echo htmlspecialchars($DatosPedidoActual['SENIA'] ?? 0); ?>">
                                <i class="bi bi-plus-circle me-2"></i>Agregar Seña
                            </button>
                            
                            <button type="button" class="btn btn-danger me-2" id="btnQuitarSenia"
                                data-pedido-id="<?php echo htmlspecialchars($DatosPedidoActual['ID']); ?>"
                                data-senia-actual="<?php echo htmlspecialchars($DatosPedidoActual['SENIA'] ?? 0); ?>">
                                <i class="bi bi-dash-circle me-2"></i>Quitar Seña
                            </button>
                            
                            <a href="listados_pedidos_trabajo.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</main>

<div class="modal fade" id="agregarDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="procesar_detalle.php" method="post">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="IdPedido" value="<?php echo $IdPedidoParaJs; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Trabajo</label>
                            <select class="form-select" name="idTrabajo" required>
                                <?php foreach (Datos_Trabajos($MiConexion) as $t): ?>
                                    <option value="<?php echo $t['idTipoTrabajo']; ?>"><?php echo $t['denominacion']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="idEstadoTrabajo" required>
                                <?php foreach (Datos_Estados_Trabajo($MiConexion) as $e): ?>
                                    <option value="<?php echo $e['idEstado']; ?>"><?php echo $e['denominacion']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Precio ($)</label>
                            <input type="number" class="form-control" name="precio" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Entrega</label>
                            <input type="date" class="form-control" name="fechaEntrega" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
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

<div class="modal fade" id="eliminarDetalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar Trabajo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Seguro que deseas eliminar este trabajo?</p>
                <p class="text-danger small">El precio se descontará del total del pedido.</p>
                
                <form action="procesar_detalle.php" method="POST">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="idDetalle" id="idDetalleEliminar">
                    <input type="hidden" name="IdPedido" value="<?php echo $IdPedidoParaJs; ?>">
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
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

<div class="modal fade" id="agregarSeniaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Agregar Seña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Monto</label>
                    <input type="number" step="0.01" class="form-control" id="montoAgregar">
                </div>
                <div class="mb-3">
                    <label>Método de Pago</label>
                    <div class="d-flex flex-wrap" id="metodosAgregarContainer"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmarAgregarBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quitarSeniaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Quitar Seña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Monto a quitar (Máx: <span id="maxSenia"></span>)</label>
                    <input type="number" step="0.01" class="form-control" id="montoQuitar">
                </div>
                <div class="mb-3">
                    <label>Método de Devolución</label>
                    <div class="d-flex flex-wrap" id="metodosQuitarContainer"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarQuitarBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. LOGICA RESTAURADA DE SEÑAS (LO QUE FUNCIONABA ANTES)
    // ============================================
    const btnAgregarSenia = document.getElementById('btnAgregarSenia');
    const btnQuitarSenia = document.getElementById('btnQuitarSenia');
    const inputMetodoPago = document.getElementById('inputMetodoPago');
    const inputEsReduccion = document.getElementById('inputEsReduccion');
    const inputMontoOperacion = document.getElementById('inputMontoOperacion');
    const formPrincipal = document.getElementById('formPrincipal');

    const agregarModal = new bootstrap.Modal(document.getElementById('agregarSeniaModal'));
    const quitarModal = new bootstrap.Modal(document.getElementById('quitarSeniaModal'));

    const metodosPagoEntrada = <?php echo json_encode($TiposPagosEntrada); ?>;
    const metodosPagoSalida = <?php echo json_encode($TiposPagosSalida); ?>;
    
    let metodoSeleccionado = null;
    let tipoOperacion = '';

    function crearBotonesPago(container, metodos, esReduccion) {
        container.innerHTML = '';
        metodoSeleccionado = null;
        metodos.forEach(m => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = esReduccion ? 'btn btn-outline-danger m-1' : 'btn btn-outline-primary m-1';
            btn.innerHTML = m.denominacion;
            btn.onclick = function() {
                container.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                metodoSeleccionado = m.idTipoPago;
            };
            container.appendChild(btn);
        });
    }

    if(btnAgregarSenia) {
        btnAgregarSenia.addEventListener('click', function() {
            tipoOperacion = 'agregar';
            crearBotonesPago(document.getElementById('metodosAgregarContainer'), metodosPagoEntrada, false);
            agregarModal.show();
        });
    }

    if(btnQuitarSenia) {
        btnQuitarSenia.addEventListener('click', function() {
            tipoOperacion = 'quitar';
            const actual = parseFloat(this.dataset.seniaActual);
            document.getElementById('maxSenia').textContent = actual;
            crearBotonesPago(document.getElementById('metodosQuitarContainer'), metodosPagoSalida, true);
            quitarModal.show();
        });
    }

    document.getElementById('confirmarAgregarBtn').addEventListener('click', function() {
        const monto = document.getElementById('montoAgregar').value;
        if(!metodoSeleccionado) return alert('Seleccione método de pago');
        if(!monto || monto <= 0) return alert('Ingrese monto válido');
        
        inputEsReduccion.value = '0';
        inputMetodoPago.value = metodoSeleccionado;
        inputMontoOperacion.value = monto;
        formPrincipal.submit();
    });

    document.getElementById('confirmarQuitarBtn').addEventListener('click', function() {
        const monto = document.getElementById('montoQuitar').value;
        if(!metodoSeleccionado) return alert('Seleccione método de devolución');
        if(!monto || monto <= 0) return alert('Ingrese monto válido');
        
        inputEsReduccion.value = '1';
        inputMetodoPago.value = metodoSeleccionado;
        inputMontoOperacion.value = monto;
        formPrincipal.submit();
    });

    // ============================================
    // 2. LOGICA DE TRABAJOS (ELIMINAR / EDITAR)
    // ============================================

    // Función Global para ELIMINAR (Llamada desde el HTML)
    window.eliminarDetalle = function(id) {
        document.getElementById('idDetalleEliminar').value = id;
        new bootstrap.Modal(document.getElementById('eliminarDetalleModal')).show();
    };

    // Función Global para EDITAR (Llamada desde el HTML)
    window.editarDetalle = function(id) {
        fetch(`obtener_detalle.php?id=${id}`)
            .then(res => {
                if(!res.ok) throw new Error("Error cargando detalle");
                return res.text();
            })
            .then(html => {
                document.getElementById('contenidoEditarDetalle').innerHTML = html;
                new bootstrap.Modal(document.getElementById('editarDetalleModal')).show();
            })
            .catch(err => alert("Error: " + err));
    };

});
</script>

<?php require('../shared/footer.inc.php'); ?>
</body>
</html>