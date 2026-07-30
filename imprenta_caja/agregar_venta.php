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
$TiposFactura = Listar_Tipos_Factura($MiConexion);

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
            
            $rsUltimo = mysqli_query($MiConexion, "SELECT MAX(idDetalleCaja) as id FROM detalle_caja WHERE idCaja = '{$_SESSION['Id_Caja']}'");
            $rowUltimo = mysqli_fetch_assoc($rsUltimo);
            $idNuevo = $rowUltimo['id'];

            header("Location: " . $_SERVER['PHP_SELF'] . "?ticket=" . $idNuevo);
            exit;
        } else {
            $_SESSION['Mensaje'] = 'Error al registrar el detalle de venta.';
            $_SESSION['Estilo'] = 'danger';
        }
    }
}

// Cargar catálogo de productos ANTES de cerrar la conexión
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

$MiConexion->close();
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
                    <!-- Campo Oculto para insumos -->
                    <input type="hidden" name="insumos_json" id="insumosJsonInput" value="">

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
                            <button type="button" class="btn btn-secondary mx-2 my-2 tipo-movimiento" 
                                    data-id="<?php echo $tipo['idTipoMovimiento']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($tipo['denominacion']); ?>">
                                <?php echo $tipo['denominacion']; ?>
                            </button>
                        <?php } ?>
                        <input type="hidden" name="idTipoMovimiento" id="idTipoMovimiento">
                    </div>

                    <!-- Panel Resumen de Insumos -->
                    <div id="panelResumenInsumos" class="my-3 p-3 bg-light border rounded" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-primary"><i class="bi bi-box-seam me-1"></i> Insumos Seleccionados:</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalInsumos()">
                                <i class="bi bi-pencil"></i> Modificar Insumos
                            </button>
                        </div>
                        <ul id="listaResumenInsumos" class="list-group list-group-flush small"></ul>
                    </div>

                    <div class="text-center mt-4">
                        <label for="valorDinero" class="form-label fw-bold">Ingrese el Valor de Dinero</label>
                        <div class="input-group w-50 mx-auto">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control text-center money-format" id="valorDinero" name="Monto" placeholder="$0,00" value="$0,00">
                            <input type="hidden" id="MontoReal" name="MontoReal" value="0">
                        </div>
                    </div>

                    <div class="row justify-content-center mb-4 mt-3">
                        <div class="col-md-6 text-center">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="Observaciones" rows="3" placeholder="Ingrese comentarios u observaciones"></textarea>
                        </div>
                    </div>

                    <!-- Sección Facturación -->
                    <div class="row justify-content-center mb-4">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="facturarCheckbox" name="facturar">
                                <label class="form-check-label" for="facturarCheckbox">Facturar este movimiento</label>
                            </div>
                            
                            <div id="facturaFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipoFactura" class="form-label">Tipo de Factura</label>
                                        <select class="form-select" id="tipoFactura" name="idTipoFactura">
                                            <?php foreach ($TiposFactura as $tipo) { ?>
                                                <option value="<?php echo $tipo['idTipoFactura']; ?>">
                                                    <?php echo $tipo['denominacion']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="numeroFactura" class="form-label">Número de Factura</label>
                                        <input type="text" class="form-control" id="numeroFactura" name="numeroFactura" placeholder="Ingrese el número">
                                    </div>
                                </div>
                            </div>
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

<!-- MODAL INSUMOS -->
<div class="modal fade" id="modalInsumos" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bi bi-search me-2"></i> Seleccionar Insumos de Inventario</h5>
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

<?php require ('../shared/footer.inc.php'); ?>

<script>
    // PRECARGA SEGURA DE PRODUCTOS
    const CATALOGO_PRODUCTOS = <?php echo json_encode($productos_memoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || [];
    let CARRITO_INSUMOS = [];
    const DOMINIO_IMG = "https://robertsgrafica.com/img/";

    function abrirModalInsumos() {
        const modalEl = document.getElementById('modalInsumos');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        setTimeout(() => { document.getElementById('inputBuscarInsumo').focus(); }, 300);
        renderizarCatalogo('');
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

        panelResumen.style.display = 'block';

        if (totalGeneral > 0) {
            document.getElementById('MontoReal').value = totalGeneral.toString();
            document.getElementById('valorDinero').value = '$' + totalGeneral.toLocaleString('es-AR');
            formatMoney(document.getElementById('valorDinero'));
        }
    }

    document.querySelectorAll('.tipo-movimiento').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.tipo-movimiento').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            });
            
            this.classList.remove('btn-secondary');
            this.classList.add('btn-primary');
            
            document.getElementById('idTipoMovimiento').value = this.getAttribute('data-id');

            const nombreMov = (this.getAttribute('data-nombre') || '').toLowerCase();
            if (nombreMov.includes('insumo')) {
                abrirModalInsumos();
            }
        });
    });

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

    document.getElementById('resetButton').addEventListener('click', function() {
        document.getElementById('MontoReal').value = '0';
        document.getElementById('valorDinero').value = '$0,00';
        document.getElementById('insumosJsonInput').value = '';
        document.getElementById('panelResumenInsumos').style.display = 'none';
        CARRITO_INSUMOS = [];

        document.querySelectorAll('.metodo-pago, .tipo-movimiento').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        
        document.getElementById('idTipoPago').value = '';
        document.getElementById('idTipoMovimiento').value = '';
        document.getElementById('facturarCheckbox').checked = false;
        document.getElementById('facturaFields').style.display = 'none';
        document.getElementById('numeroFactura').required = false;
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

    document.getElementById('facturarCheckbox').addEventListener('change', function() {
        const facturaFields = document.getElementById('facturaFields');
        facturaFields.style.display = this.checked ? 'block' : 'none';
        document.getElementById('numeroFactura').required = this.checked;
    });

    document.addEventListener('DOMContentLoaded', function() {
        formatMoney(moneyInput);
        const urlParams = new URLSearchParams(window.location.search);
        const ticketId = urlParams.get('ticket');

        if (ticketId && localStorage.getItem('imprimirTicketVenta') === 'true') {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = `ticket_venta.php?id=${ticketId}`;
            document.body.appendChild(iframe);
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>