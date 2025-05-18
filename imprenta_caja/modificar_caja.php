<?php
ob_start(); // Inicia el búfer de salida
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

$DatosCajaActual = array();

if (!empty($_POST['BotonModificarCaja'])) {
    Validar_Caja();

    if (empty($_SESSION['Mensaje'])) { // Si no hay errores de validación
        $resultado = Modificar_Caja($MiConexion);

        if ($resultado['success']) {
            $_SESSION['Mensaje'] = $resultado['message'];
            $_SESSION['Estilo'] = $resultado['style'];
            header('Location: listados_caja.php');
            exit;
        } else {
            $_SESSION['Mensaje'] = $resultado['message'];
            $_SESSION['Estilo'] = $resultado['style'];
        }
    } else { // Si hay errores de validación
        $_SESSION['Estilo'] = 'warning';
        $DatosCajaActual['IDCAJA'] = !empty($_POST['idCaja']) ? $_POST['idCaja'] : '';
        $DatosCajaActual['FECHA'] = !empty($_POST['Fecha']) ? $_POST['Fecha'] : '';
        $DatosCajaActual['ID_TURNO'] = !empty($_POST['idTurno']) ? $_POST['idTurno'] : '';
        $DatosCajaActual['CAJA_INICIAL'] = !empty($_POST['cajaInicial']) ? $_POST['cajaInicial'] : '';
    }
} else if (!empty($_GET['idCaja'])) {
    $DatosCajaActual = Datos_Caja($MiConexion, $_GET['idCaja']);
}

ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Cajas</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Menú</a></li>
                <li class="breadcrumb-item">Cajas</li>
                <li class="breadcrumb-item active">Modificar Caja</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Modificar Caja</h5>

                <!-- Formulario -->
                <form method='post'>
                    <?php if (!empty($_SESSION['Mensaje'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                            <?php echo $_SESSION['Mensaje']; ?>
                        </div>
                    <?php } ?>

                    <div class="row mb-3">
                        <label for="Fecha" class="col-sm-2 col-form-label">Fecha</label>
                        <div class="col-sm-10">
                            <input type="date" class="form-control" name="Fecha" id="Fecha"
                            value="<?php echo !empty($DatosCajaActual['FECHA']) ? $DatosCajaActual['FECHA'] : ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="idTurno" class="col-sm-2 col-form-label">Turno</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="idTurno" id="idTurno">
                                <option value="">Seleccione un turno</option>
                                <?php foreach (Listar_Turnos($MiConexion) as $turno) { ?>
                                    <option value="<?php echo $turno['idTurno']; ?>"
                                        <?php echo ((int)$DatosCajaActual['IDTURNO'] === (int)$turno['idTurno']) ? 'selected' : ''; ?>>
                                        <?php echo $turno['denominacion']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="cajaInicial" class="col-sm-2 col-form-label">Caja Inicial</label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="cajaInicial" id="cajaInicial" step="0.01"
                            value="<?php echo !empty($DatosCajaActual['CAJA_INICIAL']) ? $DatosCajaActual['CAJA_INICIAL'] : ''; ?>">
                        </div>
                    </div>

                    <div class="text-center">
                        <input type='hidden' name="idCaja" value="<?php echo $DatosCajaActual['IDCAJA']; ?>" />
                        <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarCaja">Modificar</button>
                        <a href="listados_caja.php" class="btn btn-success" title="Listado">Volver al listado</a>
                    </div>
                </form><!-- End Formulario -->

            </div>
        </div>
    </section>

</main><!-- End #main -->

<?php
    $_SESSION['Mensaje'] = '';
    require('footer.inc.php'); // Incluir el footer
?>

</body>
</html>