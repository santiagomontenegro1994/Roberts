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
    $filtros['tipo_especial'] = $_GET['tipo_especial'] ?? '';
}

// Paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

// Listado de movimientos y totales
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros, $offset, $limite);
$totalMovimientos = Contar_Movimientos_Contables($MiConexion, $filtros);
$totalPaginas = ceil($totalMovimientos / $limite);

$totalCajaFuerte = Obtener_Total_Caja_Fuerte($MiConexion);
$totalBanco = Obtener_Total_Banco($MiConexion);

// Opciones de filtros
$metodosPagoOptions = [];
$res = $MiConexion->query("SELECT DISTINCT denominacion FROM tipo_pago");
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

                        <!-- Contadores -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card stat-card bg-primary text-white p-3">
                                    <i class="bi bi-safe"></i>
                                    <h3 class="mb-0" style="letter-spacing:1px;">$<?= number_format($totalCajaFuerte, 2, ',', '.') ?></h3>
                                    <p>Total Caja Fuerte</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card stat-card bg-light p-3">
                                    <i class="bi bi-bank text-secondary"></i>
                                    <h3 class="mb-0" style="letter-spacing:1px;">$<?= number_format($totalBanco, 2, ',', '.') ?></h3>
                                    <p>Total Banco</p>
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
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Filtrar</button>
                                    <a href="movimientos_contables.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reiniciar</a>
                                </div>
                            </div>
                        </form>

                        <!-- Tabla de Movimientos -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr class="table-primary">
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Descripción</th>
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
                                                $esCajaFuerte = strtolower($mov['tipo_movimiento']) === 'caja fuerte';
                                                $esEntrada = ($mov['es_entrada'] == 1) || $esCajaFuerte;
                                                $esSalida = ($mov['es_salida'] == 1 && !$esCajaFuerte);

                                                if ($esEntrada) {
                                                    $claseFila = 'movimiento-entrada';
                                                    $claseMonto = 'text-success';
                                                    $signo = '+';
                                                } elseif ($esSalida) {
                                                    $claseFila = 'movimiento-salida';
                                                    $claseMonto = 'text-danger';
                                                    $signo = '-';
                                                } else {
                                                    $claseFila = '';
                                                    $claseMonto = '';
                                                    $signo = '';
                                                }
                                            ?>
                                            <tr class="<?= $claseFila ?>">
                                                <td><?= date("d/m/Y", strtotime($mov['fecha'])) ?></td>
                                                <td><?= htmlspecialchars($mov['observaciones']) ?></td>
                                                <td>
                                                    <span class="badge <?= ($claseMonto=='text-success') ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= htmlspecialchars($mov['tipo_movimiento']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($mov['tipo_pago']) ?></td>
                                                <td><?= htmlspecialchars($mov['usuario_nombre'] ?? '-') ?></td>
                                                <td class="text-end <?= $claseMonto ?>"><?= $signo . "$" . number_format($mov['monto'],2,',','.') ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
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

                        <!-- Paginación -->
                        <?php if($totalPaginas > 1): ?>
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-center">
                                    <?php for($p=1; $p<=$totalPaginas; $p++): ?>
                                        <li class="page-item <?= ($pagina==$p) ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina'=>$p])) ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal Nuevo Movimiento -->
<div class="modal fade" id="nuevoMovimientoModal" tabindex="-1" aria-labelledby="nuevoMovimientoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="nuevoMovimientoModalLabel">Registrar Nuevo Movimiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Aquí va tu formulario completo de nuevo movimiento -->
                <?php /* Copiar contenido completo del modal_nuevo_movimiento.inc.php */ ?>
            </div>
        </div>
    </div>
</div>

<?php require('../shared/footer.inc.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let fechaInput = document.getElementById('fecha');
    if(fechaInput) fechaInput.valueAsDate = new Date();
});
</script>
</body>
</html>
