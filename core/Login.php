<?php 
session_start();
//print_r($_SESSION);
require_once '../funciones/conexion.php';
$MiConexion=ConexionBD();

// Configurar zona horaria Argentina
date_default_timezone_set('America/Argentina/Cordoba');

$Mensaje='';
if (!empty($_POST['BotonLogin'])) {

    require_once '../funciones/login.php';
    $UsuarioLogueado = DatosLogin($_POST['user'], $_POST['password'], $MiConexion);

    //la consulta con la BD para que encuentre un usuario registrado con el usuario y clave brindados
    if ( !empty($UsuarioLogueado)) {
      // $Mensaje ='ok! ya puedes ingresar';

      //generar los valores del usuario (esto va a venir de mi BD)
      $_SESSION['Usuario_Nombre']     = $UsuarioLogueado['NOMBRE'];
      $_SESSION['Usuario_Apellido']   = $UsuarioLogueado['APELLIDO'];
      $_SESSION['Usuario_Nivel']      = $UsuarioLogueado['NIVEL'];
      $_SESSION['Usuario_Id']         = $UsuarioLogueado['ID'];
      $_SESSION['Id_Caja']            = $UsuarioLogueado['ID_CAJA']; // Aquí asignamos el ID de la caja
      $_SESSION['Mensaje']            = '';
      $_SESSION['Estilo']             = '';
      $_SESSION['Descarga']           = '';
      $_SESSION['Cliente_Pedido']           = '';
        
        header('Location: ../core/index.php');
        exit;

    }else {
        $Mensaje='Datos incorrectos, ingresa nuevamente.';
    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Login</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="../assets/img/favicon.png" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="../assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="../assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center">

        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

              <div class="card mb-3">

                <div class="card-body">

                <div class="d-flex justify-content-center logo-container" >
                  <a href="index.php" class="logo d-flex align-items-center w-auto logo-custom">
                    <img src="../assets/img/Logo1.png" alt="" class="logo-custom">
                  </a>
                </div><!-- End Logo -->

                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center pb-0 fs-4">Iniciar Sesion</h5>
                    <p class="text-center small">Ingrese su Usuario y Contraseña para Iniciar Sesion</p>
                  </div>

                  <form class="row g-3" role="form" method='post'>

                    <?php if (!empty ($Mensaje)) { ?>
                      <div class="alert alert-warning alert-dismissable">
                        <?php echo $Mensaje; ?>
                      </div>
                    <?php } ?>
                    
                    <div class="col-12">
                      <label for="yourUsername" class="form-label">Usuario</label>
                      <div class="input-group has-validation">
                        <input type="text" name="user" class="form-control" required>
                        <div class="invalid-feedback">Porfavor ingrese su Usuario.</div>
                      </div>
                    </div>

                    <div class="col-12">
                      <label for="yourPassword" class="form-label">Contraseña</label>
                      <input type="password" name="password" class="form-control" required>
                      <div class="invalid-feedback">Porfavor ingrese su Contraseña!</div>
                    </div>

                    <div class="text center">
                      <button class="btn btn-primary w-100" type="submit" value="Login" name="BotonLogin">Iniciar</button>
                    </div>
                  </form>

                </div>
              </div>

              <div class="credits">
                <!-- All the links in the footer should remain intact. -->
                <!-- You can delete the links only if you purchased the pro version. -->
                <!-- Licensing information: https://bootstrapmade.com/license/ -->
                <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
                Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
              </div>

            </div>
          </div>
        </div>

      </section>

    </div>
  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/chart.js/chart.umd.js"></script>
  <script src="../assets/vendor/echarts/echarts.min.js"></script>
  <script src="../assets/vendor/quill/quill.js"></script>
  <script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="../assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="../assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="../assets/js/main.js"></script>

</body>

</html>