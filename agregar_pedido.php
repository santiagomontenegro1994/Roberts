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

$ListadoClientes = Listar_Clientes_Pedidos($MiConexion);
$CantidadClientes = count($ListadoClientes);

$ListadoLibros = Listar_Libros_Pedidos($MiConexion);
$CantidadLibros = count($ListadoLibros);


$_SESSION['Estilo'] = 'alert';

if (!empty($_POST['Registrar'])) {
    //estoy en condiciones de poder validar los datos
    $_SESSION['Mensaje']=Validar_Pedido_Libros();
    if (empty($_SESSION['Mensaje'])) {
        if (InsertarPedidoLibros($MiConexion) != false) {
            $_SESSION['Mensaje'] = 'Se ha registrado correctamente.';
            $_POST = array(); 
            $_SESSION['Estilo'] = 'success'; 
        }
    }
}

?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Turnos</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Pedidos</li>
          <li class="breadcrumb-item active">Agregar Pedido</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Agregar Pedido</h5>

              <!-- Horizontal Form -->
              <form class="row g-3" id='miFormulario' method='post'>
              <?php if (!empty($_SESSION['Mensaje'])) { ?>
                <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                    <?php echo $_SESSION['Mensaje']; ?>
                </div>
              <?php } ?>

                    <div class="col-12">
                        <label for="selector" class="form-label">Cliente</label>
                        <select class="form-select" aria-label="Selector" name="Cliente">
                          <option selected="">Selecciona una opcion</option>
                          <?php for ($i=0; $i<$CantidadClientes; $i++) { ?>
                            <option value="<?php echo $ListadoClientes[$i]['ID']; ?>">
                              <?php echo $ListadoClientes[$i]['APELLIDO']; ?> , 
                              <?php echo $ListadoClientes[$i]['NOMBRE']; ?>
                            </option>
                          <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="selector" class="form-label">Libro</label>
                        <select class="form-select" aria-label="Selector"  name="Libro">
                          <option selected="">Selecciona una opcion</option>
                          <?php for ($i=0; $i<$CantidadLibros; $i++) { ?>
                            <option value="<?php echo $ListadoLibros[$i]['ID']; ?>">
                              <?php echo $ListadoLibros[$i]['TITULO']; ?> , 
                              <?php echo $ListadoLibros[$i]['AUTOR']; ?>
                            </option>
                          <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="fecha" class="form-label">Precio</label>
                        <input type="number" class="form-control"  name="Precio" id="precio" readonly
                        value="<?php echo $ListadoLibros[$i]['PRECIO']; ?>">
                    </div>

                    <div class="col-12">
                        <label for="fecha" class="form-label">Seña</label>
                        <input type="number" class="form-control"  name="Seña" id="seña"
                        value="<?php echo !empty($_POST['Seña']) ? $_POST['Seña'] : ''; ?>">
                    </div>

                    <div class="text-center">
                        <button class="btn btn-primary" type="submit" value="Registrar" name="Registrar">Registrar</button>
                        <button type="reset" class="btn btn-secondary">Limpiar Campos</button>
                    </div>
                </form>
                <!-- Vertical Form --><!-- End Horizontal Form -->

    </section>

  </main><!-- End #main -->

  <?php
  $_SESSION['Mensaje']='';
require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo

?>
<script>
  // In your Javascript (external .js resource or <script> tag) SELECT 2
  $(document).ready(function() {
  $('.js-example-basic-single').select2();
  });
</script>

</body>

</html>