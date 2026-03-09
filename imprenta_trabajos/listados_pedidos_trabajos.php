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

// Inicializar variables de filtro desde la sesión (Ahora con campos independientes)
if (!isset($_SESSION['filtros_pedidos'])) {
    $_SESSION['filtros_pedidos'] = [
        'idBuscado' => '',
        'fechaBuscada' => '',
        'clienteBuscado' => '',
        'telefonoBuscado' => '',
        'estadoBuscado' => '',
        'proveedorBuscado' => '',
        'trabajoBuscado' => ''
    ];
}

// Procesar búsquedas SIN REDIRECCION para evitar tildes en el navegador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['BotonLimpiar'])) {
        // Limpiar todos los filtros
        $_SESSION['filtros_pedidos'] = [
            'idBuscado' => '',
            'fechaBuscada' => '',
            'clienteBuscado' => '',
            'telefonoBuscado' => '',
            'estadoBuscado' => '',
            'proveedorBuscado' => '',
            'trabajoBuscado' => ''
        ];
    } else {
        // Acumular todos los filtros
        $_SESSION['filtros_pedidos'] = [
            'idBuscado' => trim($_POST['idBuscado'] ?? ''),
            'fechaBuscada' => trim($_POST['fechaBuscada'] ?? ''),
            'clienteBuscado' => trim($_POST['clienteBuscado'] ?? ''),
            'telefonoBuscado' => trim($_POST['telefonoBuscado'] ?? ''),
            'estadoBuscado' => $_POST['estadoBuscado'] ?? '',
            'proveedorBuscado' => $_POST['proveedorBuscado'] ?? '',
            'trabajoBuscado' => trim($_POST['trabajoBuscado'] ?? '')
        ];
    }
}

// Configuración de Paginación
$registros_por_pagina = 50; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener valores actuales de los filtros
$filtros = $_SESSION['filtros_pedidos'];
$idBuscado = $filtros['idBuscado'];
$fechaBuscada = $filtros['fechaBuscada'];
$clienteBuscado = $filtros['clienteBuscado'];
$telefonoBuscado = $filtros['telefonoBuscado'];
$estadoBuscado = $filtros['estadoBuscado'];
$proveedorBuscado = $filtros['proveedorBuscado'];
$trabajoBuscado = $filtros['trabajoBuscado'];

// Ejecutar funciones
$TotalRegistros = Contar_Pedidos_Filtrados($MiConexion, $filtros);
$TotalPaginas = ceil($TotalRegistros / $registros_por_pagina);
$ListadoPedidos = Listar_Pedidos_Filtrados_Paginados($MiConexion, $filtros, $offset, $registros_por_pagina);
$CantidadPedidos = count($ListadoPedidos);

// Obtener listas para los selects
$estados = Datos_Estados_Pedido_Trabajo($MiConexion);
$rs_prov = mysqli_query($MiConexion, "SELECT idProveedor, nombre FROM proveedores WHERE idActivo = 1 ORDER BY nombre ASC");
$proveedores = [];
if ($rs_prov) {
    while ($row = mysqli_fetch_assoc($rs_prov)) {
        $proveedores[] = $row;
    }
}
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
        .col-id { width: 50px; }
        .col-fecha { width: 90px; }
        .col-cliente { width: 120px; min-width: 120px; }
        .col-facturacion { width: 90px; }
        .col-detalle { width: 100px; }
        .col-precio { width: 80px; }
        .col-senia { width: 80px; }
        .col-saldo { width: 80px; }
        .col-tomado { width: 100px; }
        .col-acciones { width: 180px; }
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
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-2">
                        <label class="form-label text-tiny mb-0 fw-bold">ID Pedido</label>
                        <input type="number" class="form-control form-control-sm" name="idBuscado" 
                               value="<?= htmlspecialchars($idBuscado) ?>" placeholder="Ej: 1500">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-tiny mb-0 fw-bold">Fecha</label>
                        <input type="date" class="form-control form-control-sm" name="fechaBuscada" id="fechaBuscada"
                               value="<?= htmlspecialchars($fechaBuscada) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label text-tiny mb-0 fw-bold">Cliente (Nombre y/o Apellido)</label>
                        <input type="text" class="form-control form-control-sm" name="clienteBuscado" 
                               value="<?= htmlspecialchars($clienteBuscado) ?>" placeholder="Ej: Juan Perez">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label text-tiny mb-0 fw-bold">Teléfono</label>
                        <input type="text" class="form-control form-control-sm" name="telefonoBuscado" 
                               value="<?= htmlspecialchars($telefonoBuscado) ?>" placeholder="Ej: 351...">
                    </div>
                </div>

                <div class="row mt-2 g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-tiny mb-0 fw-bold">Estado:</label>
                        <select class="form-select form-select-sm" name="estadoBuscado" id="estadoBuscado">
                            <option value="">Todos los estados</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['idEstado'] ?>"
                                    <?= ($estadoBuscado == $estado['idEstado']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['denominacion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-tiny mb-0 fw-bold">Proveedor:</label>
                        <select class="form-select form-select-sm" name="proveedorBuscado" id="proveedorBuscado">
                            <option value="">Todos los proveedores</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= $prov['idProveedor'] ?>"
                                    <?= ($proveedorBuscado == $prov['idProveedor']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-tiny mb-0 fw-bold">Trabajo / Descripción:</label>
                        <input type="text" class="form-control form-control-sm" name="trabajoBuscado" id="trabajoBuscado" 
                               value="<?= htmlspecialchars($trabajoBuscado) ?>" 
                               placeholder="Ej: Carnet, Lona, Taza...">
                    </div>

                    <div class="col-md-3 text-end">
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary btn-sm" name="BotonBuscar" value="1">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <button type="submit" class="btn btn-secondary btn-sm" name="BotonLimpiar" value="1" title="Limpiar Filtros">
                                <i class="bi bi-eraser-fill"></i>
                            </button>
                            
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Descargar Informes">
                                    <i class="bi bi-download"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="pendientes">Trabajos Pendientes</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="listos">Trabajos Listos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="en_proceso">Trabajos en Proceso</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item descargar-informe" href="#" data-tipo="impresos">Trabajos Para Taller</a></li>
                                </ul>
                            </div>
                        </div>
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
                        <?php if ($CantidadPedidos == 0): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No hay registros con estos filtros</td>
                            </tr>
                        <?php else: ?>
                            <?php for ($i=0; $i<$CantidadPedidos; $i++) { 
                                $saldo = $ListadoPedidos[$i]['PRECIO'] - $ListadoPedidos[$i]['SEÑA'];
                                
                                // ACÁ SE LLAMA A TU FUNCIÓN DE COLORES
                                list($Title, $Color) = ColorDeFilaPedidoTrabajo($ListadoPedidos[$i]['ESTADO']);
                                
                                $nombreCliente = htmlspecialchars($ListadoPedidos[$i]['CLIENTE_N'] . ' ' . $ListadoPedidos[$i]['CLIENTE_A']);
                                $nombreMostrar = (strlen($nombreCliente) > 15) ? substr($nombreCliente, 0, 15) . '...' : $nombreCliente;
                                
                                // Determinar estado de facturación
                                $detallesFacturados = isset($ListadoPedidos[$i]['DETALLES_FACTURADOS']) ? $ListadoPedidos[$i]['DETALLES_FACTURADOS'] : 0;
                                $totalDetalles = isset($ListadoPedidos[$i]['TOTAL_DETALLES']) ? $ListadoPedidos[$i]['TOTAL_DETALLES'] : 0;
                                
                                if (function_exists('determinarEstadoFacturacion')) {
                                    $estadoFacturacion = determinarEstadoFacturacion($detallesFacturados, $totalDetalles);
                                } else {
                                    $estadoFacturacion = ['estado' => 'desconocido', 'tooltip' => ''];
                                }
                            ?>
                            <tr class="<?= $Color ?>" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?= $Title ?>">
                                <td class="col-id"><?= $ListadoPedidos[$i]['ID'] ?></td>
                                <td class="col-fecha text-compact"><?= date('d/m/Y', strtotime($ListadoPedidos[$i]['FECHA'])) ?></td>
                                <td class="col-cliente">
                                    <strong class="text-compact" title="<?= htmlspecialchars($nombreCliente) ?>"><?= $nombreMostrar ?></strong>
                                    <?php if (!empty($ListadoPedidos[$i]['TELEFONO'])): ?>
                                        <br><small class="text-muted text-tiny"><i class="bi bi-telephone"></i> <?= htmlspecialchars($ListadoPedidos[$i]['TELEFONO']) ?></small>
                                    <?php endif; ?>
                                </td>
                                
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($TotalPaginas > 1): ?>
            <nav aria-label="Navegación de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>">Anterior</a>
                    </li>
                    
                    <?php 
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($TotalPaginas, $pagina_actual + 2);
                    
                    if ($inicio > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    
                    for ($p = $inicio; $p <= $fin; $p++): ?>
                        <li class="page-item <?= ($p == $pagina_actual) ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; 
                    
                    if ($fin < $TotalPaginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    ?>
                    
                    <li class="page-item <?= ($pagina_actual >= $TotalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <div class="text-center text-muted text-tiny mt-2">
                Mostrando <?= $CantidadPedidos ?> pedidos. Total coincidentes: <?= $TotalRegistros ?>
            </div>

        </div>
    </div>
</section>

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
    const spinner = document.createElement('div');
    spinner.className = 'position-fixed top-50 start-50 translate-middle';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2 text-primary fs-5">Generando informe...</div>
    `;
    document.body.appendChild(spinner);

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
    const retirarPedidoModal = new bootstrap.Modal(document.getElementById('retirarPedidoModal'));
    
    document.getElementById('retirarPedidoModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('retirarPedidoId').value = button.getAttribute('data-pedido-id');
        document.getElementById('retirarPedidoSaldo').value = '$' + parseFloat(button.getAttribute('data-pedido-saldo')).toFixed(2);
    });

    document.getElementById('formRetirarPedido').addEventListener('submit', function(e) {
        if (!confirm('¿Confirmar retiro del pedido?')) {
            e.preventDefault();
        }
    });

    const formBusqueda = document.getElementById('formBusqueda');
    const estadoSelect = document.getElementById('estadoBuscado');
    const proveedorSelect = document.getElementById('proveedorBuscado');
    const fechaInput = document.getElementById('fechaBuscada');

    // FILTROS AUTOMÁTICOS AL CAMBIAR
    estadoSelect.addEventListener('change', function() {
        formBusqueda.submit();
    });

    proveedorSelect.addEventListener('change', function() {
        formBusqueda.submit();
    });
    
    // Auto-filtrar al seleccionar una fecha en el calendario
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            formBusqueda.submit();
        });
    }

    new bootstrap.Tooltip(document.body, {
        selector: '[data-bs-toggle="tooltip"]'
    });

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