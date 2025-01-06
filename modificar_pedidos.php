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
$DatosPedidoActual=array();

if (!empty($_POST['BotonModificarPedido'])) {
    Validar_Pedido();

    if (empty($_SESSION['Mensaje'])) { //ya toque el boton modificar y el mensaje esta vacio...
        
        if (Modificar_Libros($MiConexion) != false) {
            $_SESSION['Mensaje'] = "Tu pedido se ha modificado correctamente!";
            $_SESSION['Estilo']='success';
            header('Location: listados_pedidos.php');
            exit;
        }

    }else {  //ya toque el boton modificar y el mensaje NO esta vacio...
        $_SESSION['Estilo']='warning';
        $DatosPedidoActual['ID_PEDIDO'] = !empty($_POST['IdPedido']) ? $_POST['IdPEdido'] :'';
        $DatosPedidoActual['CLIENTE'] = !empty($_POST['Cliente']) ? $_POST['Cliente'] :'';
        $DatosPedidoActual['LIBRO'] = !empty($_POST['Libro']) ? $_POST['Libro'] :'';
        $DatosPedidoActual['PRECIO'] = !empty($_POST['Precio']) ? $_POST['Precio'] :'';
        $DatosPedidoActual['SEÑA'] = !empty($_POST['Seña']) ? $_POST['Seña'] :'';
    }

}else if (!empty($_GET['ID_PEDIDO'])) {
    //verifico que traigo el nro de consulta por GET si todabia no toque el boton de Modificar
    //busco los datos de esta consulta y los muestro
    $DatosPedidoActual = Datos_Pedido($MiConexion , $_GET['ID_PEDIDO']);
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Pedidos</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Pedidos</li>
          <li class="breadcrumb-item active">Modificar Pedido</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Pedido</h5>

              <!-- Horizontal Form -->
                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Precio</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Precio" id="precio"
                    value="<?php echo !empty($DatosPedidoActual['PRECIO']) ? $DatosPedidoActual['PRECIO'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Seña</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Seña" id="seña"
                    value="<?php echo !empty($DatosPedidoActual['Seña']) ? $DatosPedidoActual['Seña'] : ''; ?>">
                  </div>
                </div>



                <div class="text-center">
                  
                    <input type='hidden' name="IdPedido" value="<?php echo $DatosPedidoActual['ID_PEDIDO']; ?>" />
                    
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarLibro">Modificar</button>
                    <a href="listados_pedidos.php" 
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
