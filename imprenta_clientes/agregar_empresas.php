<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { 
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD(); 

require_once '../funciones/imprenta.php';

$Mensaje='';
$Estilo='warning';
if (!empty($_POST['BotonRegistrarEmpresa'])) {
    $Mensaje = Validar_Empresa();
    if (empty($Mensaje)) {
        $resultado = InsertarEmpresa($MiConexion);
        if ($resultado === true) {
            $Mensaje = 'Empresa registrada correctamente.';
            $_POST = array(); 
            $Estilo = 'success';
        } else {
            $Mensaje = $resultado; 
            $Estilo = 'danger';
        }
    }
}
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Empresas</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
          <li class="breadcrumb-item">Clientes / Empresas</li>
          <li class="breadcrumb-item active">Agregar Empresa</li>
        </ol>
      </nav>
    </div>

    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Registrar Nueva Empresa</h5>

              <form method='post'>
                <?php if (!empty($Mensaje)) { ?>
                    <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                    <?php echo $Mensaje; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="nombreEmpresa" class="col-sm-2 col-form-label">Nombre Empresa</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="NombreEmpresa" id="nombreEmpresa"
                    value="<?php echo !empty($_POST['NombreEmpresa']) ? $_POST['NombreEmpresa'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="telefonoEmpresa" class="col-sm-2 col-form-label">Teléfono</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="TelefonoEmpresa" id="telefonoEmpresa"
                    value="<?php echo !empty($_POST['TelefonoEmpresa']) ? $_POST['TelefonoEmpresa'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="cuitEmpresa" class="col-sm-2 col-form-label">CUIT</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="CuitEmpresa" id="cuitEmpresa" placeholder="Ej: 30123456789 (sin guiones)"
                    value="<?php echo !empty($_POST['CuitEmpresa']) ? $_POST['CuitEmpresa'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrarEmpresa">Agregar Empresa</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form>

    </section>

  </main>

  <?php
require ('../shared/footer.inc.php');
?>
</body>
</html>