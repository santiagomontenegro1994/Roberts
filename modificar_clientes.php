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

if (!empty($_POST['BotonModificarCliente'])) {
    Validar_Cliente();

    if (empty($_SESSION['Mensaje'])) { //ya toque el boton modificar y el mensaje esta vacio...
        
        if (Modificar_Cliente($MiConexion) != false) {
            $_SESSION['Mensaje'] = "Tu cliente se ha modificado correctamente!";
            $_SESSION['Estilo']='success';
            header('Location: listados_clientes.php');
            exit;
        }

    }else {  //ya toque el boton modificar y el mensaje NO esta vacio...
        $_SESSION['Estilo']='warning';
        $DatosClienteActual['ID_CLIENTE'] = !empty($_POST['IdCliente']) ? $_POST['IdCliente'] :'';
        $DatosClienteActual['NOMBRE'] = !empty($_POST['Nombre']) ? $_POST['Nombre'] :'';
        $DatosClienteActual['APELLIDO'] = !empty($_POST['Apellido']) ? $_POST['Apellido'] :'';
        $DatosClienteActual['DIRECCION'] = !empty($_POST['Direccion']) ? $_POST['Direccion'] :'';
        $DatosClienteActual['TELEFONO'] = !empty($_POST['Telefono']) ? $_POST['Telefono'] :'';
        $DatosClienteActual['DNI'] = !empty($_POST['DNI']) ? $_POST['DNI'] :'';
    }

}else if (!empty($_GET['ID_CLIENTE'])) {
    //verifico que traigo el nro de consulta por GET si todabia no toque el boton de Modificar
    //busco los datos de esta consulta y los muestro
    $DatosClienteActual = Datos_Cliente($MiConexion , $_GET['ID_CLIENTE']);
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Clientes</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Clientes</li>
          <li class="breadcrumb-item active">Modificar Clientes</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Clientes</h5>

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
                    value="<?php echo !empty($DatosClienteActual['NOMBRE']) ? $DatosClienteActual['NOMBRE'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Apellido</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Apellido" id="apellido"
                    value="<?php echo !empty($DatosClienteActual['APELLIDO']) ? $DatosClienteActual['APELLIDO'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Direccion</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Direccion" id="direccion"
                    value="<?php echo !empty($DatosClienteActual['DIRECCION']) ? $DatosClienteActual['DIRECCION'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Telefono</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Telefono" id="dtelefono"
                    value="<?php echo !empty($DatosClienteActual['TELEFONO']) ? $DatosClienteActual['TELEFONO'] : ''; ?>">
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">DNI</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="DNI" id="dni"
                    value="<?php echo !empty($DatosClienteActual['DNI']) ? $DatosClienteActual['DNI'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  
                    <input type='hidden' name="IdCliente" value="<?php echo $DatosClienteActual['ID_CLIENTE']; ?>" />
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarCliente">Modificar</button>
                    <a href="listados_clientes.php" 
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