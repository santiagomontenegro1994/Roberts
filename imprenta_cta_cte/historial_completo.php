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

// Validar ID cliente
$idCliente = isset($_GET['idCliente']) ? intval($_GET['idCliente']) : 0;

if ($idCliente <= 0) {
    header('Location: cta_cte.php');
    exit;
}

// Obtener información del cliente (Para el título)
$cliente = Obtener_Cliente_Por_ID($MiConexion, $idCliente);

if (!$cliente) {
    $_SESSION['Mensaje'] = "Cliente no encontrado";
    $_SESSION['Estilo'] = "danger";
    header('Location: cta_cte.php');
    exit;
}

// --- LÓGICA DE PAGINACIÓN ---
$registrosPorPagina = 50;
$paginaActual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// 1. Contar total de registros para saber cuántas páginas son
$sqlCount = "SELECT COUNT(*) as total FROM movimientos_cta_cte WHERE idCliente = ?";
$stmtCount = $MiConexion->prepare($sqlCount);
$stmtCount->bind_param("i", $idCliente);
$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$totalRegistros = $rowCount['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// 2. Obtener los registros de la página actual (LIMIT y OFFSET)
$sqlMovimientos = "SELECT 
                    m.*, 
                    u.nombre as usuarioNombre, 
                    u.apellido as usuarioApellido
                   FROM movimientos_cta_cte m
                   LEFT JOIN usuarios u ON m.idUsuario = u.idUsuario
                   WHERE m.idCliente = ?
                   ORDER BY m.fecha DESC
                   LIMIT ? OFFSET ?";

$stmt = $MiConexion->prepare($sqlMovimientos);
$stmt->bind_param("iii", $idCliente, $registrosPorPagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
$movimientos = [];
while ($fila = $resultado->fetch_assoc()) {
    $movimientos[] = $fila;
}
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
                                    <?php if (count($movimientos) > 0): ?>
                                        <?php foreach ($movimientos as $mov): ?>
                                            <?php 
                                            // Definir colores según tipo
                                            $badgeClass = [
                                                'DEPOSITO' => 'bg-success',
                                                'PAGO_DIRECTO' => 'bg-primary',
                                                'AJUSTE' => 'bg-warning text-dark',
                                                'APLICACION_AUTOMATICA' => 'bg-info text-dark',
                                                'NOTA_DEBITO' => 'bg-danger'
                                            ][$mov['tipo']] ?? 'bg-secondary';
                                            
                                            // Determinar si el monto suma o resta visualmente
                                            $esPositivo = ($mov['tipo'] == 'DEPOSITO' || $mov['tipo'] == 'APLICACION_AUTOMATICA' || ($mov['tipo'] == 'AJUSTE' && $mov['monto'] > 0));
                                            ?>
                                            <tr>
                                                <td style="white-space: nowrap;">
                                                    <?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $badgeClass ?>"><?= $mov['tipo'] ?></span>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($mov['observaciones']) ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($mov['usuarioNombre'] . ' ' . $mov['usuarioApellido']) ?>
                                                    </small>
                                                </td>
                                                <td class="text-end fw-bold <?= $esPositivo ? 'text-success' : 'text-danger' ?>">
                                                    $<?= number_format($mov['monto'], 2, ',', '.') ?>
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
                                Mostrando <?= count($movimientos) ?> de <?= $totalRegistros ?> registros.
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
                                        // Rango de páginas a mostrar (actual +/- 2)
                                        $rango = 2;
                                        $inicio = max(1, $paginaActual - $rango);
                                        $fin = min($totalPaginas, $paginaActual + $rango);

                                        // Si estamos muy al principio, mostrar más del final
                                        if ($inicio == 1) {
                                            $fin = min($totalPaginas, $inicio + ($rango * 2));
                                        }
                                        // Si estamos muy al final, mostrar más del principio
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