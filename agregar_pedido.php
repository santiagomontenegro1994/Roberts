<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

?>

<main id="main" class="main">

<div class="pagetitle">
  <h1>Pedidos</h1>
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
            <div class="d-flex justify-content-start align-items-center"> 
                <h5 class="card-title mr-2">Datos de Cliente</h5> 
                <button class="btn btn-primary btn-sm m-2" type="submit" value="Agregar" name="AgregarClientes">Agregar</button>
            </div>

<!-- Horizontal Form -->
            <form class="row g-1" id='miFormularioClientes' method='post'>

            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">DNI</label>
                <input type="number" class="form-control form-control-sm"  name="DNI" id="dni">
            </div>
            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">Nombre</label>
                <input type="text" class="form-control form-control-sm"  name="Nombre" id="nombre">
            </div>
            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">Apellido</label>
                <input type="text" class="form-control form-control-sm"  name="Apellido" id="apellido">
            </div>
            <div class="col-md-6 mb-1">
                <label for="fecha" class="form-label">Direccion</label>
                <input type="text" class="form-control form-control-sm"  name="Direccion" id="direccion">
            </div>
            <div class="col-md-6 mb-1">
                <label for="fecha" class="form-label">Telefono</label>
                <input type="text" class="form-control form-control-sm"  name="Telefono" id="telefono">
            </div>

            <div class="text-center">
                <button class="btn btn-primary" type="submit" value="RegistrarClientes" name="RegistrarClientes">Registrar</button>
            </div>
            </form>
<!-- End Horizontal Form -->


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