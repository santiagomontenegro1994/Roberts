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
                <button class="btn btn-primary btn-sm m-2" type="hidden" value="Agregar" name="AgregarClientes">Agregar</button>
            </div>

<!-- Horizontal Form -->
            <form class="row g-1" id="form_new_cliente_pedido" name="form_new_cliente_pedido" method='post'>

            <input type="hidden" name="action" value="addCliente">
            <input type="hidden" name="IdCliente" id="idCliente">

            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">DNI</label>
                <input type="number" class="form-control form-control-sm"  name="DNI" id="dni">
            </div>
            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">Nombre</label>
                <input type="text" class="form-control form-control-sm"  name="Nombre" id="nombre" disabled required>
            </div>
            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">Apellido</label>
                <input type="text" class="form-control form-control-sm"  name="Apellido" id="apellido" disabled required>
            </div>
            <div class="col-md-6 mb-1">
                <label for="fecha" class="form-label">Direccion</label>
                <input type="text" class="form-control form-control-sm"  name="Direccion" id="direccion" disabled required>
            </div>
            <div class="col-md-6 mb-1">
                <label for="fecha" class="form-label">Telefono</label>
                <input type="text" class="form-control form-control-sm"  name="Telefono" id="telefono" disabled required>
            </div>

            <div class="text-center" id="div_registro_cliente">
                <button class="btn btn-primary" type="submit" value="RegistrarClientes" name="RegistrarClientes">Registrar</button>
            </div>
            </form>
<!-- End Horizontal Form -->
        </div>
    </div>   
    
    <!-- Table with stripped rows -->
    <div class="card">
    <div class="card-body">
        <h5 class="card-title mr-2">Datos de Libro</h5>
    <table class="table table-striped">
        <thead>
            <tr>
            <th scope="col">ID</th>
            <th scope="col">Titulo</th>
            <th scope="col">Editorial</th>
            <th scope="col" class="col-2">Cantidad</th>
            <th scope="col">Precio</th>
            <th scope="col">Precio total</th>
            <th scope="col">Accion</th>
            </tr>
              
            <tr class=""  data-bs-toggle="tooltip" data-bs-placement="left">
                <th><input type="text" name="txt_id_libro" id="txt_id_libro"></th>
                <td id="txt_titulo">-</td>
                <td id="txt_editorial">-</td>
                <th><input type="text" name="txt_cantidad_libro" id="txt_cantidad_libro" value="0" min="1" class="form-control form-control-sm w-50" disabled></th>
                <td id="txt_precio">0.00</td>
                <td id="txt_precio_total">0.00</td>
                <td><a href="#" id="add_libro_pedido"><i class="bi bi-plus"></i>Agregar</a></td>   
            </tr>

            <tr>
                <th scope="col">ID</th>
                <th scope="col">Titulo</th>
                <th scope="col">Editorial</th>
                <th scope="col" class="col-2">Cantidad</th>
                <th scope="col">Precio</th>
                <th scope="col">Precio total</th>
                <th scope="col">Accion</th>
            </tr>
        </thead>
        <tbody id="detalle_venta"> 
            <tr class=""  data-bs-toggle="tooltip" data-bs-placement="left">
                <th>1</th>
                <td>mi pobre angelito</td>
                <td>Editorial</td>
                <th>2</th>
                <td>50.00</td>
                <td>100.00</td>
                <td>
                    <a href="#" onclick="event.preventDefault(); del_libro_detalle(1);">
                        <i class="bi bi-trash text-danger"></i></a>
                </td>   
            </tr>
            <tr class=""  data-bs-toggle="tooltip" data-bs-placement="left">
                <th>12</th>
                <td>libro 2</td>
                <td>Editorial 7</td>
                <th>3</th>
                <td>25.00</td>
                <td>75.00</td>
                <td>
                    <a href="#" onclick="event.preventDefault(); del_libro_detalle(1);">
                        <i class="bi bi-trash text-danger"></i></a>
                </td>   
            </tr>   
        </tbody>
        <tfoot>
            <tr>
                <td>SUBTOTAL</td>
                <td>1000.00</td>
            </tr>
            <tr>
                <td>SEÑA</td>
                <td>500.00</td>
            </tr>
            <tr>
                <td>TOTAL</td>
                <td>500.00</td>
            </tr>

        </tfoot>
    </table>
    </div>
    </div>
          <!-- End Table with stripped rows -->

</section>            
            
        



</main><!-- End #main -->

<?php
$_SESSION['Mensaje']='';
require ('footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo
?>

</body>

</html>