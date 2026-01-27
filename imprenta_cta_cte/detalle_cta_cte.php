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

// Obtener trabajos cta cte (estado cuenta corriente)
$trabajosPendientes = Obtener_Trabajos_Pendientes($MiConexion, $idCliente);
$totalPendiente = array_sum(array_column($trabajosPendientes, 'PRECIO'));

// Obtener tipos de pago de entrada (excluyendo Cta. Cte.)
$tiposPagoEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
$tiposPagoEntrada = array_filter($tiposPagoEntrada, function($tipo) {
    return $tipo['idTipoPago'] != 18; // Excluir Cta. Cte. (id 18)
});
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
                                <p><strong>Trabajos cta cte:</strong> <?= count($trabajosPendientes) ?></p>
                                <p><strong>Total cta cte:</strong> $<?= number_format($totalPendiente, 2, ',', '.') ?></p>
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
                                        <i class="bi bi-cash"></i> Pago Directo
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
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle"></i> Los depósitos se aplicarán automáticamente a los trabajos más antiguos pendientes de pago.
                                    </div>
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
                                                    <?php foreach ($tiposPagoEntrada as $tipo): ?>
                                                        <option value="<?= $tipo['idTipoPago'] ?>"><?= htmlspecialchars($tipo['denominacion']) ?></option>
                                                    <?php endforeach; ?>
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
                                
                                <!-- Pestaña Pago Directo -->
                                <div class="tab-pane fade" id="pago" role="tabpanel">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle"></i> Seleccione un trabajo pendiente y elija cómo desea pagarlo.
                                    </div>
                                    <form id="formPago" method="POST" action="procesar_operacion_ctacte.php">
                                        <input type="hidden" name="idCliente" value="<?= $idCliente ?>">
                                        <input type="hidden" name="tipo" value="PAGO_DIRECTO">
                                        
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="trabajoPago" class="form-label">Seleccione un trabajo pendiente</label>
                                                <select class="form-select" id="trabajoPago" name="idReferencia" required>
                                                    <option value="">-- Seleccione un trabajo --</option>
                                                    <?php foreach ($trabajosPendientes as $trabajo): ?>
                                                    <option value="<?= $trabajo['ID_DETALLE'] ?>" data-precio="<?= $trabajo['PRECIO'] ?>">
                                                        #<?= $trabajo['ID_DETALLE'] ?> - <?= htmlspecialchars(substr($trabajo['DESCRIPCION'], 0, 30)) ?>... ($<?= number_format($trabajo['PRECIO'], 2, ',', '.') ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <!-- Información de pago (se muestra al seleccionar trabajo) -->
                                            <div id="infoPago" style="display: none;" class="col-12">
                                                <div class="card bg-light mt-3">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <strong>Precio del trabajo:</strong><br>
                                                                <span id="precioTrabajo" class="text-primary fs-5">$0.00</span>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <strong>Saldo disponible:</strong><br>
                                                                <span id="saldoDisponible" class="<?= $saldoCliente >= 0 ? 'text-success' : 'text-danger' ?> fs-5">
                                                                    $<?= number_format(max(0, $saldoCliente), 2, ',', '.') ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-check mt-3">
                                                            <input class="form-check-input" type="checkbox" id="usarSaldoCheckbox" <?= $saldoCliente > 0 ? 'checked' : '' ?> <?= $saldoCliente <= 0 ? 'disabled' : '' ?>>
                                                            <label class="form-check-label" for="usarSaldoCheckbox">
                                                                Usar saldo disponible
                                                            </label>
                                                        </div>
                                                        
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <label for="metodoPago" class="form-label">Método de pago</label>
                                                                <select class="form-select" id="metodoPago" name="metodo" required>
                                                                    <?php foreach ($tiposPagoEntrada as $tipo): ?>
                                                                        <option value="<?= $tipo['idTipoPago'] ?>"><?= htmlspecialchars($tipo['denominacion']) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="montoPagar" class="form-label">Monto a pagar</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">$</span>
                                                                    <input type="number" step="0.01" class="form-control" id="montoPagar" name="montoComplemento" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12 mt-3">
                                                    <label for="observacionesPago" class="form-label">Observaciones</label>
                                                    <textarea class="form-control" id="observacionesPago" name="observaciones" rows="2"></textarea>
                                                </div>
                                                
                                                <div class="col-12 text-end mt-3">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-check-circle"></i> Registrar Pago
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Pestaña Ajuste -->
                                <div class="tab-pane fade" id="ajuste" role="tabpanel">
                                    <div class="alert alert-warning mb-3">
                                        <i class="bi bi-exclamation-triangle"></i> Use esta opción solo para correcciones manuales del saldo.
                                    </div>
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
                                                    <option value="A_FAVOR">A favor del cliente</option>
                                                    <option value="EN_CONTRA">En contra del cliente</option>
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
                                                        'PAGO_DIRECTO' => 'bg-primary',
                                                        'AJUSTE' => 'bg-warning',
                                                        'APLICACION_AUTOMATICA' => 'bg-info'
                                                    ][$mov['tipo']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= $mov['tipo'] ?></span>
                                                </td>
                                                <td class="text-end <?= ($mov['tipo'] == 'DEPOSITO' || $mov['tipo'] == 'APLICACION_AUTOMATICA') ? 'text-success' : 'text-danger' ?>">
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
            <!-- Resumen de trabajos cta cte -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trabajos cta cte</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trabajosPendientes)): ?>
                        <div class="alert alert-info mb-0">No hay trabajos cta cte</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($trabajosPendientes as $trabajo): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Pedido #<?= $trabajo['ID_PEDIDO'] ?></h6>
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
                        <span>Total trabajos cta cte:</span>
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

<!-- Incluir SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saldoActual = <?= max(0, $saldoCliente) ?>; // Solo saldo positivo disponible
    
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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un trabajo para realizar el pago'
            });
            return;
        }
        
        // Validar que el monto a pagar sea correcto
        const montoPagar = parseFloat(document.getElementById('montoPagar').value);
        const precioTrabajo = parseFloat(trabajoSelect.options[trabajoSelect.selectedIndex].getAttribute('data-precio'));
        
        if (isNaN(montoPagar) || montoPagar < 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El monto a pagar debe ser mayor o igual a cero'
            });
            return;
        }
        
        // Verificar si usa saldo y validar montos
        const usarSaldo = document.getElementById('usarSaldoCheckbox').checked;
        let montoTotal = montoPagar;
        
        if (usarSaldo) {
            const montoUsarSaldo = Math.min(saldoActual, precioTrabajo);
            montoTotal = montoPagar + montoUsarSaldo;
        }
        
        if (Math.abs(montoTotal - precioTrabajo) > 0.01) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La suma de los montos no coincide con el precio del trabajo'
            });
            return;
        }
        
        // Agregar campo oculto para indicar si usa saldo
        let campoUsarSaldo = this.querySelector('input[name="usarSaldo"]');
        if (!campoUsarSaldo) {
            campoUsarSaldo = document.createElement('input');
            campoUsarSaldo.type = 'hidden';
            campoUsarSaldo.name = 'usarSaldo';
            this.appendChild(campoUsarSaldo);
        }
        campoUsarSaldo.value = usarSaldo ? '1' : '0';
        
        // Crear campo oculto para el monto (precio del trabajo)
        let campoMonto = this.querySelector('input[name="monto"]');
        if (!campoMonto) {
            campoMonto = document.createElement('input');
            campoMonto.type = 'hidden';
            campoMonto.name = 'monto';
            this.appendChild(campoMonto);
        }
        campoMonto.value = precioTrabajo.toFixed(2);
        
        procesarOperacion(this);
    });
    
    // Manejar el formulario de ajuste con AJAX
    document.getElementById('formAjuste').addEventListener('submit', function(e) {
        e.preventDefault();
        procesarOperacion(this);
    });
    
    // Actualizar información de pago cuando se selecciona un trabajo
    document.getElementById('trabajoPago').addEventListener('change', function() {
        const infoPago = document.getElementById('infoPago');
        const precioTrabajoSpan = document.getElementById('precioTrabajo');
        const saldoDisponibleSpan = document.getElementById('saldoDisponible');
        const usarSaldoCheckbox = document.getElementById('usarSaldoCheckbox');
        const montoPagarInput = document.getElementById('montoPagar');
        
        if (this.value) {
            const precio = parseFloat(this.options[this.selectedIndex].getAttribute('data-precio'));
            
            // Mostrar información básica
            precioTrabajoSpan.textContent = '$' + precio.toLocaleString('es-AR', {
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2
            });
            
            saldoDisponibleSpan.textContent = '$' + saldoActual.toLocaleString('es-AR', {
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2
            });
            
            // Mostrar toda la sección de información de pago
            infoPago.style.display = 'block';
            
            // Configurar el checkbox
            usarSaldoCheckbox.checked = saldoActual > 0;
            usarSaldoCheckbox.disabled = saldoActual <= 0;
            
            // Actualizar el monto a pagar
            calcularMontoPagar();
            
            // Mostrar mensaje si no hay saldo disponible
            if (saldoActual <= 0) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-info mt-3';
                alertDiv.innerHTML = '<i class="bi bi-info-circle"></i> No hay saldo disponible en la cuenta corriente';
                
                // Asegurarse de no duplicar el mensaje
                if (!document.getElementById('sinSaldoAlert')) {
                    alertDiv.id = 'sinSaldoAlert';
                    infoPago.insertBefore(alertDiv, infoPago.firstChild);
                }
            } else {
                const existingAlert = document.getElementById('sinSaldoAlert');
                if (existingAlert) {
                    existingAlert.remove();
                }
            }
        } else {
            // Ocultar toda la sección si no hay trabajo seleccionado
            infoPago.style.display = 'none';
        }
    });
    
    // Event listener para el checkbox de usar saldo
    document.getElementById('usarSaldoCheckbox').addEventListener('change', function() {
        calcularMontoPagar();
    });
    
    // Función para calcular el monto a pagar
    function calcularMontoPagar() {
        const trabajoSelect = document.getElementById('trabajoPago');
        const usarSaldoCheckbox = document.getElementById('usarSaldoCheckbox');
        const montoPagarInput = document.getElementById('montoPagar');
        
        if (!trabajoSelect.value) return;
        
        const precio = parseFloat(trabajoSelect.options[trabajoSelect.selectedIndex].getAttribute('data-precio'));
        const usarSaldo = usarSaldoCheckbox.checked;
        
        if (usarSaldo) {
            // Calcular cuánto se puede pagar con saldo
            const montoUsarSaldo = Math.min(saldoActual, precio);
            const montoRestante = precio - montoUsarSaldo;
            
            // Establecer el monto a pagar con otro método
            montoPagarInput.value = montoRestante.toFixed(2);
            montoPagarInput.required = montoRestante > 0;
        } else {
            // No usar saldo, pagar todo con otro método
            montoPagarInput.value = precio.toFixed(2);
            montoPagarInput.required = true;
        }
    }
    
    // Validar antes de cambiar a pestaña de pago
    const tabPago = document.getElementById('pago-tab');
    tabPago.addEventListener('click', function(e) {
        const trabajoSelect = document.getElementById('trabajoPago');
        if (trabajoSelect.options.length <= 1) { // Solo tiene la opción vacía
            e.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: 'No hay trabajos cta cte para realizar pagos'
            });
            document.getElementById('deposito-tab').click();
        }
    });
    
    // Función para procesar operaciones con AJAX
    function procesarOperacion(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Mostrar spinner y deshabilitar botón
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Mostrar overlay de carga
        const loadingOverlay = document.createElement('div');
        loadingOverlay.style.position = 'fixed';
        loadingOverlay.style.top = '0';
        loadingOverlay.style.left = '0';
        loadingOverlay.style.width = '100%';
        loadingOverlay.style.height = '100%';
        loadingOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        loadingOverlay.style.zIndex = '9999';
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.justifyContent = 'center';
        loadingOverlay.style.alignItems = 'center';
        loadingOverlay.innerHTML = '<div class="spinner-border text-light" style="width: 3rem; height: 3rem;"></div>';
        document.body.appendChild(loadingOverlay);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no JSON:', text);
                throw new Error('La respuesta del servidor no es válida');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error en la operación');
            }
            
            // Cerrar el overlay de carga inmediatamente
            if (document.body.contains(loadingOverlay)) {
                document.body.removeChild(loadingOverlay);
            }
            
            // Mostrar mensaje de éxito con SweetAlert
            return Swal.fire({
                icon: 'success',
                title: 'Operación exitosa',
                text: data.message,
                confirmButtonText: 'Aceptar',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Recargar la página después de hacer clic en Aceptar
                    location.reload();
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Cerrar el overlay de carga si hay error
            if (document.body.contains(loadingOverlay)) {
                document.body.removeChild(loadingOverlay);
            }
            
            // Mostrar mensaje de error con SweetAlert
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonText: 'Aceptar'
            });
        })
        .finally(() => {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
});
</script>
</body>
</html>