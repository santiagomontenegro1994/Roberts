<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php'); // Incluir encabezado
require('../shared/barraLateral.inc.php'); // Incluir barra lateral

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Obtener los tipos de movimiento de entrada desde la base de datos
$ListadoTiposMovimiento = Listar_Tipos_Movimiento_Entrada($MiConexion);
$CantidadTiposMovimiento = count($ListadoTiposMovimiento);

?>

<main id="main" class="main">

<div class="pagetitle d-flex justify-content-between align-items-center">
  <h1>Listado Tipos de Movimientos (Entradas)</h1>
</div>

<nav>
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
    <li class="breadcrumb-item">Tipos de Movimientos</li>
    <li class="breadcrumb-item active">Listado Tipos de Movimientos</li>
  </ol>
</nav>

<section class="section">
    <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Listado Tipos de Movimientos (Entradas)</h5>
            <a href="agregar_tipo_movimiento.php" class="btn btn-primary btn-sm">Agregar Nuevo Tipo de Movimiento</a>
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
                <?php for ($i = 0; $i < $CantidadTiposMovimiento; $i++) { ?>
                  <tr>
                    <td class="small"><?php echo $ListadoTiposMovimiento[$i]['idTipoMovimiento']; ?></td>
                    <td class="small"><?php echo $ListadoTiposMovimiento[$i]['denominacion']; ?></td>
                    <td>
                      <!-- Acciones -->
                      <a href="modificar_tipo_movimiento.php?idTipoMovimiento=<?php echo $ListadoTiposMovimiento[$i]['idTipoMovimiento']; ?>" 
                          class="btn btn-sm btn-warning me-2"
                          title="Modificar">
                        <i class="bi bi-pencil-fill"></i>
                      </a>

                      <a href="eliminar_tipo_movimiento.php?idTipoMovimiento=<?php echo $ListadoTiposMovimiento[$i]['idTipoMovimiento']; ?>" 
                          class="btn btn-sm btn-danger me-2"
                          title="Eliminar" 
                          onclick="return confirm('Confirma eliminar este tipo de movimiento?');">
                        <i class="bi bi-trash-fill"></i>
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
  <a href="../imprenta_caja/agregar_venta.php" class="btn btn-secondary">Volver a Ventas</a>
</div>

</main><!-- End #main -->

<?php
  $_SESSION['Mensaje'] = '';
  require('../shared/footer.inc.php'); // Incluir footer
?>

</body>
</html>