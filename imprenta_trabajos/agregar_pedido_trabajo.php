<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) {
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');

require_once '../funciones/conexion.php';
$MiConexion=ConexionBD();

require_once '../funciones/imprenta.php';

$estados = Datos_Estados_Trabajo($MiConexion);
$trabajos = Datos_Trabajos($MiConexion);
$proveedores = Listar_Proveedores($MiConexion);
$TiposPagos = Listar_Tipos_Pagos_Entrada($MiConexion);
?>

<main id="main" class="main">

<div class="pagetitle">
  <h1 style="font-size:1.25rem; margin-bottom:0.25rem;">Pedidos de Trabajos</h1>
  <nav>
    <ol class="breadcrumb" style="font-size:0.85rem; margin-bottom:0.3rem;">
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
      <li class="breadcrumb-item">Pedidos de Trabajos</li>
      <li class="breadcrumb-item active">Agregar Pedido</li>
    </ol>
  </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-start align-items-center mb-0"> 
                <h6 class="card-title mr-2" style="margin-bottom:0;">Datos de Cliente</h6> 
                <a href="#" class="btn btn-primary btn_new_cliente_imprenta">Nuevo Cliente</a>
            </div>

            <form class="row g-1" id="formularioClientePedidoImprenta" name="form_new_cliente_pedido">
                <input type="hidden" name="action" value="addCliente_imprenta">
                <input type="hidden" name="idCliente_imprenta" id="idCliente_imprenta">

                <div class="col-md-4 mb-1">
                    <label for="fecha" class="form-label">Telefono</label>
                    <input type="number" class="form-control form-control-sm"  name="tel_cliente_imprenta" id="tel_cliente_imprenta">
                </div>
                <div class="col-md-4 mb-1">
                    <label for="fecha" class="form-label">Nombre</label>
                    <input type="text" class="form-control form-control-sm"  name="nom_cliente_imprenta" id="nom_cliente_imprenta" disabled required>
                </div>
                <div class="col-md-4 mb-1">
                    <label for="fecha" class="form-label">Apellido</label>
                    <input type="text" class="form-control form-control-sm"  name="ape_cliente_imprenta" id="ape_cliente_imprenta" disabled>
                </div>

                <div class="text-center" id="div_registro_cliente_imprenta" style="display: none;">
                    <button type="submit" class="btn btn-primary btn-sm">Registrar</button>
                </div>
            </form>
        </div>
    </div>   
    
    <div class="card">
        <div class="card-body">
            <h6 class="card-title mb-2">Datos del Pedido</h6>
            
            <!-- Primera fila -->
            <div class="row datos-pedido-row">
                <div class="col-md-3">
                    <label for="estado_trabajo" class="form-label">Estado del Pedido</label>
                    <select class="form-select" id="estado_trabajo" name="estado_trabajo" required>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['idEstado'] ?>">
                                <?= htmlspecialchars($estado['denominacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="tipo_trabajo" class="form-label">Trabajo</label>
                    <select class="form-select" id="tipo_trabajo" name="tipo_trabajo" required>
                        <?php foreach ($trabajos as $trabajo): ?>
                            <option value="<?= $trabajo['idTipoTrabajo'] ?>" 
                            <?= ($trabajo['idTipoTrabajo'] == 6) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($trabajo['denominacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder="Detalles del trabajo" value="">
                </div>
            </div>
            
            <!-- Segunda fila -->
            <div class="row datos-pedido-row">
                <div class="col-md-2">
                    <label for="enviado" class="form-label">Enviado a</label>
                    <select class="form-select" id="enviado" name="enviado" required>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['ID_PROVEEDOR'] ?>" <?= ($proveedor['ID_PROVEEDOR'] == 7) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proveedor['NOMBRE']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="fecha_entrega_date">Fecha Entrega</label>
                    <input type="date" class="form-control" id="fecha_entrega_date" min="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="hora_entrega">Hora Entrega</label>
                    <select class="form-select" id="hora_entrega">
                        <option value="08:30">8:30</option>
                        <option value="09:00">9:00</option>
                        <option value="09:30">9:30</option>
                        <option value="10:00">10:00</option>
                        <option value="10:30">10:30</option>
                        <option value="11:00">11:00</option>
                        <option value="11:30">11:30</option>
                        <option value="12:00">12:00</option>
                        <option value="12:30">12:30</option>
                        <option value="16:00">16:00</option>
                        <option value="16:30">16:30</option>
                        <option value="17:00">17:00</option>
                        <option value="17:30">17:30</option>
                        <option value="18:00">18:00</option>
                        <option value="18:30">18:30</option>
                        <option value="19:00">19:00</option>
                        <option value="19:30">19:30</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="precio" class="form-label">Precio ($)</label>
                    <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" value="0.00">
                </div>

                <div class="col-md-4 btn-agregar-col">
                    <a href="#" id="add_trabajo_pedido" class="text-primary fw-bold">
                        <i class="bi bi-arrow-down-circle-fill"></i> Agregar a Pedido
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr class="table-primary">
                    <th scope="col">Estado</th>
                    <th scope="col">Trabajo</th>
                    <th scope="col">Enviado a</th>
                    <th scope="col">Fecha Entrega</th>
                    <th scope="col">Hora Entrega</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Accion</th>
                </tr>
            </thead>
            <tbody id="detalleVentaTrabajo">
                <!-- CONTENIDO AJAX-->
            </tbody>
            <tfoot id="detalleTotalTrabajo">
                <!-- CONTENIDO AJAX-->
            </tfoot>
        </table>           
    </div>

    <div class="d-flex justify-content-center align-items-center"> 
        <a href="#" class="btn btn-danger btn-sm m-2" id="btn_anular_pedido_trabajo">Anular</a> 
        <a href="#" class="btn btn-primary btn-sm m-2" id="btn_new_pedido_trabajo">Crear Pedido</a>
    </div>
</section>        

<!-- Modal para selección de método de pago ------------------------------------------------ -->
<div class="modal fade" id="pagoModal" tabindex="-1" aria-labelledby="pagoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6" id="pagoModalLabel">Registrar Pago de Seña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row mb-3">
                        <div class="col-12 text-center">
                            <h6 class="fs-6">Monto de la Seña</h6>
                            <input type="text" class="form-control text-center fs-5 fw-bold" id="montoPagoModal" readonly>
                        </div>
                    </div>
                    
                    <!-- Métodos de Pago -->
                    <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 card-title">Seleccione el Método de Pago</h6>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($TiposPagos as $tipo) { ?>
                            <button type="button" class="btn btn-secondary mx-2 my-2 metodo-pago" data-id="<?php echo $tipo['idTipoPago']; ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                        <input type="hidden" name="idTipoPagoModal" id="idTipoPagoModal">
                    </div>
                    
                     <!-- Observaciones (oculto) -->
                    <input type="hidden" id="observacionesModal" value="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="confirmarPago">Confirmar Pago</button>
            </div>
        </div>
    </div>
</div>

</main>

<?php
$_SESSION['Mensaje']='';
require ('../shared/footer.inc.php');
?>

<script>
$(document).ready(function() {
    searchforDetalleTrabajo();
});

    // Manejar la selección de los botones de Métodos de Pago
    const metodoPagoButtons = document.querySelectorAll('.metodo-pago');
    metodoPagoButtons.forEach(button => {
        button.addEventListener('click', () => {
            metodoPagoButtons.forEach(btn => btn.classList.remove('btn-primary')); // Quitar selección previa
            metodoPagoButtons.forEach(btn => btn.classList.add('btn-secondary')); // Restaurar estilo secundario
            button.classList.remove('btn-secondary'); // Quitar estilo secundario
            button.classList.add('btn-primary'); // Agregar estilo seleccionado
            document.getElementById('idTipoPagoModal').value = button.getAttribute('data-id'); // Asignar valor al input hidden
        });
    });
</script>

</body>
</html>
