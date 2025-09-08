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

// Totales ajustados a filtros
$totalCajaFuerte = Obtener_Total_Caja_Fuerte($MiConexion, $filtros);
$totalBanco = Obtener_Total_Banco($MiConexion, $filtros);

// Opciones de filtros
$metodosPagoOptions = [];
$res = $MiConexion->query("SELECT DISTINCT denominacion 
                          FROM tipo_pago 
                          WHERE denominacion NOT IN ('Cta. Cte.', 'Caja Fuerte', 'Banco')");
while($row = $res->fetch_assoc()) {
    $metodosPagoOptions[$row['denominacion']] = $row['denominacion'];
}

$tipoEspecialOptions = Listar_Tipos_Especiales($MiConexion);

$MiConexion->close();
ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Movimientos Contables</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item active">Movimientos Contables</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <!-- Mostrar mensaje si existe -->
                        <?php if (!empty($_SESSION['Mensaje'])): ?>
                            <div class="alert alert-<?= $_SESSION['Estilo'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['Mensaje'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                            <?php
                                unset($_SESSION['Mensaje']);
                                unset($_SESSION['Estilo']);
                            ?>
                        <?php endif; ?>

                        <!-- Contadores -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card stat-card bg-primary text-white p-3">
                                    <i class="bi bi-safe"></i>
                                    <h3 class="mb-0" style="letter-spacing:1px;">$<?= number_format($totalCajaFuerte, 2, ',', '.') ?></h3>
                                    <p>Total Caja Fuerte</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card bg-light p-3">
                                    <i class="bi bi-bank text-secondary"></i>
                                    <h3 class="mb-0" style="letter-spacing:1px;">$<?= number_format($totalBanco, 2, ',', '.') ?></h3>
                                    <p>Total Banco</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card bg-info text-white p-3">
                                    <i class="bi bi-list-check"></i>
                                    <h3 class="mb-0" style="letter-spacing:1px;"><?= $totalMovimientos ?></h3>
                                    <p>Movimientos Filtrados</p>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <form method="GET" class="mb-3">
                            <div class="row mb-4">
                                <div class="col-md-2">
                                    <label for="fecha_desde" class="form-label">Desde</label>
                                    <input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="fecha_hasta" class="form-label">Hasta</label>
                                    <input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="tipo_movimiento" class="form-label">Tipo de Movimiento</label>
                                    <select class="form-select" name="tipo_movimiento">
                                        <option value="">Todos</option>
                                        <option value="Entrada" <?= ($filtros['tipo_movimiento'] ?? '') == 'Entrada' ? 'selected' : '' ?>>Entrada</option>
                                        <option value="Salida" <?= ($filtros['tipo_movimiento'] ?? '') == 'Salida' ? 'selected' : '' ?>>Salida</option>
                                        <option value="Retiros Contables" <?= ($filtros['tipo_movimiento'] ?? '') == 'Retiros Contables' ? 'selected' : '' ?>>Retiros Contables</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="metodo_pago" class="form-label">Método de Pago</label>
                                    <select class="form-select" name="metodo_pago">
                                        <option value="">Todos</option>
                                        <?php foreach($metodosPagoOptions as $value => $label): ?>
                                            <option value="<?= $label ?>" <?= ($filtros['metodo_pago'] ?? '') == $label ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Filtrar</button>
                                    <a href="movimientos_contables.php" class="btn btn-secondary me-2"><i class="bi bi-arrow-clockwise"></i> Reiniciar</a>
                                    <a href="agregar_movimiento_contable.php" class="btn btn-success">
                                        <i class="bi bi-plus-lg"></i> Retirar
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Tabla de Movimientos -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr class="table-primary">
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Detalle</th>
                                        <th scope="col">Tipo</th>
                                        <th scope="col">Método</th>
                                        <th scope="col">Usuario</th>
                                        <th scope="col" class="text-end">Monto</th>
                                        <th scope="col">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($movimientos)): ?>
                                        <?php foreach($movimientos as $mov): ?>
                                            <?php
                                                $esEntrada = $mov['es_entrada'] == 1;
                                                $esSalida = $mov['es_salida'] == 1;
                                                $esRetirosContables = !$esEntrada && !$esSalida;

                                                $claseMonto = $esEntrada ? 'text-success' : ($esSalida || $esRetirosContables ? 'text-danger' : '');
                                                $signo = $esEntrada ? '+' : '-';
                                                $tipoBadge = $esRetirosContables ? 'Retiros Contables' : ($esEntrada ? 'Entrada' : 'Salida');

                                                $detalle = htmlspecialchars($mov['detalle']);
                                            ?>
                                            <tr>
                                                <td><?= date("d/m/Y", strtotime($mov['fecha'])) ?></td>
                                                <td><?= $detalle ?></td>
                                                <td>
                                                    <?php if($tipoBadge === 'Entrada'): ?>
                                                        <span class="badge bg-success">Entrada</span>
                                                    <?php elseif($tipoBadge === 'Salida'): ?>
                                                        <span class="badge bg-danger">Salida</span>
                                                    <?php elseif($tipoBadge === 'Retiros Contables'): ?>
                                                        <span class="badge" style="background-color: purple; color: white;">Retiros Contables</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($mov['metodo_pago']) ?></td>
                                                <td><?= htmlspecialchars($mov['usuario'] ?? '-') ?></td>
                                                <td class="text-end <?= $claseMonto ?>"><?= $signo . "$" . number_format($mov['monto'],2,',','.') ?></td>
                                                <?php if($esRetirosContables): ?>
                                                    <td>
                                                        <a href="modificar_movimiento_contable.php?ID_MOVIMIENTO=<?= $mov['idMovimiento'] ?>"  
                                                           class="btn btn-xs btn-warning me-2" title="Modificar">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </a>
                                                        <a href="eliminar_movimiento_contable.php?id=<?= $mov['idMovimiento'] ?>" 
                                                        class="btn btn-xs btn-danger me-2" title="Eliminar" 
                                                        onclick="return confirm('¿Confirma eliminar este movimiento contable?');">
                                                            <i class="bi bi-x-circle"></i>
                                                        </a>
                                                    </td>
                                                <?php else: ?>
                                                    <td></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No se encontraron movimientos</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación con flechas -->
                        <?php if($totalPaginas > 1): ?>
                            <nav aria-label="Page navigation example">
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
