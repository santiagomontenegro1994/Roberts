<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

//voy a necesitar la conexion: incluyo la funcion de Conexion.
require_once 'funciones/conexion.php';

//genero una variable para usar mi conexion desde donde me haga falta
//no envio parametros porque ya los tiene definidos por defecto
$MiConexion = ConexionBD();

//ahora voy a llamar el script con la funcion que genera mi listado
require_once 'funciones/select_clientes.php';


//voy a ir listando lo necesario para trabajar en este script: 
$ListadoClientes = Listar_Clientes($MiConexion);
$CantidadClientes = count($ListadoClientes);

  //estoy en condiciones de poder buscar segun el parametro
  
    if (!empty($_POST['BotonBuscar'])) {

        $parametro = $_POST['parametro'];
        $criterio = $_POST['gridRadios'];
        $ListadoClientes=Listar_Clientes_Parametro($MiConexion,$criterio,$parametro);
        $CantidadClientes = count($ListadoClientes);


}


?>



<main id="main" class="main">

<div class="pagetitle">
  <h1>Listado Clientes</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
      <li class="breadcrumb-item">Clientes</li>
      <li class="breadcrumb-item active">Listado Clientes</li>
    </ol>
  </nav>
</div><!-- End Page Title -->

<section class="section">
    
    <div class="card">
        <div class="card-body">
          <h5 class="card-title">Listado Clientes</h5>
          <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
              <?php echo $_SESSION['Mensaje'] ?>
            </div>
          <?php } ?>

          <Form method="POST">
          <div class="row mb-4">
            <label for="inputEmail3" class="col-sm-1 col-form-label">Buscar</label>
              <div class="col-sm-3">
                <input type="text" class="form-control" name="parametro" id="parametro">
                </div>

                <style> .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.2rem; } </style>

              <div class="col-sm-3 mt-2">
                <button type="submit" class="btn btn-success btn-xs d-inline-block" value="buscar" name="BotonBuscar">Buscar</button>
                <button type="submit" class="btn btn-danger btn-xs d-inline-block" value="limpiar" name="BotonLimpiar">Limpiar</button>
                <button type="submit" class="btn btn-primary btn-xs d-inline-block" value="descargar" name="Descargar">Descargar</button>
              </div>
              <div class="col-sm-5 mt-2">
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Nombre" checked>
                      <label class="form-check-label" for="gridRadios1">
                        Nombre
                      </label>
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Apellido">
                      <label class="form-check-label" for="gridRadios2">
                        Apellido
                      </label>
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="Telefono">
                      <label class="form-check-label" for="gridRadios3">
                        Tel.
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios4" value="Email">
                      <label class="form-check-label" for="gridRadios4">
                        Email
                    </div>
                    
                  </div>
              
          </div>
          </form>
          <!-- Table with stripped rows -->
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Nombre</th>
                <th scope="col">Apellido</th>
                <th scope="col">Direccion</th>
                <th scope="col">Telefono</th>
                <th scope="col">Email</th>
                <th scope="col">Acciones</th>
              </tr>
            </thead>
            <tbody>
                <?php for ($i=0; $i<$CantidadClientes; $i++) { ?>
                    <tr>
                        <th scope="row"><?php echo $i+1; ?></th>
                        <td><?php echo $ListadoClientes[$i]['NOMBRE']; ?></td>
                        <td><?php echo $ListadoClientes[$i]['APELLIDO']; ?></td>
                        <td><?php echo $ListadoClientes[$i]['DIRECCION']; ?></td>
                        <td><?php echo $ListadoClientes[$i]['TELEFONO']; ?></td>
                        <td><?php echo $ListadoClientes[$i]['EMAIL']; ?></td>
                        <td>
                          <!-- eliminar la consulta -->
                          <a href="eliminar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>" 
                            class="btn btn-success btn-danger" 
                            title="Eliminar" 
                            onclick="return confirm('Confirma eliminar este cliente?');">
                              <i class="fa fa-times"></i>
                          </a>

                          <a href="modificar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>" 
                            class="btn btn-success btn-circle btn-warning" 
                            title="Modificar">
                          <i class="bi bi-person-fill-slash"></i>
                          </a>
                      
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
          </table>
          <!-- End Table with stripped rows -->

        </div>
    </div>
 
</section>

</main><!-- End #main -->

<?php
  $_SESSION['Mensaje']='';
  require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
?>


</body>

</html>