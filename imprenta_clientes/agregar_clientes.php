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

// Obtenemos el listado de empresas activas para el selector
$ListadoEmpresas = Listar_Empresas($MiConexion);

$Mensaje='';
$Estilo='warning';
if (!empty($_POST['BotonRegistrar'])) {
    $Mensaje = Validar_Cliente();
    if (empty($Mensaje)) {
        $resultado = InsertarClientes($MiConexion);
        if ($resultado === true) {
            $Mensaje = 'Se ha registrado correctamente.';
            $_POST = array(); 
            $Estilo = 'success';
        } else {
            $Mensaje = $resultado; 
            $Estilo = 'danger';
        }
    }
}
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Clientes</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
          <li class="breadcrumb-item">Clientes</li>
          <li class="breadcrumb-item active">Agregar Clientes</li>
        </ol>
      </nav>
    </div>

    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Agregar Clientes</h5>

              <form method='post'>
                <?php if (!empty($Mensaje)) { ?>
                    <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                    <?php echo $Mensaje; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="nombre" class="col-sm-2 col-form-label">Nombre</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Nombre" id="nombre"
                    value="<?php echo !empty($_POST['Nombre']) ? $_POST['Nombre'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="apellido" class="col-sm-2 col-form-label">Apellido</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Apellido" id="apellido"
                    value="<?php echo !empty($_POST['Apellido']) ? $_POST['Apellido'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="dtelefono" class="col-sm-2 col-form-label">Teléfono</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Telefono" id="dtelefono"
                    value="<?php echo !empty($_POST['Telefono']) ? $_POST['Telefono'] : ''; ?>">
                  </div>
                </div>

                <!-- Nuevo campo: CUIT Opcional -->
                <div class="row mb-3">
                  <label for="cuit" class="col-sm-2 col-form-label">CUIT (Opcional)</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Cuit" id="cuit" placeholder="Ej: 20-12345678-9"
                    value="<?php echo !empty($_POST['Cuit']) ? $_POST['Cuit'] : ''; ?>">
                  </div>
                </div>

                <!-- Nuevo campo: Selector de Empresa -->
                <div class="row mb-3">
                  <label for="idEmpresa" class="col-sm-2 col-form-label">Empresa</label>
                  <div class="col-sm-10">
                    <select class="form-control" name="IdEmpresa" id="idEmpresa">
                      <option value="">-- Sin empresa (Particular / Independiente) --</option>
                      <?php foreach ($ListadoEmpresas as $empresa) { ?>
                        <option value="<?php echo $empresa['ID_EMPRESA']; ?>" 
                          <?php echo (isset($_POST['IdEmpresa']) && $_POST['IdEmpresa'] == $empresa['ID_EMPRESA']) ? 'selected' : ''; ?>>
                          <?php echo $empresa['NOMBRE_EMPRESA']; ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
              </form>

    </section>

  </main>

  <?php
require ('../shared/footer.inc.php');
?>
</body>
</html>