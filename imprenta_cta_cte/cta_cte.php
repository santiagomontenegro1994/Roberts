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

// Inicialización de variables
$ListadoClientesCC = array();
$CantidadClientes = 0;
$parametro = '';
$criterio = 'Cliente';

// Procesar búsquedas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parametro = trim($_POST['parametro'] ?? '');
    $criterio = $_POST['gridRadios'] ?? 'Cliente';
    
    if (!empty($parametro)) {
        $ListadoClientesCC = Listar_Clientes_Cuenta_Corriente_Parametro($MiConexion, $criterio, $parametro);
    } else {
        $ListadoClientesCC = Listar_Clientes_Cuenta_Corriente($MiConexion);
    }
} else {
    $ListadoClientesCC = Listar_Clientes_Cuenta_Corriente($MiConexion);
}

$CantidadClientes = count($ListadoClientesCC);
?>

<main id="main" class="main">
<div class="pagetitle">
  <h1>Cuenta Corriente - Clientes</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
      <li class="breadcrumb-item">Clientes</li>
      <li class="breadcrumb-item active">Cuenta Corriente</li>
    </ol>
  </nav>
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Listado de Clientes con Cuenta Corriente</h5>
            
            <?php if (!empty($_SESSION['Mensaje'])) { ?>
                <div class="alert alert-<?= $_SESSION['Estilo'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['Mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['Mensaje']); unset($_SESSION['Estilo']); ?>
            <?php } ?>

            <form method="POST" class="mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="parametro" id="parametro" 
                               value="<?= htmlspecialchars($parametro) ?>" 
                               placeholder="Buscar...">
                    </div>
                    
                    <div class="col-md-4">
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary" name="BotonBuscar" value="1">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="cta_cte.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Cliente" 
                                   <?= ($criterio == 'Cliente') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios1">Cliente</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Telefono"
                                   <?= ($criterio == 'Telefono') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios2">Teléfono</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="idCliente"
                                   <?= ($criterio == 'idCliente') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="gridRadios3">ID</label>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col" class="text-end">Saldo Proyectado</th>
                            <th scope="col" class="text-end">Trabajos CC</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($CantidadClientes > 0): ?>
                            <?php foreach ($ListadoClientesCC as $cliente): ?>
                                <?php 
                                // --- LÓGICA DE CÁLCULO ---
                                $idCli = $cliente['ID_CLIENTE'];

                                // 1. Obtener saldo actual (Base de Datos)
                                $saldoClienteReal = ObtenerSaldoCliente($MiConexion, $idCli);

                                // 2. Obtener trabajos cta cte
                                $trabajosPendientes = Obtener_Trabajos_Pendientes($MiConexion, $idCli);
                                
                                // 3. Sumar pendientes
                                $totalPendiente = 0;
                                if (!empty($trabajosPendientes)) {
                                    foreach($trabajosPendientes as $tp) {
                                        $totalPendiente += floatval($tp['PRECIO']);
                                    }
                                }

                                // 4. Cálculo final (Saldo Actual - Pendientes)
                                $saldoProyectado = $saldoClienteReal - $totalPendiente;
                                ?>

                                <tr>
                                    <td><?= $cliente['ID_CLIENTE'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($cliente['NOMBRE'] . ' ' . htmlspecialchars($cliente['APELLIDO'])) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($cliente['TELEFONO']) ?></td>
                                    
                                    <td class="text-end fw-bold <?= $saldoProyectado >= 0 ? 'text-success' : 'text-danger' ?>">
                                        $<?= number_format(abs($saldoProyectado), 2, ',', '.') ?>
                                    </td>

                                    <td class="text-end"><?= $cliente['CANTIDAD_TRABAJOS'] ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="detalle_cta_cte.php?idCliente=<?= $cliente['ID_CLIENTE'] ?>"
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver detalle">
                                                <i class="bi bi-eye"></i> Detalle
                                            </a>
                                            <a href="generar_pdf_cta_cte.php?idCliente=<?= $cliente['ID_CLIENTE'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               title="Descargar PDF">
                                                <i class="bi bi-file-earmark-pdf"></i> PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No hay clientes con cuenta corriente</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require ('../shared/footer.inc.php'); ?>