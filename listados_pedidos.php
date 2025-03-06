<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require ('encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('barraLateral.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

//voy a necesitar la conexion: incluyo la funcion de Conexion.
require_once 'funciones/conexion.php';

//genero una variable para usar mi conexion desde donde me haga falta
//no envio parametros porque ya los tiene definidos por defecto
$MiConexion = ConexionBD();

//ahora voy a llamar el script con la funcion que genera mi listado
require_once 'funciones/select_general.php';


//voy a ir listando lo necesario para trabajar en este script: 
$ListadoPedidos = Listar_Pedidos($MiConexion);
$CantidadPedidos = count($ListadoPedidos);

  //estoy en condiciones de poder buscar segun el parametro
  
    if (!empty($_POST['BotonBuscar'])) {

        $parametro = $_POST['parametro'];
        $criterio = $_POST['gridRadios'];
        $ListadoPedidos=Listar_Pedidos_Parametro($MiConexion,$criterio,$parametro);
        $CantidadPedidos = count($ListadoPedidos);


}


?>



<main id="main" class="main">

<div class="pagetitle">
  <h1>Listado Pedidos</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
      <li class="breadcrumb-item">Pedidos</li>
      <li class="breadcrumb-item active">Listado Pedidos</li>
    </ol>
  </nav>
</div><!-- End Page Title -->

<section class="section">
    
    <div class="card">
        <div class="card-body">
          <h5 class="card-title">Listado Pedidos</h5>
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
                <a href="imprimir_listado.php">
                          <i class="btn btn-primary btn-xs d-inline-block">Descargar</i>
                          </a>
              </div>
              <div class="col-sm-5 mt-2">
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Fecha" checked>
                      <label class="form-check-label" for="gridRadios1">
                        Fecha
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Cliente">
                      <label class="form-check-label" for="gridRadios2">
                      Cliente
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Id">
                      <label class="form-check-label" for="gridRadios2">
                      ID
                      </label>
                    </div>

                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios4" value="Estado">
                      <label class="form-check-label" for="gridRadios4">
                        Estado (1, 2, 3 o 4)
                    </div>
                    
              </div>
              
          </div>
          </form>
          <!-- Table with stripped rows -->
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Fecha</th>
                <th scope="col">Cliente</th>
                <th scope="col">Detalle</th>
                <th scope="col">Precio</th>
                <th scope="col">%Desc.</th>
                <th scope="col">Seña</th>
                <th scope="col">Saldo</th>
                <th scope="col">Acciones</th>
              </tr>
            </thead>
            <tbody>
                <?php for ($i=0; $i<$CantidadPedidos; $i++) { 
                  //cuento la cantidad de pedidos
                  $cantidad = Contar_Pedidos($MiConexion,$ListadoPedidos[$i]['ID']);
                  
                  //Calculo el saldo
                  // Calcular el monto del descuento
                  $montoDescuento = $ListadoPedidos[$i]['PRECIO'] * ($ListadoPedidos[$i]['DESCUENTO'] / 100);
                  $saldo=($ListadoPedidos[$i]['PRECIO']-$ListadoPedidos[$i]['SEÑA'])-$montoDescuento;

                  //Metodo para pintar las filas
                  list($Title, $Color) = ColorDeFila($ListadoPedidos[$i]['ESTADO']);
                  ?>
                    
                    <tr class="<?php echo $Color; ?>"  data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="<?php echo $Title; ?>">
                        <td><?php echo $ListadoPedidos[$i]['ID']; ?></td>
                        <td><?php echo $ListadoPedidos[$i]['FECHA']; ?></td>
                        <td><?php echo $ListadoPedidos[$i]['CLIENTE_N'];?> ,
                        <?php echo $ListadoPedidos[$i]['CLIENTE_A'];?></td>
                        <td><?php echo $cantidad; ?> pedido/s</td>
                        <td>$<?php echo number_format($ListadoPedidos[$i]['PRECIO'], 2); ?></td>
                        <td class="text-center">%<?php echo $ListadoPedidos[$i]['DESCUENTO']; ?></td>
                        <td>$<?php echo number_format($ListadoPedidos[$i]['SEÑA'], 2); ?></td>
                        <td>$<?php echo number_format($saldo, 2); ?></td>
                        <td>
                          <!-- eliminar la consulta -->
                          <a href="eliminar_pedido.php?ID_PEDIDO=<?php echo $ListadoPedidos[$i]['ID']; ?>"  
                            title="anular" 
                            onclick="return confirm('Confirma anular este Pedido?');">
                              <i class="bi bi-trash-fill text-danger fs-5"></i>
                          </a>

                          <a href="modificar_pedidos.php?ID_PEDIDO=<?php echo $ListadoPedidos[$i]['ID']; ?>" 
                            title="Modificar">
                          <i class="bi bi-pencil-fill text-warning fs-5"></i>
                          </a>

                          <a href="imprimir_pedido.php?ID_PEDIDO=<?php echo $ListadoPedidos[$i]['ID']; ?>"  
                            title="Imprimir">
                          <i class="bi bi-printer-fill text-success fs-5"></i>
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