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
require_once 'funciones/imprenta.php';

$MiConexion = ConexionBD();

// Obtener los tipos de servicio y métodos de pago desde la base de datos
$TiposServicio = Listar_Tipos_Servicios($MiConexion);
$TiposPagos = Listar_Tipos_Pagos($MiConexion);

if (!empty($_POST['BotonRegistrar'])) {
    // Validar y limpiar los datos del formulario
    Validar_Venta();

    // Asignar el mensaje de validación a una variable local
    $Mensaje = $_SESSION['Mensaje'];
    $Estilo = 'danger'; // Estilo para mensajes de error

    // Si no hay errores, proceder con la inserción
    if (empty($Mensaje)) {
        // Verificar si $_SESSION['Id_Caja'] tiene contenido
        if (empty($_SESSION['Id_Caja'])) {
            echo "<script>
                alert('Error: No hay caja seleccionada. Por favor, seleccione una caja antes de registrar la venta.');
                window.location.href = 'index.php';
            </script>";
            exit;
        }

        // Llamar al método InsertarVenta
        if (InsertarVenta($MiConexion)) {
            $_SESSION['Mensaje'] = 'Detalle de venta registrado correctamente.';
            $_SESSION['Estilo'] = 'success';

            // Redirigir para evitar reenvío del formulario
            header("Location: planilla_caja.php");
            exit;
        } else {
            $_SESSION['Mensaje'] = 'Error al registrar el detalle de venta.';
            $_SESSION['Estilo'] = 'danger';
        }
    }
}

$MiConexion->close();
ob_end_flush(); // Envía el contenido del búfer al navegador
?>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Ventas</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Menu</a></li>
                <li class="breadcrumb-item">Ventas</li>
                <li class="breadcrumb-item active">Agregar Venta</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="card">
            <div class="card-body">

                <!-- Sección de Métodos de Pago -->
                <form method="post">
                    <?php if (!empty($Mensaje)) { ?>
                        <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                        <?php echo $Mensaje; ?>
                        </div>
                        <?php unset($_SESSION['Mensaje'], $_SESSION['Estilo']); // Limpiar el mensaje después de mostrarlo ?>
                    <?php } ?>

                    <!-- Campo oculto para idCaja -->
                    <input type="hidden" name="idCaja" value="<?php echo isset($_SESSION['Id_Caja']) ? $_SESSION['Id_Caja'] : ''; ?>">

                    <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 card-title">Seleccione el Método de Pago</h6>
                        <a href="listados_metodos_pago.php" class="btn btn-outline-primary btn-sm">Gestionar Métodos de Pago</a>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($TiposPagos as $tipo) { ?>
                            <button type="button" class="btn btn-secondary mx-2 my-2 metodo-pago" data-id="<?php echo $tipo['idTipoPago']; ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                        <input type="hidden" name="idTipoPago" id="idTipoPago">
                    </div>

                    <!-- Sección de Tipos de Servicio -->
                    <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 card-title">Seleccione el Tipo de Servicio</h6>
                        <a href="listados_tipos_servicios.php" class="btn btn-outline-primary btn-sm">Gestionar Tipos de Servicio</a>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($TiposServicio as $tipo) { ?>
                            <button type="button" class="btn btn-secondary mx-2 my-2 tipo-servicio" data-id="<?php echo $tipo['idTipoServicio']; ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                        <input type="hidden" name="idDetalle" id="idDetalle">
                    </div>

                    <!-- Campo para ingresar el valor de dinero -->
                    <div class="text-center mt-4">
                        <label for="valorDinero" class="form-label">Ingrese el Valor de Dinero</label>
                        <div class="input-group w-50 mx-auto">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control text-center" id="valorDinero" name="Monto" placeholder="0" min="0" step="1">
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
                    <div class="text-center mt-4">
                        <input type='hidden' name="idTipoOperacion" value="1"/>
                        <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </form><!-- End Horizontal Form -->
            </div>
        </div>

    </section>

</main><!-- End #main -->

<?php
require ('footer.inc.php'); // Incluir el footer
?>

<script>
    // Manejar la selección de los botones de Métodos de Pago
    const metodoPagoButtons = document.querySelectorAll('.metodo-pago');
    metodoPagoButtons.forEach(button => {
        button.addEventListener('click', () => {
            metodoPagoButtons.forEach(btn => btn.classList.remove('btn-primary')); // Quitar selección previa
            metodoPagoButtons.forEach(btn => btn.classList.add('btn-secondary')); // Restaurar estilo secundario
            button.classList.remove('btn-secondary'); // Quitar estilo secundario
            button.classList.add('btn-primary'); // Agregar estilo seleccionado
            document.getElementById('idTipoPago').value = button.getAttribute('data-id'); // Asignar valor al input hidden
        });
    });

    // Manejar la selección de los botones de Tipos de Servicio
    const tipoServicioButtons = document.querySelectorAll('.tipo-servicio');
    tipoServicioButtons.forEach(button => {
        button.addEventListener('click', () => {
            tipoServicioButtons.forEach(btn => btn.classList.remove('btn-primary')); // Quitar selección previa
            tipoServicioButtons.forEach(btn => btn.classList.add('btn-secondary')); // Restaurar estilo secundario
            button.classList.remove('btn-secondary'); // Quitar estilo secundario
            button.classList.add('btn-primary'); // Agregar estilo seleccionado
            document.getElementById('idDetalle').value = button.getAttribute('data-id'); // Asignar valor al input hidden
        });
    });
</script>

<?php
ob_end_flush();
?>

</body>
</html>