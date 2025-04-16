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
    $idCaja = isset($_SESSION['Id_Caja']) ? (int)$_SESSION['Id_Caja'] : null;
    $idTipoPago = isset($_POST['idTipoPago']) ? (int)$_POST['idTipoPago'] : null;
    $idTipoServicio = isset($_POST['idTipoServicio']) ? (int)$_POST['idTipoServicio'] : null;
    $idUsuario = isset($_SESSION['Usuario_Id']) ? (int)$_SESSION['Usuario_Id'] : null;
    $monto = isset($_POST['ValorDinero']) ? (float)$_POST['ValorDinero'] : null;
    $observaciones = isset($_POST['Observaciones']) ? trim($_POST['Observaciones']) : null;

    if (empty($idCaja)) {
        echo "<script>
            alert('Error: No hay caja seleccionada. Por favor, seleccione una caja antes de registrar el retiro.');
            window.location.href = 'index.php';
        </script>";
        exit;
    } elseif ($idCaja && $idTipoPago && $idTipoServicio && $idUsuario && $monto > 0) {
        // Insertar el retiro en la base de datos
        $query = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoServicio, idUsuario, monto, observaciones) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $MiConexion->prepare($query);
        $stmt->bind_param("iiiids", $idCaja, $idTipoPago, $idTipoServicio, $idUsuario, $monto, $observaciones);

        if ($stmt->execute()) {
            $_SESSION['Mensaje'] = 'Retiro registrado correctamente.';
            $_SESSION['Estilo'] = 'success';

            // Redirigir para evitar reenvío del formulario
            header("Location: planilla_caja.php");
            exit;
        } else {
            $_SESSION['Mensaje'] = 'Error al registrar el retiro: ' . $stmt->error;
            $_SESSION['Estilo'] = 'danger';
        }

        $stmt->close();
    } else {
        $_SESSION['Mensaje'] = 'Por favor, complete todos los campos correctamente.';
        $_SESSION['Estilo'] = 'warning';
    }
}

$MiConexion->close();
ob_end_flush();
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

            <!-- Sección de Tipos de Retiro -->
            <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 card-title">Seleccione el Tipo de Retiro</h6>
            </div>
            <div class="d-flex flex-wrap justify-content-center">
                <div class="dropdown mx-2 my-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownProveedores" data-bs-toggle="dropdown" aria-expanded="false">
                        Proveedores
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownProveedores">
                        <?php foreach ($Proveedores as $proveedor) { ?>
                            <li><a class="dropdown-item tipo-servicio" data-id="<?php echo $proveedor['idProveedor']; ?>"><?php echo $proveedor['nombre']; ?></a></li>
                        <?php } ?>
                    </ul>
                </div>
                <button type="button" class="btn btn-secondary mx-2 my-2 tipo-servicio" data-id="3">Sueldos</button>
                <button type="button" class="btn btn-secondary mx-2 my-2 tipo-servicio" data-id="4">Etc.</button>
                <input type="hidden" name="idTipoServicio" id="idTipoServicio">
            </div>

            <!-- Campo para ingresar el valor de dinero -->
            <div class="text-center mt-4">
                <label for="valorDinero" class="form-label">Ingrese el Valor de Dinero</label>
                <div class="input-group w-50 mx-auto">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control text-center" id="valorDinero" name="ValorDinero" placeholder="0" min="0" step="1">
                </div>
            </div>

            <!-- Campo para observaciones -->
            <div class="text-center mt-4">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea class="form-control w-50 mx-auto" id="observaciones" name="Observaciones" rows="3" placeholder="Ingrese comentarios u observaciones"></textarea>
            </div>

            <!-- Botones de registrar o reset -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Registrar Retiro</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        </form><!-- End Horizontal Form -->
        </div>
      </div>

    </section>

</main><!-- End #main -->

<script>
    // Manejar la selección de los botones de Métodos de Retiro
    const metodoPagoButtons = document.querySelectorAll('.metodo-pago');
    metodoPagoButtons.forEach(button => {
        button.addEventListener('click', () => {
            metodoPagoButtons.forEach(btn => btn.classList.remove('btn-primary'));
            metodoPagoButtons.forEach(btn => btn.classList.add('btn-secondary'));
            button.classList.remove('btn-secondary');
            button.classList.add('btn-primary');
            document.getElementById('idTipoPago').value = button.getAttribute('data-id');
        });
    });

    // Manejar la selección de los botones de Tipos de Retiro
    const tipoServicioButtons = document.querySelectorAll('.tipo-servicio');
    tipoServicioButtons.forEach(button => {
        button.addEventListener('click', () => {
            tipoServicioButtons.forEach(btn => btn.classList.remove('btn-primary'));
            tipoServicioButtons.forEach(btn => btn.classList.add('btn-secondary'));
            button.classList.remove('btn-secondary');
            button.classList.add('btn-primary');
            document.getElementById('idTipoServicio').value = button.getAttribute('data-id');
        });
    });
</script>

<?php
ob_end_flush();
?>

</body>
</html>