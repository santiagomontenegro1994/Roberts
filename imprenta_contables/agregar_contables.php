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

// Obtener filtros desde GET
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'tipo_movimiento' => $_GET['tipo_movimiento'] ?? '',
    'metodo_pago' => $_GET['metodo_pago'] ?? ''
];

// obtenemos los datos con filtros
$movimientos = Listar_Movimientos_Contables($MiConexion, $filtros);
$totalCajaFuerte = Obtener_Total_Caja_Fuerte($MiConexion);
$totalBanco = Obtener_Total_Banco($MiConexion);

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

                <!-- Tarjetas resumen -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Caja Fuerte</h5>
                                    <h3 class="card-text">$<?= number_format($totalCajaFuerte, 2, ',', '.') ?></h3>
                                </div>
                                <i class="bi bi-safe display-4"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-white bg-secondary mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Banco</h5>
                                    <h3 class="card-text">$<?= number_format($totalBanco, 2, ',', '.') ?></h3>
                                </div>
                                <i class="bi bi-bank display-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Registro de Movimientos</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#nuevoMovimientoModal">
                                <i class="bi bi-plus-circle me-1"></i> Nuevo Movimiento
                            </button>
                        </div>

                        <!-- Filtro de búsqueda -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Fecha desde</label>
                                <input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                                <input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_movimiento" class="form-label">Tipo de movimiento</label>
                                <select class="form-select" name="tipo_movimiento">
                                    <option value="">Todos</option>
                                    <option value="Caja Fuerte" <?= ($filtros['tipo_movimiento']=='Caja Fuerte') ? 'selected' : '' ?>>Caja Fuerte</option>
                                    <option value="Banco" <?= ($filtros['tipo_movimiento']=='Banco') ? 'selected' : '' ?>>Banco</option>
                                    <option value="Entrada" <?= ($filtros['tipo_movimiento']=='Entrada') ? 'selected' : '' ?>>Entrada</option>
                                    <option value="Salida" <?= ($filtros['tipo_movimiento']=='Salida') ? 'selected' : '' ?>>Salida</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="metodo_pago" class="form-label">Método de pago</label>
                                <select class="form-select" name="metodo_pago">
                                    <option value="">Todos</option>
                                    <option value="Efectivo" <?= ($filtros['metodo_pago']=='Efectivo') ? 'selected' : '' ?>>Efectivo</option>
                                    <option value="Transferencia bancaria" <?= ($filtros['metodo_pago']=='Transferencia bancaria') ? 'selected' : '' ?>>Transferencia</option>
                                    <option value="Cheque" <?= ($filtros['metodo_pago']=='Cheque') ? 'selected' : '' ?>>Cheque</option>
                                    <option value="Débito" <?= ($filtros['metodo_pago']=='Débito') ? 'selected' : '' ?>>Débito</option>
                                    <option value="Crédito" <?= ($filtros['metodo_pago']=='Crédito') ? 'selected' : '' ?>>Crédito</option>
                                </select>
                            </div>
                            <div class="col-md-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                            </div>
                        </form>

                        <!-- Lista de movimientos -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Descripción</th>
                                        <th>Tipo</th>
                                        <th>Método</th>
                                        <th>Usuario</th>
                                        <th class="text-end">Monto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($movimientos)): ?>
                                        <?php foreach ($movimientos as $mov): 
                                            $esCajaFuerte = (strtolower($mov['tipo_movimiento']) === 'caja fuerte' || $mov['idTipoMovimiento'] == 9);
                                            $esEntrada = ($mov['es_entrada'] == 1);
                                            $esSalida = ($mov['es_salida'] == 1);

                                            if ($esCajaFuerte || $esEntrada) {
                                                $claseMonto = 'text-success';
                                                $signo = '+';
                                            } elseif ($esSalida) {
                                                $claseMonto = 'text-danger';
                                                $signo = '-';
                                            } else {
                                                $claseMonto = '';
                                                $signo = '';
                                            }
                                        ?>
                                            <tr>
                                                <td><?= date("d/m/Y", strtotime($mov['fecha'])) ?></td>
                                                <td><?= htmlspecialchars($mov['observaciones']) ?></td>
                                                <td>
                                                    <span class="badge <?= ($claseMonto=='text-success') ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= htmlspecialchars($mov['tipo_movimiento']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($mov['tipo_pago']) ?></td>
                                                <td><?= htmlspecialchars($_SESSION['Usuario_Nombre']) ?></td>
                                                <td class="text-end <?= $claseMonto ?>"><?= $signo . "$" . number_format($mov['monto'], 2, ',', '.') ?></td>
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

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal Nuevo Movimiento -->
<?php require('../shared/modal_nuevo_movimiento.inc.php'); ?>

<!-- Footer -->
<?php require('../shared/footer.inc.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('fecha').valueAsDate = new Date();
    });
</script>
</body>
</html>
