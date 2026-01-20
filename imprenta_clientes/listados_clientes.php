<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) {
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();
require_once '../funciones/imprenta.php';

// Determinamos qué estado buscar. 
// Si el checkbox 'chkInactivos' viene marcado, buscamos 2 (Inactivos), sino 1 (Activos).
$estadoBuscar = !empty($_POST['chkInactivos']) ? 2 : 1; 

// Inicializamos listado
$ListadoClientes = array();

if (!empty($_POST['BotonBuscar'])) {
    $parametro = $_POST['parametro'];
    $criterio = $_POST['gridRadios'];
    // Pasamos el $estadoBuscar a la función
    $ListadoClientes = Listar_Clientes_Parametro($MiConexion, $criterio, $parametro, $estadoBuscar);
} else {
    // Pasamos el $estadoBuscar a la función
    $ListadoClientes = Listar_Clientes($MiConexion, $estadoBuscar);
}

$CantidadClientes = count($ListadoClientes);
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
</div>

<section class="section">
    <div class="card">
        <div class="card-body">
          <h5 class="card-title">
              <?php echo ($estadoBuscar == 1) ? 'Clientes Activos' : 'Clientes Inactivos / Eliminados'; ?>
          </h5>
          
          <?php if (!empty($_SESSION['Mensaje'])) { ?>
            <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
              <?php echo $_SESSION['Mensaje'] ?>
            </div>
          <?php } ?>

          <Form method="POST">
          <div class="row mb-4 align-items-end"> <div class="col-sm-3">
                <label for="parametro" class="form-label">Buscar</label>
                <input type="text" class="form-control" name="parametro" id="parametro" value="<?php echo $_POST['parametro'] ?? ''; ?>">
              </div>

              <div class="col-sm-4">
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="Nombre" <?php echo (empty($_POST['gridRadios']) || $_POST['gridRadios'] == 'Nombre') ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="gridRadios1">Nombre</label>
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="Telefono" <?php echo (isset($_POST['gridRadios']) && $_POST['gridRadios'] == 'Telefono') ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="gridRadios2">Teléfono</label>
                    </div>
                    <div class="form-check form-check-inline small-text">
                      <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="idCliente" <?php echo (isset($_POST['gridRadios']) && $_POST['gridRadios'] == 'idCliente') ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="gridRadios3">ID</label>
                    </div>
              </div>

              <div class="col-sm-2 text-center">
                  <div class="form-check form-switch d-inline-block">
                      <input class="form-check-input" type="checkbox" id="chkInactivos" name="chkInactivos" value="1" <?php echo ($estadoBuscar == 2) ? 'checked' : ''; ?> onchange="this.form.submit()">
                      <label class="form-check-label fw-bold text-danger" for="chkInactivos">Ver Inactivos</label>
                  </div>
              </div>

              <div class="col-sm-3">
                <style> .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; } </style>
                <button type="submit" class="btn btn-success btn-xs" value="buscar" name="BotonBuscar">Buscar</button>
                <a href="listados_clientes.php" class="btn btn-danger btn-xs">Limpiar</a>
                <button type="submit" class="btn btn-primary btn-xs" value="descargar" name="Descargar">Descargar</button>
              </div>
              
          </div>
          </Form>

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
                <?php if ($CantidadClientes > 0) { ?>
                    <?php for ($i=0; $i<$CantidadClientes; $i++) { ?>
                      <tr>
                        <td class="extra-small"><?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?></td>
                        <td class="extra-small"><?php echo $ListadoClientes[$i]['NOMBRE']; ?> <?php echo $ListadoClientes[$i]['APELLIDO']; ?></td>
                        <td class="extra-small"><?php echo $ListadoClientes[$i]['TELEFONO']; ?></td>
                        <td class="extra-small">
                          
                          <?php if ($estadoBuscar == 1) { // Si estamos viendo ACTIVOS ?>
                              
                              <a href="eliminar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>" 
                                class="btn btn-xs btn-danger me-2"
                                title="Eliminar / Desactivar" 
                                onclick="return confirm('¿Confirma eliminar este cliente?');">
                                <i class="bi bi-trash-fill"></i>
                              </a>

                              <a href="modificar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>"  
                                class="btn btn-xs btn-warning me-2"
                                title="Modificar">
                              <i class="bi bi-pencil-fill"></i>
                              </a>

                          <?php } else { // Si estamos viendo INACTIVOS ?>
                              
                              <a href="reactivar_clientes.php?ID_CLIENTE=<?php echo $ListadoClientes[$i]['ID_CLIENTE']; ?>" 
                                class="btn btn-xs btn-success me-2"
                                title="Reactivar Cliente" 
                                onclick="return confirm('¿Desea volver a activar este cliente?');">
                                <i class="bi bi-arrow-counterclockwise"></i> Reactivar
                              </a>

                          <?php } ?>

                        </td>
                      </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No se encontraron clientes.</td>
                    </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

        </div>
    </div>
</section>

</main>

<?php
  $_SESSION['Mensaje']='';
  require ('../shared/footer.inc.php');
?>
</body>
</html>