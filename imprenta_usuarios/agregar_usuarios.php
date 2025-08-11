<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Obtener tipos de usuario para el select
$TiposUsuario = Listar_Tipos_Usuario($MiConexion);

$Mensaje = '';
$Estilo = 'warning';

if (!empty($_POST['BotonRegistrar'])) {
    $Mensaje = Validar_Usuario();
    if (empty($Mensaje)) {
        $resultado = InsertarUsuario($MiConexion);
        if ($resultado === true) {
            $Mensaje = 'Usuario registrado correctamente.';
            $_POST = array();
            $Estilo = 'success';
        } else {
            $Mensaje = $resultado; // Mensaje de error
            $Estilo = 'danger';
        }
    }
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Usuarios</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item">Usuarios</li>
                <li class="breadcrumb-item active">Agregar Usuario</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->
    
    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Agregar Usuario</h5>

                <!-- Horizontal Form -->
                <form method='post'>
                    <?php if (!empty($Mensaje)) { ?>
                        <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                            <?php echo $Mensaje; ?>
                        </div>
                    <?php } ?>
                    
                    <div class="row mb-3">
                        <label for="nombre" class="col-sm-2 col-form-label">Nombre</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="Nombre" id="nombre"
                            value="<?php echo !empty($_POST['Nombre']) ? htmlspecialchars($_POST['Nombre']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="apellido" class="col-sm-2 col-form-label">Apellido</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="Apellido" id="apellido"
                            value="<?php echo !empty($_POST['Apellido']) ? htmlspecialchars($_POST['Apellido']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="usuario" class="col-sm-2 col-form-label">Usuario</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="Usuario" id="usuario"
                            value="<?php echo !empty($_POST['Usuario']) ? htmlspecialchars($_POST['Usuario']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="clave" class="col-sm-2 col-form-label">Contraseña</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="Clave" id="clave">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="confirmar_clave" class="col-sm-2 col-form-label">Confirmar Contraseña</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="ConfirmarClave" id="confirmar_clave">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="tipo_usuario" class="col-sm-2 col-form-label">Tipo de Usuario</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="TipoUsuario" id="tipo_usuario">
                                <?php foreach ($TiposUsuario as $tipo) { ?>
                                    <option value="<?php echo $tipo['ID_TIPO_USUARIO']; ?>"
                                        <?php echo (!empty($_POST['TipoUsuario']) && $_POST['TipoUsuario'] == $tipo['ID_TIPO_USUARIO']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['DENOMINACION']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </form><!-- End Horizontal Form -->
            </div>
        </div>
    </section>
</main><!-- End #main -->

<?php
require ('../shared/footer.inc.php');
?>