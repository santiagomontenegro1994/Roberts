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

// Obtener saldo actual
$saldoCliente = ObtenerSaldoCliente($MiConexion, $idCliente);

// Obtener últimos movimientos
$movimientos = ObtenerMovimientosCliente($MiConexion, $idCliente, 10);

// Obtener trabajos pendientes (opcional)
$trabajosPendientes = Obtener_Trabajos_Pendientes($MiConexion, $idCliente);
$totalPendiente = array_sum(array_column($trabajosPendientes, 'PRECIO'));
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
    <?php if (!empty($_SESSION['Mensaje'])) { ?>
        <div class="alert alert-<?= $_SESSION['Estilo'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['Mensaje'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['Mensaje']); unset($_SESSION['Estilo']); ?>
    <?php } ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Resumen de Cuenta Corriente</h5>
                        <a href="cta_cte.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border p-3 rounded">
                                <h6 class="mb-3">Información del Cliente</h6>
                                <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['NOMBRE'] . ' ' . htmlspecialchars($cliente['APELLIDO'])) ?></p>
                                <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['TELEFONO']) ?></p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="border p-3 rounded">
                                <h6 class="mb-3">Estado de Cuenta</h6>
                                <p><strong>Trabajos pendientes:</strong> <?= count($trabajosPendientes) ?></p>
                                <p><strong>Total pendiente:</strong> $<?= number_format($totalPendiente, 2, ',', '.') ?></p>
                                <p class="fs-5 fw-bold <?= $saldoCliente >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <strong>Saldo actual:</strong> $<?= number_format(abs($saldoCliente), 2, ',', '.') ?>
                                    <small class="d-block">(<?= $saldoCliente >= 0 ? 'Saldo a favor' : 'Saldo deudor' ?>)</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulario para operaciones -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="operacionesTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="deposito-tab" data-bs-toggle="tab" 
                                            data-bs-target="#deposito" type="button" role="tab">
                                        <i class="bi bi-plus-circle"></i> Depósito
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pago-tab" data-bs-toggle="tab" 
                                            data-bs-target="#pago" type="button" role="tab">
                                        <i class="bi bi-cash"></i> Pago
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="ajuste-tab" data-bs-toggle="tab" 
                                            data-bs-target="#ajuste" type="button" role="tab">
                                        <i class="bi bi-sliders"></i> Ajuste
                                    </button>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card-body">
                            <div class="tab-content" id="operacionesTabContent">
                                <!-- Pestaña Depósito -->
                                <div class="tab-pane fade show active" id="deposito" role="tabpanel">
                                    <form id="formDeposito" method="POST" action="procesar_operacion_ctacte.php">
                                        <input type="hidden" name="idCliente" value="<?= $idCliente ?>">
                                        <input type="hidden" name="tipo" value="DEPOSITO">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="montoDeposito" class="form-label">Monto</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" min="0.01" class="form-control" 
                                                           id="montoDeposito" name="monto" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="metodoDeposito" class="form-label">Método</label>
                                                <select class="form-select" id="metodoDeposito" name="metodo" required>
                                                    <option value="EFECTIVO">Efectivo</option>
                                                    <option value="TRANSFERENCIA">Transferencia</option>
                                                    <option value="CHEQUE">Cheque</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="observacionesDeposito" class="form-label">Observaciones</label>
                                                <textarea class="form-control" id="observacionesDeposito" 
                                                          name="observaciones" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check-circle"></i> Registrar Depósito
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Pestaña Pago -->
                                <div class="tab-pane fade" id="pago" role="tabpanel">
                                    <form id="formPago" method="POST" action="procesar_operacion_ctacte.php">
                                        <input type="hidden" name="idCliente" value="<?= $idCliente ?>">
                                        <input type="hidden" name="tipo" value="PAGO">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="montoPago" class="form-label">Monto</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control" 
                                                           id="montoPago" name="monto" required readonly>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="metodoPago" class="form-label">Método</label>
                                                <select class="form-select" id="metodoPago" name="metodo" required>
                                                    <option value="EFECTIVO">Efectivo</option>
                                                    <option value="TRANSFERENCIA">Transferencia</option>
                                                    <option value="CHEQUE">Cheque</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="trabajoPago" class="form-label">Seleccione un trabajo</label>
                                                <select class="form-select" id="trabajoPago" name="idReferencia" required>
                                                    <option value="">-- Seleccione un trabajo --</option>
                                                    <?php foreach ($trabajosPendientes as $trabajo): ?>
                                                    <option value="<?= $trabajo['ID_DETALLE'] ?>" data-precio="<?= $trabajo['PRECIO'] ?>">
                                                        #<?= $trabajo['ID_DETALLE'] ?> - <?= substr($trabajo['DESCRIPCION'], 0, 30) ?>... ($<?= number_format($trabajo['PRECIO'], 2, ',', '.') ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="observacionesPago" class="form-label">Observaciones</label>
                                                <textarea class="form-control" id="observacionesPago" 
                                                          name="observaciones" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="bi bi-check-circle"></i> Registrar Pago
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Pestaña Ajuste -->
                                <div class="tab-pane fade" id="ajuste" role="tabpanel">
                                    <form id="formAjuste" method="POST" action="procesar_operacion_ctacte.php">
                                        <input type="hidden" name="idCliente" value="<?= $idCliente ?>">
                                        <input type="hidden" name="tipo" value="AJUSTE">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="montoAjuste" class="form-label">Monto</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control" 
                                                           id="montoAjuste" name="monto" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="tipoAjuste" class="form-label">Tipo de ajuste</label>
                                                <select class="form-select" id="tipoAjuste" name="tipoAjuste" required>
                                                    <option value="INCREMENTO">Incremento</option>
                                                    <option value="DECREMENTO">Decremento</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="motivoAjuste" class="form-label">Motivo del ajuste</label>
                                                <select class="form-select" id="motivoAjuste" name="motivo" required>
                                                    <option value="CORRECCION">Corrección de error</option>
                                                    <option value="DESCUENTO">Descuento especial</option>
                                                    <option value="INTERES">Interés/recargo</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="observacionesAjuste" class="form-label">Observaciones</label>
                                                <textarea class="form-control" id="observacionesAjuste" 
                                                          name="observaciones" rows="2" required></textarea>
                                            </div>
                                            
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="bi bi-check-circle"></i> Aplicar Ajuste
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de movimientos -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Historial de Movimientos</h5>
                            <a href="historial_completo.php?idCliente=<?= $idCliente ?>" class="btn btn-sm btn-outline-primary">
                                Ver completo
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th class="text-end">Monto</th>
                                            <th>Detalle</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($movimientos)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">No hay movimientos registrados</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($movimientos as $mov): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
                                                <td>
                                                    <?php 
                                                    $badgeClass = [
                                                        'DEPOSITO' => 'bg-success',
                                                        'PAGO' => 'bg-primary',
                                                        'AJUSTE' => 'bg-warning'
                                                    ][$mov['tipo']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= $mov['tipo'] ?></span>
                                                </td>
                                                <td class="text-end <?= $mov['tipo'] == 'DEPOSITO' ? 'text-success' : 'text-danger' ?>">
                                                    $<?= number_format($mov['monto'], 2, ',', '.') ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars(substr($mov['observaciones'], 0, 30)) ?>
                                                    <?= strlen($mov['observaciones']) > 30 ? '...' : '' ?>
                                                </td>
                                                <td><?= htmlspecialchars($mov['usuarioNombre']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Resumen de trabajos pendientes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trabajos Pendientes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trabajosPendientes)): ?>
                        <div class="alert alert-info mb-0">No hay trabajos pendientes</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($trabajosPendientes as $trabajo): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">#<?= $trabajo['ID_DETALLE'] ?></h6>
                                    <small><?= date('d/m/Y', strtotime($trabajo['FECHA_PEDIDO'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($trabajo['TIPO_TRABAJO']) ?></p>
                                <small><?= htmlspecialchars(substr($trabajo['DESCRIPCION'], 0, 50)) ?>...</small>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="fw-bold">$<?= number_format($trabajo['PRECIO'], 2, ',', '.') ?></span>
                                    <span class="badge bg-secondary"><?= $trabajo['ESTADO'] ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Resumen rápido de saldos -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resumen de Saldos</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total trabajos pendientes:</span>
                        <span class="fw-bold">$<?= number_format($totalPendiente, 2, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Saldo en cuenta:</span>
                        <span class="fw-bold <?= $saldoCliente >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format(abs($saldoCliente), 2, ',', '.') ?>
                        </span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Saldo proyectado:</span>
                        <span class="fw-bold <?= ($saldoCliente - $totalPendiente) >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format(abs($saldoCliente - $totalPendiente), 2, ',', '.') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require ('../shared/footer.inc.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar el formulario de depósito con AJAX
    document.getElementById('formDeposito').addEventListener('submit', function(e) {
        e.preventDefault();
        procesarOperacion(this);
    });
    
    // Manejar el formulario de pago con AJAX
    document.getElementById('formPago').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar que se haya seleccionado un trabajo
        const trabajoSelect = document.getElementById('trabajoPago');
        if (!trabajoSelect.value) {
            alert('Debe seleccionar un trabajo para realizar el pago');
            return;
        }
        
        procesarOperacion(this);
    });
    
    // Manejar el formulario de ajuste con AJAX
    document.getElementById('formAjuste').addEventListener('submit', function(e) {
        e.preventDefault();
        procesarOperacion(this);
    });
    
    function procesarOperacion(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Mostrar spinner
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        fetch(form.action, {
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
                location.reload(); // Recargar para ver cambios
            } else {
                throw new Error(data.message || 'Error al procesar la operación');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
    // Actualizar monto de pago cuando se selecciona un trabajo
    document.getElementById('trabajoPago').addEventListener('change', function() {
        const montoPago = document.getElementById('montoPago');
        if (this.value) {
            const precio = this.options[this.selectedIndex].getAttribute('data-precio');
            montoPago.value = parseFloat(precio).toFixed(2);
        } else {
            montoPago.value = '';
        }
    });

    // Validar antes de cambiar a pestaña de pago
    const tabPago = document.getElementById('pago-tab');
    tabPago.addEventListener('click', function(e) {
        const trabajoSelect = document.getElementById('trabajoPago');
        if (trabajoSelect.options.length <= 1) { // Solo tiene la opción vacía
            e.preventDefault();
            alert('No hay trabajos pendientes para realizar pagos');
            document.getElementById('deposito-tab').click();
        }
    });
});
</script>
</body>
</html>