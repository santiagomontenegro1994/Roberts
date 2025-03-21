<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado
require('barraLateral.inc.php'); // Incluir barra lateral
require_once 'funciones/conexion.php';

$MiConexion = ConexionBD();

// Obtener los datos de la tabla `caja`
$queryCaja = "SELECT c.idCaja, c.Fecha, c.idTurno, c.cajaInicial
              FROM caja c
              ORDER BY c.Fecha DESC";
$resultadoCaja = $MiConexion->query($queryCaja);

// Calcular los totales dinámicamente
$queryTotales = "SELECT dc.idCaja, tp.denominacion AS metodoPago, SUM(dc.monto) AS totalMonto
                 FROM detalle_caja dc
                 JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                 GROUP BY dc.idCaja, tp.denominacion";
$resultadoTotales = $MiConexion->query($queryTotales);

// Crear un array para almacenar los totales por caja
$totalesPorCaja = [];
while ($fila = $resultadoTotales->fetch_assoc()) {
    $idCaja = $fila['idCaja'];
    $metodoPago = $fila['metodoPago'];
    $totalMonto = $fila['totalMonto'];

    if (!isset($totalesPorCaja[$idCaja])) {
        $totalesPorCaja[$idCaja] = [
            'totalEfectivo' => 0,
            'totalTransferencia' => 0,
            'totalTarjeta' => 0,
        ];
    }

    // Asignar los totales según el método de pago
    if ($metodoPago === 'Efectivo') {
        $totalesPorCaja[$idCaja]['totalEfectivo'] = $totalMonto;
    } elseif ($metodoPago === 'Transferencia') {
        $totalesPorCaja[$idCaja]['totalTransferencia'] = $totalMonto;
    } elseif ($metodoPago === 'Tarjeta') {
        $totalesPorCaja[$idCaja]['totalTarjeta'] = $totalMonto;
    }
}

// Obtener los detalles de la tabla `detalle_caja`
$queryDetalleCaja = "SELECT dc.idDetalleCaja, dc.idCaja, tp.denominacion AS metodoPago, ts.denominacion AS tipoServicio, dc.monto
                     FROM detalle_caja dc
                     JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                     JOIN tipo_servicio ts ON dc.IdTipoServicio = ts.idTipoServicio
                     ORDER BY dc.idDetalleCaja DESC";
$resultadoDetalleCaja = $MiConexion->query($queryDetalleCaja);

// Verificar si la consulta de detalle_caja se ejecutó correctamente
if (!$resultadoDetalleCaja) {
    die("Error en la consulta de detalle_caja: " . $MiConexion->error);
}
?>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Planilla de Caja</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Resumen de Caja</h5>

                <div class="row">
                    <?php while ($fila = $resultadoCaja->fetch_assoc()) { 
                        $idCaja = $fila['idCaja'];
                        $cajaInicial = $fila['cajaInicial'];
                        $totalEfectivo = $totalesPorCaja[$idCaja]['totalEfectivo'] ?? 0;
                        $totalTransferencia = $totalesPorCaja[$idCaja]['totalTransferencia'] ?? 0;
                        $totalTarjeta = $totalesPorCaja[$idCaja]['totalTarjeta'] ?? 0;
                        $cajaFuerte = $totalEfectivo - $cajaInicial;
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Caja ID: <?php echo $idCaja; ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><strong>Fecha:</strong> <?php echo $fila['Fecha']; ?></p>
                                    <p class="card-text"><strong>Turno:</strong> <?php echo $fila['idTurno']; ?></p>
                                    <p class="card-text"><strong>Total Efectivo:</strong> $<?php echo number_format($totalEfectivo, 2); ?></p>
                                    <p class="card-text"><strong>Total Transferencia:</strong> $<?php echo number_format($totalTransferencia, 2); ?></p>
                                    <p class="card-text"><strong>Total Tarjeta:</strong> $<?php echo number_format($totalTarjeta, 2); ?></p>
                                    <p class="card-text"><strong>Caja Inicial:</strong> $<?php echo number_format($cajaInicial, 2); ?></p>
                                    <p class="card-text"><strong>Caja Fuerte:</strong> $<?php echo number_format($cajaFuerte, 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <h5 class="card-title mt-5">Detalles de Caja</h5>

                <!-- Tabla de Detalles de Caja -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Detalle</th>
                                <th>ID Caja</th>
                                <th>Método de Pago</th>
                                <th>Tipo de Servicio</th>
                                <th>Usuario</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fila = $resultadoDetalleCaja->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $fila['idDetalleCaja']; ?></td>
                                    <td><?php echo $fila['idCaja']; ?></td>
                                    <td><?php echo $fila['metodoPago']; ?></td>
                                    <td><?php echo $fila['tipoServicio']; ?></td>
                                    <td><?php echo $_SESSION['Usuario_Nombre']; ?></td> <!-- Usuario desde la sesión -->
                                    <td>$<?php echo number_format($fila['monto'], 2); ?></td>
                                    <td>
                                        <!-- Acciones -->
                                        <a href="eliminar_detalle_caja.php?idDetalleCaja=<?php echo $fila['idDetalleCaja']; ?>" 
                                           title="Anular" 
                                           onclick="return confirm('¿Confirma anular este detalle de caja?');">
                                            <i class="bi bi-trash-fill text-danger fs-5"></i>
                                        </a>

                                        <a href="modificar_detalle_caja.php?idDetalleCaja=<?php echo $fila['idDetalleCaja']; ?>" 
                                           title="Modificar">
                                            <i class="bi bi-pencil-fill text-warning fs-5"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
  $_SESSION['Mensaje']='';
  require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
?>

</body>

</html>