<?php
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Obtener información de la caja seleccionada
$MiConexion = ConexionBD();
$cajaSeleccionada = null;

if (!empty($_SESSION['Id_Caja'])) {
    $cajaSeleccionada = Obtener_Info_Caja($MiConexion, $_SESSION['Id_Caja']);
}
$MiConexion->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Roberts</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- SELECT2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- Favicons -->
  <link href="../assets/img/favicono.png" rel="icon">
  <link href="../assets/img/apple-touch-icono.png" rel="apple-touch-icon">

  <!-- FIconos pagos -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link
    href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i"
    rel="stylesheet">

  <!-- Bootstrap personalizado -->
  <link href="../assets/css/custom.css" rel="stylesheet">

  <!-- Vendor CSS Files 
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">-->
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  

  <!-- Template Main CSS File -->
  <link href="../assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin - v2.2.2
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center justify-content-between px-3">

    <!-- Logo -->
    <div class="d-flex align-items-center">
        <a href="../core/index.php" class="logo d-flex align-items-center">
            <img src="../assets/img/logo.png" alt="Logo" class="img-fluid me-2">
        </a>
        <i class="bi bi-list toggle-sidebar-btn ms-3"></i>
    </div><!-- End Logo -->

    <!-- Información de la caja seleccionada -->
    <div class="d-flex align-items-center justify-content-center flex-column flex-md-row mx-auto">
        <span class="badge bg-primary text-white px-3 py-2 text-wrap text-center caja-seleccionada">
            <?php
            if ($cajaSeleccionada) {
                $dias = [
                    'Sunday' => 'domingo',
                    'Monday' => 'lunes',
                    'Tuesday' => 'martes',
                    'Wednesday' => 'miércoles',
                    'Thursday' => 'jueves',
                    'Friday' => 'viernes',
                    'Saturday' => 'sábado'
                ];
                $diaEnIngles = date('l', strtotime($cajaSeleccionada['Fecha']));
                $diaEnEspañol = $dias[$diaEnIngles] ?? $diaEnIngles;
                echo "Caja: " . $diaEnEspañol . " " . date('d-m-Y', strtotime($cajaSeleccionada['Fecha']));
            } else {
                echo "Sin caja seleccionada";
            }
            ?>        
        </span>
    </div>

    <!-- Usuario -->
    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <img src="../assets/img/user.jpg" alt="Profile" class="rounded-circle img-fluid me-2">
                    <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $_SESSION['Usuario_Nombre'] . ' ' . $_SESSION['Usuario_Apellido']; ?></span>
                </a><!-- End Profile Image Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6><?php echo $_SESSION['Usuario_Nombre'] . ' ' . $_SESSION['Usuario_Apellido']; ?></h6>
                        <span><?php echo $_SESSION['Usuario_Tipo']; ?></span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="../imprenta_usuarios/modificar_cuenta.php">
                            <i class="bi bi-gear"></i>
                            <span>Modificar Cuenta</span>
                        </a>
                    </li>
                                        <li>
                        <a class="dropdown-item d-flex align-items-center" href="../core/cerrarsesion.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Cerrar sesión</span>
                        </a>
                    </li>
                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->
        </ul>
    </nav><!-- End Icons Navigation -->

</header><!-- End Header -->
</body>
</html>