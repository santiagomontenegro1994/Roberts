<?php
// --- ACTIVAR VISUALIZACIÓN DE ERRORES ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Verificaciones de seguridad de archivos
if (!file_exists('../shared/encabezado.inc.php')) die("Error: Falta encabezado.inc.php");
require ('../shared/encabezado.inc.php');

if (!file_exists('../shared/barraLateral.inc.php')) die("Error: Falta barraLateral.inc.php");
require ('../shared/barraLateral.inc.php');

require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// Validar ID cliente
$idCliente = isset($_GET['idCliente']) ? intval($_GET['idCliente']) : 0;

if ($idCliente <= 0) {
    echo "<script>window.location.href='cta_cte.php';</script>";
    exit;
}

// Verificar que la nueva función existe antes de usarla
if (!function_exists('ObtenerTodosLosMovimientosCliente')) {
    die("Error crítico: Debes agregar la función 'ObtenerTodosLosMovimientosCliente' en el archivo imprenta.php tal como te indiqué en el PASO 1.");
}

// Obtener información del cliente
$cliente = Obtener_Cliente_Por_ID($MiConexion, $idCliente);

if (!$cliente) {
    $_SESSION['Mensaje'] = "Cliente no encontrado";
    $_SESSION['Estilo'] = "danger";
    echo "<script>window.location.href='cta_cte.php';</script>";
    exit;
}

// --- LÓGICA DE PAGINACIÓN (Vía PHP) ---
$registrosPorPagina = 50;
$paginaActual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// 1. Obtener TODOS los movimientos usando la NUEVA función
$todosLosMovimientos = ObtenerTodosLosMovimientosCliente($MiConexion, $idCliente);

// 2. Calcular totales y offsets usando Arrays de PHP
$totalRegistros = count($todosLosMovimientos);
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);
$offset = ($paginaActual - 1) * $registrosPorPagina;

// 3. Cortar el array para mostrar solo la página actual
// Si no hay registros, array_slice devuelve array vacío sin error
$movimientosPagina = array_slice($todosLosMovimientos, $offset, $registrosPorPagina);

?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Historial Completo</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item"><a href="cta_cte.php">Cuenta Corriente</a></li>
                <li class="breadcrumb-item"><a href="detalle_cta_cte.php?idCliente=<?= $idCliente ?>">Detalle</a></li>
                <li class="breadcrumb-item active">Historial</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                            <h5 class="card-title p-0 m-0">
                                Movimientos de: <?= htmlspecialchars($cliente['NOMBRE'] . ' ' . htmlspecialchars($cliente['APELLIDO'])) ?>
                            </h5>
                            <a href="detalle_cta_cte.php?idCliente=<?= $idCliente ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al Detalle
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaHistorial">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Observaciones / Detalle</th>
                                        <th>Usuario</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($movimientosPagina) > 0): ?>
                                        <?php foreach ($movimientosPagina as $mov): ?>
                                            <?php 
                                            // Lógica de colores idéntica a detalle_cta_cte.php
                                            $tipo = $mov['tipo'] ?? 'DESCONOCIDO';
                                            $badgeClass = [
                                                'DEPOSITO' => 'bg-success',
                                                'PAGO_DIRECTO' => 'bg-primary',
                                                'AJUSTE' => 'bg-warning text-dark',
                                                'APLICACION_AUTOMATICA' => 'bg-info text-dark',
                                                'NOTA_DEBITO' => 'bg-danger'
                                            ][$tipo] ?? 'bg-secondary';
                                            
                                            $monto = floatval($mov['monto']);
                                            $esPositivo = ($tipo == 'DEPOSITO' || $tipo == 'APLICACION_AUTOMATICA' || ($tipo == 'AJUSTE' && $monto > 0));
                                            
                                            // Manejo seguro de campos opcionales
                                            $observaciones = $mov['observaciones'] ?? '-';
                                            $usuarioNombre = $mov['usuarioNombre'] ?? '-'; 
                                            ?>
                                            <tr>
                                                <td style="white-space: nowrap;">
                                                    <?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $badgeClass ?>"><?= $tipo ?></span>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($observaciones) ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($usuarioNombre) ?>
                                                    </small>
                                                </td>
                                                <td class="text-end fw-bold <?= $esPositivo ? 'text-success' : 'text-danger' ?>">
                                                    $<?= number_format($monto, 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                                No hay movimientos registrados en el historial.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Mostrando <?= count($movimientosPagina) ?> de <?= $totalRegistros ?> registros.
                            </small>

                            <?php if ($totalPaginas > 1): ?>
                                <nav aria-label="Navegación de páginas">
                                    <ul class="pagination justify-content-end mb-0">
                                        <li class="page-item <?= ($paginaActual <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?idCliente=<?= $idCliente ?>&pagina=<?= max(1, $paginaActual - 1) ?>" aria-label="Anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>

                                        <?php
                                        // Lógica para mostrar rango de páginas (Actual +/- 2)
                                        $rango = 2;
                                        $inicio = max(1, $paginaActual - $rango);
                                        $fin = min($totalPaginas, $paginaActual + $rango);

                                        if ($inicio == 1) {
                                            $fin = min($totalPaginas, $inicio + ($rango * 2));
                                        }
                                        if ($fin == $totalPaginas) {
                                            $inicio = max(1, $fin - ($rango * 2));
                                        }

                                        for ($p = $inicio; $p <= $fin; $p++):
                                        ?>
                                            <li class="page-item <?= ($paginaActual == $p) ? 'active' : '' ?>">
                                                <a class="page-link" href="?idCliente=<?= $idCliente ?>&pagina=<?= $p ?>">
                                                    <?= $p ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?= ($paginaActual >= $totalPaginas) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?idCliente=<?= $idCliente ?>&pagina=<?= min($totalPaginas, $paginaActual + 1) ?>" aria-label="Siguiente">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require ('../shared/footer.inc.php'); ?>