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

$DatosTipoServicioActual = array();

if (!empty($_POST['BotonModificarCliente'])) {
    Validar_Tipos_Servicio();

    if (empty($_SESSION['Mensaje'])) { // Si no hay errores de validación
        if (Modificar_Tipo_Servicio($MiConexion) != false) {
            $_SESSION['Mensaje'] = "El Tipo de Servicio se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listados_tipos_servicios.php');
            exit;
        }
    } else { // Si hay errores de validación
        $_SESSION['Estilo'] = 'warning';
        $DatosTipoServicioActual['IdTipoServicio'] = !empty($_POST['IdTipoServicio']) ? $_POST['IdTipoServicio'] : '';
        $DatosTipoServicioActual['Denominacion'] = !empty($_POST['Denominacion']) ? $_POST['Denominacion'] : '';
    }
} else if (!empty($_GET['idTipoServicio'])) {
    $DatosTipoServicioActual = Datos_Tipo_Servicio($MiConexion, $_GET['idTipoServicio']);
}

ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">

    <div class="pagetitle">
      <h1>Tipos de Servicios</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Ventas</a></li>
          <li class="breadcrumb-item">Tipos de Servicios</li>
          <li class="breadcrumb-item active">Modificar Tipo de Servicio</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Tipo de Servicio</h5>

              <!-- Formulario -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="denominacion" class="col-sm-2 col-form-label">Denominación</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Denominacion" id="denominacion"
                    value="<?php echo !empty($DatosTipoServicioActual['Denominacion']) ? $DatosTipoServicioActual['Denominacion'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                    <input type='hidden' name="IdTipoServicio" value="<?php echo $DatosTipoServicioActual['IdTipoServicio']; ?>"/>
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarCliente">Modificar</button>
                    <a href="listados_tipos_servicios.php" 
                    class="btn btn-success btn-info" 
                    title="Listado"> Volver al listado  </a>
                </div>
              </form><!-- End Formulario -->

    </section>

</main><!-- End #main -->

<?php
    $_SESSION['Mensaje'] = '';
    require('footer.inc.php'); // Incluir footer
?>

</body>
</html>