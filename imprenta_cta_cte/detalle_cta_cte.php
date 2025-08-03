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
$idCliente = $_GET['idCliente'] ?? 0;

// Obtener información del cliente
$cliente = Obtener_Cliente_Por_ID($MiConexion, $idCliente);
if (!$cliente) {
    $_SESSION['Mensaje'] = "Cliente no encontrado";
    $_SESSION['Estilo'] = "danger";
    header('Location: cta_cte.php');
    exit;
}

// Obtener trabajos en cuenta corriente del cliente
$trabajosCC = Obtener_Trabajos_Cuenta_Corriente_Cliente($MiConexion, $idCliente);
$totalDeuda = array_reduce($trabajosCC, function($carry, $item) {
    return $carry + $item['PRECIO'];
}, 0);
?>

<main id="main" class="main">
<div class="pagetitle">
  <h1>Cuenta Corriente - <?= htmlspecialchars($cliente['NOMBRE'] . ' ' . htmlspecialchars($cliente['APELLIDO'])) ?></h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
      <li class="breadcrumb-item"><a href="cta_cte.php">Cuenta Corriente</a></li>
      <li class="breadcrumb-item active">Detalle Cliente</li>
    </ol>
  </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Detalle de Cuenta Corriente</h5>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Información del Cliente</h6>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['NOMBRE'] . ' ' . htmlspecialchars($cliente['APELLIDO'])) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['TELEFONO']) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <h6>Resumen de Deuda</h6>
                    <p><strong>Trabajos en CC:</strong> <?= count($trabajosCC) ?></p>
                    <p class="fw-bold <?= $totalDeuda > 0 ? 'text-danger' : 'text-success' ?>">
                        <strong>Total adeudado:</strong> $<?= number_format($totalDeuda, 2, ',', '.') ?>
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID Trabajo</th>
                            <th scope="col">Fecha Pedido</th>
                            <th scope="col">Tipo Trabajo</th>
                            <th scope="col">Descripción</th>
                            <th scope="col" class="text-end">Precio</th>
                            <th scope="col">Fecha Entrega</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($trabajosCC) > 0): ?>
                            <?php foreach ($trabajosCC as $trabajo): ?>
                                <tr>
                                    <td><?= $trabajo['ID_DETALLE'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($trabajo['FECHA_PEDIDO'])) ?></td>
                                    <td><?= htmlspecialchars($trabajo['TIPO_TRABAJO']) ?></td>
                                    <td><?= htmlspecialchars($trabajo['DESCRIPCION']) ?></td>
                                    <td class="text-end">$<?= number_format($trabajo['PRECIO'], 2, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($trabajo['FECHA_ENTREGA'])) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" data-bs-target="#pagarTrabajoModal"
                                                    data-trabajo-id="<?= $trabajo['ID_DETALLE'] ?>"
                                                    data-trabajo-precio="<?= $trabajo['PRECIO'] ?>"
                                                    title="Registrar pago">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No hay trabajos en cuenta corriente para este cliente</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal para Pagar Trabajo específico -->
<div class="modal fade" id="pagarTrabajoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Pago de Trabajo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formPagarTrabajo">
                    <input type="hidden" name="idDetalleTrabajo" id="pagarTrabajoId">
                    <input type="hidden" name="idCliente" value="<?= $idCliente ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Monto del trabajo:</label>
                        <input type="text" class="form-control" id="pagarTrabajoPrecio" readonly>
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
    const pagoTrabajoModal = new bootstrap.Modal(document.getElementById('pagarTrabajoModal'));
    
    // Configurar modal al mostrarse
    document.getElementById('pagarTrabajoModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const trabajoId = button.getAttribute('data-trabajo-id');
        const precio = button.getAttribute('data-trabajo-precio');
        
        document.getElementById('pagarTrabajoId').value = trabajoId;
        document.getElementById('pagarTrabajoPrecio').value = '$' + parseFloat(precio).toFixed(2);
        document.getElementById('montoPago').value = parseFloat(precio).toFixed(2);
        document.getElementById('montoPago').max = precio;
    });
    
    // Manejar envío del formulario
    document.getElementById('formPagarTrabajo').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const montoPago = parseFloat(document.getElementById('montoPago').value);
        const precio = parseFloat(document.getElementById('pagarTrabajoPrecio').value.replace('$', ''));
        
        if (montoPago <= 0) {
            alert('El monto a pagar debe ser mayor que cero');
            return;
        }
        
        if (montoPago > precio) {
            alert('El monto a pagar no puede ser mayor que el precio del trabajo');
            return;
        }
        
        if (confirm(`¿Confirmar pago de $${montoPago.toFixed(2)} por este trabajo?`)) {
            const formData = new FormData(this);
            
            // Mostrar indicador de carga
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
            
            fetch('procesar_pago_trabajo_cta_cte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la red');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    pagoTrabajoModal.hide();
                    location.reload(); // Recargar para ver cambios
                } else {
                    throw new Error(data.message || 'Error al procesar el pago');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Registrar Pago';
            });
        }
    });
});
</script>
</body>
</html>