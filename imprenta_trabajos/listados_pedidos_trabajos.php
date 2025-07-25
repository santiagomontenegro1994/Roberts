<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('../shared/barraLateral.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

//voy a necesitar la conexion: incluyo la funcion de Conexion.
require_once '../funciones/conexion.php';

//genero una variable para usar mi conexion desde donde me haga falta
//no envio parametros porque ya los tiene definidos por defecto
$MiConexion = ConexionBD();

//ahora voy a llamar el script con la funcion que genera mi listado
require_once '../funciones/imprenta.php';


//voy a ir listando lo necesario para trabajar en este script: 
$ListadoPedidos = Listar_Pedidos_Trabajos($MiConexion);
$CantidadPedidos = count($ListadoPedidos);

  //estoy en condiciones de poder buscar segun el parametro
  
    if (!empty($_POST['BotonBuscar'])) {

        $parametro = $_POST['parametro'];
        $criterio = $_POST['gridRadios'];
        $ListadoPedidos=Listar_Pedidos_Trabajo_Parametro($MiConexion,$criterio,$parametro);
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
</div><!-- End Page Title -->

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
                <input type="text" class="form-control" name="parametro" id="parametro">
                </div>

                <style> .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.2rem; } </style>

              <div class="col-sm-3 mt-2">
                <button type="submit" class="btn btn-success btn-xs d-inline-block" value="buscar" name="BotonBuscar">Buscar</button>
                <button type="submit" class="btn btn-danger btn-xs d-inline-block" value="limpiar" name="BotonLimpiar">Limpiar</button>
                <a href="imprimir_listado.php">
                          <i class="btn btn-primary btn-xs d-inline-block">Descargar</i>
                          </a>
              </div>
              <div class="col-sm-5 mt-2">
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Cliente" checked>
                      <label class="form-check-label fs-7" for="gridRadios1">
                      Cliente
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Fecha">
                      <label class="form-check-label fs-7" for="gridRadios2">
                        Fecha
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="Telefono">
                      <label class="form-check-label fs-7" for="gridRadios3">
                      Telefono
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios4" value="Id">
                      <label class="form-check-label fs-7" for="gridRadios4">
                      ID
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios5" value="Estado">
                      <label class="form-check-label fs-7" for="gridRadios5">
                        Estado
                    </div>
                    
              </div>
              
          </div>
          </form>
          <!-- Table with stripped rows -->
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
                  // Calcular la cantidad de trabajos asociados (si tienes una función, si no, puedes omitir)
                  // $cantidad = Contar_Trabajos($MiConexion, $ListadoPedidos[$i]['ID']);
                  $cantidad = 1; // O ajusta según tu lógica

                  // Calcular el saldo
                  $saldo = $ListadoPedidos[$i]['PRECIO'] - $ListadoPedidos[$i]['SEÑA'];

                  // Obtener el color y título de la fila según el estado
                  list($Title, $Color) = ColorDeFilaPedidoTrabajo($ListadoPedidos[$i]['ESTADO']);
                ?>
                  <tr class="<?php echo $Color; ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?php echo $Title; ?>">
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['ID']; ?></td>
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['FECHA']; ?></td>
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['CLIENTE_N']; ?> <?php echo $ListadoPedidos[$i]['CLIENTE_A']; ?></td>
                    <td class="extra-small"><?php echo $cantidad; ?> trabajo/s</td>
                    <td class="extra-small">$<?php echo number_format($ListadoPedidos[$i]['PRECIO'], 2); ?></td>
                    <td class="extra-small">$<?php echo number_format($ListadoPedidos[$i]['SEÑA'], 2); ?></td>
                    <td class="extra-small">$<?php echo number_format($saldo, 2); ?></td>
                    <td class="extra-small"><?php echo $ListadoPedidos[$i]['USUARIO']; ?></td>
                    <td class="extra-small">
                      <!-- Acciones -->
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
          <!-- End Table with stripped rows -->

        </div>
    </div>
 
</section>

</main><!-- End #main -->

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
                            // Asegúrate de tener $TiposPagosEntrada disponible
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
// Configurar modal de retiro de pedido
document.addEventListener('DOMContentLoaded', function() {
    const retirarPedidoModal = new bootstrap.Modal(document.getElementById('retirarPedidoModal'));
    
    // Configurar modal cuando se abre
    document.getElementById('retirarPedidoModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const pedidoId = button.getAttribute('data-pedido-id');
        const saldo = button.getAttribute('data-pedido-saldo');
        
        document.getElementById('retirarPedidoId').value = pedidoId;
        document.getElementById('retirarPedidoSaldo').value = '$' + parseFloat(saldo).toFixed(2);
    });
    
    // Manejar envío del formulario
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