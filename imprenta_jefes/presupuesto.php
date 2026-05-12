<?php
require_once 'auth_jefes.php';
verificarSesionApp();

// --- CARGAMOS LAS BASES DE DATOS DEL EXCEL (JSON) ---
// Ajusta la ruta '../datos/' si tu carpeta se llama distinto
$path_variables = __DIR__ . '/../datos/variables_imprenta.json';
$json_variables = file_exists($path_variables) ? file_get_contents($path_variables) : '[]';

$path_escalas = __DIR__ . '/../datos/escalas_cantidades.json';
$json_escalas = file_exists($path_escalas) ? file_get_contents($path_escalas) : '[]';

$path_productos = __DIR__ . '/../datos/productos_simples.json';
$json_productos = file_exists($path_productos) ? file_get_contents($path_productos) : '[]';

include 'header_mobile.php';
?>
<style>
    body { padding-bottom: 90px; }
    
    .cart-item {
        background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); position: relative;
    }
    .cart-item-title { font-weight: bold; font-size: 0.95rem; color: #333; margin-bottom: 5px; padding-right: 25px;}
    .cart-item-price { font-size: 1.1rem; font-weight: bold; color: #0d6efd; }
    .btn-remove-item { position: absolute; top: 10px; right: 10px; color: #dc3545; background: none; border: none; font-size: 1.2rem; }

    .bottom-bar {
        position: fixed; bottom: 0; left: 0; right: 0; background: #fff;
        padding: 15px 20px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05);
        display: flex; justify-content: space-between; align-items: center; z-index: 1000;
        border-top-left-radius: 20px; border-top-right-radius: 20px;
    }

    .fab-button {
        position: fixed; bottom: 100px; right: 20px;
        width: 60px; height: 60px; border-radius: 30px;
        background: #0d6efd; color: white; display: flex; align-items: center; justify-content: center;
        font-size: 2rem; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4); z-index: 999;
        border: none; transition: transform 0.2s;
    }
    .fab-button:active { transform: scale(0.9); }
    
    /* Estilos para la lista del Catálogo Rápido */
    .catalogo-item { cursor: pointer; transition: background 0.2s; }
    .catalogo-item:active { background: #f8f9fa; }
</style>

<div class="bg-white p-3 d-flex align-items-center shadow-sm sticky-top">
    <a href="index.php" class="text-dark fs-3 me-3"><i class="bi bi-arrow-left-short"></i></a>
    <h5 class="m-0 fw-bold">Nuevo Presupuesto</h5>
    <a href="historial_presupuestos.php" class="ms-auto text-primary fs-4" title="Historial"><i class="bi bi-clock-history"></i></a>
</div>

<div class="container py-3">
    <div class="card card-custom p-3 mb-4">
        <div class="row g-2">
            <div class="col-8">
                <label class="form-label small fw-bold text-muted mb-1">Cliente (Para el PDF)</label>
                <input type="text" id="nombreCliente" class="form-control form-control-lg border-0 bg-light" placeholder="Ej: Juan Perez" style="font-size: 1.1rem;">
            </div>
            <div class="col-4">
                <label class="form-label small fw-bold text-muted mb-1">Fecha</label>
                <input type="date" id="fechaPresupuesto" class="form-control form-control-lg border-0 bg-light px-2" value="<?= date('Y-m-d') ?>" style="font-size: 1rem;">
            </div>
        </div>
    </div>

    <h6 class="fw-bold text-muted mb-3 ps-1">Ítems del Presupuesto</h6>
    
    <div id="carritoVacio" class="text-center py-5 text-muted">
        <i class="bi bi-cart-x" style="font-size: 3rem; opacity: 0.5;"></i>
        <p class="mt-2">El presupuesto está vacío.<br>Toca el botón <b>+</b> para empezar.</p>
    </div>

    <div id="listaCarrito"></div>
</div>

<button class="fab-button" data-bs-toggle="modal" data-bs-target="#modalOpcionesAgregar">
    <i class="bi bi-plus"></i>
</button>

<div class="bottom-bar">
    <div>
        <small class="text-muted d-block" style="line-height: 1;">Total Estimado</small>
        <h3 class="m-0 fw-bold text-dark" id="txtTotalFinal">$0.00</h3>
    </div>
    <button class="btn btn-primary btn-lg rounded-pill px-4" onclick="guardarYGenerarPDF()">
        Crear PDF <i class="bi bi-file-pdf-fill ms-1"></i>
    </button>
</div>

<div class="modal fade" id="modalOpcionesAgregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Agregar al Presupuesto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-3">
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" onclick="abrirModalManual()" data-bs-dismiss="modal">
                        <i class="bi bi-pencil-square text-primary fs-4 me-2 align-middle"></i> 
                        <span class="fw-bold fs-5 align-middle">Ítem Manual libre</span>
                    </button>
                    
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" onclick="abrirModalCatalogo()" data-bs-dismiss="modal">
                        <i class="bi bi-tags text-purple fs-4 me-2 align-middle" style="color:#6f42c1;"></i> 
                        <span class="fw-bold fs-5 align-middle">Catálogo Rápido</span>
                    </button>

                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" data-bs-dismiss="modal" onclick="alert('En el próximo paso armamos el menú desplegable del Cotizador Inteligente!')">
                        <i class="bi bi-calculator text-success fs-4 me-2 align-middle"></i> 
                        <span class="fw-bold fs-5 align-middle">Usar Cotizador Inteligente</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalItemManual" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Ítem Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Descripción del trabajo</label>
                    <textarea id="manualDesc" class="form-control form-control-lg bg-light" rows="2" placeholder="Ej: Diseño de Logo corporativo"></textarea>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Cant.</label>
                        <input type="number" id="manualCant" class="form-control form-control-lg bg-light" value="1" min="1">
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold text-muted">Precio Unitario ($)</label>
                        <input type="number" id="manualPrecio" class="form-control form-control-lg bg-light" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 btn-lg rounded-pill" onclick="agregarItemManual()">Agregar al Carrito</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCatalogo" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 d-flex align-items-center">
                <button type="button" class="btn btn-light rounded-circle me-2" data-bs-toggle="modal" data-bs-target="#modalOpcionesAgregar">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <h5 class="modal-title fw-bold m-0">Catálogo de Productos</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="input-group mb-3 shadow-sm rounded-pill overflow-hidden bg-white">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscadorCatalogo" class="form-control border-0 py-2 shadow-none" placeholder="Buscar producto..." onkeyup="filtrarCatalogo()">
                </div>
                <div id="listaCatalogo" class="list-group shadow-sm" style="border-radius: 15px;">
                    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- VARIABLES GLOBALES DEL EXCEL ---
    const db_variables = <?= $json_variables ?>;
    const db_escalas = <?= $json_escalas ?>;
    const db_productos = <?= $json_productos ?>;

    let carrito = [];
    
    // --- LÓGICA DEL CARRITO ---
    function actualizarInterfaz() {
        let divLista = document.getElementById('listaCarrito');
        let divVacio = document.getElementById('carritoVacio');
        let txtTotal = document.getElementById('txtTotalFinal');
        
        divLista.innerHTML = '';
        let sumaTotal = 0;

        if (carrito.length === 0) {
            divVacio.style.display = 'block';
        } else {
            divVacio.style.display = 'none';
            carrito.forEach(item => {
                sumaTotal += item.precio_total;
                divLista.innerHTML += `
                    <div class="cart-item">
                        <button class="btn-remove-item" onclick="eliminarItem(${item.id})"><i class="bi bi-x-circle-fill"></i></button>
                        <div class="cart-item-title">${item.descripcion}</div>
                        <div class="cart-item-price">$${item.precio_total.toLocaleString('es-AR', {minimumFractionDigits: 2})}</div>
                        <small class="text-muted">${item.cantidad} uni. a $${item.precio_unitario.toLocaleString('es-AR')} c/u</small>
                    </div>
                `;
            });
        }
        txtTotal.innerText = '$' + sumaTotal.toLocaleString('es-AR', {minimumFractionDigits: 2});
    }

    function eliminarItem(id) {
        carrito = carrito.filter(i => i.id !== id);
        actualizarInterfaz();
    }

    // --- ÍTEM MANUAL ---
    function abrirModalManual() {
        document.getElementById('manualDesc').value = '';
        document.getElementById('manualPrecio').value = '';
        document.getElementById('manualCant').value = '1';
        new bootstrap.Modal(document.getElementById('modalItemManual')).show();
    }

    function agregarItemManual() {
        let desc = document.getElementById('manualDesc').value.trim();
        let cant = parseInt(document.getElementById('manualCant').value) || 1;
        let precio = parseFloat(document.getElementById('manualPrecio').value) || 0;

        if (desc === '' || precio <= 0) {
            alert("Completa la descripción y el precio.");
            return;
        }

        carrito.push({
            id: Date.now(),
            descripcion: (cant > 1) ? `${cant}x ${desc}` : desc,
            precio_unitario: precio,
            cantidad: cant,
            precio_total: cant * precio,
            tipo: 'manual'
        });

        bootstrap.Modal.getInstance(document.getElementById('modalItemManual')).hide();
        actualizarInterfaz();
    }

    // --- CATÁLOGO RÁPIDO ---
    function abrirModalCatalogo() {
        renderizarCatalogo(db_productos);
        document.getElementById('buscadorCatalogo').value = '';
        new bootstrap.Modal(document.getElementById('modalCatalogo')).show();
    }

    function renderizarCatalogo(lista) {
        let contenedor = document.getElementById('listaCatalogo');
        contenedor.innerHTML = '';
        
        if(lista.length === 0) {
            contenedor.innerHTML = '<div class="p-4 text-center text-muted">No se encontraron productos.</div>';
            return;
        }

        lista.forEach((prod, index) => {
            // Nota: Si en tu JSON las columnas se llaman diferente, cambialo acá.
            // Asumo que se llaman 'Producto' o 'Nombre Visible' y 'Precio' o 'Precio_Unidad'
            let nombre = prod['Nombre Visible'] || prod['Producto'] || prod['nombre'] || 'Producto sin nombre';
            let precio = parseFloat(prod['Precio_Unidad'] || prod['Precio'] || prod['precio'] || 0);

            // Si el estado es Inactivo, lo salteamos
            if(prod['Estado'] && prod['Estado'].toUpperCase() !== 'ACTIVO') return;

            contenedor.innerHTML += `
                <div class="list-group-item catalogo-item p-3 border-0 border-bottom" onclick="agregarDesdeCatalogo('${nombre}', ${precio})">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold text-dark">${nombre}</h6>
                            <span class="badge bg-primary rounded-pill fs-6">$${precio.toLocaleString('es-AR')}</span>
                        </div>
                        <i class="bi bi-plus-circle text-primary fs-3"></i>
                    </div>
                </div>
            `;
        });
    }

    function filtrarCatalogo() {
        let query = document.getElementById('buscadorCatalogo').value.toLowerCase();
        let filtrados = db_productos.filter(prod => {
            let nombre = (prod['Nombre Visible'] || prod['Producto'] || prod['nombre'] || '').toLowerCase();
            return nombre.includes(query);
        });
        renderizarCatalogo(filtrados);
    }

    function agregarDesdeCatalogo(nombre, precio) {
        let cant = prompt(`¿Cuántas unidades de "${nombre}" querés agregar?`, "1");
        if (cant === null) return; // Canceló
        
        cant = parseInt(cant);
        if (isNaN(cant) || cant <= 0) return alert("Cantidad inválida.");

        carrito.push({
            id: Date.now(),
            descripcion: (cant > 1) ? `${cant}x ${nombre}` : nombre,
            precio_unitario: precio,
            cantidad: cant,
            precio_total: cant * precio,
            tipo: 'catalogo'
        });

        bootstrap.Modal.getInstance(document.getElementById('modalCatalogo')).hide();
        actualizarInterfaz();
    }

    // --- GUARDAR Y GENERAR PDF ---
    function guardarYGenerarPDF() {
        let cliente = document.getElementById('nombreCliente').value.trim();
        let fecha = document.getElementById('fechaPresupuesto').value;
        
        if (cliente === '') return alert("Ingresa el nombre del cliente para el PDF.");
        if (carrito.length === 0) return alert("El presupuesto está vacío.");

        let total = carrito.reduce((sum, i) => sum + i.precio_total, 0);

        let formData = new FormData();
        formData.append('cliente', cliente);
        formData.append('fecha', fecha); // CORREGIDO (Estaba duplicado "cliente" en tu código)
        formData.append('total', total);
        formData.append('items', JSON.stringify(carrito));
        
        const btn = document.querySelector('.bottom-bar .btn-primary');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
        btn.disabled = true;

        fetch('procesar_presupuesto.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.open(`generar_pdf_presupuesto.php?id=${data.id_presupuesto}`, '_blank');
                carrito = [];
                document.getElementById('nombreCliente').value = '';
                actualizarInterfaz();
            } else {
                alert("Error al guardar: " + data.error);
            }
        })
        .finally(() => {
            btn.innerHTML = 'Crear PDF <i class="bi bi-file-pdf-fill ms-1"></i>';
            btn.disabled = false;
        });
    }
</script>
</body>
</html>