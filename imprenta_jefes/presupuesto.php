<?php
require_once 'auth_jefes.php';
verificarSesionApp();

include 'header_mobile.php';
?>
<style>
    /* Estilos específicos para la App de Presupuestos */
    body { padding-bottom: 90px; /* Espacio para la barra inferior */ }
    
    .cart-item {
        background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); position: relative;
    }
    .cart-item-title { font-weight: bold; font-size: 0.95rem; color: #333; margin-bottom: 5px; padding-right: 25px;}
    .cart-item-price { font-size: 1.1rem; font-weight: bold; color: #0d6efd; }
    .btn-remove-item { position: absolute; top: 10px; right: 10px; color: #dc3545; background: none; border: none; font-size: 1.2rem; }

    /* Barra inferior fija */
    .bottom-bar {
        position: fixed; bottom: 0; left: 0; right: 0; background: #fff;
        padding: 15px 20px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05);
        display: flex; justify-content: space-between; align-items: center; z-index: 1000;
        border-top-left-radius: 20px; border-top-right-radius: 20px;
    }

    /* Botón flotante para agregar */
    .fab-button {
        position: fixed; bottom: 100px; right: 20px;
        width: 60px; height: 60px; border-radius: 30px;
        background: #0d6efd; color: white; display: flex; align-items: center; justify-content: center;
        font-size: 2rem; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4); z-index: 999;
        border: none; transition: transform 0.2s;
    }
    .fab-button:active { transform: scale(0.9); }
</style>

<div class="bg-white p-3 d-flex align-items-center shadow-sm sticky-top">
    <a href="index.php" class="text-dark fs-3 me-3"><i class="bi bi-arrow-left-short"></i></a>
    <h5 class="m-0 fw-bold">Nuevo Presupuesto</h5>
    <a href="historial_presupuestos.php" class="ms-auto text-primary fs-4"><i class="bi bi-clock-history"></i></a>
</div>

<div class="container py-3">
    <div class="card card-custom p-3 mb-4">
        <label class="form-label small fw-bold text-muted mb-1">Nombre del Cliente (Para el PDF)</label>
        <input type="text" id="nombreCliente" class="form-control form-control-lg border-0 bg-light" placeholder="Ej: Juan Perez" style="font-size: 1.1rem;">
    </div>

    <h6 class="fw-bold text-muted mb-3 ps-1">Ítems del Presupuesto</h6>
    
    <div id="carritoVacio" class="text-center py-5 text-muted">
        <i class="bi bi-cart-x" style="font-size: 3rem; opacity: 0.5;"></i>
        <p class="mt-2">El presupuesto está vacío.<br>Toca el botón <b>+</b> para empezar.</p>
    </div>

    <div id="listaCarrito">
        </div>
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
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" data-bs-dismiss="modal" onclick="alert('En el próximo paso conectamos el Cotizador acá!')">
                        <i class="bi bi-calculator text-success fs-4 me-2 align-middle"></i> 
                        <span class="fw-bold fs-5 align-middle">Usar Cotizador Inteligente</span>
                    </button>
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" data-bs-dismiss="modal" onclick="alert('En el próximo paso conectamos el Excel de Productos acá!')">
                        <i class="bi bi-tags text-purple fs-4 me-2 align-middle" style="color:#6f42c1;"></i> 
                        <span class="fw-bold fs-5 align-middle">Catálogo Rápido</span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- LÓGICA DEL CARRITO ---
    let carrito = [];
    
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

        // Estructura del ítem (Igual a la que usaba tu cotizador)
        let item = {
            id: Date.now(),
            descripcion: (cant > 1) ? `${cant}x ${desc}` : desc,
            precio_unitario: precio,
            cantidad: cant,
            precio_total: cant * precio,
            tipo: 'manual'
        };

        carrito.push(item);
        bootstrap.Modal.getInstance(document.getElementById('modalItemManual')).hide();
        actualizarInterfaz();
    }

    function eliminarItem(id) {
        carrito = carrito.filter(i => i.id !== id);
        actualizarInterfaz();
    }

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

    // --- GUARDAR Y GENERAR PDF ---
    function guardarYGenerarPDF() {
        let cliente = document.getElementById('nombreCliente').value.trim();
        
        if (cliente === '') return alert("Ingresa el nombre del cliente para el PDF.");
        if (carrito.length === 0) return alert("El presupuesto está vacío.");

        let total = carrito.reduce((sum, i) => sum + i.precio_total, 0);

        // Preparamos los datos para enviarlos por POST a PHP
        let formData = new FormData();
        formData.append('cliente', cliente);
        formData.append('total', total);
        formData.append('items', JSON.stringify(carrito)); // Mandamos el carrito entero empacado
        
        // Deshabilitar botón temporalmente para que no hagan doble clic
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
                // Abrimos el PDF en una pestaña nueva
                window.open(`generar_pdf_presupuesto.php?id=${data.id_presupuesto}`, '_blank');
                // Limpiamos el carrito
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