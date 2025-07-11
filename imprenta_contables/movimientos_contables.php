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

// Obtener los datos
$movimientos = Listar_Movimientos_Contables($MiConexion);
$totalBajaFuerte = Obtener_Total_Caja_Fuerte($MiConexion);
$totalBanco = Obtener_Total_Banco($MiConexion);

$MiConexion->close();
ob_end_flush();
?>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Contables</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item">Movimientos Contables</li>
                <li class="breadcrumb-item active">Lista Histórica</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Movimientos Contables Históricos</h5>

                <!-- Mostrar totales -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-subtitle mb-2">Total Caja Fuerte</h6>
                                <h4 class="card-title text-primary">$<?php echo number_format($totalBajaFuerte, 2, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-subtitle mb-2">Total Banco</h6>
                                <h4 class="card-title text-primary">$<?php echo number_format($totalBanco, 2, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de movimientos -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tipo Pago</th>
                                <th>Tipo Movimiento</th>
                                <th>Monto</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td><?php echo $movimiento['idDetalleCaja']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha'])); ?></td>
                                <td><?php echo $movimiento['tipo_pago']; ?></td>
                                <td><?php echo $movimiento['tipo_movimiento']; ?></td>
                                <td>$<?php echo number_format($movimiento['monto'], 2, ',', '.'); ?></td>
                                <td><?php echo $movimiento['observaciones']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>

</main><!-- End #main -->

<?php
require ('../shared/footer.inc.php');
?>

</body>
</html>