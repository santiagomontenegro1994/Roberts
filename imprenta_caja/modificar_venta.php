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
    Validar_Modificar_Venta(); 

    if (empty($_SESSION['Mensaje'])) {
        $idDetalleCaja = (int)$_POST['idDetalleCaja'];
        $idUsuario = isset($_SESSION['Usuario_Id']) ? (int)$_SESSION['Usuario_Id'] : 0;

        // Revertir el stock original de la venta antes de aplicar la modificación
        AnularMovimientosStockVenta($MiConexion, $idDetalleCaja, $idUsuario);

        if (Modificar_Venta($MiConexion) != false) {
            $_SESSION['Mensaje'] = "El movimiento se ha modificado correctamente!";
            $_SESSION['Estilo'] = 'success';
            header("Location: " . $_SERVER['PHP_SELF'] . "?idDetalleCaja=" . $_POST['idDetalleCaja'] . "&ticket_mod=" . $_POST['idDetalleCaja']);
            exit;
        }
    } else {
        $_SESSION['Estilo'] = 'warning';
        $DatosVentaActual = $_POST;
    }
} elseif (!empty($_GET['idDetalleCaja'])) {
    $DatosVentaActual = Datos_Venta($MiConexion, $_GET['idDetalleCaja']);
}

// Cargar catálogo de productos para la memoria JS
$productos_memoria = [];
$sql_prod = "SELECT id, titulo, precio, stock, stock_infinito, imagen FROM productos WHERE idActivo = 1 ORDER BY titulo ASC";
$res_prod = mysqli_query($MiConexion, $sql_prod);

if ($res_prod) {
    while ($p = mysqli_fetch_assoc($res_prod)) {
        $idP = (int)$p['id'];
        $variantes = [];
        $res_var = mysqli_query($MiConexion, "SELECT id, color_nombre, color_hex, stock, nombre_imagen FROM productos_imagenes WHERE id_producto = $idP");
        if ($res_var) {
            while ($v = mysqli_fetch_assoc($res_var)) {
                $variantes[] = $v;
            }
        }
        $p['variantes'] = $variantes;
        $productos_memoria[] = $p;
    }
}

// Cargar insumos previamente guardados en esta venta específica
$insumos_actuales = [];
if (!empty($DatosVentaActual['idDetalleCaja'])) {
    $idDet = (int)$DatosVentaActual['idDetalleCaja'];
    $sql_ins_act = "SELECT m.idProducto, m.idVariante, ABS(m.cantidad) as cantidad, p.titulo, p.precio, v.color_nombre
                    FROM movimientos_stock m
                    JOIN productos p ON m.idProducto = p.id
                    LEFT JOIN productos_imagenes v ON m.idVariante = v.id
                    WHERE m.descripcion LIKE 'Venta en Caja #$idDet:%' 
                      AND m.tipo_movimiento = 'VENTA'";
    $res_ins_act = mysqli_query($MiConexion, $sql_ins_act);
    if ($res_ins_act) {
        while ($row_i = mysqli_fetch_assoc($res_ins_act)) {
            $idP = (int)$row_i['idProducto'];
            $idV = (int)($row_i['idVariante'] ?? 0);
            $nomVar = !empty($row_i['color_nombre']) ? " ({$row_i['color_nombre']})" : "";
            $insumos_actuales[] = [
                'clave' => "{$idP}_{$idV}",
                'idProducto' => $idP,
                'idVariante' => $idV,
                'titulo' => $row_i['titulo'] . $nomVar,
                'precioUnitario' => (float)$row_i['precio'],
                'cantidad' => (int)$row_i['cantidad']
            ];
        }
    }
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

    $Usuarios = [];
    $sqlUsuarios = "SELECT idUsuario, nombre FROM usuarios WHERE idActivo = 1 ORDER BY nombre";
    $rsUsuarios = mysqli_query($MiConexion, $sqlUsuarios);
    while ($u = mysqli_fetch_assoc($rsUsuarios)) $Usuarios[] = $u;

    $Proveedores = [];
    $sqlProv = "SELECT idProveedor, nombre FROM proveedores WHERE idActivo = 1 ORDER BY nombre";
    $rsProv = mysqli_query($MiConexion, $sqlProv);
    while ($p = mysqli_fetch_assoc($rsProv)) $Proveedores[] = $p;

    $Servicios = [];
    $sqlServicios = "SELECT idServicio, denominacion FROM servicios ORDER BY denominacion";
    $resServicios = mysqli_query($MiConexion, $sqlServicios);
    if ($resServicios) { while ($s = mysqli_fetch_assoc($resServicios)) $Servicios[] = $s; }

    $ProveedoresInsumos = [];
    $sqlProveedoresInsumos = "SELECT idProveedorInsumo, nombre FROM proveedores_insumos WHERE idActivo = 1 ORDER BY nombre";
    $resProveedoresInsumos = mysqli_query($MiConexion, $sqlProveedoresInsumos);
    if ($resProveedoresInsumos) { while ($pi = mysqli_fetch_assoc($resProveedoresInsumos)) $ProveedoresInsumos[] = $pi; }

    $Insumos = [];
    $sqlInsumos = "SELECT idInsumo, denominacion FROM insumos ORDER BY denominacion";
    $resInsumos = mysqli_query($MiConexion, $sqlInsumos);
    if ($resInsumos) { while ($i = mysqli_fetch_assoc($resInsumos)) $Insumos[] = $i; }
} else {
    $TiposPagos = [];
    $TiposMovimiento = [];
}

ob_end_flush();
?>

<style>
    .modal-insumos-body { max-height: 65vh; overflow-y: auto; }
    .item-insumo-card { transition: background 0.15s ease-in-out; }
    .item-insumo-card:hover { background-color: #f8f9fa; }
    .stock-badge-negativo { background-color: #dc3545; color: white; }
    .stock-badge-cero { background-color: #ffc107; color: #212529; }
</style>

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

                <form method='post' id='formVenta'>
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
                    <input type="hidden" name="insumos_json" id="insumosJsonInput" value="">

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

                    <!-- Panel Resumen de Insumos -->
                    <div id="panelResumenInsumos" class="row mb-3" style="display: none;">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-10">
                            <div class="p-3 bg-light border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-primary"><i class="bi bi-box-seam me-1"></i> Insumos Seleccionados:</strong>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalInsumos()">
                                        <i class="bi bi-pencil"></i> Modificar Insumos
                                    </button>
                                </div>
                                <ul id="listaResumenInsumos" class="list-group list-group-flush small"></ul>
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

                    <!-- Sección dinámica para retiros -->
                    <?php if ($esSalida) { ?>
                        <div class="row mb-3 align-items-center retiro-section" id="retiroUsuarios" style="display: <?php echo (strpos($denominacionMovimiento,'sueldo')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2"><label class="col-form-label">Usuario</label></div>
                            <div class="col-sm-10">
                                <select name="usuarioSueldo" class="form-control">
                                    <option value="">Seleccione un usuario</option>
                                    <?php foreach ($Usuarios as $u) { ?>
                                        <option value="<?php echo $u['idUsuario']; ?>" <?php echo (!empty($DatosVentaActual['idUsuarioSueldo']) && $DatosVentaActual['idUsuarioSueldo']==$u['idUsuario'])?'selected':''; ?>>
                                            <?php echo $u['nombre']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center retiro-section" id="retiroProveedores" style="display: <?php echo (strpos($denominacionMovimiento,'proveedor')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2"><label class="col-form-label">Proveedor</label></div>
                            <div class="col-sm-10">
                                <select name="proveedor" class="form-control">
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach ($Proveedores as $p) { ?>
                                        <option value="<?php echo $p['idProveedor']; ?>" <?php echo (isset($DatosVentaActual['idProveedor']) && (int)$DatosVentaActual['idProveedor'] == $p['idProveedor']) ? 'selected' : ''; ?>>
                                            <?php echo $p['nombre']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center retiro-section" id="retiroServicios" style="display: <?php echo (strpos($denominacionMovimiento,'servicio')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2"><label class="col-form-label">Servicio</label></div>
                            <div class="col-sm-10">
                                <select name="servicio" class="form-control">
                                    <option value="">Seleccione un servicio</option>
                                    <?php foreach ($Servicios as $s) { ?>
                                        <option value="<?php echo $s['idServicio']; ?>" <?php echo (!empty($DatosVentaActual['idServicio']) && $DatosVentaActual['idServicio'] == $s['idServicio']) ? 'selected' : ''; ?>><?php echo $s['denominacion']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center retiro-section" id="retiroInsumos" style="display: <?php echo (strpos($denominacionMovimiento,'insumo')!==false)?'flex':'none'; ?>;">
                            <div class="col-sm-2">
                                <label class="col-form-label mb-2">Prov. de Insumo</label>
                                <label class="col-form-label mt-2">Insumo</label>
                            </div>
                            <div class="col-sm-10">
                                <select name="proveedorInsumo" class="form-control mb-3">
                                    <option value="">Seleccione un proveedor de insumo</option>
                                    <?php foreach ($ProveedoresInsumos as $pi) { ?>
                                        <option value="<?php echo $pi['idProveedorInsumo']; ?>" <?php echo (!empty($DatosVentaActual['idProveedorInsumo']) && $DatosVentaActual['idProveedorInsumo'] == $pi['idProveedorInsumo']) ? 'selected' : ''; ?>><?php echo $pi['nombre']; ?></option>
                                    <?php } ?>
                                </select>

                                <select name="insumo" class="form-control">
                                    <option value="">Seleccione un insumo</option>
                                    <?php foreach ($Insumos as $i) { ?>
                                        <option value="<?php echo $i['idInsumo']; ?>" <?php echo (!empty($DatosVentaActual['idInsumo']) && $DatosVentaActual['idInsumo'] == $i['idInsumo']) ? 'selected' : ''; ?>><?php echo $i['denominacion']; ?></option>
                                    <?php } ?>
                                </select>
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

<!-- MODAL DE BÚSQUEDA Y SELECCIÓN DE INSUMOS -->
<div class="modal fade" id="modalInsumos" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bi bi-search me-2"></i> Modificar Insumos de Inventario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-3">
        <div class="mb-3">
            <input type="text" id="inputBuscarInsumo" class="form-control form-control-lg border-primary shadow-sm" placeholder="🔍 Escribí para buscar por nombre...">
        </div>

        <div class="row">
            <div class="col-md-7 border-end modal-insumos-body">
                <small class="text-muted fw-bold d-block mb-2">Catálogo Disponible:</small>
                <div id="contenedorCatalogoInsumos" class="d-flex flex-column gap-2"></div>
            </div>

            <div class="col-md-5 modal-insumos-body">
                <small class="text-muted fw-bold d-block mb-2">Lista a Vender:</small>
                <div id="contenedorCarritoInsumos">
                    <p class="text-muted small text-center my-4">No hay insumos agregados aún.</p>
                </div>
                <div class="border-top pt-2 mt-2 text-end" id="boxTotalCarrito" style="display:none;">
                    <span class="fw-bold">Total Insumos: </span>
                    <span class="fs-5 fw-bold text-success" id="textoTotalModal">$0</span>
                </div>
            </div>
        </div>
      </div>

      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-bold" onclick="confirmarInsumosModal()"><i class="bi bi-check-circle me-1"></i> Confirmar e Insertar Monto</button>
      </div>
    </div>
  </div>
</div>

<?php
    $_SESSION['Mensaje'] = '';
    require('../shared/footer.inc.php');
?>

<script>
    // PRECARGA DE CATÁLOGO E INSUMOS ACTUALES EN JS
    const CATALOGO_PRODUCTOS = <?php echo json_encode($productos_memoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || [];
    let CARRITO_INSUMOS = <?php echo json_encode($insumos_actuales, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || [];

    const DOMINIO_IMG = "https://robertsgrafica.com/img/";

    function abrirModalInsumos() {
        const modalEl = document.getElementById('modalInsumos');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        setTimeout(() => { document.getElementById('inputBuscarInsumo').focus(); }, 300);
        renderizarCatalogo('');
        renderizarCarritoModal();
    }

    function renderizarCatalogo(filtro) {
        const contenedor = document.getElementById('contenedorCatalogoInsumos');
        contenedor.innerHTML = '';

        const term = filtro.toLowerCase().trim();
        const filtrados = CATALOGO_PRODUCTOS.filter(p => p.titulo.toLowerCase().includes(term));

        if (filtrados.length === 0) {
            contenedor.innerHTML = '<p class="text-muted small my-3 text-center">No se encontraron productos.</p>';
            return;
        }

        filtrados.forEach(p => {
            const card = document.createElement('div');
            card.className = 'p-2 border rounded item-insumo-card bg-white shadow-sm';

            const stockNum = parseInt(p.stock);
            let stockBadge = '';
            if (p.stock_infinito == 1) {
                stockBadge = '<span class="badge bg-info text-dark">A medida</span>';
            } else if (stockNum <= 0) {
                stockBadge = `<span class="badge stock-badge-negativo">Stock: ${stockNum} (Sin Stock)</span>`;
            } else if (stockNum <= 5) {
                stockBadge = `<span class="badge stock-badge-cero">Stock: ${stockNum}</span>`;
            } else {
                stockBadge = `<span class="badge bg-light text-dark border">Stock: ${stockNum}</span>`;
            }

            let selectorVariantesHTML = '';
            if (p.variantes && p.variantes.length > 0) {
                selectorVariantesHTML = `<select class="form-select form-select-sm my-1" id="var_select_${p.id}">
                    <option value="0">Color Principal (${p.stock} u.)</option>`;
                p.variantes.forEach(v => {
                    selectorVariantesHTML += `<option value="${v.id}" data-nombre="${v.color_nombre}">${v.color_nombre} (${v.stock} u.)</option>`;
                });
                selectorVariantesHTML += `</select>`;
            }

            const imgRuta = p.imagen ? (p.imagen.includes('productos/') ? p.imagen : 'productos/' + p.imagen) : 'productos/sin-imagen.jpg';

            card.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <img src="${DOMINIO_IMG}${imgRuta}" style="width:40px; height:40px; object-fit:cover;" class="rounded border" onerror="this.src='../img/productos/sin-imagen.jpg'">
                    <div class="flex-grow-1 overflow-hidden">
                        <strong class="d-block text-truncate small text-dark">${p.titulo}</strong>
                        <div>${stockBadge} <span class="fw-bold text-success small ms-1">$${p.precio}</span></div>
                        ${selectorVariantesHTML}
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <input type="number" id="cant_input_${p.id}" class="form-control form-control-sm text-center" value="1" min="1" style="width: 50px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick="agregarAlCarritoModal(${p.id})">+</button>
                    </div>
                </div>
            `;
            contenedor.appendChild(card);
        });
    }

    document.getElementById('inputBuscarInsumo').addEventListener('keyup', function() {
        renderizarCatalogo(this.value);
    });

    function agregarAlCarritoModal(idProd) {
        const prod = CATALOGO_PRODUCTOS.find(p => p.id == idProd);
        if (!prod) return;

        const cantInput = document.getElementById(`cant_input_${idProd}`);
        const cantidad = parseInt(cantInput.value) || 1;

        let idVariante = 0;
        let nombreVariante = '';
        const selectVar = document.getElementById(`var_select_${idProd}`);
        if (selectVar && selectVar.value != "0") {
            idVariante = parseInt(selectVar.value);
            const selectedOpt = selectVar.options[selectVar.selectedIndex];
            nombreVariante = selectedOpt.getAttribute('data-nombre');
        }

        const claveUnica = `${idProd}_${idVariante}`;
        const itemExistente = CARRITO_INSUMOS.find(i => i.clave === claveUnica);

        if (itemExistente) {
            itemExistente.cantidad += cantidad;
        } else {
            CARRITO_INSUMOS.push({
                clave: claveUnica,
                idProducto: prod.id,
                idVariante: idVariante,
                titulo: prod.titulo + (nombreVariante ? ` (${nombreVariante})` : ''),
                precioUnitario: parseFloat(prod.precio),
                cantidad: cantidad
            });
        }

        cantInput.value = 1;
        renderizarCarritoModal();
    }

    function quitarDelCarritoModal(clave) {
        CARRITO_INSUMOS = CARRITO_INSUMOS.filter(i => i.clave !== clave);
        renderizarCarritoModal();
    }

    function renderizarCarritoModal() {
        const contenedor = document.getElementById('contenedorCarritoInsumos');
        const totalBox = document.getElementById('boxTotalCarrito');
        contenedor.innerHTML = '';

        if (CARRITO_INSUMOS.length === 0) {
            contenedor.innerHTML = '<p class="text-muted small text-center my-4">No hay insumos agregados aún.</p>';
            totalBox.style.display = 'none';
            return;
        }

        let totalSumado = 0;
        const listGroup = document.createElement('ul');
        listGroup.className = 'list-group list-group-flush border rounded';

        CARRITO_INSUMOS.forEach(item => {
            const subtotal = item.precioUnitario * item.cantidad;
            totalSumado += subtotal;

            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center p-2 small';
            li.innerHTML = `
                <div>
                    <strong class="d-block text-dark">${item.titulo}</strong>
                    <span class="text-muted">${item.cantidad} x $${item.precioUnitario}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-success">$${subtotal.toLocaleString('es-AR')}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="quitarDelCarritoModal('${item.clave}')">×</button>
                </div>
            `;
            listGroup.appendChild(li);
        });

        contenedor.appendChild(listGroup);
        totalBox.style.display = 'block';
        document.getElementById('textoTotalModal').innerText = '$' + totalSumado.toLocaleString('es-AR');
    }

    function confirmarInsumosModal() {
        const modalElement = document.getElementById('modalInsumos');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();

        const inputHidden = document.getElementById('insumosJsonInput');
        const panelResumen = document.getElementById('panelResumenInsumos');
        const listaResumen = document.getElementById('listaResumenInsumos');

        if (CARRITO_INSUMOS.length === 0) {
            inputHidden.value = '';
            panelResumen.style.display = 'none';
            return;
        }

        inputHidden.value = JSON.stringify(CARRITO_INSUMOS);
        listaResumen.innerHTML = '';
        let totalGeneral = 0;

        CARRITO_INSUMOS.forEach(item => {
            const subtotal = item.precioUnitario * item.cantidad;
            totalGeneral += subtotal;

            const li = document.createElement('li');
            li.className = 'list-group-item bg-transparent d-flex justify-content-between align-items-center py-1 px-0';
            li.innerHTML = `
                <span><b>${item.cantidad}x</b> ${item.titulo}</span>
                <span class="fw-bold">$${subtotal.toLocaleString('es-AR')}</span>
            `;
            listaResumen.appendChild(li);
        });

        panelResumen.style.display = 'flex';

        if (totalGeneral > 0) {
            document.getElementById('MontoReal').value = totalGeneral.toString();
            document.getElementById('valorDinero').value = '$' + totalGeneral.toLocaleString('es-AR');
            formatMoney(document.getElementById('valorDinero'));
        }
    }

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

    const tipoMovimientoInput = document.getElementById('idTipoMovimiento');

    function actualizarSecciones() {
        const movimientoId = tipoMovimientoInput.value;
        document.querySelectorAll('.retiro-section').forEach(section => section.style.display = 'none');

        if (tiposMovimientoData[movimientoId] && tiposMovimientoData[movimientoId].es_salida) {
            const denominacion = tiposMovimientoData[movimientoId].denominacion;
            if (denominacion.includes('sueldo')) document.getElementById('retiroUsuarios').style.display = 'flex';
            else if (denominacion.includes('proveedor')) document.getElementById('retiroProveedores').style.display = 'flex';
            else if (denominacion.includes('servicio')) document.getElementById('retiroServicios').style.display = 'flex';
            else if (denominacion.includes('insumo') || denominacion.includes('material')) document.getElementById('retiroInsumos').style.display = 'flex';
        }
    }

    tipoMovimientoInput.addEventListener('change', actualizarSecciones);

    document.querySelectorAll('.tipo-movimiento').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.tipo-movimiento').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            this.classList.remove('btn-secondary');
            this.classList.add('btn-primary');
            tipoMovimientoInput.value = this.getAttribute('data-id');
            tipoMovimientoInput.dispatchEvent(new Event('change'));
        });
    });

    document.querySelectorAll('.metodo-pago').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.metodo-pago').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            this.classList.remove('btn-secondary');
            this.classList.add('btn-primary');
            document.getElementById('idTipoPago').value = this.getAttribute('data-id');
        });
    });

    const facturarCheckbox = document.getElementById('facturarCheckbox');
    if (facturarCheckbox) {
        facturarCheckbox.addEventListener('change', function() {
            const facturaFields = document.getElementById('facturaFields');
            if (facturaFields) {
                facturaFields.style.display = this.checked ? 'block' : 'none';
            }
            const numFactura = document.getElementById('numeroFactura');
            if (numFactura) {
                numFactura.required = this.checked;
            }
        });
    }

    const formVenta = document.getElementById('formVenta');
    if (formVenta) {
        formVenta.addEventListener('submit', function(e) {
            if (parseFloat(document.getElementById('MontoReal').value) <= 0) {
                e.preventDefault();
                alert('Por favor ingrese un monto válido mayor a cero');
                moneyInput.focus();
            }

            if (facturarCheckbox && facturarCheckbox.checked && document.getElementById('numeroFactura').value.trim() === '') {
                e.preventDefault();
                alert('Por favor ingrese un número de factura');
                document.getElementById('numeroFactura').focus();
            }
        });
    }

    // Al cargar la página, si ya tenía insumos precargados los mostramos automáticamente
    document.addEventListener('DOMContentLoaded', function() { 
        formatMoney(moneyInput); 
        if (CARRITO_INSUMOS && CARRITO_INSUMOS.length > 0) {
            confirmarInsumosModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const ticketId = urlParams.get('ticket_mod');

        if (ticketId) {
            if (localStorage.getItem('imprimirTicketVenta') === 'true') {
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                const esRetiro = <?php echo $esSalida ? 'true' : 'false'; ?>;
                const archivoTicket = esRetiro ? 'ticket_retiro.php' : 'ticket_venta.php';
                
                iframe.src = `${archivoTicket}?id=${ticketId}`;
                iframe.onload = function() {
                    setTimeout(() => {
                        window.location.href = 'planilla_caja.php';
                    }, 1000);
                };
                document.body.appendChild(iframe);
            } else {
                window.location.href = 'planilla_caja.php';
            }
        }
    });
</script>