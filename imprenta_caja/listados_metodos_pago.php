<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado
require('barraLateral.inc.php'); // Incluir barra lateral

require_once 'funciones/conexion.php';
$MiConexion = ConexionBD();

require_once 'funciones/imprenta.php';

// Obtener los métodos de pago desde la base de datos
$ListadoMetodosPago = Listar_Tipos_Pagos($MiConexion);
$CantidadMetodosPago = count($ListadoMetodosPago);

?>

<main id="main" class="main">

<div class="pagetitle d-flex justify-content-between align-items-center">
  <h1>Listado Tipos de Pago</h1>
 </div>

<nav>
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
    <li class="breadcrumb-item">Tipos de Pago</li>
    <li class="breadcrumb-item active">Listado Tipos de Pago</li>
  </ol>
</nav>

<section class="section">
    <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Listado Tipos de Pago</h5>
            <a href="agregar_metodo_pago.php" class="btn btn-primary btn-sm">Agregar Nuevo Tipo de Pago</a>
          </div>
          <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
              <?php echo $_SESSION['Mensaje'] ?>
            </div>
          <?php } ?>

          <!-- Table with stripped rows -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Denominación</th>
                  <th scope="col">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php for ($i = 0; $i < $CantidadMetodosPago; $i++) { ?>
                  <tr>
                    <td class="small"><?php echo $ListadoMetodosPago[$i]['idTipoPago']; ?></td>
                    <td class="small"><?php echo $ListadoMetodosPago[$i]['denominacion']; ?></td>
                    <td>
                      <!-- Acciones -->
                      <a href="modificar_metodo_pago.php?idTipoPago=<?php echo $ListadoMetodosPago[$i]['idTipoPago']; ?>" 
                          title="Modificar">
                        <i class="bi bi-pencil-fill text-warning fs-5"></i>
                      </a>

                      <a href="eliminar_metodo_pago.php?idTipoPago=<?php echo $ListadoMetodosPago[$i]['idTipoPago']; ?>" 
                          title="Eliminar" 
                          onclick="return confirm('Confirma eliminar este método de pago?');">
                        <i class="bi bi-trash-fill text-danger fs-5"></i>
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

<!-- Botón Volver a Ventas -->
<div class="text-center mt-4">
  <a href="agregar_venta.php" class="btn btn-secondary">Volver a Ventas</a>
</div>

</main><!-- End #main -->

<?php
  $_SESSION['Mensaje'] = '';
  require('footer.inc.php'); // Incluir footer
?>

</body>
</html>