<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre']) || empty($_SESSION['Usuario_Id'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();
$DatosUsuario = ObtenerDatosUsuario($MiConexion, $_SESSION['Usuario_Id']);

if (!$DatosUsuario) {
    $_SESSION['Mensaje'] = 'Error: Usuario no encontrado';
    $_SESSION['Estilo'] = 'danger';
    header('Location: ../core/index.php');
    exit;
}

$Mensaje = '';
$MensajeExito = '';

if (!empty($_POST['BotonActualizar'])) {
    // Validación básica
    if (empty($_POST['ClaveActual'])) {
        $Mensaje = 'Debe ingresar su contraseña actual';
    } 
    // Validar si quiere cambiar contraseña y coinciden
    elseif (!empty($_POST['NuevaClave']) && $_POST['NuevaClave'] !== $_POST['ConfirmarClave']) {
        $Mensaje = 'Las nuevas contraseñas no coinciden';
    }
    else {
        // Verificar contraseña actual
        if (!VerificarCredencialesActuales($MiConexion, $_SESSION['Usuario_Id'], $_POST['ClaveActual'])) {
            $Mensaje = 'Contraseña actual incorrecta';
        }
        // Verificar nuevo usuario (si aplica)
        elseif (!empty($_POST['NuevoUsuario']) && $_POST['NuevoUsuario'] !== $DatosUsuario['usuario']) {
            if (!VerificarDisponibilidadUsuario($MiConexion, $_SESSION['Usuario_Id'], $_POST['NuevoUsuario'])) {
                $Mensaje = 'El nuevo nombre de usuario ya está en uso';
            }
            elseif (!ActualizarCredenciales(
                $MiConexion,
                $_SESSION['Usuario_Id'],
                $_POST['NuevoUsuario'],
                $_POST['NuevaClave'] ?? null
            )) {
                $Mensaje = 'Error al actualizar el nombre de usuario';
            }
            else {
                $MensajeExito = '¡Credenciales actualizadas correctamente!';
                $DatosUsuario['usuario'] = $_POST['NuevoUsuario'];
            }
        }
        // Solo cambiar contraseña
        elseif (!empty($_POST['NuevaClave'])) {
            if (ActualizarCredenciales(
                $MiConexion,
                $_SESSION['Usuario_Id'],
                $DatosUsuario['usuario'],
                $_POST['NuevaClave']
            )) {
                $MensajeExito = '¡Contraseña actualizada correctamente!';
            } else {
                $Mensaje = 'Error al actualizar la contraseña';
            }
        }
        else {
            $Mensaje = 'No se realizaron cambios';
        }
    }
}

ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Cambiar Credenciales</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Inicio</a></li>
                <li class="breadcrumb-item active">Credenciales</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Actualizar mis credenciales</h5>

                <?php if (!empty($Mensaje)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $Mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($MensajeExito)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $MensajeExito; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Usuario actual:</strong> <?php echo htmlspecialchars($DatosUsuario['usuario']); ?>
                            <br>
                            <strong>Nombre:</strong> <?php echo htmlspecialchars($DatosUsuario['nombre'].' '.$DatosUsuario['apellido']); ?>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label for="ClaveActual" class="form-label">Contraseña Actual*</label>
                        <input type="password" class="form-control" name="ClaveActual" required>
                        <small class="text-muted">Ingrese su contraseña actual para confirmar cambios</small>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cambiarUsuario" name="cambiarUsuario">
                            <label class="form-check-label" for="cambiarUsuario">
                                Deseo cambiar mi nombre de usuario
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12" id="nuevoUsuarioContainer" style="display:none;">
                        <label for="NuevoUsuario" class="form-label">Nuevo Usuario</label>
                        <input type="text" class="form-control" name="NuevoUsuario">
                        <small class="text-muted">Mínimo 4 caracteres, sin espacios</small>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cambiarClave" name="cambiarClave">
                            <label class="form-check-label" for="cambiarClave">
                                Deseo cambiar mi contraseña
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12" id="nuevaClaveContainer" style="display:none;">
                        <label for="NuevaClave" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="NuevaClave">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="col-md-12" id="confirmarClaveContainer" style="display:none;">
                        <label for="ConfirmarClave" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" name="ConfirmarClave">
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" name="BotonActualizar" class="btn btn-primary" value="1">Actualizar Credenciales</button>
                        <a href="../core/index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<script>
// Mostrar/ocultar campos según selección
document.getElementById('cambiarUsuario').addEventListener('change', function() {
    document.getElementById('nuevoUsuarioContainer').style.display = this.checked ? 'block' : 'none';
    if (this.checked) {
        document.querySelector('[name="NuevoUsuario"]').focus();
    }
});

document.getElementById('cambiarClave').addEventListener('change', function() {
    const display = this.checked ? 'block' : 'none';
    document.getElementById('nuevaClaveContainer').style.display = display;
    document.getElementById('confirmarClaveContainer').style.display = display;
    if (this.checked) {
        document.querySelector('[name="NuevaClave"]').focus();
    }
});

// Limpiar campos después de éxito
<?php if (!empty($MensajeExito)): ?>
    document.querySelector('form').reset();
    document.getElementById('nuevoUsuarioContainer').style.display = 'none';
    document.getElementById('nuevaClaveContainer').style.display = 'none';
    document.getElementById('confirmarClaveContainer').style.display = 'none';
    document.getElementById('cambiarUsuario').checked = false;
    document.getElementById('cambiarClave').checked = false;
<?php endif; ?>
</script>

<?php
require('../shared/footer.inc.php');
?>