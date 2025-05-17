<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

require_once 'funciones/conexion.php';
$MiConexion=ConexionBD(); 

require_once 'funciones/select_general.php';

$Mensaje='';
$Estilo='warning';
if (!empty($_POST['BotonRegistrar'])) {
    //estoy en condiciones de poder validar los datos
    $Mensaje=Validar_Proveedor();
    if (empty($Mensaje)) {
        if (InsertarProveedores($MiConexion) != false) {
            $Mensaje = 'Se ha registrado correctamente.';
            $_POST = array(); 
            $Estilo = 'success'; 
        }
    }
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Proveedores</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Proveedores</li>
          <li class="breadcrumb-item active">Agregar Proveedores</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Agregar Proveedores</h5>

              <!-- Horizontal Form -->
              <form method='post'>
                <?php if (!empty($Mensaje)) { ?>
                    <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                    <?php echo $Mensaje; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Nombre</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Nombre" id="nombre"
                    value="<?php echo !empty($_POST['Nombre']) ? $_POST['Nombre'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Contacto</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Contacto" id="contacto"
                    value="<?php echo !empty($_POST['Contacto']) ? $_POST['Contacto'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">CUIT</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="CUIT" id="cuit"
                    value="<?php echo !empty($_POST['CUIT']) ? $_POST['CUIT'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form><!-- End Horizontal Form -->

    </section>

  </main><!-- End #main -->

  <?php
require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo

?>


</body>

</html>