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
    $Mensaje=Validar_Libros();
    if (empty($Mensaje)) {
        if (InsertarLibros($MiConexion) != false) {
            $Mensaje = 'Se ha registrado correctamente.';
            $_POST = array(); 
            $Estilo = 'success'; 
        }
    }
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Libros</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Libros</li>
          <li class="breadcrumb-item active">Agregar Libros</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Agregar Libros</h5>

              <!-- Horizontal Form -->
              <form method='post'>
                <?php if (!empty($Mensaje)) { ?>
                    <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                    <?php echo $Mensaje; ?>
                    </div>
                <?php } ?>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">ISBN</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="ISBN" id="isbn"
                    value="<?php echo !empty($_POST['ISBN']) ? $_POST['ISBN'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Titulo</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Titulo" id="titulo"
                    value="<?php echo !empty($_POST['Titulo']) ? $_POST['Titulo'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Autor</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Autor" id="autor"
                    value="<?php echo !empty($_POST['Autor']) ? $_POST['Autor'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Editorial</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Editorial" id="editorial"
                    value="<?php echo !empty($_POST['Editorial']) ? $_POST['Editorial'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Precio</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Precio" id="precio"
                    value="<?php echo !empty($_POST['Precio']) ? $_POST['Precio'] : ''; ?>">
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