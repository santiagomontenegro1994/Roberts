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
require_once '../funciones/imprenta.php'; // Aquí ya está Obtener_Tipo_Movimiento()

$MiConexion = ConexionBD();

// Obtener datos actuales de la venta
$DatosVentaActual = array();
$TiposFactura = Listar_Tipos_Factura($MiConexion);

if (!empty($_POST['BotonModificarVenta'])) {
    Validar_Modificar_Venta(); 

    if (empty($_SESSION['Mensaje'])) {
        if (Modificar_Venta($MiConexion) != false) {
            $_SESSION['Mensaje'] = "El movimiento se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header('Location: planilla_caja.php');
            exit;
        }
    } else {
        $_SESSION['Estilo'] = 'warning';
        $DatosVentaActual = $_POST;
    }
} elseif (!empty($_GET['idDetalleCaja'])) {
    $DatosVentaActual = Datos_Venta($MiConexion, $_GET['idDetalleCaja']);
}

// Obtener tipo de movimiento actual
$idTipoMovimientoActual = $DatosVentaActual['idTipoMovimiento'] ?? null;
$esEntrada = false;
$esSalida = false;
$denominacionMovimiento = "";

if ($idTipoMovimientoActual) {
    $infoTipoMov = Obtener_Tipo_Movimiento($MiConexion, $idTipoMovimientoActual);
    if ($infoTipoMov) {
        $esEntrada = $infoTipoMov['es_entrada'];
        $esSalida = $infoTipoMov['es_salida'];
        $denominacionMovimiento = strtolower($infoTipoMov['denominacion']);
    }
}

// Listar tipos de pagos y movimientos
if ($esEntrada) {
    $TiposPagos = Listar_Tipos_Pagos_Entrada($MiConexion);
    $TiposMovimiento = [];
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_entrada = 1 AND idActivo = 1";
    $rs = mysqli_query($MiConexion, $sql);
    while ($row = mysqli_fetch_assoc($rs)) $TiposMovimiento[] = $row;
} elseif ($esSalida) {
    $TiposPagos = Listar_Tipos_Pagos_Salida($MiConexion);
    $TiposMovimiento = [];
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_salida = 1 AND idActivo = 1";
    $rs = mysqli_query($MiConexion, $sql);
    while ($row = mysqli_fetch_assoc($rs)) $TiposMovimiento[] = $row;

    // Listados auxiliares para retiros
    $Usuarios = [];
    $sqlUsuarios = "SELECT idUsuario, nombre FROM usuarios WHERE idActivo = 1 ORDER BY nombre";
    $rsUsuarios = mysqli_query($MiConexion, $sqlUsuarios);
    while ($u = mysqli_fetch_assoc($rsUsuarios)) $Usuarios[] = $u;

    $Proveedores = [];
    $sqlProv = "SELECT idProveedor, nombre FROM proveedores WHERE idActivo = 1 ORDER BY nombre";
    $rsProv = mysqli_query($MiConexion, $sqlProv);
    while ($p = mysqli_fetch_assoc($rsProv)) $Proveedores[] = $p;
} else {
    $TiposPagos = [];
    $TiposMovimiento = [];
}

ob_end_flush();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?php echo $esSalida ? "Modificar Retiro" : "Modificar Venta"; ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Caja</a></li>
                <li class="breadcrumb-item"><a href="planilla_caja.php">Planilla Caja</a></li>
                <li class="breadcrumb-item active">Modificar</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo $esSalida ? "Modificar Retiro" : "Modificar Venta"; ?></h5>

                <form method='post'>
                    <?php if (!empty($_SESSION['Mensaje'])) { ?>
                        <div class="alert alert-<?php echo $_SESSION['Estilo']; ?> alert-dismissable">
                            <?php echo $_SESSION['Mensaje']; ?>
                        </div>
                    <?php } ?>

                    <!-- Campos ocultos -->
                    <input type='hidden' name="idDetalleCaja" value="<?php echo $DatosVentaActual['idDetalleCaja']; ?>"/>
                    <input type='hidden' name="idCaja" value="<?php echo $DatosVentaActual['idCaja']; ?>"/>
                    <input type='hidden' name="idUsuario" value="<?php echo $_SESSION['Usuario_Id']; ?>"/>
                    <input type='hidden' name="facturado_anterior" value="<?php echo $DatosVentaActual['facturado'] ?? 0; ?>"/>

                    <!-- Monto -->
                    <div class="row mb-3">
                        <label for="valorDinero" class="col-sm-2 col-form-label">Monto</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control text-center money-format" id="valorDinero" name="Monto" 
                                    value="<?php echo !empty($DatosVentaActual['Monto']) ? '$'.number_format($DatosVentaActual['Monto'], 2, ',', '.') : '$0,00'; ?>">
                                <input type="hidden" id="MontoReal" name="MontoReal" 
                                    value="<?php echo !empty($DatosVentaActual['Monto']) ? $DatosVentaActual['Monto'] : '0'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Tipo Pago -->
                    <div class="row mb-3">
                        <label for="idTipoPago" class="col-sm-2 col-form-label">Tipo de Pago</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="idTipoPago" id="idTipoPago" required>
                                <option value="">Seleccione un tipo de pago</option>
                                <?php foreach ($TiposPagos as $tipoPago) { ?>
                                    <option value="<?php echo $tipoPago['idTipoPago']; ?>"
                                        <?php echo ($DatosVentaActual['idTipoPago'] == $tipoPago['idTipoPago']) ? 'selected' : ''; ?>>
                                        <?php echo $tipoPago['denominacion']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tipo Movimiento -->
                    <div class="row mb-3">
                        <label for="idTipoMovimiento" class="col-sm-2 col-form-label">Tipo de Movimiento</label>
                        <div class="col-sm-10">
                            <select class="form-control" name="idTipoMovimiento" id="idTipoMovimiento" required>
                                <option value="">Seleccione un tipo de movimiento</option>
                                <?php foreach ($TiposMovimiento as $tipoMov) { ?>
                                    <option value="<?php echo $tipoMov['idTipoMovimiento']; ?>"
                                        <?php echo ($DatosVentaActual['idTipoMovimiento'] == $tipoMov['idTipoMovimiento']) ? 'selected' : ''; ?>>
                                        <?php echo $tipoMov['denominacion']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- 🔹 Sección dinámica para retiros -->
                    <?php if ($esSalida) { ?>
                        <!-- Usuarios -->
                        <div class="row mb-3 align-items-center retiro-section" id="retiroUsuarios" style="display: <?php echo (strpos($denominacionMovimiento,'sueldo')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2">
                                <label class="col-form-label">Usuario</label>
                            </div>
                            <div class="col-sm-10">
                                <select name="usuarioSueldo" class="form-control">
                                    <option value="">Seleccione un usuario</option>
                                    <?php foreach ($Usuarios as $u) { ?>
                                        <option value="<?php echo $u['idUsuario']; ?>"
                                            <?php echo (!empty($DatosVentaActual['idUsuarioSueldo']) && $DatosVentaActual['idUsuarioSueldo']==$u['idUsuario'])?'selected':''; ?>>
                                            <?php echo $u['nombre']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Proveedores -->
                        <div class="row mb-3 align-items-center retiro-section" id="retiroProveedores" style="display: <?php echo (strpos($denominacionMovimiento,'proveedor')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2">
                                <label class="col-form-label">Proveedor</label>
                            </div>
                            <div class="col-sm-10">
                                <select name="proveedor" class="form-control">
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($Proveedores as $p) { 
                                        $selected = (isset($DatosVentaActual['idProveedor']) && (int)$DatosVentaActual['idProveedor'] == $p['idProveedor']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $p['idProveedor']; ?>" <?php echo $selected; ?>>
                                            <?php echo $p['nombre']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Servicios -->
                        <div class="row mb-3 align-items-center retiro-section" id="retiroServicios" style="display: <?php echo (strpos($denominacionMovimiento,'servicio')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2">
                                <label class="col-form-label">Servicio</label>
                            </div>
                            <div class="col-sm-10">
                                <select name="servicio" class="form-control">
                                    <option value="">Seleccione un servicio</option>
                                </select>
                            </div>
                        </div>

                        <!-- Insumos -->
                        <div class="row mb-3 align-items-center retiro-section" id="retiroInsumos" style="display: <?php echo (strpos($denominacionMovimiento,'insumo')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2">
                                <label class="col-form-label">Insumo</label>
                            </div>
                            <div class="col-sm-10">
                                <select name="insumo" class="form-control">
                                    <option value="">Seleccione un insumo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Varios -->
                        <div class="row mb-3 align-items-center retiro-section" id="retiroVarios" style="display: <?php echo (strpos($denominacionMovimiento,'varios')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2">
                                <label class="col-form-label">Categoría Varios</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="text" name="categoriaVarios" class="form-control" 
                                    value="<?php echo $DatosVentaActual['categoria'] ?? ''; ?>" placeholder="Descripción">
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Observaciones -->
                    <div class="row mb-3">
                        <label for="observaciones" class="col-sm-2 col-form-label">Observaciones</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" name="Observaciones" id="observaciones" rows="3"><?php echo htmlspecialchars($DatosVentaActual['observaciones'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Facturación -->
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
                                                    <?php echo ($DatosVentaActual['idTipoFactura'] == $tipo['idTipoFactura']) ? 'selected' : ''; ?>>
                                                    <?php echo $tipo['denominacion']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numeroFactura" class="form-label">Número de Factura</label>
                                        <input type="text" class="form-control" id="numeroFactura" name="numeroFactura" 
                                            value="<?php echo $DatosVentaActual['numeroFactura'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary" value="Modificar" name="BotonModificarVenta">Modificar</button>
                        <a href="planilla_caja.php" class="btn btn-success btn-info">Volver al listado</a>
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
    // Formateo dinero
    function formatMoney(input) {
        let rawValue = input.value.replace(/[^\d,]/g, '');
        let commaPos = rawValue.indexOf(',');
        if (commaPos !== -1) rawValue = rawValue.substring(0, commaPos+1) + rawValue.substring(commaPos+1).replace(/,/g, '');
        let parts = rawValue.split(',');
        let integerPart = parts[0].replace(/\D/g, '') || '0';
        let decimalPart = parts[1] || '00';
        decimalPart = decimalPart.padEnd(2,'0').substring(0,2);
        let intFormatted = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        input.value = '$' + intFormatted + ',' + decimalPart;
        document.getElementById('MontoReal').value = integerPart + '.' + decimalPart;
    }

    document.querySelector('.money-format').addEventListener('input', function(){ formatMoney(this); });

    // Facturación
    document.getElementById('facturarCheckbox').addEventListener('change', function() {
        document.getElementById('facturaFields').style.display = this.checked ? 'block' : 'none';
    });

    // Mostrar secciones dinámicas de retiro según denominación
    const tiposMovimientoData = <?php
        $tiposMovimientoAll = [];
        $sqlTM = "SELECT idTipoMovimiento, denominacion, es_entrada, es_salida FROM tipo_movimiento WHERE idActivo = 1";
        $rsTM = mysqli_query($MiConexion, $sqlTM);
        while($row = mysqli_fetch_assoc($rsTM)) {
            $tiposMovimientoAll[$row['idTipoMovimiento']] = [
                'denominacion' => strtolower($row['denominacion']),
                'es_entrada' => (bool)$row['es_entrada'],
                'es_salida' => (bool)$row['es_salida']
            ];
        }
        echo json_encode($tiposMovimientoAll);
    ?>;

    document.getElementById('idTipoMovimiento').addEventListener('change', function() {
        const movimientoId = this.value;
        document.querySelectorAll('.retiro-section').forEach(section => section.style.display = 'none');

        if (tiposMovimientoData[movimientoId] && tiposMovimientoData[movimientoId].es_salida) {
            const denominacion = tiposMovimientoData[movimientoId].denominacion;
            if (denominacion.includes('sueldo')) document.getElementById('retiroUsuarios').style.display = 'flex';
            else if (denominacion.includes('proveedor')) document.getElementById('retiroProveedores').style.display = 'flex';
            else if (denominacion.includes('servicio')) document.getElementById('retiroServicios').style.display = 'flex';
            else if (denominacion.includes('insumo') || denominacion.includes('material')) document.getElementById('retiroInsumos').style.display = 'flex';
            else document.getElementById('retiroVarios').style.display = 'flex';
        }
    });
</script>
