<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado
require('barraLateral.inc.php'); // Incluir barra lateral
require_once 'funciones/conexion.php';

$MiConexion = ConexionBD();

// Manejar la actualización de Caja Inicial
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCaja = $_POST['idCaja'];
    $cajaInicial = $_POST['cajaInicial'];

    // Validar los datos
    if (!empty($idCaja) && is_numeric($cajaInicial)) {
        // Actualizar el valor de Caja Inicial en la base de datos
        $query = "UPDATE caja SET cajaInicial = ? WHERE idCaja = ?";
        $stmt = $MiConexion->prepare($query);
        $stmt->bind_param("di", $cajaInicial, $idCaja);

        if ($stmt->execute()) {
            header("Location: planilla_caja.php?mensaje=actualizado");
            exit;
        } else {
            echo "Error al actualizar Caja Inicial: " . $MiConexion->error;
        }
    } else {
        echo "Datos inválidos.";
    }
}

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
                     JOIN tipo_servicio ts ON dc.idTipoServicio = ts.idTipoServicio
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
                <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'actualizado') { ?>
                    <div id="mensajeExito" class="alert alert-success" role="alert">
                        ¡Caja Inicial actualizada correctamente!
                    </div>
                <?php } ?>

                <div class="container">
                    <?php while ($fila = $resultadoCaja->fetch_assoc()) { 
                        $idCaja = $fila['idCaja'];
                        $cajaInicial = $fila['cajaInicial'];
                        $totalEfectivo = $totalesPorCaja[$idCaja]['totalEfectivo'] ?? 0;
                        $totalTransferencia = $totalesPorCaja[$idCaja]['totalTransferencia'] ?? 0;
                        $totalTarjeta = $totalesPorCaja[$idCaja]['totalTarjeta'] ?? 0;
                        $cajaFuerte = $totalEfectivo - $cajaInicial;
                    ?>
                            <!-- Encabezado con datos alineados horizontalmente -->
                            <div class="row mt-2 align-items-center">
                                <!-- Caja Inicial a la izquierda -->
                                <div class="col-12 col-md-6 d-flex flex-wrap align-items-center">
                                    <form action="planilla_caja.php" method="POST" class="d-inline d-flex flex-wrap align-items-center">
                                        <input type="hidden" name="idCaja" value="<?php echo $idCaja; ?>">
                                        <label for="cajaInicial_<?php echo $idCaja; ?>" class="me-2 mb-2 mb-md-0"><strong>Caja Inicial:</strong></label>
                                        <input type="number" step="0.01" name="cajaInicial" id="cajaInicial_<?php echo $idCaja; ?>" 
                                               value="<?php echo number_format($cajaInicial, 2, '.', ''); ?>" 
                                               class="form-control form-control-sm text-center w-auto me-2 mb-2 mb-md-0">
                                        <button type="submit" class="btn btn-primary btn-sm">Actualizar</button>
                                    </form>
                                </div>

                                <!-- Caja ID -->
                                <div class="col-12 col-md-2">
                                    <p><strong>Caja ID:</strong> <?php echo $idCaja; ?></p>
                                </div>

                                <!-- Turno -->
                                <div class="col-12 col-md-2">
                                    <p><strong>Turno:</strong> <?php echo $fila['idTurno']; ?></p>
                                </div>

                                <!-- Fecha -->
                                <div class="col-12 col-md-2">
                                    <p><strong>Fecha:</strong> <?php echo $fila['Fecha']; ?></p>
                                </div>
                            </div>
                    <?php } ?>
                </div>

                <script>
                    // Ocultar el mensaje después de 3 segundos
                    setTimeout(function() {
                        const mensaje = document.getElementById('mensajeExito');
                        if (mensaje) {
                            mensaje.style.display = 'none';
                        }
                    }, 3000); // 3000 milisegundos = 3 segundos
                </script>

                <h5 class="card-title pb-0 d-flex justify-content-between align-items-center">
                    Detalles de Caja
                    <a href="agregar_venta.php" class="btn btn-success btn-sm">
                        Agregar Venta
                    </a>
                </h5>

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

                <!-- Totales en un solo renglón -->
                <div class="row mt-4 border-top pt-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Total Efectivo:</strong> $<?php echo number_format($totalEfectivo, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Total Transferencia:</strong> $<?php echo number_format($totalTransferencia, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Total Tarjeta:</strong> $<?php echo number_format($totalTarjeta, 2); ?></p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <p><strong>Caja Fuerte:</strong> $<?php echo number_format($cajaFuerte, 2); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<?php
  $_SESSION['Mensaje']='';
  require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
  ob_end_flush();
?>

</body>

</html>