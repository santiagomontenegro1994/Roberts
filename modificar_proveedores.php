<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

//require ('barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

require_once 'funciones/conexion.php';
$MiConexion=ConexionBD();

//ahora voy a llamar el script gral para usar las funciones necesarias
require_once 'funciones/select_general.php';
 
//este array contendra los datos de la consulta original, y cuando 
//pulse el boton, mantendrá los datos ingresados hasta que se validen y se puedan modificar
$DatosClienteActual=array();

if (!empty($_POST['BotonModificarProveedor'])) {
    Validar_Proveedor();

    if (empty($_SESSION['Mensaje'])) { //ya toque el boton modificar y el mensaje esta vacio...
        
        if (Modificar_Proveedor($MiConexion) != false) {
            $_SESSION['Mensaje'] = "Tu proveedor se ha modificado correctamente!";
            $_SESSION['Estilo']='success';
            header('Location: listados_proveedores.php');
            exit;
        }

    }else {  //ya toque el boton modificar y el mensaje NO esta vacio...
        $_SESSION['Estilo']='warning';
        $DatosProveedorActual['ID_PROVEEDOR'] = !empty($_POST['IdProveedor']) ? $_POST['IdProveedor'] :'';
        $DatosProveedorActual['NOMBRE'] = !empty($_POST['Nombre']) ? $_POST['Nombre'] :'';
        $DatosProveedorActual['CONTACTO'] = !empty($_POST['Contacto']) ? $_POST['Contacto'] :'';
        $DatosProveedorActual['CUIT'] = !empty($_POST['CUIT']) ? $_POST['CUIT'] :'';
    }

}else if (!empty($_GET['ID_PROVEEDOR'])) {
    //verifico que traigo el nro de consulta por GET si todabia no toque el boton de Modificar
    //busco los datos de esta consulta y los muestro
    $DatosProveedorActual = Datos_Proveedor($MiConexion , $_GET['ID_PROVEEDOR']);
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Proveedores</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Proveedores</li>
          <li class="breadcrumb-item active">Modificar Proveedor</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Proveedores</h5>

              <!-- Horizontal Form -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Nombre</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Nombre" id="nombre"
                    value="<?php echo !empty($DatosProveedorActual['NOMBRE']) ? $DatosProveedorActual['NOMBRE'] : ''; ?>">
                  </div>
                </div>
            
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Contacto</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Contacto" id="contacto"
                    value="<?php echo !empty($DatosProveedorActual['CONTACTO']) ? $DatosProveedorActual['CONTACTO'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">CUIT</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="CUIT" id="cuit"
                    value="<?php echo !empty($DatosProveedorActual['CUIT']) ? $DatosProveedorActual['CUIT'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  
                    <input type='hidden' name="IdProveedor" value="<?php echo $DatosProveedorActual['ID_PROVEEDOR']; ?>" />
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarProveedor">Modificar</button>
                    <a href="listados_proveedores.php" 
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