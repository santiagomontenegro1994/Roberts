<?php
// --- ACTIVAR REPORTE DE ERRORES (Solo para depuración, quítalo cuando funcione) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------------------------------------------------------------------

ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// --- VERIFICACIÓN DE SEGURIDAD DE FUNCIONES ---
// Esto evita la pantalla blanca si faltan funciones en imprenta.php
if (!function_exists('Obtener_Total_MercadoPago')) {
    die("ERROR FATAL: No se encuentran las nuevas funciones en 'imprenta.php'. Por favor actualiza ese archivo con el código de la respuesta anterior.");
}

// 1. Obtener filtros
$filtros = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';
    $filtros['tipo_movimiento'] = $_GET['tipo_movimiento'] ?? '';
    $filtros['metodo_pago'] = $_GET['metodo_pago'] ?? '';
}

// 2. Obtener lista dinámica de Métodos de Pago (para el select)
// MODIFICADO: Se agrega DISTINCT para unificar duplicados y NOT IN para excluir Banco/Caja Fuerte
$tiposPagoDisponibles = [];
$sqlTP = "SELECT DISTINCT denominacion 
          FROM tipo_pago 
          WHERE idActivo = 1 
          AND denominacion NOT IN ('Banco', 'Caja Fuerte') 
          ORDER BY denominacion ASC";

$resTP = $MiConexion->query($sqlTP);
if($resTP){
    while($rowTP = $resTP->fetch_assoc()){
        $tiposPagoDisponibles[] = $rowTP['denominacion'];
    }
}

// 3. Paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

// 4. Listado de movimientos (Llama a la nueva función Listar_Movimientos_Contables)
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, $offset, $limite);
$totalMovimientos = Contar_Movimientos_Contables($MiConexion, $filtros);
$totalPaginas = ceil($totalMovimientos / $limite);

// 5. CÁLCULO DE TOTALES (Usando las nuevas funciones específicas para corregir saldos)
$totalCajaFuerte = Obtener_Total_Caja_Fuerte($MiConexion, $filtros);
$totalBanco      = Obtener_Total_Banco($MiConexion, $filtros);
$totalMercadoPago= Obtener_Total_MercadoPago($MiConexion, $filtros);
$totalPayway     = Obtener_Total_Payway($MiConexion, $filtros);

// Total General
$granTotal = $totalCajaFuerte + $totalBanco + $totalMercadoPago + $totalPayway;

// Variable para mostrar cantidad listada
$totalMovimientosListados = $totalMovimientos; 
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Movimientos Contables</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item active">Contabilidad</li>
            </ol>
        </nav>
    </div>

    <?php if (!empty($_SESSION['Mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['Estilo'] ?? 'info' ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['Mensaje'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['Mensaje']);
        unset($_SESSION['Estilo']);
        ?>
    <?php endif; ?>

    <section class="section dashboard">
        
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3 shadow-sm">
                    <div class="card-header fw-bold text-white"><i class="bi bi-safe"></i> Caja Fuerte</div>
                    <div class="card-body">
                        <h4 class="card-title text-white mb-0">$ <?= number_format($totalCajaFuerte, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3 shadow-sm">
                    <div class="card-header fw-bold text-white"><i class="bi bi-bank"></i> Banco</div>
                    <div class="card-body">
                        <h4 class="card-title text-white mb-0">$ <?= number_format($totalBanco, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white mb-3 shadow-sm" style="background-color: #009EE3;">
                    <div class="card-header fw-bold text-white"><i class="bi bi-phone"></i> Mercado Pago</div>
                    <div class="card-body">
                        <h4 class="card-title text-white mb-0">$ <?= number_format($totalMercadoPago, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white mb-3 shadow-sm" style="background-color: #4B0082;">
                    <div class="card-header fw-bold text-white"><i class="bi bi-credit-card"></i> Payway</div>
                    <div class="card-body">
                        <h4 class="card-title text-white mb-0">$ <?= number_format($totalPayway, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-secondary shadow-sm">
                    <div class="card-body text-center bg-light">
                        <h3 class="card-title text-dark m-0 p-2">
                            Total General: <strong>$ <?= number_format($granTotal, 2, ',', '.') ?></strong>
                        </h3>
                        <p class="card-text text-muted small mt-1 mb-0">
                            <i class="bi bi-list-ul"></i> Listando <?= $totalMovimientosListados ?> movimientos con los filtros actuales.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Filtros y Listado</h5>
                            <a href="agregar_movimiento_contable.php" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Nuevo Movimiento
                            </a>
                        </div>

                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_movimiento" class="form-label">Tipo</label>
                                <select class="form-select" name="tipo_movimiento">
                                    <option value="">Todos</option>
                                    <option value="Entrada" <?= ($filtros['tipo_movimiento'] == 'Entrada') ? 'selected' : '' ?>>Entrada</option>
                                    <option value="Salida" <?= ($filtros['tipo_movimiento'] == 'Salida') ? 'selected' : '' ?>>Salida</option>
                                    <option value="Retiros Contables" <?= ($filtros['tipo_movimiento'] == 'Retiros Contables') ? 'selected' : '' ?>>Retiros Contables</option>
                                </select>
                            </div>
                             <div class="col-md-3">
                                <label for="metodo_pago" class="form-label">Método Pago</label>
                                <select class="form-select" name="metodo_pago">
                                    <option value="">Todos</option>
                                    <?php foreach ($tiposPagoDisponibles as $tp): ?>
                                        <option value="<?= htmlspecialchars($tp) ?>" <?= ($filtros['metodo_pago'] == $tp) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tp) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="movimientos_contables.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Categoría/Detalle</th>
                                        <th>Usuario</th>
                                        <th>Método Pago</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($movimientos) > 0): ?>
                                        <?php foreach ($movimientos as $mov): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($mov['fecha'])) ?></td>
                                                <td>
                                                    <?php if ($mov['es_entrada']): ?>
                                                        <span class="badge bg-success">Entrada</span>
                                                    <?php elseif ($mov['es_salida']): ?>
                                                        <span class="badge bg-danger">Salida</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Contable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($mov['detalle'] ?? '-') ?>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($mov['usuario'] ?? '') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($mov['metodo_pago']) ?></td>
                                                <td class="text-end">
                                                    $ <?= number_format($mov['monto'], 2, ',', '.') ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (isset($mov['origen']) && $mov['origen'] === 'retiro'): ?>
                                                        <a href="modificar_movimiento_contable.php?id=<?= $mov['idMovimiento'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                        <a href="#" onclick="confirmarEliminacion(<?= $mov['idMovimiento'] ?>)" class="btn btn-sm btn-danger" title="Eliminar"><i class="bi bi-trash"></i></a>
                                                    <?php else: ?>
                                                        <span class="badge bg-info text-dark" title="Generado desde Caja">Automático</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No se encontraron movimientos.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPaginas > 1): ?>
                            <nav aria-label="Page navigation" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => max(1, $pagina - 1)])) ?>">&laquo; Anterior</a>
                                    </li>
                                    <?php
                                    $rango = 5; 
                                    $inicio = max(1, $pagina - $rango);
                                    $fin = min($totalPaginas, $pagina + $rango);
                                    for ($p = $inicio; $p <= $fin; $p++):
                                    ?>
                                        <li class="page-item <?= ($pagina == $p) ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $p])) ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => min($totalPaginas, $pagina + 1)])) ?>">Siguiente &raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
function confirmarEliminacion(id) {
    if (confirm('¿Está seguro de que desea eliminar este movimiento contable?')) {
        window.location.href = 'eliminar_movimiento_contable.php?id=' + id;
    }
}
</script>

<?php require('../shared/footer.inc.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>