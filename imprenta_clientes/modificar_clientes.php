<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) { 
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php'); 
require('../shared/barraLateral.inc.php'); 

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

require_once '../funciones/imprenta.php';

// Listado de empresas para el select
$ListadoEmpresas = Listar_Empresas($MiConexion);

$DatosClienteActual = array();

if (!empty($_POST['BotonModificarCliente'])) {
    Validar_Cliente();

    if (empty($_SESSION['Mensaje'])) { 
        if (Modificar_Cliente($MiConexion) != false) {
            $_SESSION['Mensaje'] = "Tu cliente se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: listados_clientes.php');
            exit;
        }
    } else { 
        $_SESSION['Estilo'] = 'warning';
        $DatosClienteActual['ID_CLIENTE'] = !empty($_POST['IdCliente']) ? $_POST['IdCliente'] : '';
        $DatosClienteActual['NOMBRE'] = !empty($_POST['Nombre']) ? $_POST['Nombre'] : '';
        $DatosClienteActual['APELLIDO'] = !empty($_POST['Apellido']) ? $_POST['Apellido'] : '';
        $DatosClienteActual['TELEFONO'] = !empty($_POST['Telefono']) ? $_POST['Telefono'] : '';
        $DatosClienteActual['CUIT'] = !empty($_POST['Cuit']) ? $_POST['Cuit'] : '';
        $DatosClienteActual['ID_EMPRESA'] = !empty($_POST['IdEmpresa']) ? $_POST['IdEmpresa'] : '';
    }
} else if (!empty($_GET['ID_CLIENTE'])) {
    $DatosClienteActual = Datos_Cliente($MiConexion, $_GET['ID_CLIENTE']);
}

ob_end_flush();
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Clientes</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
          <li class="breadcrumb-item">Clientes</li>
          <li class="breadcrumb-item active">Modificar Clientes</li>
        </ol>
      </nav>
    </div>
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Modificar Clientes</h5>

                <form method='post'>
                <?php if (!empty($_SESSION['Mensaje'])) { ?>
                    <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                        <?php echo $_SESSION['Mensaje']; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="nombre" class="col-sm-2 col-form-label">Nombre</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Nombre" id="nombre"
                    value="<?php echo !empty($DatosClienteActual['NOMBRE']) ? $DatosClienteActual['NOMBRE'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="apellido" class="col-sm-2 col-form-label">Apellido</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Apellido" id="apellido"
                    value="<?php echo !empty($DatosClienteActual['APELLIDO']) ? $DatosClienteActual['APELLIDO'] : ''; ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="dtelefono" class="col-sm-2 col-form-label">Teléfono</label>
                  <div class="col-sm-10">
                    <input type="number" class="form-control" name="Telefono" id="dtelefono"
                    value="<?php echo !empty($DatosClienteActual['TELEFONO']) ? $DatosClienteActual['TELEFONO'] : ''; ?>">
                  </div>
                </div>

                <!-- Campo CUIT -->
                <div class="row mb-3">
                  <label for="cuit" class="col-sm-2 col-form-label">CUIT (Opcional)</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Cuit" id="cuit"
                    value="<?php echo !empty($DatosClienteActual['CUIT']) ? $DatosClienteActual['CUIT'] : ''; ?>">
                  </div>
                </div>

                <!-- Selector de Empresa -->
                <div class="row mb-3">
                  <label for="idEmpresa" class="col-sm-2 col-form-label">Empresa</label>
                  <div class="col-sm-10">
                    <select class="form-control" name="IdEmpresa" id="idEmpresa">
                      <option value="">-- Sin empresa --</option>
                      <?php foreach ($ListadoEmpresas as $empresa) { ?>
                        <option value="<?php echo $empresa['ID_EMPRESA']; ?>" 
                          <?php echo (isset($DatosClienteActual['ID_EMPRESA']) && $DatosClienteActual['ID_EMPRESA'] == $empresa['ID_EMPRESA']) ? 'selected' : ''; ?>>
                          <?php echo $empresa['NOMBRE_EMPRESA']; ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="text-center">
                    <input type='hidden' name="IdCliente" value="<?php echo $DatosClienteActual['ID_CLIENTE']; ?>" />
                    <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarCliente">Modificar</button>
                    <a href="listados_clientes.php" class="btn btn-success btn-info" title="Listado"> Volver al listado  </a>
                </div>
              </form>

    </section>

  </main>

<?php
    $_SESSION['Mensaje']='';
    require ('../shared/footer.inc.php');
?>
</body>
</html>