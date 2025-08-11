<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Obtener listado inicial de usuarios (solo activos por defecto)
$mostrarInactivos = isset($_GET['mostrar_inactivos']) ? true : false;
$ListadoUsuarios = Listar_Usuarios($MiConexion, $mostrarInactivos);
$CantidadUsuarios = count($ListadoUsuarios);

// Procesar búsqueda si se envió el formulario
if (!empty($_POST['BotonBuscar'])) {
    $parametro = $_POST['parametro'];
    $criterio = $_POST['gridRadios'];
    $ListadoUsuarios = Listar_Usuarios_Parametro($MiConexion, $criterio, $parametro, $mostrarInactivos);
    $CantidadUsuarios = count($ListadoUsuarios);
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Listado de Usuarios</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item">Usuarios</li>
                <li class="breadcrumb-item active">Listado Usuarios</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Listado de Usuarios</h5>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje'] ?>
                    </div>
                    <?php $_SESSION['Mensaje'] = ''; ?>
                <?php } ?>

                <div class="d-flex justify-content-between mb-3">
                    <form method="POST" class="w-75">
                        <div class="row mb-4">
                            <label for="inputEmail3" class="col-sm-1 col-form-label">Buscar</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control" name="parametro" id="parametro" 
                                       value="<?php echo !empty($_POST['parametro']) ? htmlspecialchars($_POST['parametro']) : ''; ?>">
                            </div>

                            <div class="col-sm-3 mt-2">
                                <button type="submit" class="btn btn-success btn-xs d-inline-block" value="buscar" name="BotonBuscar">Buscar</button>
                                <button type="reset" class="btn btn-danger btn-xs d-inline-block">Limpiar</button>
                            </div>
                            <div class="col-sm-5 mt-2">
                                <div class="form-check form-check-inline small-text">
                                    <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="nombre_completo" checked>
                                    <label class="form-check-label" for="gridRadios1">
                                        Nombre
                                    </label>
                                </div>
                                <div class="form-check form-check-inline small-text">
                                    <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="usuario">
                                    <label class="form-check-label" for="gridRadios2">
                                        Usuario
                                    </label>
                                </div>
                                <div class="form-check form-check-inline small-text">
                                    <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="idUsuario">
                                    <label class="form-check-label" for="gridRadios3">
                                        ID
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="form-check form-switch align-self-center">
                        <input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault" 
                               onchange="toggleInactiveUsers(this)" <?php echo $mostrarInactivos ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="flexSwitchCheckDefault">Mostrar inactivos</label>
                    </div>
                </div>

                <!-- Table with stripped rows -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Usuario</th>
                                <th scope="col">Nombre Completo</th>
                                <th scope="col">Tipo Usuario</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < $CantidadUsuarios; $i++) { ?>
                                <tr>
                                    <td class="extra-small"><?php echo $ListadoUsuarios[$i]['ID_USUARIO']; ?></td>
                                    <td class="extra-small"><?php echo $ListadoUsuarios[$i]['USUARIO']; ?></td>
                                    <td class="extra-small"><?php echo $ListadoUsuarios[$i]['NOMBRE_COMPLETO']; ?></td>
                                    <td class="extra-small"><?php echo $ListadoUsuarios[$i]['TIPO_USUARIO']; ?></td>
                                    <td class="extra-small">
                                        <?php if ($ListadoUsuarios[$i]['ID_ACTIVO'] == 1): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php elseif ($ListadoUsuarios[$i]['ID_ACTIVO'] == 2): ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo $ListadoUsuarios[$i]['ID_ACTIVO']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="extra-small">
                                        <?php if ($ListadoUsuarios[$i]['ID_ACTIVO'] == 1): ?>
                                            <a href="eliminar_usuarios.php?ID_USUARIO=<?php echo $ListadoUsuarios[$i]['ID_USUARIO']; ?>" 
                                               class="btn btn-xs btn-danger me-2"
                                               title="Desactivar" 
                                               onclick="return confirm('¿Confirma desactivar este usuario?');">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="reactivar_usuarios.php?ID_USUARIO=<?php echo $ListadoUsuarios[$i]['ID_USUARIO']; ?>" 
                                               class="btn btn-xs btn-success me-2"
                                               title="Reactivar" 
                                               onclick="return confirm('¿Confirma reactivar este usuario?');">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="modificar_usuarios.php?ID_USUARIO=<?php echo $ListadoUsuarios[$i]['ID_USUARIO']; ?>"  
                                           class="btn btn-xs btn-warning me-2"
                                           title="Modificar">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!-- End Table with stripped rows -->
            </div>
        </div>
    </section>
</main><!-- End #main -->

<script>
function toggleInactiveUsers(checkbox) {
    const url = new URL(window.location.href);
    if (checkbox.checked) {
        url.searchParams.set('mostrar_inactivos', '1');
    } else {
        url.searchParams.delete('mostrar_inactivos');
    }
    window.location.href = url.toString();
}
</script>

<?php
require('../shared/footer.inc.php');
?>