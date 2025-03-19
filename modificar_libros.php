<?php
ob_start(); // Inicia el búfer de salida
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo
require('barraLateral.inc.php'); // Incluir barra lateral

require_once 'funciones/conexion.php';
$MiConexion=ConexionBD();

//ahora voy a llamar el script gral para usar las funciones necesarias
require_once 'funciones/select_general.php';
 
//este array contendra los datos de la consulta original, y cuando 
//pulse el boton, mantendrá los datos ingresados hasta que se validen y se puedan modificar
$DatosLibroActual=array();

if (!empty($_POST['BotonModificarLibro'])) {
    Validar_Libros();

    if (empty($_SESSION['Mensaje'])) { //ya toque el boton modificar y el mensaje esta vacio...
        
        if (Modificar_Libros($MiConexion) != false) {
            $_SESSION['Mensaje'] = "Tu libro se ha modificado correctamente!";
            $_SESSION['Estilo']='success';
            header('Location: listados_libros.php');
            exit;
        }

    }else {  //ya toque el boton modificar y el mensaje NO esta vacio...
        $_SESSION['Estilo']='warning';
        $DatosLibroActual['ID_LIBRO'] = !empty($_POST['IdLibro']) ? $_POST['IdLibro'] :'';
        $DatosLibroActual['ISBN'] = !empty($_POST['ISBN']) ? $_POST['ISBN'] :'';
        $DatosLibroActual['AUTOR'] = !empty($_POST['Autor']) ? $_POST['Autor'] :'';
        $DatosLibroActual['TITULO'] = !empty($_POST['Titulo']) ? $_POST['Titulo'] :'';
        $DatosLibroActual['EDITORIAL'] = !empty($_POST['Editorial']) ? $_POST['Editorial'] :'';
        $DatosLibroActual['PRECIO'] = !empty($_POST['Precio']) ? $_POST['Precio'] :'';
    }

}else if (!empty($_GET['ID_LIBRO'])) {
    //verifico que traigo el nro de consulta por GET si todabia no toque el boton de Modificar
    //busco los datos de esta consulta y los muestro
    $DatosLibroActual = Datos_Libro($MiConexion , $_GET['ID_LIBRO']);
}
ob_end_flush(); // Envía el contenido del búfer al navegador
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Libros</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Libros</li>
          <li class="breadcrumb-item active">Modificar Libros</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Libros</h5>

              <!-- Horizontal Form -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">ISBN</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="ISBN" id="isbn"
                    value="<?php echo !empty($DatosLibroActual['ISBN']) ? $DatosLibroActual['ISBN'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Titulo</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Titulo" id="titulo"
                    value="<?php echo !empty($DatosLibroActual['TITULO']) ? $DatosLibroActual['TITULO'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Autor</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Autor" id="autor"
                    value="<?php echo !empty($DatosLibroActual['AUTOR']) ? $DatosLibroActual['AUTOR'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Editorial</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Editorial" id="editorial"
                    value="<?php echo !empty($DatosLibroActual['EDITORIAL']) ? $DatosLibroActual['EDITORIAL'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Precio</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Precio" id="precio"
                    value="<?php echo !empty($DatosLibroActual['PRECIO']) ? $DatosLibroActual['PRECIO'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  
                    <input type='hidden' name="IdLibro" value="<?php echo $DatosLibroActual['ID_LIBRO']; ?>" />
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarLibro">Modificar</button>
                    <a href="listados_libros.php" 
                    class="btn btn-success btn-info " 
                    title="Listado"> Volver al listado  </a>
                </div>
              </form><!-- End Horizontal Form -->

    </section>

  </main><!-- End #main -->

<?php
    $_SESSION['Mensaje']='';
    require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
?>


</body>

</html>