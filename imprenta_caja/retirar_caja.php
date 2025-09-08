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

// Obtener tipos de movimiento de salida (retiros)
$TiposMovimientoSalida = [];
$sql = "SELECT idTipoMovimiento, denominacion 
        FROM tipo_movimiento 
        WHERE es_salida = 1 AND idActivo = 1 
        ORDER BY denominacion";
$resultado = mysqli_query($MiConexion, $sql);
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $TiposMovimientoSalida[] = $fila;
    }
}

// Listar usuarios activos
$Usuarios = [];
$sqlUsuarios = "SELECT idUsuario, nombre, apellido 
                FROM usuarios 
                WHERE idActivo = 1 
                ORDER BY apellido, nombre";
$resUsuarios = mysqli_query($MiConexion, $sqlUsuarios);
if ($resUsuarios) {
    while ($fila = mysqli_fetch_assoc($resUsuarios)) {
        $Usuarios[] = $fila;
    }
}

// Listar proveedores activos
$Proveedores = [];
$sqlProveedores = "SELECT idProveedor, nombre 
                   FROM proveedores 
                   WHERE idActivo = 1 
                   ORDER BY nombre";
$resProveedores = mysqli_query($MiConexion, $sqlProveedores);
if ($resProveedores) {
    while ($fila = mysqli_fetch_assoc($resProveedores)) {
        $Proveedores[] = $fila;
    }
}

// Listar proveedores de insumos activos
$ProveedoresInsumos = [];
$sqlProveedoresInsumos = "SELECT idProveedorInsumo, nombre FROM proveedores_insumos WHERE idActivo = 1 ORDER BY nombre";
$resProveedoresInsumos = mysqli_query($MiConexion, $sqlProveedoresInsumos);
if ($resProveedoresInsumos) {
    while ($fila = mysqli_fetch_assoc($resProveedoresInsumos)) {
        $ProveedoresInsumos[] = $fila;
    }
}

// Listar insumos activos
$Insumos = [];
$sqlInsumos = "SELECT idInsumo, denominacion FROM insumos ORDER BY denominacion";
$resInsumos = mysqli_query($MiConexion, $sqlInsumos);
if ($resInsumos) {
    while ($fila = mysqli_fetch_assoc($resInsumos)) {
        $Insumos[] = $fila;
    }
}

// Listar servicios activos
$Servicios = [];
$sqlServicios = "SELECT idServicio, denominacion FROM servicios ORDER BY denominacion";
$resServicios = mysqli_query($MiConexion, $sqlServicios);
if ($resServicios) {
    while ($fila = mysqli_fetch_assoc($resServicios)) {
        $Servicios[] = $fila;
    }
}

$Mensaje = '';
$Estilo = '';

if (!empty($_POST['BotonRegistrar'])) {
    Validar_Venta();
    $Mensaje = $_SESSION['Mensaje'];
    $Estilo = 'danger';

    if (empty($Mensaje)) {
        if (empty($_SESSION['Id_Caja'])) {
            echo "<script>
                alert('Error: No hay caja seleccionada. Por favor, seleccione una caja antes de registrar el retiro.');
                window.location.href = 'index.php';
            </script>";
            exit;
        }

        if (InsertarMovimientoRetiro($MiConexion)) {
            $_SESSION['Mensaje'] = 'Detalle de retiro registrado correctamente.';
            $_SESSION['Estilo'] = 'success';
            header("Location: planilla_caja.php");
            exit;
        } else {
            $_SESSION['Mensaje'] = 'Error al registrar el detalle de retiro.';
            $_SESSION['Estilo'] = 'danger';
        }
    }
}

$MiConexion->close();
ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Retiros</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item">Retiros</li>
                <li class="breadcrumb-item active">Agregar Retiro</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <form method="post" id="formRetiro">
                    <?php if (!empty($Mensaje)) { ?>
                        <div class="alert alert-<?php echo $Estilo; ?> alert-dismissable">
                            <?php echo $Mensaje; ?>
                        </div>
                        <?php unset($_SESSION['Mensaje'], $_SESSION['Estilo']); ?>
                    <?php } ?>

                    <input type="hidden" name="idCaja" value="<?php echo isset($_SESSION['Id_Caja']) ? $_SESSION['Id_Caja'] : ''; ?>">
                    <input type="hidden" name="idTipoPago" id="idTipoPago" value="14">
                    <input type="hidden" id="MontoReal" name="MontoReal" value="0">
                    <input type="hidden" name="idTipoMovimiento" id="idTipoMovimiento">
                    <input type="hidden" name="nombreMovimiento" id="nombreMovimiento">

                    <div class="text-center mb-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 card-title">Seleccione el Tipo de Retiro</h6>
                        <a href="../imprenta_tipos_movimientos_salida/listados_tipos_movimientos.php" class="btn btn-outline-primary btn-sm">Gestionar Tipos de Retiro</a>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($TiposMovimientoSalida as $tipo) { ?>
                            <button type="button" class="btn btn-secondary mx-2 my-2 tipo-movimiento" data-id="<?php echo $tipo['idTipoMovimiento']; ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                    </div>

                    <div class="text-center mt-4">
                        <label for="valorDinero" class="form-label">Ingrese el Valor de Dinero</label>
                        <div class="input-group w-50 mx-auto">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-center money-format" id="valorDinero" name="Monto" placeholder="$0,00" value="$0,00">
                        </div>
                    </div>

                    <div class="row justify-content-center mb-4">
                        <div class="col-md-6 text-center">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="Observaciones" rows="3" placeholder="Ingrese comentarios u observaciones"></textarea>
                        </div>
                    </div>

                    <!-- Campos dinámicos según tipo de retiro -->
                    <div id="extraFields" style="display:none;">
                        <div id="fieldSueldos" style="display:none;" class="mb-3 text-center">
                            <label for="usuarioSueldo" class="form-label">Seleccione Usuario</label>
                            <select class="form-control" name="usuarioSueldo" id="usuarioSueldo">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($Usuarios as $u) { ?>
                                    <option value="<?php echo $u['idUsuario']; ?>">
                                        <?php echo $u['apellido'] . ', ' . $u['nombre']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div id="fieldProveedores" style="display:none;" class="mb-3 text-center">
                            <label for="proveedor" class="form-label">Seleccione Proveedor</label>
                            <select class="form-control" name="proveedor" id="proveedor">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($Proveedores as $p) { ?>
                                    <option value="<?php echo $p['idProveedor']; ?>"><?php echo $p['nombre']; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div id="fieldServicios" style="display:none;" class="mb-3 text-center">
                            <label for="servicio" class="form-label">Seleccione Servicio</label>
                            <select class="form-control" name="servicio" id="servicio">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($Servicios as $s) { ?>
                                    <option value="<?php echo $s['idServicio']; ?>"><?php echo $s['denominacion']; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div id="fieldInsumos" style="display:none;" class="mb-3 text-center">
                            <label for="proveedorInsumo" class="form-label">Seleccione Proveedor de Insumo</label>
                            <select class="form-control" name="proveedorInsumo" id="proveedorInsumo">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($ProveedoresInsumos as $pi) { ?>
                                    <option value="<?php echo $pi['idProveedorInsumo']; ?>"><?php echo $pi['nombre']; ?></option>
                                <?php } ?>
                            </select>

                            <label for="insumo" class="form-label mt-2">Seleccione Insumo</label>
                            <select class="form-control" name="insumo" id="insumo">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($Insumos as $i) { ?>
                                    <option value="<?php echo $i['idInsumo']; ?>"><?php echo $i['denominacion']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary" value="Registrar" name="BotonRegistrar">Agregar Retiro</button>
                        <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require ('../shared/footer.inc.php'); ?>

<script>
// FUNCIÓN PARA FORMATEAR EL DINERO
function formatMoney(input) {
    let cursorPos = input.selectionStart;
    let originalLength = input.value.length;
    let rawValue = input.value.replace(/[^\d,]/g, '');
    let commaPos = rawValue.indexOf(',');
    if (commaPos !== -1) {
        rawValue = rawValue.substring(0, commaPos + 1) + rawValue.substring(commaPos + 1).replace(/,/g, '');
    }
    let parts = rawValue.split(',');
    let integerPart = parts[0].replace(/\D/g, '') || '0';
    let decimalPart = parts[1] ? parts[1].replace(/\D/g, '').substring(0, 2) : '';
    let formattedInteger = '';
    if (integerPart.length > 3) {
        formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    } else {
        formattedInteger = integerPart;
    }
    let newValue = '$' + formattedInteger;
    if (decimalPart.length > 0) {
        newValue += ',' + decimalPart;
    } else if (commaPos !== -1) {
        newValue += ',00';
    }
    input.value = newValue;
    let newLength = input.value.length;
    cursorPos = Math.max(1, cursorPos + (newLength - originalLength));
    input.setSelectionRange(cursorPos, cursorPos);
    let numericValue = newValue.replace(/[^\d,]/g, '').replace(',', '.');
    document.getElementById('MontoReal').value = numericValue || '0';
}

const moneyInput = document.getElementById('valorDinero');
moneyInput.addEventListener('input', function() { formatMoney(this); });
moneyInput.addEventListener('focus', function() { this.value = this.value.replace('$', ''); });
moneyInput.addEventListener('blur', function() {
    if (!this.value.includes('$')) { this.value = '$' + this.value; }
    formatMoney(this);
    if (this.value === '$' || this.value === '') {
        this.value = '$0,00';
        document.getElementById('MontoReal').value = '0';
    }
});

document.getElementById('resetButton').addEventListener('click', function() {
    document.getElementById('MontoReal').value = '0';
    document.getElementById('valorDinero').value = '$0,00';
});

// Manejo de botones de tipo de movimiento
document.querySelectorAll('.tipo-movimiento').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.tipo-movimiento').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        this.classList.remove('btn-secondary');
        this.classList.add('btn-primary');
        document.getElementById('idTipoMovimiento').value = this.getAttribute('data-id');
        document.getElementById('nombreMovimiento').value = this.innerText;

        document.getElementById('extraFields').style.display = 'block';
        document.getElementById('fieldSueldos').style.display = 'none';
        document.getElementById('fieldProveedores').style.display = 'none';
        document.getElementById('fieldServicios').style.display = 'none';
        document.getElementById('fieldInsumos').style.display = 'none';

        let nombreMovimiento = this.innerText.toLowerCase();
        if (nombreMovimiento.includes('sueldo')) {
            document.getElementById('fieldSueldos').style.display = 'block';
        } else if (nombreMovimiento.includes('proveedor')) {
            document.getElementById('fieldProveedores').style.display = 'block';
        } else if (nombreMovimiento.includes('servicio')) {
            document.getElementById('fieldServicios').style.display = 'block';
        } else if (nombreMovimiento.includes('insumo')) {
            document.getElementById('fieldInsumos').style.display = 'block';
        } else {
            document.getElementById('extraFields').style.display = 'none';
        }
    });
});

// VALIDACIÓN ANTES DE ENVIAR
document.getElementById('formRetiro').addEventListener('submit', function(e) {
    let tipoMovimiento = document.getElementById('nombreMovimiento').value.toLowerCase();
    let errorMsg = '';

    if (parseFloat(document.getElementById('MontoReal').value) <= 0) {
        errorMsg = 'Por favor ingrese un monto válido mayor a cero';
    }

    if (tipoMovimiento.includes('sueldo')) {
        if (!document.getElementById('usuarioSueldo').value) {
            errorMsg = 'Debe seleccionar un usuario para el retiro de sueldo';
        }
    } else if (tipoMovimiento.includes('proveedor')) {
        if (!document.getElementById('proveedor').value) {
            errorMsg = 'Debe seleccionar un proveedor para este tipo de retiro';
        }
    } else if (tipoMovimiento.includes('servicio')) {
        if (!document.getElementById('servicio').value) {
            errorMsg = 'Debe seleccionar un servicio para este retiro';
        }
    } else if (tipoMovimiento.includes('insumo')) {
        if (!document.getElementById('proveedorInsumo').value) {
            errorMsg = 'Debe seleccionar un proveedor de insumo';
        } else if (!document.getElementById('insumo').value) {
            errorMsg = 'Debe seleccionar un insumo';
        }
    }

    if (errorMsg) {
        e.preventDefault();
        alert(errorMsg);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    formatMoney(moneyInput);
});
</script>

<?php ob_end_flush(); ?>
</body>
</html>
