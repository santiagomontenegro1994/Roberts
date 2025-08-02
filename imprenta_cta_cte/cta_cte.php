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
define('ESTADO_CUENTA_CORRIENTE', 8); // ID del estado para cuenta corriente en detalle_trabajos

// Procesar búsquedas
$parametro = $_POST['parametro'] ?? '';
$criterio = $_POST['gridRadios'] ?? 'Cliente';
$ClientesCuentaCorriente = array();

if (!empty($_POST['BotonBuscar'])) {
    $ClientesCuentaCorriente = Listar_Clientes_Cuenta_Corriente($MiConexion, $criterio, $parametro);
} else {
    $ClientesCuentaCorriente = Listar_Clientes_Cuenta_Corriente($MiConexion);
}

$CantidadClientes = count($ClientesCuentaCorriente);
?>

<main id="main" class="main">
<div class="pagetitle">
  <h1>Cuenta Corriente - Clientes</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
      <li class="breadcrumb-item">Clientes</li>
      <li class="breadcrumb-item active">Cuenta Corriente</li>
    </ol>
  </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Listado de Clientes con Cuenta Corriente</h5>
            
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
                            <a href="cta_cte.php" class="btn btn-secondary">
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
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Telefono"
                                   <?= $criterio == 'Telefono' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios2">Teléfono</label>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col" class="text-end">Total Deuda</th>
                            <th scope="col" class="text-end">Trabajos CC</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($CantidadClientes > 0): ?>
                            <?php foreach ($ClientesCuentaCorriente as $cliente): ?>
                                <tr>
                                    <td><?= $cliente['ID_CLIENTE'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($cliente['NOMBRE'] . ' ' . htmlspecialchars($cliente['APELLIDO'])) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($cliente['TELEFONO']) ?></td>
                                    <td class="text-end fw-bold <?= $cliente['TOTAL_DEUDA'] > 0 ? 'text-danger' : 'text-success' ?>">
                                        $<?= number_format($cliente['TOTAL_DEUDA'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end"><?= $cliente['CANTIDAD_TRABAJOS'] ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="detalle_cta_cte.php?idCliente=<?= $cliente['ID_CLIENTE'] ?>"
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver detalle">
                                                <i class="bi bi-eye"></i> Detalle
                                            </a>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" data-bs-target="#pagarCuentaCorrienteModal"
                                                    data-cliente-id="<?= $cliente['ID_CLIENTE'] ?>"
                                                    data-cliente-nombre="<?= htmlspecialchars($cliente['NOMBRE']) . ' ' . htmlspecialchars($cliente['APELLIDO']) ?>"
                                                    data-cliente-saldo="<?= $cliente['TOTAL_DEUDA'] ?>"
                                                    title="Registrar pago">
                                                <i class="bi bi-cash"></i> Pagar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No hay clientes con cuenta corriente</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

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
                    <input type="hidden" name="idCliente" id="pagarClienteId">
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente:</label>
                        <input type="text" class="form-control" id="pagarClienteNombre" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo pendiente:</label>
                        <input type="text" class="form-control" id="pagarClienteSaldo" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="montoPago" class="form-label">Monto a pagar:</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="montoPago" id="montoPago" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="metodoPago" class="form-label">Método de pago:</label>
                        <select class="form-select" name="metodoPago" id="metodoPago" required>
                            <?php 
                            $TiposPagosEntrada = Listar_Tipos_Pagos_Entradas($MiConexion);
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
    // Configurar modal de pago
    const pagoModal = new bootstrap.Modal(document.getElementById('pagarCuentaCorrienteModal'));
    
    document.getElementById('pagarCuentaCorrienteModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const clienteId = button.getAttribute('data-cliente-id');
        const clienteNombre = button.getAttribute('data-cliente-nombre');
        const saldo = button.getAttribute('data-cliente-saldo');
        
        document.getElementById('pagarClienteId').value = clienteId;
        document.getElementById('pagarClienteNombre').value = clienteNombre;
        document.getElementById('pagarClienteSaldo').value = '$' + parseFloat(saldo).toFixed(2);
        document.getElementById('montoPago').value = parseFloat(saldo).toFixed(2);
        document.getElementById('montoPago').max = saldo;
    });
    
    // Manejar envío del formulario de pago
    document.getElementById('formPagarCuentaCorriente').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const montoPago = parseFloat(document.getElementById('montoPago').value);
        const saldo = parseFloat(document.getElementById('pagarClienteSaldo').value.replace('$', ''));
        
        if (montoPago <= 0) {
            alert('El monto a pagar debe ser mayor que cero');
            return;
        }
        
        if (montoPago > saldo) {
            alert('El monto a pagar no puede ser mayor que el saldo pendiente');
            return;
        }
        
        if (confirm(`¿Confirmar pago de $${montoPago.toFixed(2)} para este cliente?`)) {
            const formData = new FormData(this);
            
            fetch('procesar_pago_cliente_cta_cte.php', {
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