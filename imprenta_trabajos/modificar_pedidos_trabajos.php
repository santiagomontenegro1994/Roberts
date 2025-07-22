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

// Obtener métodos de pago desde la base de datos
$TiposPagosEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
$TiposPagosSalida = Listar_Tipos_Pagos_Salida($MiConexion);

$DatosPedidoActual = array();
$DetallesPedido = array();
$IdPedidoParaJs = 'null';

// --- MODIFICAR PEDIDO COMPLETO ---
if (!empty($_POST['BotonModificarPedido'])) {
    if (false!=false) {
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
    $metodoPago = $_POST['metodoPago'] ?? null;
    $esReduccion = ($_POST['esReduccion'] ?? '0') === '1';
    
    // Obtener datos actuales del pedido
    $DatosPedidoActual = Datos_Pedido_Trabajo($MiConexion, $idPedido);
    $precioTotal = (float)($DatosPedidoActual['PRECIO_TOTAL'] ?? 0);
    
    // Actualizar la seña
    $resultado = Modificar_Senia_Pedido($MiConexion, $idPedido, $nuevaSenia, $metodoPago, $esReduccion);
    if (!$resultado['success']) {
        // Mostrar error al usuario
        $_SESSION['Mensaje'] = "Error: " . $resultado['error'];
        $_SESSION['Estilo'] = 'danger';
    } else {
        $_SESSION['Mensaje'] = "Seña actualizada correctamente";
        $_SESSION['Estilo'] = 'success';
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

// Función auxiliar para obtener iconos según el método de pago
function obtenerIconoMetodoPago($nombreMetodo) {
    $iconos = [
        'Efectivo' => 'bi-cash',
        'Transferencia' => 'bi-bank',
        'Tarjeta' => 'bi-credit-card',
        'Mercado Pago' => 'bi-wallet',
        'Cheque' => 'bi-wallet2',
        'Depósito' => 'bi-piggy-bank',
        'Débito' => 'bi-credit-card-2-back',
        'Crédito' => 'bi-credit-card-2-front',
        'Retiro' => 'bi-cash-stack'
    ];
    
    return $iconos[$nombreMetodo] ?? 'bi-coin';
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
                <!-- SECCIÓN 1 - Datos del Pedido -->
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

                <!-- SECCIÓN 2 - Trabajos del Pedido -->
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
                                        <th>Estado</th>
                                        <th>Descripción</th>
                                        <th>Fecha Entrega</th>
                                        <th class="text-end">Precio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($DetallesPedido)) {
                                        foreach ($DetallesPedido as $detalle) { 
                                                    // Obtener el color y título de la fila según el estado
                                        list($Title, $Color) = ColorDeFilaTrabajo($detalle['ESTADO_ID']);
                                        ?>
                                            <tr class="<?php echo $Color; ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?php echo $Title; ?>">
                                                <td class="extra-small"><?php echo htmlspecialchars($detalle['TRABAJO']); ?></td>
                                                <td class="extra-small"><?php echo htmlspecialchars($detalle['ESTADO']); ?></td>
                                                <td class="extra-small"><?php echo htmlspecialchars($detalle['DESCRIPCION']); ?></td>
                                                <td class="extra-small"><?php echo date("d/m/Y", strtotime($detalle['FECHA_ENTREGA'])); ?></td>
                                                <td class="text-end extra-small">$<?php echo number_format($detalle['PRECIO'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-warning me-2" onclick="editarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-danger" onclick="eliminarDetalle(<?php echo $detalle['ID_DETALLE']; ?>)" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No hay trabajos registrados en este pedido</td>
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
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Total:</label>
                                <p id="precioTotal" class="form-control-static fs-5 text-success mb-0" 
                                data-value="<?php echo htmlspecialchars($DatosPedidoActual['PRECIO_TOTAL'] ?? 0); ?>">
                                    $<?php echo number_format($DatosPedidoActual['PRECIO_TOTAL'] ?? 0, 2, ',', '.'); ?>
                                </p>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Seña actual:</label>
                                <p class="form-control-static text-muted mb-0">
                                    $<?php echo number_format($DatosPedidoActual['SENIA'] ?? 0, 2, ',', '.'); ?>
                                </p>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="senia" class="form-label fw-bold">Seña Modificada</label>
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

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Saldo a Pagar:</label>
                                <p id="saldo" class="form-control-static fw-bold fs-5 <?php echo ($saldoInicial > 0) ? 'text-danger' : 'text-success'; ?> mb-0">
                                    $<?php echo number_format($saldoInicial, 2, ',', '.'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 d-flex justify-content-end">
                                <input type="hidden" name="IdPedido" value="<?php echo htmlspecialchars($DatosPedidoActual['ID'] ?? ''); ?>">
                                <input type="hidden" name="BotonModificarSenia" value="1">
                                <input type="hidden" name="metodoPago" id="inputMetodoPago">
                                <input type="hidden" name="esReduccion" id="inputEsReduccion" value="0">
                                
                                <button type="button" class="btn btn-success me-2" id="btnModificarSenia"
                                    data-pedido-id="<?php echo htmlspecialchars($DatosPedidoActual['ID'] ?? ''); ?>"
                                    data-senia-actual="<?php echo htmlspecialchars($DatosPedidoActual['SENIA'] ?? 0); ?>">
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
                    <form id="formAgregarDetalle" action="procesar_detalle.php" method="post">
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="idPedido" value="<?php echo $IdPedidoParaJs; ?>">
                        
                        <!-- Primera fila: Estado, Trabajo y Descripción -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="estado_trabajo" class="form-label">Estado</label>
                                <select class="form-select" id="estado_trabajo" name="idEstadoTrabajo" required>
                                    <?php 
                                    $estados = Datos_Estados_Trabajo($MiConexion);
                                    foreach ($estados as $estado): ?>
                                        <option value="<?php echo $estado['idEstado']; ?>">
                                            <?php echo htmlspecialchars($estado['denominacion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="tipo_trabajo" class="form-label">Trabajo</label>
                                <select class="form-select" id="tipo_trabajo" name="idTrabajo" required>
                                    <?php 
                                    $trabajos = Datos_Trabajos($MiConexion);
                                    foreach ($trabajos as $trabajo): ?>
                                        <option value="<?php echo $trabajo['idTipoTrabajo']; ?>">
                                            <?php echo htmlspecialchars($trabajo['denominacion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion">
                            </div>
                        </div>
                        
                        <!-- Segunda fila: Proveedor, Fecha y Hora -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="enviado" class="form-label">Enviado a</label>
                                <select class="form-select" id="enviado" name="idProveedor" required>
                                    <?php 
                                    $proveedores = Listar_Proveedores($MiConexion);
                                    foreach ($proveedores as $proveedor): ?>
                                        <option value="<?php echo $proveedor['ID_PROVEEDOR']; ?>">
                                            <?php echo htmlspecialchars($proveedor['NOMBRE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Fecha Entrega</label>
                                <input type="date" class="form-control" id="fecha_entrega" name="fechaEntrega" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Hora Entrega</label>
                                <select class="form-select" id="hora_entrega" name="horaEntrega">
                                    <?php
                                    $horas = ['08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', 
                                             '12:00', '12:30', '16:00', '16:30', '17:00', '17:30', 
                                             '18:00', '18:30', '19:00', '19:30'];
                                    ?>
                                    <?php foreach ($horas as $hora): ?>
                                        <option value="<?php echo $hora; ?>">
                                            <?php echo $hora; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tercera fila: Precio -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="precio" class="form-label">Precio ($)</label>
                                <input type="number" class="form-control" id="precio" name="precio" 
                                    step="0.01" min="0" required>
                            </div>
                        </div>

                        <input type="hidden" name="IdPedido" value="<?php echo htmlspecialchars($DatosPedidoActual['ID'] ?? ''); ?>">
                        
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

    <!-- Modal de Pago -->
    <div class="modal fade" id="pagoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalPagoTitle">Registrar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto a registrar</label>
                        <input type="text" class="form-control form-control-lg text-center fw-bold" id="montoPagoModal" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Método</label>
                        <div class="d-flex flex-wrap justify-content-center" id="metodosPagoContainer">
                            <!-- Se llena dinámicamente con JavaScript -->
                        </div>
                    </div>
                    <input type="hidden" id="metodoPagoSeleccionado" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmarPagoBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Pre-cargar los métodos de pago desde PHP
    const metodosPagoEntrada = <?php echo json_encode($TiposPagosEntrada); ?>;
    const metodosPagoSalida = <?php echo json_encode($TiposPagosSalida); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        // Elementos del DOM
        const btnModificarSenia = document.getElementById('btnModificarSenia');
        const pagoModal = new bootstrap.Modal(document.getElementById('pagoModal'));
        const montoPagoModal = document.getElementById('montoPagoModal');
        const confirmarPagoBtn = document.getElementById('confirmarPagoBtn');
        const seniaInput = document.getElementById('senia');
        const inputMetodoPago = document.getElementById('inputMetodoPago');
        const inputEsReduccion = document.getElementById('inputEsReduccion');
        const metodoPagoSeleccionado = document.getElementById('metodoPagoSeleccionado');
        const modalPagoTitle = document.getElementById('modalPagoTitle');
        const metodosPagoContainer = document.getElementById('metodosPagoContainer');
        const form = document.querySelector('form');
        const precioTotal = document.getElementById('precioTotal');
        const saldoElement = document.getElementById('saldo');

        /**
         * Validar que la seña no sea mayor que el total ni negativa
         */
        function validarSenia() {
            const total = parseFloat(precioTotal.dataset.value) || 0;
            const senia = parseFloat(seniaInput.value) || 0;
            
            if (senia > total) {
                alert('La seña no puede ser mayor que el total del pedido');
                seniaInput.value = total.toFixed(2);
                return false;
            } else if (senia < 0) {
                alert('La seña no puede ser negativa');
                seniaInput.value = '0.00';
                return false;
            }
            
            // Calcular y actualizar saldo
            const saldo = total - senia;
            saldoElement.textContent = '$' + saldo.toFixed(2).replace('.', ',');
            saldoElement.className = saldo > 0 ? 'form-control-static fw-bold fs-5 text-danger mb-0' : 'form-control-static fw-bold fs-5 text-success mb-0';
            
            return true;
        }

        /**
         * Cargar métodos de pago en el modal según el tipo de operación
         */
        function cargarMetodosPago(esReduccion) {
            metodosPagoContainer.innerHTML = '';
            const metodos = esReduccion ? metodosPagoSalida : metodosPagoEntrada;
            
            metodos.forEach(metodo => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'btn btn-outline-primary m-2 metodo-pago';
                boton.dataset.id = metodo.idTipoPago;
                boton.innerHTML = `<i class="bi ${obtenerIconoMetodoPago(metodo.denominacion)} me-1"></i>${metodo.denominacion}`;
                metodosPagoContainer.appendChild(boton);
            });
        }

        /**
         * Función auxiliar para obtener iconos según el método de pago
         */
        function obtenerIconoMetodoPago(nombreMetodo) {
            const iconos = {
                'Efectivo': 'bi-cash',
                'Transferencia': 'bi-bank',
                'Tarjeta': 'bi-credit-card',
                'Mercado Pago': 'bi-wallet',
                'Cheque': 'bi-wallet2',
                'Depósito': 'bi-piggy-bank',
                'Débito': 'bi-credit-card-2-back',
                'Crédito': 'bi-credit-card-2-front',
                'Retiro': 'bi-cash-stack'
            };
            return iconos[nombreMetodo] || 'bi-coin';
        }

        /**
         * Manejar clic en el botón de modificar seña
         */
        btnModificarSenia.addEventListener('click', function() {
            if (!validarSenia()) return;
            
            const nuevaSenia = parseFloat(seniaInput.value) || 0;
            const seniaActual = parseFloat(this.dataset.seniaActual) || 0;
            const diferencia = nuevaSenia - seniaActual;
            
            // Si no hay cambio, enviar directamente
            if (diferencia === 0) {
                form.submit();
                return;
            }
            
            // Determinar si es reducción (salida)
            const esReduccion = diferencia < 0;
            inputEsReduccion.value = esReduccion ? '1' : '0';
            
            // Configurar el modal según el tipo de operación
            modalPagoTitle.textContent = esReduccion ? 'Registrar Retiro' : 'Registrar Pago';
            montoPagoModal.value = '$' + Math.abs(diferencia).toFixed(2);
            
            // Cargar los métodos de pago correspondientes
            cargarMetodosPago(esReduccion);
            
            // Resetear selección
            document.querySelectorAll('.metodo-pago').forEach(b => b.classList.remove('active'));
            metodoPagoSeleccionado.value = '';
            pagoModal.show();
        });

        // Manejar selección de método de pago
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('metodo-pago')) {
                // Remover selección previa
                document.querySelectorAll('.metodo-pago').forEach(b => b.classList.remove('active'));
                
                // Seleccionar nuevo método
                e.target.classList.add('active');
                metodoPagoSeleccionado.value = e.target.dataset.id;
            }
        });

        // Manejar confirmación de pago
        confirmarPagoBtn.addEventListener('click', function() {
            if (!metodoPagoSeleccionado.value) {
                alert('Por favor seleccione un método');
                return;
            }
            
            // Asignar el valor al campo oculto del formulario principal
            inputMetodoPago.value = metodoPagoSeleccionado.value;
            
            // Cerrar modal y enviar formulario
            pagoModal.hide();
            form.submit();
        });

        // Validar la seña al cambiar el valor
        seniaInput.addEventListener('change', validarSenia);
        
        /**
         * Función para editar un detalle del pedido
         */
        window.editarDetalle = function(id) {
            fetch(`obtener_detalle.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los datos del detalle');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('contenidoEditarDetalle').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('editarDetalleModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message);
                });
        };
        
        /**
         * Función para eliminar un detalle del pedido
         */
        window.eliminarDetalle = function(id) {
            if (confirm('¿Está seguro que desea eliminar este trabajo?')) {
                window.location.href = `procesar_detalle.php?accion=eliminar&id=${id}&ID_PEDIDO=<?php echo $IdPedidoParaJs; ?>`;
            }
        };
        
        /**
         * Manejar el envío del formulario de agregar detalle
         */
        document.getElementById('formAgregarDetalle').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else if (response.ok) {
                    return response.text();
                } else {
                    throw new Error('Error en la respuesta del servidor');
                }
            })
            .then(data => {
                if (data) {
                    // Si hay datos (no fue redirección), mostrar mensaje
                    alert(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el trabajo: ' + error.message);
            });
        });
    });
    </script>

<?php
$_SESSION['Mensaje'] = '';
$_SESSION['Estilo'] = '';
require('../shared/footer.inc.php');
?>

</body>
</html>