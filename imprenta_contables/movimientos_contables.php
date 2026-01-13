<?php
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

// --- 1. PROCESAR MENSAJES DE SESIÓN (ÉXITO/ERROR) ---
$mensaje = $_SESSION['Mensaje'] ?? '';
$estilo = $_SESSION['Estilo'] ?? '';
unset($_SESSION['Mensaje'], $_SESSION['Estilo']);

// --- 2. OBTENER FILTROS ---
$filtros = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';
    $filtros['tipo_movimiento'] = $_GET['tipo_movimiento'] ?? '';
    $filtros['metodo_pago'] = $_GET['metodo_pago'] ?? '';
}

// --- 3. OBTENER OPCIONES PARA EL SELECT DE MÉTODOS DE PAGO ---
$tiposPagoDisponibles = [];
$sqlTP = "SELECT denominacion FROM tipo_pago WHERE idActivo = 1 ORDER BY denominacion ASC";
$resTP = $MiConexion->query($sqlTP);
if($resTP){
    while($rowTP = $resTP->fetch_assoc()){
        $tiposPagoDisponibles[] = $rowTP['denominacion'];
    }
}

// --- 4. PAGINACIÓN ---
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

// --- 5. OBTENER DATOS (LISTADO) ---
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, $offset, $limite);
$totalMovimientos = Contar_Movimientos_Contables($MiConexion, $filtros); // Función existente
$totalPaginas = ceil($totalMovimientos / $limite);

// --- 6. CALCULAR TOTALES (Lógica Corregida) ---
// Estas funciones deben existir en imprenta.php con la lógica de IDs que definimos
$totalBanco      = Obtener_Total_Banco($MiConexion, $filtros);
$totalMP         = Obtener_Total_MercadoPago($MiConexion, $filtros);
$totalPayway     = Obtener_Total_Payway($MiConexion, $filtros);
$totalCajaFuerte = Obtener_Total_Caja_Fuerte($MiConexion, $filtros); // Función existente

?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Movimientos Contables</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item active">Movimientos Contables</li>
            </ol>
        </nav>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $estilo ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <section class="section dashboard">
        <div class="row mb-4">
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card sales-card">
                    <div class="card-body">
                        <h5 class="card-title">Caja Fuerte</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-safe"></i>
                            </div>
                            <div class="ps-3">
                                <h6>$ <?= number_format($totalCajaFuerte, 2, ',', '.') ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card revenue-card">
                    <div class="card-body">
                        <h5 class="card-title">Banco</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-bank"></i>
                            </div>
                            <div class="ps-3">
                                <h6>$ <?= number_format($totalBanco, 2, ',', '.') ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-3 col-md-6">
                <div class="card info-card customers-card">
                    <div class="card-body">
                        <h5 class="card-title">Mercado Pago</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-qr-code"></i>
                            </div>
                            <div class="ps-3">
                                <h6>$ <?= number_format($totalMP, 2, ',', '.') ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-md-6">
                <div class="card info-card sales-card">
                    <div class="card-body">
                        <h5 class="card-title">Payway</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <div class="ps-3">
                                <h6>$ <?= number_format($totalPayway, 2, ',', '.') ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Listado de Movimientos</h5>
                            <a href="agregar_movimiento_contable.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Nuevo Movimiento
                            </a>
                        </div>

                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Fecha Desde</label>
                                <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo Movimiento</label>
                                <select name="tipo_movimiento" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Entrada" <?= ($filtros['tipo_movimiento'] === 'Entrada') ? 'selected' : '' ?>>Entrada</option>
                                    <option value="Salida" <?= ($filtros['tipo_movimiento'] === 'Salida') ? 'selected' : '' ?>>Salida</option>
                                    <option value="Retiros Contables" <?= ($filtros['tipo_movimiento'] === 'Retiros Contables') ? 'selected' : '' ?>>Retiros Contables</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Método de Pago</label>
                                <select name="metodo_pago" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($tiposPagoDisponibles as $tp): ?>
                                        <option value="<?= htmlspecialchars($tp) ?>" <?= ($filtros['metodo_pago'] == $tp) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tp) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="movimientos_contables.php" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Detalle</th>
                                        <th>Tipo</th>
                                        <th>Método Pago</th>
                                        <th>Monto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($movimientos) > 0): ?>
                                        <?php foreach ($movimientos as $m): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                                                <td><?= htmlspecialchars($m['usuario']) ?></td>
                                                <td><?= htmlspecialchars($m['detalle']) ?></td>
                                                <td>
                                                    <?php if ($m['tipo'] == 'Entrada'): ?>
                                                        <span class="badge bg-success">Entrada</span>
                                                    <?php elseif ($m['tipo'] == 'Salida'): ?>
                                                        <span class="badge bg-danger">Salida</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Contable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($m['metodo_pago']) ?></td>
                                                <td>$ <?= number_format($m['monto'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($m['origen'] === 'retiro'): ?>
                                                        <a href="modificar_movimiento_contable.php?id=<?= $m['idMovimiento'] ?>" 
                                                           class="btn btn-warning btn-sm" title="Editar">
                                                           <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="#" onclick="confirmarEliminacion(<?= $m['idMovimiento'] ?>)" 
                                                           class="btn btn-danger btn-sm" title="Eliminar">
                                                           <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-info text-dark" title="Generado automáticamente desde Caja">Auto</span>
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
                            <nav>
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