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
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// Obtener los tipos de servicio y métodos de pago desde la base de datos
$TiposPagos = Listar_Tipos_Pagos_Entrada($MiConexion);
$TiposMovimientoEntrada = Listar_Tipos_Movimiento_Entrada($MiConexion);

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

        // Llamar al método InsertarMovimiento
        if (InsertarMovimiento($MiConexion)) {
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
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
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
                        <a href="../imprenta_metodos_pago/listados_metodos_pago.php" class="btn btn-outline-primary btn-sm">Gestionar Métodos de Pago</a>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($TiposPagos as $tipo) { ?>
                            <button type="button" class="btn btn-secondary mx-2 my-2 metodo-pago" data-id="<?php echo $tipo['idTipoPago']; ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                        <input type="hidden" name="idTipoPago" id="idTipoPago">
                    </div>


                    <!-- Sección de Tipos de Movimiento Entrada -->
                    <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 card-title">Seleccione el Tipo de Entrada</h6>
                        <a href="../imprenta_tipos_movimientos_entrada/listados_tipos_movimientos.php" class="btn btn-outline-primary btn-sm">Gestionar Tipos de Entrada</a>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($TiposMovimientoEntrada as $tipo) { ?>
                            <button type="button" class="btn btn-secondary mx-2 my-2 tipo-movimiento" data-id="<?php echo $tipo['idTipoMovimiento']; ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                        <input type="hidden" name="idTipoMovimiento" id="idTipoMovimiento">
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
require ('../shared/footer.inc.php'); // Incluir el footer
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

    // Manejar la selección de los botones de Tipos de Movimiento Entrada
    const tipoMovimientoButtons = document.querySelectorAll('.tipo-movimiento');
    tipoMovimientoButtons.forEach(button => {
        button.addEventListener('click', () => {
            tipoMovimientoButtons.forEach(btn => btn.classList.remove('btn-primary')); // Quitar selección previa
            tipoMovimientoButtons.forEach(btn => btn.classList.add('btn-secondary')); // Restaurar estilo secundario
            button.classList.remove('btn-secondary'); // Quitar estilo secundario
            button.classList.add('btn-primary'); // Agregar estilo seleccionado
            document.getElementById('idTipoMovimiento').value = button.getAttribute('data-id'); // Asignar valor al input hidden
        });
    });
</script>

<?php
ob_end_flush();
?>

</body>
</html>