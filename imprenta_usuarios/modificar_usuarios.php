<?php
ob_start(); // Inicia el búfer de salida
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php'); // Incluir encabezado
require('../shared/barraLateral.inc.php'); // Incluir barra lateral

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Obtener tipos de usuario para el select
$TiposUsuario = Listar_Tipos_Usuario($MiConexion);

$DatosUsuarioActual = array();

if (!empty($_POST['BotonModificarUsuario'])) {
    Validar_Usuario_Modificacion();

    if (empty($_SESSION['Mensaje'])) { // Si no hay errores de validación
        if (Modificar_Usuario($MiConexion) != false) {
            $_SESSION['Mensaje'] = "El usuario se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listado_usuarios.php');
            exit;
        }
    } else { // Si hay errores de validación
        $_SESSION['Estilo'] = 'warning';
        $DatosUsuarioActual['ID_USUARIO'] = !empty($_POST['IdUsuario']) ? $_POST['IdUsuario'] : '';
        $DatosUsuarioActual['NOMBRE'] = !empty($_POST['Nombre']) ? $_POST['Nombre'] : '';
        $DatosUsuarioActual['APELLIDO'] = !empty($_POST['Apellido']) ? $_POST['Apellido'] : '';
        $DatosUsuarioActual['USUARIO'] = !empty($_POST['Usuario']) ? $_POST['Usuario'] : '';
        $DatosUsuarioActual['ID_TIPO_USUARIO'] = !empty($_POST['TipoUsuario']) ? $_POST['TipoUsuario'] : '';
    }
} else if (!empty($_GET['ID_USUARIO'])) {
    $DatosUsuarioActual = Datos_Usuario($MiConexion, $_GET['ID_USUARIO']);
}

ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Usuarios</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item">Usuarios</li>
                <li class="breadcrumb-item active">Modificar Usuario</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->
    
    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Modificar Usuario</h5>

                <!-- Horizontal Form -->
                <form method='post'>
                    <?php if (!empty($_SESSION['Mensaje'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                            <?php echo $_SESSION['Mensaje']; ?>
                        </div>
                    <?php } ?>
                    
                    <div class="row mb-3">
                        <label for="nombre" class="col-sm-2 col-form-label">Nombre</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="Nombre" id="nombre"
                            value="<?php echo !empty($DatosUsuarioActual['NOMBRE']) ? htmlspecialchars($DatosUsuarioActual['NOMBRE']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="apellido" class="col-sm-2 col-form-label">Apellido</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="Apellido" id="apellido"
                            value="<?php echo !empty($DatosUsuarioActual['APELLIDO']) ? htmlspecialchars($DatosUsuarioActual['APELLIDO']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="usuario" class="col-sm-2 col-form-label">Usuario</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="Usuario" id="usuario"
                            value="<?php echo !empty($DatosUsuarioActual['USUARIO']) ? htmlspecialchars($DatosUsuarioActual['USUARIO']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="tipo_usuario" class="col-sm-2 col-form-label">Tipo de Usuario</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="TipoUsuario" id="tipo_usuario">
                                <?php foreach ($TiposUsuario as $tipo) { ?>
                                    <option value="<?php echo $tipo['ID_TIPO_USUARIO']; ?>"
                                        <?php echo (!empty($DatosUsuarioActual['ID_TIPO_USUARIO']) && $DatosUsuarioActual['ID_TIPO_USUARIO'] == $tipo['ID_TIPO_USUARIO']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['DENOMINACION']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">Restablecer contraseña</label>
                        <div class="col-sm-10">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ResetearClave" id="resetearClave">
                                <label class="form-check-label" for="resetearClave">
                                    Restablecer a "12345"
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <input type='hidden' name="IdUsuario" value="<?php echo $DatosUsuarioActual['ID_USUARIO']; ?>" />
                        <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarUsuario">Modificar</button>
                        <a href="listado_usuarios.php" class="btn btn-success btn-info" title="Listado">Volver al listado</a>
                    </div>

                    <script>
                    document.querySelector('form').addEventListener('submit', function(e) {
                        if (document.getElementById('resetearClave').checked) {
                            if (!confirm('¿Está seguro que desea restablecer la contraseña a "12345"?')) {
                                e.preventDefault();
                            }
                        }
                    });
                    </script>
                </form><!-- End Horizontal Form -->
            </div>
        </div>
    </section>
</main><!-- End #main -->

<?php
    $_SESSION['Mensaje'] = '';
    require('../shared/footer.inc.php');
?>