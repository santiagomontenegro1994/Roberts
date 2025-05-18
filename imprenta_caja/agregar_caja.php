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

require_once 'funciones/imprenta.php';

// Obtener los turnos disponibles desde la base de datos
$Turnos = Listar_Turnos($MiConexion);

if (!empty($_POST['BotonRegistrar'])) {
    // Validar y limpiar los datos del formulario
    $Fecha = !empty($_POST['Fecha']) ? $_POST['Fecha'] : null;
    $idTurno = !empty($_POST['idTurno']) ? (int)$_POST['idTurno'] : null;
    $cajaInicial = !empty($_POST['cajaInicial']) ? (int)$_POST['cajaInicial'] : null;

    if ($Fecha && $idTurno && $cajaInicial >= 0) {
        // Llamar a la función para insertar la caja
        $resultado = InsertarCaja($MiConexion, $Fecha, $idTurno, $cajaInicial);

        // Manejar el resultado de la función
        $_SESSION['Mensaje'] = $resultado['message'];
        $_SESSION['Estilo'] = $resultado['style'];
    } else {
        $_SESSION['Mensaje'] = 'Por favor, complete todos los campos correctamente.';
        $_SESSION['Estilo'] = 'warning';
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
                    <?php if (!empty($_SESSION['Mensaje'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['Mensaje']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['Mensaje'], $_SESSION['Estilo']); // Limpiar el mensaje después de mostrarlo ?>
                    <?php } ?>

                    <div class="row mb-3">
                        <!-- Campo de Fecha -->
                        <div class="col-md-6">
                            <label for="Fecha" class="form-label">Fecha</label>
                            <input type="date" class="form-control" name="Fecha" id="Fecha"
                                value="<?php echo !empty($_POST['Fecha']) ? $_POST['Fecha'] : ''; ?>" required>
                        </div>

                        <!-- Campo de Turno -->
                        <div class="col-md-6">
                            <label for="idTurno" class="form-label">Turno</label>
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
                        <a href="listados_caja.php" class="btn btn-info">Volver a Listado</a>
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