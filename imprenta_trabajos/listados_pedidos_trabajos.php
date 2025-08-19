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

// Inicializar variables de filtro desde la sesión
if (!isset($_SESSION['filtros_pedidos'])) {
    $_SESSION['filtros_pedidos'] = [
        'parametro' => '',
        'criterio' => 'Cliente',
        'estadoBuscado' => ''
    ];
}

// Procesar búsquedas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['BotonLimpiar'])) {
        // Limpiar todos los filtros
        $_SESSION['filtros_pedidos'] = [
            'parametro' => '',
            'criterio' => 'Cliente',
            'estadoBuscado' => ''
        ];
    } else {
        // Determinar qué tipo de búsqueda se está realizando
        $esBusquedaPorEstado = isset($_POST['estadoBuscado']) && $_POST['estadoBuscado'] != '';
        $esBusquedaPorParametro = isset($_POST['parametro']) && trim($_POST['parametro']) != '';
        
        if ($esBusquedaPorEstado) {
            // Búsqueda por estado - resetear parámetro de búsqueda
            $_SESSION['filtros_pedidos'] = [
                'parametro' => '',
                'criterio' => 'Cliente',
                'estadoBuscado' => $_POST['estadoBuscado']
            ];
        } elseif ($esBusquedaPorParametro) {
            // Búsqueda por parámetro - resetear estado
            $_SESSION['filtros_pedidos'] = [
                'parametro' => trim($_POST['parametro']),
                'criterio' => $_POST['gridRadios'] ?? 'Cliente',
                'estadoBuscado' => ''
            ];
        } else {
            // Ninguna búsqueda activa - mantener valores actuales
            $_SESSION['filtros_pedidos'] = [
                'parametro' => trim($_POST['parametro'] ?? ''),
                'criterio' => $_POST['gridRadios'] ?? 'Cliente',
                'estadoBuscado' => $_POST['estadoBuscado'] ?? ''
            ];
        }
    }
}

// Obtener valores actuales de los filtros
$parametro = $_SESSION['filtros_pedidos']['parametro'];
$criterio = $_SESSION['filtros_pedidos']['criterio'];
$estadoBuscado = $_SESSION['filtros_pedidos']['estadoBuscado'];

// Obtener datos según los filtros
if (!empty($estadoBuscado)) {
    $ListadoPedidos = Listar_Pedidos_Trabajo_Por_Estado($MiConexion, $estadoBuscado);
} elseif (!empty($parametro)) {
    $ListadoPedidos = Listar_Pedidos_Trabajo_Parametro_Detallado($MiConexion, $criterio, $parametro);
} else {
    $ListadoPedidos = Listar_Pedidos_Trabajos_Detallado($MiConexion);
}

$CantidadPedidos = count($ListadoPedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Pedidos Trabajos</title>
    <style>
        .badge-facturado {
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.75rem;
        }
        
        .table {
            font-size: 0.85rem;
        }
        
        .table td, .table th {
            vertical-align: middle;
            padding: 0.4rem 0.5rem;
        }
        
        .factura-info {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        /* Estilos específicos para los estados de facturación */
        .badge-bg-success, .badge-bg-warning, .badge-bg-secondary, .badge-bg-info {
            min-width: 80px;
            justify-content: center;
            font-size: 0.75rem;
            padding: 0.25rem 0.4rem;
        }
        
        .btn-group .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
        
        .dropdown-toggle::after {
            margin-left: 0.2rem;
        }
        
        .dropdown-menu {
            font-size: 0.8rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        /* Estilos para tooltips */
        .tooltip-inner {
            font-size: 0.8rem;
            max-width: 200px;
        }
        
        /* Estilos para textos compactos */
        .text-compact {
            font-size: 0.8rem;
            line-height: 1.2;
        }
        
        .text-tiny {
            font-size: 0.7rem;
        }
        
        /* Columnas específicas */
        .col-id {
            width: 50px;
        }
        
        .col-fecha {
            width: 90px;
        }
        
        .col-cliente {
            width: 120px;
            min-width: 120px;
        }
        
        .col-facturacion {
            width: 90px;
        }
        
        .col-detalle {
            width: 100px;
        }
        
        .col-precio {
            width: 80px;
        }
        
        .col-senia {
            width: 80px;
        }
        
        .col-saldo {
            width: 80px;
        }
        
        .col-tomado {
            width: 100px;
        }
        
        .col-acciones {
            width: 180px;
        }
    </style>
</head>
<body>

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
                <div class="alert alert-<?= $_SESSION['Estilo'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['Mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['Mensaje']); unset($_SESSION['Estilo']); ?>
            <?php } ?>

            <form method="POST" class="mb-3" id="formBusqueda">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="parametro" id="parametro" 
                               value="<?= htmlspecialchars($parametro) ?>" 
                               placeholder="Buscar...">
                    </div>
                    
                    <div class="col-md-4">
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary btn-sm" name="BotonBuscar" value="1">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <button type="submit" class="btn btn-secondary btn-sm" name="BotonLimpiar" value="1">
                                <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                            </button>
                            
                            <!-- Botón de descarga con dropdown -->
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-download"></i> Descargar
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="pendientes">Trabajos Pendientes</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="listos">Trabajos Listos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="impresos">Trabajos Para Taller</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Cliente" 
                                   <?= ($criterio == 'Cliente') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios1">Cliente</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Fecha"
                                   <?= ($criterio == 'Fecha') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios2">Fecha</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="Telefono"
                                   <?= ($criterio == 'Telefono') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios3">Teléfono</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios4" value="Id"
                                   <?= ($criterio == 'Id') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios4">ID</label>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="estadoBuscado" id="estadoBuscado">
                            <option value="">Todos los estados</option>
                            <?php 
                            $estados = Datos_Estados_Pedido_Trabajo($MiConexion);
                            foreach ($estados as $estado): ?>
                                <option value="<?= $estado['idEstado'] ?>"
                                    <?= ($estadoBuscado == $estado['idEstado']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['denominacion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="col-id">ID</th>
                            <th scope="col" class="col-fecha">Fecha</th>
                            <th scope="col" class="col-cliente">Cliente</th>
                            <th scope="col" class="col-facturacion">Facturación</th>
                            <th scope="col" class="col-detalle">Detalle</th>
                            <th scope="col" class="col-precio">Precio</th>
                            <th scope="col" class="col-senia">Seña</th>
                            <th scope="col" class="col-saldo">Saldo</th>
                            <th scope="col" class="col-tomado">Tomado</th>
                            <th scope="col" class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i=0; $i<$CantidadPedidos; $i++) { 
                            $saldo = $ListadoPedidos[$i]['PRECIO'] - $ListadoPedidos[$i]['SEÑA'];
                            list($Title, $Color) = ColorDeFilaPedidoTrabajo($ListadoPedidos[$i]['ESTADO']);
                            $nombreCliente = htmlspecialchars($ListadoPedidos[$i]['CLIENTE_N'] . ' ' . $ListadoPedidos[$i]['CLIENTE_A']);
                            $nombreMostrar = (strlen($nombreCliente) > 15) ? substr($nombreCliente, 0, 15) . '...' : $nombreCliente;
                            
                            // Determinar estado de facturación
                            $detallesFacturados = isset($ListadoPedidos[$i]['DETALLES_FACTURADOS']) ? $ListadoPedidos[$i]['DETALLES_FACTURADOS'] : 0;
                            $totalDetalles = isset($ListadoPedidos[$i]['TOTAL_DETALLES']) ? $ListadoPedidos[$i]['TOTAL_DETALLES'] : 0;
                            $estadoFacturacion = determinarEstadoFacturacion($detallesFacturados, $totalDetalles);
                        ?>
                        <tr class="<?= $Color ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= $Title ?>">
                            <td class="col-id"><?= $ListadoPedidos[$i]['ID'] ?></td>
                            <td class="col-fecha text-compact"><?= $ListadoPedidos[$i]['FECHA'] ?></td>
                            <td class="col-cliente">
                                <strong class="text-compact" title="<?= htmlspecialchars($nombreCliente) ?>"><?= $nombreMostrar ?></strong>
                                <?php if (!empty($ListadoPedidos[$i]['TELEFONO'])): ?>
                                    <br><small class="text-muted text-tiny"><i class="bi bi-telephone"></i> <?= htmlspecialchars($ListadoPedidos[$i]['TELEFONO']) ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Nueva columna para estado de facturación -->
                            <td class="col-facturacion text-center">
                                <?php if ($estadoFacturacion['estado'] == 'totalmente_facturado'): ?>
                                    <span class="badge bg-success d-inline-flex align-items-center" 
                                          data-bs-toggle="tooltip" title="<?= $estadoFacturacion['tooltip'] ?>">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        <span>Facturado</span>
                                    </span>
                                <?php elseif ($estadoFacturacion['estado'] == 'parcialmente_facturado'): ?>
                                    <span class="badge bg-warning text-dark d-inline-flex align-items-center" 
                                          data-bs-toggle="tooltip" title="<?= $estadoFacturacion['tooltip'] ?>">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                                        <span>Parcial</span>
                                    </span>
                                <?php elseif ($estadoFacturacion['estado'] == 'sin_detalles'): ?>
                                    <span class="badge bg-info d-inline-flex align-items-center" 
                                          data-bs-toggle="tooltip" title="<?= $estadoFacturacion['tooltip'] ?>">
                                        <i class="bi bi-info-circle-fill me-1"></i>
                                        <span>Sin detalles</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary d-inline-flex align-items-center" 
                                          data-bs-toggle="tooltip" title="<?= $estadoFacturacion['tooltip'] ?>">
                                        <i class="bi bi-x-circle-fill me-1"></i>
                                        <span>No facturado</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-detalle">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0" type="button" id="dropdownTrabajos<?= $i ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        Ver (<?= count($ListadoPedidos[$i]['TRABAJOS']) ?>)
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownTrabajos<?= $i ?>">
                                        <?php if (!empty($ListadoPedidos[$i]['TRABAJOS'])): ?>
                                            <?php foreach ($ListadoPedidos[$i]['TRABAJOS'] as $trabajo): ?>
                                                <li>
                                                    <span class="dropdown-item-text text-compact">
                                                        <strong><?= htmlspecialchars($trabajo['DENOMINACION']) ?></strong>
                                                        <br>
                                                        <small><?= htmlspecialchars($trabajo['DESCRIPCION']) ?></small>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li><span class="dropdown-item-text text-compact">Sin trabajos</span></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                            <td class="col-precio text-compact">$<?= number_format($ListadoPedidos[$i]['PRECIO'], 2) ?></td>
                            <td class="col-senia text-compact">$<?= number_format($ListadoPedidos[$i]['SEÑA'], 2) ?></td>
                            <td class="col-saldo text-compact">$<?= number_format($saldo, 2) ?></td>
                            <td class="col-tomado text-compact"><?= $ListadoPedidos[$i]['USUARIO'] ?></td>
                            <td class="col-acciones">
                                <div class="btn-group" role="group">
                                    <a href="eliminar_pedido_trabajo.php?ID_PEDIDO=<?= $ListadoPedidos[$i]['ID'] ?>" 
                                        class="btn btn-sm btn-danger me-1"
                                        title="Anular" 
                                        onclick="return confirm('Confirma anular este Pedido?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>

                                    <a href="modificar_pedidos_trabajos.php?ID_PEDIDO=<?= $ListadoPedidos[$i]['ID'] ?>"
                                        class="btn btn-sm btn-warning me-1" 
                                        title="Modificar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <a href="imprimir_pedido_trabajo.php?ID_PEDIDO=<?= $ListadoPedidos[$i]['ID'] ?>"
                                        class="btn btn-sm btn-primary me-1" 
                                        title="Imprimir">
                                        <i class="bi bi-printer-fill"></i>
                                    </a>

                                    <button type="button" class="btn btn-sm btn-success" 
                                            data-bs-toggle="modal" data-bs-target="#retirarPedidoModal"
                                            data-pedido-id="<?= $ListadoPedidos[$i]['ID'] ?>"
                                            data-pedido-saldo="<?= $saldo ?>"
                                            title="Retirar Pedido">
                                        <i class="bi bi-box-seam"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

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
                                <option value="<?= $metodo['idTipoPago'] ?>">
                                    <?= htmlspecialchars($metodo['denominacion']) ?>
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
  $_SESSION['Mensaje'] = '';
  require ('../shared/footer.inc.php');
?>

<script>
// Función para descargar informes (definida en ámbito global)
function descargarInforme(tipo) {
    // Mostrar spinner de carga
    const spinner = document.createElement('div');
    spinner.className = 'position-fixed top-50 start-50 translate-middle';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2 text-primary fs-5">Generando informe...</div>
    `;
    document.body.appendChild(spinner);

    // Fetch para generar el PDF
    fetch(`generar_informe_trabajos.php?tipo=${tipo}`)
        .then(response => {
            if (!response.ok) throw new Error('Error en el servidor');
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Informe_${tipo}_${new Date().toLocaleDateString()}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al generar: ' + error.message);
        })
        .finally(() => {
            document.body.removeChild(spinner);
        });
}

// Eventos cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', function() {
    // Configuración del modal
    const retirarPedidoModal = new bootstrap.Modal(document.getElementById('retirarPedidoModal'));
    
    // Evento para mostrar datos en el modal
    document.getElementById('retirarPedidoModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('retirarPedidoId').value = button.getAttribute('data-pedido-id');
        document.getElementById('retirarPedidoSaldo').value = '$' + parseFloat(button.getAttribute('data-pedido-saldo')).toFixed(2);
    });

    // Validación del formulario de retiro
    document.getElementById('formRetirarPedido').addEventListener('submit', function(e) {
        if (!confirm('¿Confirmar retiro del pedido?')) {
            e.preventDefault();
        }
    });

    // Eventos para los filtros de búsqueda
    const formBusqueda = document.getElementById('formBusqueda');
    const parametroInput = document.getElementById('parametro');
    const estadoSelect = document.getElementById('estadoBuscado');

    estadoSelect.addEventListener('change', function() {
        parametroInput.value = '';
        formBusqueda.submit();
    });

    formBusqueda.addEventListener('submit', function(e) {
        if (parametroInput.value.trim() !== '' && !(e.submitter && e.submitter.name === 'BotonLimpiar')) {
            estadoSelect.value = '';
        }
    });

    // Inicializar tooltips
    new bootstrap.Tooltip(document.body, {
        selector: '[data-bs-toggle="tooltip"]'
    });

    // Asignar eventos a los botones de descarga
    document.querySelectorAll('.dropdown-item.descargar-informe').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const tipo = this.getAttribute('data-tipo');
            descargarInforme(tipo);
        });
    });
});
</script>

</body>
</html>