<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('../shared/barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

//voy a necesitar la conexion: incluyo la funcion de Conexion.
require_once '../funciones/conexion.php';

//genero una variable para usar mi conexion desde donde me haga falta
//no envio parametros porque ya los tiene definidos por defecto
$MiConexion = ConexionBD();

//ahora voy a llamar el script con la funcion que genera mi listado
require_once '../funciones/imprenta.php';


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
      <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
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
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="Telefono">
                      <label class="form-check-label" for="gridRadios3">
                        Telefono
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="idCliente">
                      <label class="form-check-label" for="gridRadios3">
                        ID
                    </div>
                    
                  </div>
              
          </div>
          </form>
          <!-- Table with stripped rows -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Nombre</th>
                  <th scope="col">Telefono</th>
                  <th scope="col">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php for ($i=0; $i<$CantidadClientes; $i++) { ?>
                  <tr>
                    <td><?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?></td>
                    <td><?php echo $ListadoClientes[$i]['NOMBRE']; ?> <?php echo $ListadoClientes[$i]['APELLIDO']; ?></td>
                    <td><?php echo $ListadoClientes[$i]['TELEFONO']; ?></td>
                    <td>
                      <!-- eliminar la consulta -->
                      <a href="eliminar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>" 
                        class="btn btn-sm btn-danger me-2"
                        title="Eliminar" 
                        onclick="return confirm('Confirma eliminar este cliente?');">
                          <i class="bi bi-trash-fill"></i>
                      </a>

                      <a href="modificar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>"  
                        class="btn btn-sm btn-warning me-2"
                        title="Modificar">
                      <i class="bi bi-pencil-fill"></i>
                      </a>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <!-- End Table with stripped rows -->

        </div>
    </div>
 
</section>

</main><!-- End #main -->

<?php
  $_SESSION['Mensaje']='';
  require ('../shared/footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
?>


</body>

</html>