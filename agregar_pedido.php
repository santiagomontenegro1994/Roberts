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
                <a href="#" class="btn btn-primary btn-sm m-2 btn_new_cliente">Nuevo Cliente</a>
            </div>

<!-- Horizontal Form -->
        <form class="row g-1" id="formularioClientePedido" name="form_new_cliente_pedido">

            <input type="hidden" name="action" value="addCliente">
            <input type="hidden" name="idCliente" id="idCliente">

            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">DNI</label>
                <input type="number" class="form-control form-control-sm"  name="dni_cliente" id="dni_cliente">
            </div>
            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">Nombre</label>
                <input type="text" class="form-control form-control-sm"  name="nom_cliente" id="nom_cliente" disabled required>
            </div>
            <div class="col-md-4 mb-1">
                <label for="fecha" class="form-label">Apellido</label>
                <input type="text" class="form-control form-control-sm"  name="ape_cliente" id="ape_cliente" disabled required>
            </div>
            <div class="col-md-6 mb-1">
                <label for="fecha" class="form-label">Direccion</label>
                <input type="text" class="form-control form-control-sm"  name="dir_cliente" id="dir_cliente" disabled required>
            </div>
            <div class="col-md-6 mb-1">
                <label for="fecha" class="form-label">Telefono</label>
                <input type="number" class="form-control form-control-sm"  name="tel_cliente" id="tel_cliente" disabled required>
            </div>

            <div class="text-center" id="div_registro_cliente" style="display: none;">
                <button type="submit" class="btn btn-primary">Registrar</button>
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
                <td><a href="#" id="add_libro_pedido"><i class="bi bi-plus text-danger"></i>Agregar</a></td>   
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
        <tfoot >
            <tr>
                <td colspan="5" class="text-end">SUBTOTAL</td>
                <td colspan="5" class="text-end">1000.00</td>
            </tr>
            <tr>
                <td colspan="5" class="text-end">SEÑA</td>
                <td colspan="5" class="text-end">500.00</td>
            </tr>
            <tr>
                <td colspan="5" class="text-end">TOTAL</td>
                <td colspan="5" class="text-end">500.00</td>
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