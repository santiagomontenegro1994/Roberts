<?php
ob_start();
session_start();

$Mensaje = '';
$Estilo = '';

if (!empty($_SESSION['Mensaje'])) {
    $Mensaje = $_SESSION['Mensaje'];
    $Estilo = $_SESSION['Estilo'];
    unset($_SESSION['Mensaje'], $_SESSION['Estilo']);
}

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

$TiposPagos = Listar_Tipos_Pagos_Entrada($MiConexion);
$TiposMovimientoEntrada = Listar_Tipos_Movimiento_Entrada($MiConexion);

if (!empty($_POST['BotonRegistrar'])) {
    Validar_Venta();
    $Mensaje = $_SESSION['Mensaje'];
    $Estilo = 'danger';

    if (empty($Mensaje)) {
        if (empty($_SESSION['Id_Caja'])) {
            echo "<script>
                alert('Error: No hay caja seleccionada. Por favor, seleccione una caja antes de registrar la venta.');
                window.location.href = 'index.php';
            </script>";
            exit;
        }

        if (InsertarMovimiento($MiConexion)) {
            $_SESSION['Mensaje'] = 'Detalle de venta registrado correctamente.';
            $_SESSION['Estilo'] = 'success';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $_SESSION['Mensaje'] = 'Error al registrar el detalle de venta.';
            $_SESSION['Estilo'] = 'danger';
        }
    }
}

$MiConexion->close();
ob_end_flush();
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
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <form method="post" id="formVenta">
                    <?php if (!empty($Mensaje)) { ?>
                        <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                            <?php echo $Mensaje; ?>
                        </div>
                    <?php } ?>

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

                    <div class="text-center mt-4">
                        <label for="valorDinero" class="form-label">Ingrese el Valor de Dinero</label>
                        <div class="input-group w-50 mx-auto">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-center money-format" id="valorDinero" name="Monto" placeholder="$0,00" value="$0,00">
                            <input type="hidden" id="MontoReal" name="MontoReal" value="0">
                        </div>
                    </div>

                    <div class="row justify-content-center mb-4">
                        <div class="col-md-6 text-center">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="Observaciones" rows="3" placeholder="Ingrese comentarios u observaciones"></textarea>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <input type='hidden' name="idTipoOperacion" value="1"/>
                        <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar</button>
                        <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                        <a href="planilla_caja.php" class="btn btn-success">Ir a Planilla de Caja</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require ('../shared/footer.inc.php'); ?>

<script>
    // Función principal para formatear el dinero
    function formatMoney(input) {
        // Guardar posición del cursor
        let cursorPos = input.selectionStart;
        let originalLength = input.value.length;
        
        // Obtener solo números y coma decimal
        let rawValue = input.value.replace(/[^\d,]/g, '');
        
        // Manejar múltiples comas
        let commaPos = rawValue.indexOf(',');
        if (commaPos !== -1) {
            rawValue = rawValue.substring(0, commaPos + 1) + rawValue.substring(commaPos + 1).replace(/,/g, '');
        }
        
        // Separar parte entera y decimal
        let parts = rawValue.split(',');
        let integerPart = parts[0].replace(/\D/g, '') || '0';
        let decimalPart = parts[1] ? parts[1].replace(/\D/g, '').substring(0, 2) : '';
        
        // Formatear parte entera con puntos cada 3 dígitos
        let formattedInteger = '';
        if (integerPart.length > 3) {
            formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        } else {
            formattedInteger = integerPart;
        }
        
        // Construir valor final
        let newValue = '$' + formattedInteger;
        if (decimalPart.length > 0) {
            newValue += ',' + decimalPart;
        } else if (commaPos !== -1) {
            newValue += ',00';
        }
        
        // Actualizar campo visible
        input.value = newValue;
        
        // Ajustar posición del cursor
        let newLength = input.value.length;
        cursorPos = Math.max(1, cursorPos + (newLength - originalLength));
        input.setSelectionRange(cursorPos, cursorPos);
        
        // Actualizar campo hidden para el servidor
        let numericValue = newValue.replace(/[^\d,]/g, '').replace(',', '.');
        document.getElementById('MontoReal').value = numericValue || '0';
    }

    // Eventos del campo de dinero
    const moneyInput = document.getElementById('valorDinero');

    moneyInput.addEventListener('input', function() {
        formatMoney(this);
    });

    moneyInput.addEventListener('focus', function() {
        // Quitar $ temporalmente para edición
        this.value = this.value.replace('$', '');
    });

    moneyInput.addEventListener('blur', function() {
        // Asegurar formato completo al salir del campo
        if (!this.value.includes('$')) {
            this.value = '$' + this.value;
        }
        formatMoney(this);
        
        // Si está vacío o solo tiene $, poner $0,00
        if (this.value === '$' || this.value === '') {
            this.value = '$0,00';
            document.getElementById('MontoReal').value = '0';
        }
    });

    // Validación al enviar el formulario
    document.getElementById('formVenta').addEventListener('submit', function(e) {
        if (parseFloat(document.getElementById('MontoReal').value) <= 0) {
            e.preventDefault();
            alert('Por favor ingrese un monto válido mayor a cero');
            moneyInput.focus();
        }
    });

    // Botón reset
    document.getElementById('resetButton').addEventListener('click', function() {
        document.getElementById('MontoReal').value = '0';
        document.getElementById('valorDinero').value = '$0,00';
        
        // También resetear los botones seleccionados
        document.querySelectorAll('.metodo-pago, .tipo-movimiento').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        
        document.getElementById('idTipoPago').value = '';
        document.getElementById('idTipoMovimiento').value = '';
    });

    // Manejo de botones de método de pago
    document.querySelectorAll('.metodo-pago').forEach(button => {
        button.addEventListener('click', function() {
            // Remover selección previa
            document.querySelectorAll('.metodo-pago').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            
            // Seleccionar actual
            this.classList.remove('btn-secondary');
            this.classList.add('btn-primary');
            
            // Actualizar campo hidden
            document.getElementById('idTipoPago').value = this.getAttribute('data-id');
        });
    });

    // Manejo de botones de tipo de movimiento
    document.querySelectorAll('.tipo-movimiento').forEach(button => {
        button.addEventListener('click', function() {
            // Remover selección previa
            document.querySelectorAll('.tipo-movimiento').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            
            // Seleccionar actual
            this.classList.remove('btn-secondary');
            this.classList.add('btn-primary');
            
            // Actualizar campo hidden
            document.getElementById('idTipoMovimiento').value = this.getAttribute('data-id');
        });
    });

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        formatMoney(moneyInput);
    });
</script>

<?php ob_end_flush(); ?>
</body>
</html>