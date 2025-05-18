<?php
ob_start(); // Inicia el búfer de salida
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require ('../shared/encabezado.inc.php'); //Aca uso el encabezado que esta seccionados en otro archivo

require ('../shared/barraLateral.inc.php'); //Aca uso el encabezaso que esta seccionados en otro archivo

require_once '../funciones/conexion.php';
$MiConexion=ConexionBD(); 

require_once '../funciones/imprenta.php';

$Mensaje='';
$Estilo='warning';
if (!empty($_POST['BotonRegistrar'])) {
    //estoy en condiciones de poder validar los datos
    $Mensaje=Validar_Tipos_Pago();
    if (empty($Mensaje)) {
        if (InsertarTipoPago($MiConexion) != false) {
          $_SESSION['Mensaje'] = "Tu Metodo de pago se agrego correctamente!";
          $_SESSION['Estilo'] = 'success';
          header('Location: listados_metodos_pago.php');
            exit;
        }
    }
}
ob_end_flush(); // Envía el contenido del búfer al navegador
?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Tipos de Pago</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Ventas</li>
          <li class="breadcrumb-item">Metodos de Pago</li>
          <li class="breadcrumb-item active">Agregar Tipo de Pago</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Agregar Tipo de Pago</h5>

              <!-- Horizontal Form -->
              <form method='post'>
                <?php if (!empty($Mensaje)) { ?>
                    <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                    <?php echo $Mensaje; ?>
                    </div>
                <?php } ?>

                <div class="row mb-3">
                  <label for="inputEmail3" class="col-sm-2 col-form-label">Denominacion</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" name="Denominacion" id="denominacion"
                    value="<?php echo !empty($_POST['Denominacion']) ? $_POST['Denominacion'] : ''; ?>">
                  </div>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
                  <a href="agregar_venta.php" class="btn btn-secondary">Volver a Ventas</a>
                </div>
              </form><!-- End Horizontal Form -->

    </section>

  </main><!-- End #main -->

  <?php
require ('../shared/footer.inc.php'); //Aca uso el FOOTER que esta seccionados en otro archivo

?>


</body>

</html>