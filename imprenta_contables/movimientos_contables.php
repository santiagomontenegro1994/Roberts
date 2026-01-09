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

// Obtener filtros
$filtros = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filtros['fecha_desde'] = $_GET['fecha_desde'] ?? '';
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'] ?? '';
    $filtros['tipo_movimiento'] = $_GET['tipo_movimiento'] ?? '';
    $filtros['metodo_pago'] = $_GET['metodo_pago'] ?? '';
}

// Paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

// Listado de movimientos y totales filtrados
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, $offset, $limite);
$totalMovimientos = Contar_Movimientos_Contables($MiConexion, $filtros);
$totalPaginas = ceil($totalMovimientos / $limite);

// --- SECCIÓN DE CÁLCULO DE TOTALES ---

// 1. Caja Fuerte y Banco
// Nota: 'Obtener_Total_Banco' ya contiene la lógica de exclusión (No Efectivo, No MP, No Payway)
$totalCajaFuerte  = Obtener_Total_Caja_Fuerte($MiConexion, $filtros);
$totalBanco       = Obtener_Total_Banco($MiConexion, $filtros);

// 2. Mercado Pago
// Entrada: ID 22 | Salida: ID 24
$mpEntradas = Obtener_Total_Por_TipoPago($MiConexion, 22, $filtros);
$mpSalidas  = Obtener_Total_Por_TipoPago($MiConexion, 24, $filtros); 
// RESTA: Entrada (Positivo) - Salida (Positivo)
$totalMercadoPago = $mpEntradas - $mpSalidas; 

// 3. Payway
// Entrada: ID 23 | Salida: ID 25
$pwEntradas = Obtener_Total_Por_TipoPago($MiConexion, 23, $filtros);
$pwSalidas  = Obtener_Total_Por_TipoPago($MiConexion, 25, $filtros);
// RESTA: Entrada (Positivo) - Salida (Positivo)
$totalPayway = $pwEntradas - $pwSalidas;

// Total General (Suma de los saldos netos de cada cuenta)
$granTotal = $totalCajaFuerte + $totalBanco + $totalMercadoPago + $totalPayway;

// Dato informativo
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
                        <h5 class="card-title">Filtros y Listado</h5>

                        <?php if (!empty($_SESSION['Mensaje'])): ?>
                            <div class="alert alert-<?= $_SESSION['Estilo'] ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['Mensaje'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php 
                            unset($_SESSION['Mensaje']);
                            unset($_SESSION['Estilo']);
                            ?>
                        <?php endif; ?>

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
                                </select>
                            </div>
                             <div class="col-md-3">
                                <label for="metodo_pago" class="form-label">Método Pago</label>
                                <select class="form-select" name="metodo_pago">
                                    <option value="">Todos</option>
                                    <option value="Efectivo" <?= ($filtros['metodo_pago'] == 'Efectivo') ? 'selected' : '' ?>>Efectivo</option>
                                    <option value="Transferencia" <?= ($filtros['metodo_pago'] == 'Transferencia') ? 'selected' : '' ?>>Transferencia</option>
                                    <option value="Cheque" <?= ($filtros['metodo_pago'] == 'Cheque') ? 'selected' : '' ?>>Cheque</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="movimientos_contables.php" class="btn btn-secondary">Limpiar</a>
                                <a href="agregar_movimiento_contable.php" class="btn btn-success float-end">Nuevo Movimiento</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Categoría/Detalle</th>
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
                                                        <span class="badge bg-secondary">Neutro</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($mov['nombre_movimiento']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($mov['detalle'] ?? '-') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($mov['metodo_pago']) ?></td>
                                                <td class="text-end">
                                                    $ <?= number_format($mov['monto'], 2, ',', '.') ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="modificar_movimiento_contable.php?id=<?= $mov['idRetiro'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                    <a href="eliminar_movimiento_contable.php?id=<?= $mov['idRetiro'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este movimiento?');" title="Eliminar"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No se encontraron movimientos.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPaginas > 1): ?>
                            <nav aria-label="Page navigation">
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

<?php require('../shared/footer.inc.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>