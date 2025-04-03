<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { // Si el usuario no está logueado, redirigir
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php'); // Incluir encabezado
require('barraLateral.inc.php'); // Incluir barra lateral
require_once 'funciones/conexion.php';
require_once 'funciones/select_general.php';

$MiConexion = ConexionBD();

// Obtener el listado de cajas
$ListadoCajas = Listar_Cajas($MiConexion);
$CantidadCajas = count($ListadoCajas);

// Buscar según el parámetro
if (!empty($_POST['BotonBuscar'])) {
    $parametro = $_POST['parametro'];
    $criterio = $_POST['gridRadios'];
    $ListadoCajas = Listar_Cajas_Parametro($MiConexion, $criterio, $parametro);
    $CantidadCajas = count($ListadoCajas);
}
?>

<main id="main" class="main">

<div class="pagetitle">
  <h1>Listado de Cajas</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Menú</a></li>
      <li class="breadcrumb-item">Cajas</li>
      <li class="breadcrumb-item active">Listado de Cajas</li>
    </ol>
  </nav>
</div><!-- End Page Title -->

<section class="section">
    <div class="card">
        <div class="card-body">
          <h5 class="card-title d-flex justify-content-between align-items-center">
            Listado de Cajas
            <a href="agregar_caja.php" class="btn btn-primary btn-sm">Agregar Caja</a>
          </h5>
          <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
              <?php echo $_SESSION['Mensaje']; ?>
            </div>
          <?php } ?>

          <form method="POST">
          <div class="row mb-4">
            <label for="parametro" class="col-sm-1 col-form-label">Buscar</label>
              <div class="col-sm-3">
                <input type="text" class="form-control" name="parametro" id="parametro">
              </div>

              <div class="col-sm-3 mt-2">
                <button type="submit" class="btn btn-success btn-xs d-inline-block" value="buscar" name="BotonBuscar">Buscar</button>
                <button type="submit" class="btn btn-danger btn-xs d-inline-block" value="limpiar" name="BotonLimpiar">Limpiar</button>
              </div>
              <div class="col-sm-5 mt-2">
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Fecha" checked>
                      <label class="form-check-label" for="gridRadios1">Fecha</label>
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="idTurno">
                      <label class="form-check-label" for="gridRadios2">Turno</label>
                    </div>
                  </div>
          </div>
          </form>

          <!-- Table with stripped rows -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">ID Caja</th>
                  <th scope="col">Fecha</th>
                  <th scope="col">Turno</th>
                  <th scope="col">Caja Inicial</th>
                  <th scope="col">Acciones</th>
                  <th scope="col" class="text-end">Seleccionar</th> <!-- Nueva columna para el botón Seleccionar -->
                </tr>
              </thead>
              <tbody>
                <?php for ($i = 0; $i < $CantidadCajas; $i++) { ?>
                  <tr>
                    <td><?php echo $ListadoCajas[$i]['idCaja']; ?></td>
                    <td><?php echo $ListadoCajas[$i]['Fecha']; ?></td>
                    <td><?php echo $ListadoCajas[$i]['idTurno']; ?></td>
                    <td>$<?php echo number_format($ListadoCajas[$i]['cajaInicial'], 2); ?></td>
                    <td>
                      <!-- Botón Eliminar -->
                      <a href="eliminar_caja.php?idCaja=<?php echo $ListadoCajas[$i]['idCaja']; ?>" 
                        title="Eliminar" 
                        onclick="return confirm('¿Confirma eliminar esta caja?');">
                          <i class="bi bi-trash-fill text-danger fs-5"></i>
                      </a>

                      <!-- Botón Modificar -->
                      <a href="modificar_caja.php?idCaja=<?php echo $ListadoCajas[$i]['idCaja']; ?>"  
                        title="Modificar">
                        <i class="bi bi-pencil-fill text-warning fs-5"></i>
                      </a>
                    </td>
                    <td class="text-end"> <!-- Nueva celda para el botón Seleccionar -->
                      <a href="seleccionar_caja.php?idCaja=<?php echo $ListadoCajas[$i]['idCaja']; ?>" 
                        class="btn btn-success btn-sm" 
                        title="Seleccionar">
                        Seleccionar
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
  $_SESSION['Mensaje'] = '';
  require('footer.inc.php'); // Incluir footer
?>