<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: cerrarsesion.php');
    exit;
}

require('encabezado.inc.php');
require('barraLateral.inc.php');
require_once 'funciones/conexion.php';
require_once 'funciones/select_general.php';

$MiConexion = ConexionBD();

// Obtener los proveedores desde la base de datos
$Proveedores = Listar_Proveedores($MiConexion);

if (!empty($_POST['BotonRegistrar'])) {
    // Validar y limpiar los datos del formulario
    $_POST['idCaja'] = isset($_SESSION['Id_Caja']) ? (int)$_SESSION['Id_Caja'] : null;
    $_POST['idTipoPago'] = isset($_POST['idTipoPago']) ? (int)$_POST['idTipoPago'] : null;
    $_POST['idTipoServicio'] = isset($_POST['idTipoServicio']) ? (int)$_POST['idTipoServicio'] : null;
    $_POST['idUsuario'] = isset($_SESSION['Usuario_Id']) ? (int)$_SESSION['Usuario_Id'] : null;
    $_POST['Monto'] = isset($_POST['ValorDinero']) ? (float)$_POST['ValorDinero'] : null;
    $_POST['idTipoOperacion'] = isset($_POST['idTipoOperacion']) ? (int)$_POST['idTipoOperacion'] : null;
    $_POST['Observaciones'] = isset($_POST['Observaciones']) ? trim($_POST['Observaciones']) : null;

    if (empty($_POST['idCaja'])) {
        echo "<script>
            alert('Error: No hay caja seleccionada. Por favor, seleccione una caja antes de registrar el retiro.');
            window.location.href = 'index.php';
        </script>";
        exit;
    }

    // Llamar al método InsertarVenta
    if (InsertarVenta($MiConexion)) {
        $_SESSION['Mensaje'] = 'Retiro registrado correctamente.';
        $_SESSION['Estilo'] = 'success';

        // Redirigir para evitar reenvío del formulario
        header("Location: planilla_caja.php");
        exit;
    } else {
        $_SESSION['Mensaje'] = 'Error al registrar el retiro.';
        $_SESSION['Estilo'] = 'danger';
    }
}

$MiConexion->close();
?>

<main id="main" class="main">

    <div class="pagetitle">
      <h1>Retiros de Caja</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
          <li class="breadcrumb-item">Caja</li>
          <li class="breadcrumb-item active">Retirar de Caja</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="card">
        <div class="card-body">

          <!-- Sección de Métodos de Retiro -->
        <form method="post">
            <?php if (!empty($_SESSION['Mensaje'])) { ?>
                <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['Mensaje']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['Mensaje'], $_SESSION['Estilo']); ?>
            <?php } ?>
            <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 card-title">Seleccione el Método de Retiro</h6>
            </div>
            <div class="d-flex flex-wrap justify-content-center">
                <button type="button" class="btn btn-secondary mx-2 my-2 metodo-pago" data-id="1">Efectivo</button>
                <button type="button" class="btn btn-secondary mx-2 my-2 metodo-pago" data-id="2">Banco</button>
                <input type="hidden" name="idTipoPago" id="idTipoPago">
            </div>

            <div class="container">
                <!-- Sección de Tipos de Retiro -->
                <div class="text-center mb-4">
                    <h6 class="mb-0 card-title">Seleccione el Tipo de Retiro</h6>
                </div>
                <div class="row justify-content-center mb-4">
                    <!-- Select de Proveedores -->
                    <div class="col-auto">
                        <select class="form-select btn btn-secondary text-start" name="idProveedor" id="idProveedor" style="width: 120px;">
                            <option value="" selected disabled>Proveedor</option>
                            <?php foreach ($Proveedores as $proveedor) { ?>
                                <option value="<?php echo $proveedor['ID_PROVEEDOR']; ?>">
                                    <?php echo $proveedor['NOMBRE']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <!-- Botones de Sueldos y Etc. -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-secondary tipo-servicio" data-id="3">Sueldos</button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-secondary tipo-servicio" data-id="4">Etc.</button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="idTipoServicio" id="idTipoServicio">

            <!-- Campo para ingresar el valor de dinero -->
            <div class="row justify-content-center mb-4">
                <div class="col-md-6 text-center">
                    <label for="valorDinero" class="form-label">Ingrese el Valor de Dinero</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control text-center" id="valorDinero" name="ValorDinero" placeholder="0" min="0" step="1">
                    </div>
                </div>
            </div>

            <!-- Campo para observaciones -->
            <div class="row justify-content-center mb-4">
                <div class="col-md-6 text-center">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="Observaciones" rows="3" placeholder="Ingrese comentarios u observaciones"></textarea>
                </div>
            </div>

            <!-- Botones de registrar o reset -->
            <div class="row justify-content-center">
                <input type='hidden' name="idTipoOperacion" id="idTipoOperacion" />
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Registrar Retiro</button>
                </div>
                <div class="col-auto">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </div>
        </form><!-- End Horizontal Form -->
        </div>
      </div>

    </section>

</main><!-- End #main -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const metodoPagoButtons = document.querySelectorAll('.metodo-pago');
        const tipoServicioButtons = document.querySelectorAll('.tipo-servicio');
        const idTipoOperacionInput = document.getElementById('idTipoOperacion');

        // Manejar métodos de pago
        metodoPagoButtons.forEach(button => {
            button.addEventListener('click', () => {
                metodoPagoButtons.forEach(btn => btn.classList.remove('btn-primary'));
                button.classList.add('btn-primary');
                document.getElementById('idTipoPago').value = button.getAttribute('data-id');
                idTipoOperacionInput.value = button.getAttribute('data-id') === '1' ? '2' : '3';
            });
        });

        // Manejar tipos de servicio
        tipoServicioButtons.forEach(button => {
            button.addEventListener('click', () => {
                tipoServicioButtons.forEach(btn => btn.classList.remove('btn-primary'));
                button.classList.add('btn-primary');
                document.getElementById('idTipoServicio').value = button.getAttribute('data-id');
            });
        });
    });
</script>

<?php
require('footer.inc.php');
ob_end_flush();
?>