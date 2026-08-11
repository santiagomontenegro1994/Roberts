<?php
ob_start();
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

$DatosEmpresaActual = array();

if (!empty($_POST['BotonModificarEmpresa'])) {
    Validar_Empresa();

    if (empty($_SESSION['Mensaje'])) { 
        if (Modificar_Empresa($MiConexion) != false) {
            $_SESSION['Mensaje'] = "¡La empresa se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listados_empresas.php');
            exit;
        }
    } else { 
        $_SESSION['Estilo'] = 'warning';
        $DatosEmpresaActual['ID_EMPRESA'] = !empty($_POST['IdEmpresa']) ? $_POST['IdEmpresa'] : '';
        $DatosEmpresaActual['NOMBRE_EMPRESA'] = !empty($_POST['NombreEmpresa']) ? $_POST['NombreEmpresa'] : '';
        $DatosEmpresaActual['TELEFONO'] = !empty($_POST['TelefonoEmpresa']) ? $_POST['TelefonoEmpresa'] : '';
        $DatosEmpresaActual['CUIT'] = !empty($_POST['CuitEmpresa']) ? $_POST['CuitEmpresa'] : '';
    }
} else if (!empty($_GET['ID_EMPRESA'])) {
    $DatosEmpresaActual = Datos_Empresa($MiConexion, $_GET['ID_EMPRESA']);
}

ob_end_flush();
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Empresas</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
          <li class="breadcrumb-item">Clientes / Empresas</li>
          <li class="breadcrumb-item active">Modificar Empresa</li>
        </ol>
      </nav>
    </div>
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Empresa</h5>

                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="nombreEmpresa" class="col-sm-2 col-form-label">Nombre Empresa</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="NombreEmpresa" id="nombreEmpresa"
                    value="<?php echo !empty($DatosEmpresaActual['NOMBRE_EMPRESA']) ? $DatosEmpresaActual['NOMBRE_EMPRESA'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="telefonoEmpresa" class="col-sm-2 col-form-label">Teléfono</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="TelefonoEmpresa" id="telefonoEmpresa"
                    value="<?php echo !empty($DatosEmpresaActual['TELEFONO']) ? $DatosEmpresaActual['TELEFONO'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="cuitEmpresa" class="col-sm-2 col-form-label">CUIT</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="CuitEmpresa" id="cuitEmpresa"
                    value="<?php echo !empty($DatosEmpresaActual['CUIT']) ? $DatosEmpresaActual['CUIT'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                    <input type='hidden' name="IdEmpresa" value="<?php echo $DatosEmpresaActual['ID_EMPRESA']; ?>" />
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarEmpresa">Modificar</button>
                    <a href="listados_empresas.php" class="btn btn-success btn-info" title="Listado"> Volver al listado  </a>
                </div>
              </form>

    </section>

  </main>

<?php
    $_SESSION['Mensaje']='';
    require ('../shared/footer.inc.php');
?>
</body>
</html>