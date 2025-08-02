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
$ListadoPedidos = Listar_Pedidos_Trabajos_Detallado($MiConexion);
$CantidadPedidos = count($ListadoPedidos);

if (!empty($_POST['BotonBuscar'])) {
    $parametro = $_POST['parametro'] ?? '';
    $criterio = $_POST['gridRadios'] ?? 'Cliente';
    $estadoBuscado = $_POST['estadoBuscado'] ?? '';
    
    if (!empty($estadoBuscado)) {
        $ListadoPedidos = Listar_Pedidos_Trabajo_Por_Estado($MiConexion, $estadoBuscado);
    } else {
        $ListadoPedidos = Listar_Pedidos_Trabajo_Parametro_Detallado($MiConexion, $criterio, $parametro);
    }
    $CantidadPedidos = count($ListadoPedidos);
} elseif (!empty($_POST['BotonLimpiar'])) {
    $_POST = array();
    $ListadoPedidos = Listar_Pedidos_Trabajos_Detallado($MiConexion);
    $CantidadPedidos = count($ListadoPedidos);
}
?>

<main id="main" class="main">
<div class="pagetitle">
  <h1>Listado de Pedidos Trabajos</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
      <li class="breadcrumb-item">Pedidos Trabajos</li>
      <li class="breadcrumb-item active">Listado Pedidos Trabajos</li>
    </ol>
  </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
          <h5 class="card-title">Listado Pedidos Trabajos</h5>
          <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
              <?php echo $_SESSION['Mensaje'] ?>
            </div>
          <?php } ?>

          <Form method="POST">
          <div class="row mb-4">
            <label for="inputEmail3" class="col-sm-1 col-form-label">Buscar</label>
              <div class="col-sm-3">
                <input type="text" class="form-control" name="parametro" id="parametro" value="<?php echo !empty($_POST['parametro']) ? htmlspecialchars($_POST['parametro']) : ''; ?>">
              </div>

              <style> .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.2rem; } </style>

              <div class="col-sm-2 mt-2">
                <button type="submit" class="btn btn-success btn-xs d-inline-block" value="buscar" name="BotonBuscar">Buscar</button>
                <button type="submit" class="btn btn-danger btn-xs d-inline-block" value="limpiar" name="BotonLimpiar">Limpiar</button>
              </div>
              <div class="col-sm-6 mt-2 small">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-check form-check-inline form-check-sm">
                          <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Cliente" <?php echo (empty($_POST['gridRadios']) || $_POST['gridRadios'] == 'Cliente') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="gridRadios1">Cliente</label>
                        </div>
                        <div class="form-check form-check-inline form-check-sm">
                          <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Fecha" <?php echo (!empty($_POST['gridRadios']) && $_POST['gridRadios'] == 'Fecha') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="gridRadios2">Fecha</label>
                        </div>
                        <div class="form-check form-check-inline form-check-sm">
                          <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="Telefono" <?php echo (!empty($_POST['gridRadios']) && $_POST['gridRadios'] == 'Telefono') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="gridRadios3">Telefono</label>
                        </div>
                        <div class="form-check form-check-inline form-check-sm">
                          <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios4" value="Id" <?php echo (!empty($_POST['gridRadios']) && $_POST['gridRadios'] == 'Id') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="gridRadios4">ID</label>
                        </div>
                    </div>
                    <div>
                      <select class="form-select form-select-sm" name="estadoBuscado" id="estadoBuscado">
                          <option value="">Todos los estados</option>
                          <?php 
                          $estados = Datos_Estados_Pedido_Trabajo($MiConexion);
                          foreach ($estados as $estado): ?>
                              <option value="<?php echo $estado['idEstado']; ?>"
                                  <?php echo (!empty($_POST['estadoBuscado']) && $_POST['estadoBuscado'] == $estado['idEstado']) ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($estado['denominacion']); ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                    </div>
                </div>
              </div>
          </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped table-sm small">
              <thead>
                <tr class="fs-6">
                  <th scope="col">ID</th>
                  <th scope="col">Fecha</th>
                  <th scope="col">Cliente</th>
                  <th scope="col">Detalle</th>
                  <th scope="col">Precio</th>
                  <th scope="col">Seña</th>
                  <th scope="col">Saldo</th>
                  <th scope="col">Tomado</th>
                  <th scope="col">Acciones</th>
                </tr>
              </thead>
              <tbody class="fs-6">
                <?php for ($i=0; $i<$CantidadPedidos; $i++) { 
                  $saldo = $ListadoPedidos[$i]['PRECIO'] - $ListadoPedidos[$i]['SEÑA'];
                  list($Title, $Color) = ColorDeFilaPedidoTrabajo($ListadoPedidos[$i]['ESTADO']);
                  $nombreCliente = htmlspecialchars($ListadoPedidos[$i]['CLIENTE_N'] . ' ' . $ListadoPedidos[$i]['CLIENTE_A']);
                  $nombreMostrar = (strlen($nombreCliente) > 20) ? substr($nombreCliente, 0, 20) . '...' : $nombreCliente;
                ?>
                <tr class="<?php echo $Color; ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?php echo $Title; ?>">
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['ID']; ?></td>
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['FECHA']; ?></td>
                    <td class="extra-small">
                        <strong title="<?php echo htmlspecialchars($nombreCliente); ?>"><?php echo $nombreMostrar; ?></strong>
                        <?php if (!empty($ListadoPedidos[$i]['TELEFONO'])): ?>
                            <br><small class="text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($ListadoPedidos[$i]['TELEFONO']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                      <div class="dropdown">
                          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownTrabajos<?php echo $i; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                              Ver trabajos (<?php echo count($ListadoPedidos[$i]['TRABAJOS']); ?>)
                          </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownTrabajos<?php echo $i; ?>">
                            <?php if (!empty($ListadoPedidos[$i]['TRABAJOS'])): ?>
                                <?php foreach ($ListadoPedidos[$i]['TRABAJOS'] as $trabajo): ?>
                                    <li>
                                        <span class="dropdown-item-text">
                                            <strong><?php echo htmlspecialchars($trabajo['DENOMINACION']); ?></strong>
                                            <br>
                                            <small><?php echo htmlspecialchars($trabajo['DESCRIPCION']); ?></small>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><span class="dropdown-item-text">Sin trabajos</span></li>
                            <?php endif; ?>
                        </ul>
                      </div>
                  </td>
                    <td class="extra-small">$<?php echo number_format($ListadoPedidos[$i]['PRECIO'], 2); ?></td>
                    <td class="extra-small">$<?php echo number_format($ListadoPedidos[$i]['SEÑA'], 2); ?></td>
                    <td class="extra-small">$<?php echo number_format($saldo, 2); ?></td>
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['USUARIO']; ?></td>
                    <td class="extra-small">
                      <a href="eliminar_pedido_trabajo.php?ID_PEDIDO=<?php echo $ListadoPedidos[$i]['ID']; ?>" 
                        class="btn btn-xs btn-danger me-2"
                        title="Anular" 
                        onclick="return confirm('Confirma anular este Pedido?');">
                        <i class="bi bi-trash-fill"></i>
                      </a>

                      <a href="modificar_pedidos_trabajos.php?ID_PEDIDO=<?php echo $ListadoPedidos[$i]['ID']; ?>"
                        class="btn btn-xs btn-warning me-2" 
                        title="Modificar">
                        <i class="bi bi-pencil-fill"></i>
                      </a>

                      <a href="imprimir_pedido_trabajo.php?ID_PEDIDO=<?php echo $ListadoPedidos[$i]['ID']; ?>"
                        class="btn btn-xs btn-primary me-2" 
                        title="Imprimir">
                        <i class="bi bi-printer-fill"></i>
                      </a>

                        <button type="button" class="btn btn-xs btn-success me-2" 
                                data-bs-toggle="modal" data-bs-target="#retirarPedidoModal"
                                data-pedido-id="<?php echo $ListadoPedidos[$i]['ID']; ?>"
                                data-pedido-saldo="<?php echo $saldo; ?>"
                                title="Retirar Pedido">
                          <i class="bi bi-box-seam"></i> Retirar
                        </button>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
    </div>
</section>

</main>

<!-- Modal Retirar Pedido -->
<div class="modal fade" id="retirarPedidoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Retirar Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRetirarPedido" action="procesar_retiro_pedido.php" method="post">
                    <input type="hidden" name="idPedido" id="retirarPedidoId">
                    
                    <div class="mb-3">
                        <label class="form-label">Saldo a pagar:</label>
                        <input type="text" class="form-control" id="retirarPedidoSaldo" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Método de pago:</label>
                        <select class="form-select" name="metodoPago" required>
                            <?php 
                            $TiposPagosEntrada = Listar_Tipos_Pagos_Entrada($MiConexion);
                            foreach ($TiposPagosEntrada as $metodo): ?>
                                <option value="<?php echo $metodo['idTipoPago']; ?>">
                                    <?php echo htmlspecialchars($metodo['denominacion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Retiro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
  $_SESSION['Mensaje']='';
  require ('../shared/footer.inc.php');
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const retirarPedidoModal = new bootstrap.Modal(document.getElementById('retirarPedidoModal'));
    
    document.getElementById('retirarPedidoModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const pedidoId = button.getAttribute('data-pedido-id');
        const saldo = button.getAttribute('data-pedido-saldo');
        
        document.getElementById('retirarPedidoId').value = pedidoId;
        document.getElementById('retirarPedidoSaldo').value = '$' + parseFloat(saldo).toFixed(2);
    });
    
    document.getElementById('formRetirarPedido').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (confirm('¿Confirmar retiro del pedido? Esta acción no se puede deshacer.')) {
            this.submit();
        }
    });
});
</script>

</body>
</html>