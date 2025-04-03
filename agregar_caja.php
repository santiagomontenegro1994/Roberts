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

require_once 'funciones/select_general.php';

// Obtener los turnos disponibles desde la base de datos
$Turnos = [];
$queryTurnos = "SELECT idTurno, denominacion FROM turnos";
$resultadoTurnos = $MiConexion->query($queryTurnos);
if ($resultadoTurnos) {
    while ($fila = $resultadoTurnos->fetch_assoc()) {
        $Turnos[] = $fila;
    }
}

$Mensaje = '';
$Estilo = 'warning';

if (!empty($_POST['BotonRegistrar'])) {
    // Validar y limpiar los datos del formulario
    $Fecha = !empty($_POST['Fecha']) ? $_POST['Fecha'] : null;
    $idTurno = !empty($_POST['idTurno']) ? (int)$_POST['idTurno'] : null;
    $cajaInicial = !empty($_POST['cajaInicial']) ? (float)$_POST['cajaInicial'] : null;

    if ($Fecha && $idTurno && $cajaInicial >= 0) {
        // Insertar la nueva caja en la base de datos
        $query = "INSERT INTO caja (Fecha, idTurno, cajaInicial) VALUES (?, ?, ?)";
        $stmt = $MiConexion->prepare($query);
        $stmt->bind_param("sid", $Fecha, $idTurno, $cajaInicial);

        if ($stmt->execute()) {
            $Mensaje = 'La caja se ha registrado correctamente.';
            $Estilo = 'success';
            $_POST = array(); // Limpiar los datos del formulario
        } else {
            $Mensaje = 'Error al registrar la caja: ' . $stmt->error;
            $Estilo = 'danger';
        }

        $stmt->close();
    } else {
        $Mensaje = 'Por favor, complete todos los campos correctamente.';
        $Estilo = 'warning';
    }
}

$MiConexion->close();
?>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Cajas</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Menú</a></li>
                <li class="breadcrumb-item">Ventas</li>
                <li class="breadcrumb-item">Listado de Cajas</li>
                <li class="breadcrumb-item active">Agregar Caja</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Agregar Caja</h5>

                <!-- Formulario -->
                <form method="post">
                    <?php if (!empty($Mensaje)) { ?>
                        <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                            <?php echo $Mensaje; ?>
                        </div>
                    <?php } ?>

                    <div class="row mb-3">
                        <label for="Fecha" class="col-sm-2 col-form-label">Fecha</label>
                        <div class="col-sm-10">
                            <input type="date" class="form-control" name="Fecha" id="Fecha"
                                value="<?php echo !empty($_POST['Fecha']) ? $_POST['Fecha'] : ''; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="idTurno" class="col-sm-2 col-form-label">Turno</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="idTurno" id="idTurno" required>
                                <option value="">Seleccione un turno</option>
                                <?php foreach ($Turnos as $turno) { ?>
                                    <option value="<?php echo $turno['idTurno']; ?>" 
                                        <?php echo (!empty($_POST['idTurno']) && $_POST['idTurno'] == $turno['idTurno']) ? 'selected' : ''; ?>>
                                        <?php echo $turno['denominacion']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="cajaInicial" class="col-sm-2 col-form-label">Caja Inicial</label>
                        <div class="col-sm-10">
                            <input type="number" step="1" class="form-control" name="cajaInicial" id="cajaInicial"
                                value="<?php echo !empty($_POST['cajaInicial']) ? $_POST['cajaInicial'] : '19500'; ?>" required>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </form><!-- End Formulario -->

            </div>
        </div>
    </section>

</main><!-- End #main -->

<?php
require('footer.inc.php'); // Incluir footer
?>

</body>
</html>