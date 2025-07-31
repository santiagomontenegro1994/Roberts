<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();
define('ESTADO_CUENTA_CORRIENTE', 5); // ID del estado para cuenta corriente

// Procesar búsquedas
$parametro = $_POST['parametro'] ?? '';
$criterio = $_POST['gridRadios'] ?? 'Cliente';
$TrabajosCuentaCorriente = array();

if (!empty($_POST['BotonBuscar'])) {
    $TrabajosCuentaCorriente = Listar_Pedidos_Trabajos_Detallado_Cta_Cte($MiConexion, $criterio, $parametro);
} else {
    $TrabajosCuentaCorriente = Listar_Pedidos_Trabajos_Detallado_Cta_Cte($MiConexion);
}

$CantidadPedidos = count($TrabajosCuentaCorriente);
?>

<main id="main" class="main">
<div class="pagetitle">
  <h1>Cuenta Corriente - Pedidos de Trabajo</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
      <li class="breadcrumb-item">Pedidos</li>
      <li class="breadcrumb-item active">Cuenta Corriente</li>
    </ol>
  </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Listado de Pedidos en Cuenta Corriente</h5>
            
            <?php if (!empty($_SESSION['Mensaje'])) { ?>
                <div class="alert alert-<?= $_SESSION['Estilo'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['Mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['Mensaje']); unset($_SESSION['Estilo']); ?>
            <?php } ?>

            <form method="POST" class="mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="parametro" id="parametro" 
                               value="<?= htmlspecialchars($parametro) ?>" 
                               placeholder="Buscar...">
                    </div>
                    
                    <div class="col-md-4">
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary" name="BotonBuscar">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="listados_cta_cte.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Cliente" 
                                   <?= (empty($criterio) || $criterio == 'Cliente') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios1">Cliente</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Fecha"
                                   <?= $criterio == 'Fecha' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios2">Fecha</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="Telefono"
                                   <?= $criterio == 'Telefono' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios3">Teléfono</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios4" value="Id"
                                   <?= $criterio == 'Id' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios4">ID</label>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Cliente</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col" class="text-end">Seña</th>
                            <th scope="col" class="text-end">Saldo</th>
                            <th scope="col">Tomado por</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($CantidadPedidos > 0): ?>
                            <?php foreach ($TrabajosCuentaCorriente as $pedido): 
                                $saldo = $pedido['PRECIO'] - $pedido['SEÑA'];
                            ?>
                                <tr>
                                    <td><?= $pedido['ID'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($pedido['FECHA'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($pedido['CLIENTE_N'] . ' ' . htmlspecialchars($pedido['CLIENTE_A'])) ?></strong>
                                        <?php if (!empty($pedido['TELEFONO'])): ?>
                                            <br><small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($pedido['TELEFONO']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">$<?= number_format($pedido['PRECIO'], 2, ',', '.') ?></td>
                                    <td class="text-end">$<?= number_format($pedido['SEÑA'], 2, ',', '.') ?></td>
                                    <td class="text-end fw-bold <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">
                                        $<?= number_format($saldo, 2, ',', '.') ?>
                                    </td>
                                    <td><?= htmlspecialchars($pedido['USUARIO']) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#detallesPedidoModal"
                                                    data-pedido-id="<?= $pedido['ID'] ?>"
                                                    title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <a href="modificar_pedidos_trabajos.php?ID_PEDIDO=<?= $pedido['ID'] ?>"
                                               class="btn btn-sm btn-outline-warning" 
                                               title="Modificar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" data-bs-target="#pagarCuentaCorrienteModal"
                                                    data-pedido-id="<?= $pedido['ID'] ?>"
                                                    data-pedido-saldo="<?= $saldo ?>"
                                                    title="Registrar pago">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No hay pedidos en cuenta corriente</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal para Ver Detalles del Pedido -->
<div class="modal fade" id="detallesPedidoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detalles del Pedido <span id="detallePedidoId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detallePedidoContenido">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles del pedido...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Pagar Cuenta Corriente -->
<div class="modal fade" id="pagarCuentaCorrienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formPagarCuentaCorriente">
                    <input type="hidden" name="idPedido" id="pagarPedidoId">
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo pendiente:</label>
                        <input type="text" class="form-control" id="pagarPedidoSaldo" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="montoPago" class="form-label">Monto a pagar:</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="montoPago" id="montoPago" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="metodoPago" class="form-label">Método de pago:</label>
                        <select class="form-select" name="metodoPago" id="metodoPago" required>
                            <?php 
                            $TiposPagosEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
                            foreach ($TiposPagosEntrada as $metodo): ?>
                                <option value="<?= $metodo['idTipoPago'] ?>">
                                    <?= htmlspecialchars($metodo['denominacion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones:</label>
                        <textarea class="form-control" name="observaciones" id="observaciones" rows="2"></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require ('../shared/footer.inc.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de detalles
    const detallesModal = new bootstrap.Modal(document.getElementById('detallesPedidoModal'));
    
    document.getElementById('detallesPedidoModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const pedidoId = button.getAttribute('data-pedido-id');
        
        document.getElementById('detallePedidoId').textContent = '#' + pedidoId;
        
        fetch(`obtener_detalle_pedido.php?id=${pedidoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar los detalles');
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('detallePedidoContenido').innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('detallePedidoContenido').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> ${error.message}
                    </div>
                `;
            });
    });
    
    // Configurar modal de pago
    const pagoModal = new bootstrap.Modal(document.getElementById('pagarCuentaCorrienteModal'));
    
    document.getElementById('pagarCuentaCorrienteModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const pedidoId = button.getAttribute('data-pedido-id');
        const saldo = button.getAttribute('data-pedido-saldo');
        
        document.getElementById('pagarPedidoId').value = pedidoId;
        document.getElementById('pagarPedidoSaldo').value = '$' + parseFloat(saldo).toFixed(2);
        document.getElementById('montoPago').value = parseFloat(saldo).toFixed(2);
        document.getElementById('montoPago').max = saldo;
    });
    
    // Manejar envío del formulario de pago
    document.getElementById('formPagarCuentaCorriente').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const montoPago = parseFloat(document.getElementById('montoPago').value);
        const saldo = parseFloat(document.getElementById('pagarPedidoSaldo').value.replace('$', ''));
        
        if (montoPago <= 0) {
            alert('El monto a pagar debe ser mayor que cero');
            return;
        }
        
        if (montoPago > saldo) {
            alert('El monto a pagar no puede ser mayor que el saldo pendiente');
            return;
        }
        
        if (confirm(`¿Confirmar pago de $${montoPago.toFixed(2)}?`)) {
            const formData = new FormData(this);
            
            fetch('procesar_pago_cuenta_corriente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    pagoModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el pago');
            });
        }
    });
});
</script>
</body>
</html>