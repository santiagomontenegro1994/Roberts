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

$DatosVentaActual = array();
$TiposFactura = Listar_Tipos_Factura($MiConexion);

if (!empty($_POST['BotonModificarVenta'])) {
    Validar_Modificar_Venta(); // Nueva función específica para modificar

    if (empty($_SESSION['Mensaje'])) {
        if (Modificar_Venta($MiConexion) != false) {
            $_SESSION['Mensaje'] = "La venta se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: planilla_caja.php');
            exit;
        }
    } else {
        $_SESSION['Estilo'] = 'warning';
        $DatosVentaActual['idDetalleCaja'] = $_POST['idDetalleCaja'] ?? '';
        $DatosVentaActual['idCaja'] = $_POST['idCaja'] ?? '';
        $DatosVentaActual['Monto'] = $_POST['MontoReal'] ?? '';
        $DatosVentaActual['idTipoPago'] = $_POST['idTipoPago'] ?? '';
        $DatosVentaActual['idTipoMovimiento'] = $_POST['idTipoMovimiento'] ?? '';
        $DatosVentaActual['observaciones'] = $_POST['Observaciones'] ?? '';
        $DatosVentaActual['facturado'] = $_POST['facturado'] ?? 0;
        $DatosVentaActual['idTipoFactura'] = $_POST['idTipoFactura'] ?? null;
        $DatosVentaActual['numeroFactura'] = $_POST['numeroFactura'] ?? null;
    }
} else if (!empty($_GET['idDetalleCaja'])) {
    $DatosVentaActual = Datos_Venta($MiConexion, $_GET['idDetalleCaja']);
}

// Obtener el idTipoMovimiento actual
$idTipoMovimientoActual = $DatosVentaActual['idTipoMovimiento'] ?? null;
$esEntrada = false;
$esSalida = false;

if ($idTipoMovimientoActual) {
    $sql = "SELECT es_entrada, es_salida FROM tipo_movimiento WHERE idTipoMovimiento = $idTipoMovimientoActual";
    $rs = mysqli_query($MiConexion, $sql);
    if ($rs) {
        $row = mysqli_fetch_assoc($rs);
        $esEntrada = !empty($row['es_entrada']);
        $esSalida = !empty($row['es_salida']);
    }
}

// Listar métodos de pago y tipos de movimiento según corresponda
if ($esEntrada) {
    $TiposPagos = Listar_Tipos_Pagos_Entrada($MiConexion);
    $TiposMovimiento = [];
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_entrada = 1 AND idActivo = 1";
    $rs = mysqli_query($MiConexion, $sql);
    while ($row = mysqli_fetch_assoc($rs)) {
        $TiposMovimiento[] = $row;
    }
} elseif ($esSalida) {
    $TiposPagos = Listar_Tipos_Pagos_Salida($MiConexion);
    $TiposMovimiento = [];
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_salida = 1 AND idActivo = 1";
    $rs = mysqli_query($MiConexion, $sql);
    while ($row = mysqli_fetch_assoc($rs)) {
        $TiposMovimiento[] = $row;
    }
} else {
    $TiposPagos = [];
    $TiposMovimiento = [];
}

ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
      <h1>Ventas</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../core/index.php">Ventas</a></li>
          <li class="breadcrumb-item">Listado de Ventas</li>
          <li class="breadcrumb-item active">Modificar Venta</li>
        </ol>
      </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Modificar Venta</h5>

                <form method='post'>
                    <?php if (!empty($_SESSION['Mensaje'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                            <?php echo $_SESSION['Mensaje']; ?>
                        </div>
                    <?php } ?>

                    <!-- Campos ocultos para control -->
                    <input type='hidden' name="idDetalleCaja" value="<?php echo $DatosVentaActual['idDetalleCaja']; ?>"/>
                    <input type='hidden' name="idCaja" value="<?php echo $DatosVentaActual['idCaja']; ?>"/>
                    <input type='hidden' name="idUsuario" value="<?php echo $_SESSION['Usuario_Id']; ?>"/>
                    <input type='hidden' name="facturado_anterior" value="<?php echo $DatosVentaActual['facturado'] ?? 0; ?>"/>

                    <div class="row mb-3">
                        <label for="valorDinero" class="col-sm-2 col-form-label">Monto</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control text-center money-format" id="valorDinero" name="Monto" placeholder="$0,00" 
                                    value="<?php echo !empty($DatosVentaActual['Monto']) ? '$'.number_format($DatosVentaActual['Monto'], 2, ',', '.') : '$0,00'; ?>">
                                <input type="hidden" id="MontoReal" name="MontoReal" 
                                    value="<?php echo !empty($DatosVentaActual['Monto']) ? $DatosVentaActual['Monto'] : '0'; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="idTipoPago" class="col-sm-2 col-form-label">Tipo de Pago</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="idTipoPago" id="idTipoPago" required>
                                <option value="">Seleccione un tipo de pago</option>
                                <?php foreach ($TiposPagos as $tipoPago) { ?>
                                    <option value="<?php echo $tipoPago['idTipoPago']; ?>"
                                        <?php echo (!empty($DatosVentaActual['idTipoPago']) && $DatosVentaActual['idTipoPago'] == $tipoPago['idTipoPago']) ? 'selected' : ''; ?>>
                                        <?php echo $tipoPago['denominacion']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="idTipoMovimiento" class="col-sm-2 col-form-label">Tipo de Movimiento</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="idTipoMovimiento" id="idTipoMovimiento" required>
                                <option value="">Seleccione un tipo de movimiento</option>
                                <?php foreach ($TiposMovimiento as $tipoMov) { ?>
                                    <option value="<?php echo $tipoMov['idTipoMovimiento']; ?>"
                                        <?php echo (!empty($DatosVentaActual['idTipoMovimiento']) && $DatosVentaActual['idTipoMovimiento'] == $tipoMov['idTipoMovimiento']) ? 'selected' : ''; ?>>
                                        <?php echo $tipoMov['denominacion']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="observaciones" class="col-sm-2 col-form-label">Observaciones</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" name="Observaciones" id="observaciones" rows="3"><?php echo !empty($DatosVentaActual['observaciones']) ? htmlspecialchars($DatosVentaActual['observaciones']) : ''; ?></textarea>
                        </div>
                    </div>

                    <!-- Sección de Facturación -->
                    <div class="row mb-3">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-10">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="facturarCheckbox" name="facturado" 
                                    <?php echo (!empty($DatosVentaActual['facturado']) && $DatosVentaActual['facturado'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="facturarCheckbox">Facturar este movimiento</label>
                            </div>
                            
                            <div id="facturaFields" style="display: <?php echo (!empty($DatosVentaActual['facturado']) && $DatosVentaActual['facturado'] == 1) ? 'block' : 'none'; ?>;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipoFactura" class="form-label">Tipo de Factura</label>
                                        <select class="form-select" id="tipoFactura" name="idTipoFactura">
                                            <option value="">Seleccione un tipo</option>
                                            <?php foreach ($TiposFactura as $tipo) { ?>
                                                <option value="<?php echo $tipo['idTipoFactura']; ?>"
                                                    <?php echo (!empty($DatosVentaActual['idTipoFactura']) && $DatosVentaActual['idTipoFactura'] == $tipo['idTipoFactura']) ? 'selected' : ''; ?>>
                                                    <?php echo $tipo['denominacion']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numeroFactura" class="form-label">Número de Factura</label>
                                        <input type="text" class="form-control" id="numeroFactura" name="numeroFactura" 
                                            placeholder="Ingrese el número" 
                                            value="<?php echo $DatosVentaActual['numeroFactura'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarVenta">Modificar</button>
                        <a href="planilla_caja.php" class="btn btn-success btn-info" title="Listado">Volver al listado</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php
    $_SESSION['Mensaje'] = '';
    require('../shared/footer.inc.php');
?>

<script>
    // Función para formatear el dinero
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
        
        let formattedInteger = integerPart.length > 3 ? integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : integerPart;
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

    // Eventos del campo de dinero
    const moneyInput = document.getElementById('valorDinero');
    moneyInput.addEventListener('input', function() { formatMoney(this); });
    moneyInput.addEventListener('focus', function() { this.value = this.value.replace('$', ''); });
    moneyInput.addEventListener('blur', function() {
        if (!this.value.includes('$')) this.value = '$' + this.value;
        formatMoney(this);
        if (this.value === '$' || this.value === '') {
            this.value = '$0,00';
            document.getElementById('MontoReal').value = '0';
        }
    });

    // Mostrar/ocultar campos de facturación
    document.getElementById('facturarCheckbox').addEventListener('change', function() {
        const facturaFields = document.getElementById('facturaFields');
        facturaFields.style.display = this.checked ? 'block' : 'none';
        
        // Hacer requeridos los campos si está marcado
        document.getElementById('tipoFactura').required = this.checked;
        document.getElementById('numeroFactura').required = this.checked;
    });

    // Validación al enviar el formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        if (parseFloat(document.getElementById('MontoReal').value) <= 0) {
            e.preventDefault();
            alert('Por favor ingrese un monto válido mayor a cero');
            moneyInput.focus();
        }
        
        if (document.getElementById('facturarCheckbox').checked) {
            const tipoFactura = document.getElementById('tipoFactura').value;
            const numeroFactura = document.getElementById('numeroFactura').value.trim();
            
            if (!tipoFactura) {
                e.preventDefault();
                alert('Por favor seleccione un tipo de factura');
                return;
            }
            
            if (!numeroFactura) {
                e.preventDefault();
                alert('Por favor ingrese un número de factura');
                document.getElementById('numeroFactura').focus();
                return;
            }
        }
    });

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        formatMoney(moneyInput);
    });
</script>