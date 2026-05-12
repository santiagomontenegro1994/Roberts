<script>
    const db_variables = <?= $json_variables ?>;
    const db_escalas = <?= $json_escalas ?>;
    const db_productos = <?= $json_productos ?>;

    // --- CARGAMOS EL CARRITO DESDE PHP (Si venimos del historial) ---
    let carrito = <?= $carritoCargadoJSON ?>;

    // --- BASE DE DATOS DE BÚSQUEDA GLOBAL ---
    const cotizadorSecciones = [
        { id: 'apunte', titulo: 'Armar Libro / Apunte', keywords: 'libro apunte cuadernillo fotocopia anillado' },
        { id: 'sueltas', titulo: 'Impresiones Sueltas', keywords: 'sueltas hojas a4 oficio simple doble faz color blanco negro fotocopia impresion' },
        { id: 'lonas', titulo: 'Lonas y Vinilos', keywords: 'lona vinilo cartel banner gigantografia front back portabanner' },
        { id: 'talonarios', titulo: 'Talonarios', keywords: 'talonario recibo factura receta anotador entrada rifa' },
        { id: 'rollos', titulo: 'Plotter y DTF (Rollos)', keywords: 'plotter dtf rollo textil uv metro lineal plano fotografia' },
        { id: 'paquetes', titulo: 'Tarjetas y Volantes', keywords: 'tarjeta personal volante flyer folleto paquete millar' },
        { id: 'planchas', titulo: 'Rígidos y Stickers', keywords: 'rigido altoimpacto microcorrugado iman sticker calcomania plancha troquelado' },
        { id: 'resmas', titulo: 'Impresión por Resmas', keywords: 'resma offset chapa afiche millar' }
    ];

    document.addEventListener('DOMContentLoaded', () => {
        cargarSueltasYVarios();
        actualizarListasDinamicas();
        actualizarInterfaz(); // <--- Llamamos a la interfaz apenas carga para pintar los items reutilizados
        
        // Limpiamos el buscador universal cuando se abre el modal principal
        document.getElementById('modalOpcionesAgregar').addEventListener('show.bs.modal', function () {
            document.getElementById('buscadorGlobal').value = '';
            ejecutarBusquedaGlobal('');
        });
    });

    // ==========================================
    // LÓGICA DEL CARRITO Y PDF
    // ==========================================
    function actualizarInterfaz() {
        let divLista = document.getElementById('listaCarrito');
        let divVacio = document.getElementById('carritoVacio');
        let txtTotal = document.getElementById('txtTotalFinal');
        divLista.innerHTML = '';
        let sumaTotal = 0;

        if (carrito.length === 0) { divVacio.style.display = 'block'; } else {
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

    function eliminarItem(id) { carrito = carrito.filter(i => i.id !== id); actualizarInterfaz(); }

    function guardarYGenerarPDF() {
        let cliente = document.getElementById('nombreCliente').value.trim();
        let fecha = document.getElementById('fechaPresupuesto').value;
        
        if (cliente === '') return alert("Ingresa el nombre del cliente para el PDF.");
        if (carrito.length === 0) return alert("El presupuesto está vacío.");

        let total = carrito.reduce((sum, i) => sum + i.precio_total, 0);

        let formData = new FormData();
        formData.append('cliente', cliente);
        formData.append('fecha', fecha);
        formData.append('total', total);
        formData.append('items', JSON.stringify(carrito));
        
        const btn = document.querySelector('.bottom-bar .btn-primary');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
        btn.disabled = true;

        fetch('procesar_presupuesto.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.open(`generar_pdf_presupuesto.php?id=${data.id_presupuesto}`, '_blank');
                carrito = []; document.getElementById('nombreCliente').value = ''; actualizarInterfaz();
            } else { alert("Error al guardar: " + data.error); }
        }).finally(() => {
            btn.innerHTML = 'Crear PDF <i class="bi bi-file-pdf-fill ms-1"></i>'; btn.disabled = false;
        });
    }

    // ==========================================
    // BUSCADOR GLOBAL Y MODALES
    // ==========================================
    function ejecutarBusquedaGlobal(query) {
        query = query.toLowerCase().trim();
        const btnContainer = document.getElementById('botonesOpcionesPrincipales');
        const resContainer = document.getElementById('resultadosBusquedaGlobal');

        if (query === '') {
            btnContainer.classList.remove('d-none');
            resContainer.classList.add('d-none');
            return;
        }

        btnContainer.classList.add('d-none');
        resContainer.classList.remove('d-none');
        resContainer.innerHTML = '';
        let hayResultados = false;

        // Buscar en Cotizador
        let cotResultados = cotizadorSecciones.filter(c => c.titulo.toLowerCase().includes(query) || c.keywords.includes(query));
        cotResultados.forEach(cot => {
            hayResultados = true;
            resContainer.innerHTML += `
                <button class="list-group-item list-group-item-action p-3 text-start border-0 border-bottom" onclick="abrirCotizadorDesdeBuscador('${cot.id}')" data-bs-dismiss="modal">
                    <i class="bi bi-calculator text-success me-2 fs-5 align-middle"></i>
                    <span class="fw-bold align-middle">Cotizar: ${cot.titulo}</span>
                </button>
            `;
        });

        // Buscar en Catálogo (Productos fijos)
        let prodResultados = db_productos.filter(p => {
            let nom = (p['Titulo'] || p['Nombre Visible'] || p['Producto'] || p['nombre'] || '').toLowerCase();
            let estado = (p['Estado'] || '').toUpperCase();
            let precio = parseFloat(p['Precio_Unidad'] || p['Precio'] || p['precio'] || 0);
            return nom.includes(query) && estado === 'ACTIVO' && precio > 0;
        }).slice(0, 8); // Límite de 8 para no llenar la pantalla

        prodResultados.forEach(prod => {
            hayResultados = true;
            let nombre = prod['Titulo'] || prod['Nombre Visible'] || prod['Producto'] || prod['nombre'];
            let precio = parseFloat(prod['Precio_Unidad'] || prod['Precio'] || prod['precio'] || 0);
            resContainer.innerHTML += `
                <button class="list-group-item list-group-item-action p-3 text-start border-0 border-bottom d-flex justify-content-between align-items-center" onclick="agregarDesdeGlobal('${nombre}', ${precio})">
                    <div>
                        <i class="bi bi-tag text-purple me-2 fs-5 align-middle" style="color:#6f42c1;"></i>
                        <span class="fw-bold align-middle">${nombre}</span>
                    </div>
                    <span class="badge bg-primary rounded-pill">$${precio.toLocaleString('es-AR')}</span>
                </button>
            `;
        });

        if (!hayResultados) {
            resContainer.innerHTML = '<div class="p-4 text-center text-muted small">No encontramos nada con esa palabra.</div>';
        }
    }

    function abrirCotizadorDesdeBuscador(idCategoria) {
        document.getElementById('selectorCategoriaCotizador').value = idCategoria;
        cambiarSeccionCotizador();
        new bootstrap.Modal(document.getElementById('modalCotizador')).show();
    }

    function agregarDesdeGlobal(nombre, precio) {
        let cant = prompt(`¿Cuántas unidades de "${nombre}" querés agregar?`, "1");
        if (cant === null) return;
        cant = parseInt(cant);
        if (isNaN(cant) || cant <= 0) return alert("Cantidad inválida.");

        carrito.push({ id: Date.now(), descripcion: (cant > 1) ? `${cant}x ${nombre}` : nombre, precio_unitario: precio, cantidad: cant, precio_total: cant * precio, tipo: 'catalogo' });
        bootstrap.Modal.getInstance(document.getElementById('modalOpcionesAgregar')).hide();
        actualizarInterfaz();
    }

    // Modal Manual
    function abrirModalManual() {
        document.getElementById('manualDesc').value = ''; document.getElementById('manualPrecio').value = ''; document.getElementById('manualCant').value = '1';
        new bootstrap.Modal(document.getElementById('modalItemManual')).show();
    }

    function agregarItemManual() {
        let desc = document.getElementById('manualDesc').value.trim();
        let cant = parseInt(document.getElementById('manualCant').value) || 1;
        let precio = parseFloat(document.getElementById('manualPrecio').value) || 0;
        if (desc === '' || precio <= 0) return alert("Completa la descripción y el precio.");
        carrito.push({ id: Date.now(), descripcion: (cant > 1) ? `${cant}x ${desc}` : desc, precio_unitario: precio, cantidad: cant, precio_total: cant * precio, tipo: 'manual' });
        bootstrap.Modal.getInstance(document.getElementById('modalItemManual')).hide(); actualizarInterfaz();
    }

    // ==========================================
    // RENDERIZADO DEL CATÁLOGO (CORREGIDO ANTI-S/N)
    // ==========================================
    function abrirModalCatalogo() {
        renderizarCatalogo(db_productos); document.getElementById('buscadorCatalogo').value = '';
        new bootstrap.Modal(document.getElementById('modalCatalogo')).show();
    }

    function renderizarCatalogo(lista) {
        let cont = document.getElementById('listaCatalogo'); cont.innerHTML = '';
        if(lista.length === 0) { cont.innerHTML = '<div class="p-4 text-center text-muted">Sin productos.</div>'; return; }
        lista.forEach(prod => {
            // Buscamos el nombre también en la columna "Titulo"
            let nombre = prod['Titulo'] || prod['Nombre Visible'] || prod['Producto'] || prod['nombre'] || '';
            let precio = parseFloat(prod['Precio_Unidad'] || prod['Precio'] || prod['precio'] || 0);
            
            // FILTRO DE BASURA: Ocultar los headers vacíos o productos S/N y los de precio 0
            if(nombre.trim() === '' || nombre === 'S/N' || precio <= 0) return;
            if(prod['Estado'] && prod['Estado'].toUpperCase() !== 'ACTIVO') return;

            cont.innerHTML += `<div class="list-group-item catalogo-item p-3 border-0 border-bottom" onclick="agregarDesdeCatalogo('${nombre}', ${precio})"><div class="d-flex justify-content-between align-items-center"><div><h6 class="mb-1 fw-bold text-dark">${nombre}</h6><span class="badge bg-primary rounded-pill">$${precio.toLocaleString('es-AR')}</span></div><i class="bi bi-plus-circle text-primary fs-3"></i></div></div>`;
        });
    }

    function filtrarCatalogo() {
        let q = document.getElementById('buscadorCatalogo').value.toLowerCase();
        renderizarCatalogo(db_productos.filter(p => (p['Titulo'] || p['Nombre Visible'] || p['Producto'] || p['nombre'] || '').toLowerCase().includes(q)));
    }

    function agregarDesdeCatalogo(nombre, precio) {
        let cant = prompt(`Unidades de "${nombre}":`, "1");
        if (cant === null) return; cant = parseInt(cant);
        if (isNaN(cant) || cant <= 0) return alert("Invalido.");
        carrito.push({ id: Date.now(), descripcion: (cant > 1) ? `${cant}x ${nombre}` : nombre, precio_unitario: precio, cantidad: cant, precio_total: cant * precio, tipo: 'catalogo' });
        bootstrap.Modal.getInstance(document.getElementById('modalCatalogo')).hide(); actualizarInterfaz();
    }

    // ==========================================
    // 4. EL COTIZADOR MÓVIL INTEGADO
    // ==========================================
    function abrirModalCotizador() { new bootstrap.Modal(document.getElementById('modalCotizador')).show(); }

    function cambiarSeccionCotizador() {
        let sel = document.getElementById('selectorCategoriaCotizador').value;
        document.querySelectorAll('.cotizador-seccion').forEach(e => e.classList.add('d-none'));
        document.getElementById('sec_' + sel).classList.remove('d-none');
    }

    function ejecutarCotizadorMobile() {
        let sel = document.getElementById('selectorCategoriaCotizador').value;
        let trabajo = null;
        if (sel === 'apunte') trabajo = procesarApunte();
        if (sel === 'sueltas') trabajo = procesarSueltas();
        if (sel === 'lonas') trabajo = procesarLona();
        if (sel === 'talonarios') trabajo = procesarTalonarios();
        if (sel === 'rollos') trabajo = procesarRollos();
        if (sel === 'paquetes') trabajo = procesarPaquetes();
        if (sel === 'planchas') trabajo = procesarPlanchas();
        if (sel === 'resmas') trabajo = procesarResmas();

        if (trabajo) {
            carrito.push({ id: trabajo.id, descripcion: trabajo.descripcion, precio_unitario: trabajo.precio, cantidad: 1, precio_total: trabajo.precio, tipo: 'cotizador' });
            bootstrap.Modal.getInstance(document.getElementById('modalCotizador')).hide(); actualizarInterfaz();
        }
    }

    // Funciones Matemáticas del Motor
    function darFormatoLegible(c) { return c.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()); }
    function obtenerPrecioEscala(g, c) { let ops = db_escalas.filter(i => i.Codigo_Grupo === g && i.Estado.toUpperCase() === 'ACTIVO'); ops.sort((a,b) => b.Cantidad - a.Cantidad); for(let o of ops) { if(c >= parseInt(o.Cantidad)) return parseFloat(o.Precio_Total); } return 0; }
    function obtenerPrecioVariable(cod) { let v = db_variables.find(i => i.Codigo_Variable === cod && i.Estado.toUpperCase() === 'ACTIVO'); return v ? parseFloat(v.Precio_Unidad) : 0; }

    function cargarSueltasYVarios() {
        const fill = (id, cond, isLona=false) => { let s = document.getElementById(id); s.innerHTML=''; db_variables.filter(i => i.Estado.toUpperCase()==='ACTIVO' && cond(i)).forEach(p=>{ let o=document.createElement('option'); o.value=p.Codigo_Variable; o.text=p['Nombre Visible']||darFormatoLegible(p.Codigo_Variable); if(isLona) o.setAttribute('data-tipo', p.Codigo_Variable.startsWith('lona_')?'lona':'vinilo'); s.appendChild(o); }); };
        fill('hs_tipo', i => i.Codigo_Variable.startsWith('bn_') || i.Codigo_Variable.startsWith('color_'));
        fill('lv_material', i => i.Codigo_Variable.startsWith('lona_') || i.Codigo_Variable.startsWith('vinilo_'), true);
        fill('rl_material', i => i.Codigo_Variable.startsWith('rollo_'));
        fill('pq_tarjeta_sel', i => i.Codigo_Variable.startsWith('tarj_paq_'));
        fill('pq_volante_sel', i => i.Codigo_Variable.startsWith('vol_paq_'));
        fill('pl_sel_rigidos', i => i.Codigo_Variable.startsWith('rigido_'));
        fill('pl_sel_imanes', i => i.Codigo_Variable.startsWith('iman_'));
        fill('pl_sel_stickers', i => i.Codigo_Variable.startsWith('sticker_'));
        setTimeout(toggleRedondeado, 100);
    }
    function actualizarListasDinamicas() {
        let tam = document.getElementById('ap_tamano').value;
        const fillP = (id, pref, cb) => { let s=document.getElementById(id); s.innerHTML=cb?'<option value="comun">Papel Común (Obra)</option>':''; db_variables.filter(i=>i.Estado.toUpperCase()==='ACTIVO'&&i.Codigo_Variable.startsWith(pref)&&i.Codigo_Variable.endsWith('_'+tam)).forEach(p=>{ let o=document.createElement('option'); o.value=p.Codigo_Variable; o.text=p['Nombre Visible']||darFormatoLegible(p.Codigo_Variable); s.appendChild(o); }); };
        fillP('ap_bn_papel', 'bn_', true); fillP('ap_color_papel', 'color_', false); fillP('ap_tapas_papel', 'color_', false);
    }

    // Interacciones UI
    function toggleLonaOpciones() { let s=document.getElementById('lv_material'); let t=s.options[s.selectedIndex]?.getAttribute('data-tipo'); let b=document.getElementById('lv_bordes').value; document.getElementById('opt_costura').style.display=(t==='vinilo')?'none':'block'; document.getElementById('panel_costura').classList.toggle('d-none', b!=='costura'||t==='vinilo'); }
    function togglePaquetes() { let t=document.getElementById('pq_tipo').value; document.getElementById('pq_tarjeta_sel').classList.toggle('d-none', t!=='tarjetas'); document.getElementById('pq_volante_sel').classList.toggle('d-none', t!=='volantes'); document.getElementById('pq_tarj_redondeado').parentElement.classList.toggle('d-none', t!=='tarjetas'); }
    function toggleRedondeado() { let s=document.getElementById('pq_tarjeta_sel'); let t=s.options[s.selectedIndex]?.text||''; let is1000=t.includes('1000'); let cb=document.getElementById('pq_tarj_redondeado'); cb.checked=is1000?cb.checked:false; cb.disabled=!is1000; document.getElementById('lbl_redondeado').className=`form-check-label fw-bold small ${is1000?'text-dark':'text-muted'}`; }
    function togglePlanchas() { let c=document.getElementById('pl_categoria').value; document.getElementById('pl_sel_rigidos').classList.toggle('d-none', c!=='rigidos'); document.getElementById('pl_sel_imanes').classList.toggle('d-none', c!=='imanes'); document.getElementById('pl_sel_stickers').classList.toggle('d-none', c!=='stickers'); }

    // Procesadores Matemáticos Reales
    function procesarApunte() { let tam=document.getElementById('ap_tamano').value, cBN=parseInt(document.getElementById('ap_bn_cant').value)||0, cCol=parseInt(document.getElementById('ap_color_cant').value)||0, cTap=parseInt(document.getElementById('ap_tapas_cant').value)||0; if(cBN+cCol+cTap===0) return null; let cBNm=document.getElementById('ap_bn_modo').value, codBN=`bn_obra${cBNm}_${tam}`, pBN=document.getElementById('ap_bn_papel').value; let cosBN = cBN*(obtenerPrecioEscala(codBN, cBN) + (pBN==='comun'?0:obtenerPrecioVariable(pBN))); let cosCol = cCol*obtenerPrecioVariable(document.getElementById('ap_color_papel').value); let cosTap = cTap*obtenerPrecioVariable(document.getElementById('ap_tapas_papel').value); let cosAn = 0, anil=0; if(document.getElementById('ap_anillado').checked) { let hr = (cBN/2)+(cCol/2)+cTap, pa = (tam==='oficio')?'anillado_a4':'anillado_'+tam; while(hr>0) { cosAn+=obtenerPrecioEscala(pa, Math.min(hr,450)); hr-=450; anil++; } } return { id:Date.now(), descripcion:`Apunte ${tam.toUpperCase()} (${cBN}BN, ${cCol}Col) ${anil>0?'+'+anil+' Anill.':''}`, precio: cosBN+cosCol+cosTap+cosAn }; }
    function procesarLona() { let a=parseFloat(document.getElementById('lv_ancho').value)||0, h=parseFloat(document.getElementById('lv_alto').value)||0; if(a*h===0) return null; let s=document.getElementById('lv_material'), mat=s.value, nMat=s.options[s.selectedIndex].text, m2F=Math.max(0.5, (a/100)*(h/100)); let cos = Math.round(m2F*obtenerPrecioVariable(mat)), ext=0, det=[], b=document.getElementById('lv_bordes').value; if(b==='ras') det.push("Al ras"); if(b==='blanco') det.push("Exc. blanco"); if(b==='costura') { let ml=0; if(document.getElementById('cost_arriba').checked) ml+=a/100; if(document.getElementById('cost_abajo').checked) ml+=a/100; if(document.getElementById('cost_izq').checked) ml+=h/100; if(document.getElementById('cost_der').checked) ml+=h/100; ext+=Math.round(ml*obtenerPrecioVariable('costura_ml')); det.push("Costura"); let arg=parseInt(document.getElementById('lv_argollas_cant').value)||0; if(arg>0){ ext+=Math.round(arg*obtenerPrecioVariable('argolla_u')); det.push(`${arg} Argollas`); } } if(a>150&&h>150) det.push("Soldadura"); return { id:Date.now(), descripcion:`${nMat} ${a}x${h}cm [${det.join('|')}]`, precio:cos+ext }; }
    function procesarSueltas() { let c=parseInt(document.getElementById('hs_cant').value)||0, t=document.getElementById('hs_tipo').value, m=document.getElementById('hs_modo').value==='df'?"Doble":"Simple"; if(c<=0)return null; return { id:Date.now(), descripcion:`${c}x Sueltas: ${darFormatoLegible(t)} (${m})`, precio:c*obtenerPrecioVariable(t) }; }
    function procesarTalonarios() { let t=document.getElementById('tal_tipo').value, tam=document.getElementById('tal_tamano').value, c=parseInt(document.getElementById('tal_cant').value)||0, fV=document.getElementById('tal_formato').value, fT=document.getElementById('tal_formato').options[document.getElementById('tal_formato').selectedIndex].text; if(c<=0) return alert("Ingresa cant."); if(tam==='octavo'&&c%8!==0) return alert("1/8 en múltiplos de 8"); if(tam==='cuarto'&&c%4!==0) return alert("1/4 en múltiplos de 4"); if(fV==='triplicado'&&c%2!==0) return alert("Triplicado en múltiplos de 2"); let mul = (tam==='octavo')?0.25:(tam==='cuarto')?0.5:(tam==='entero')?2:1, uC=c*mul, cE=(fV==='triplicado')?'tal_trip':'tal_dupl'; let ops=db_escalas.filter(i=>i.Estado.toUpperCase()==='ACTIVO'&&i.Codigo_Grupo===cE).sort((a,b)=>parseInt(b.Cantidad)-parseInt(a.Cantidad)); if(!ops.length) return null; let esc = ops.find(o=>uC>=parseInt(o.Cantidad)) || ops[ops.length-1]; let pre = Math.round(uC*(parseFloat(esc.Precio_Total)/parseInt(esc.Cantidad))); return { id:Date.now(), descripcion:`${c}x ${t} [${fT} | ${document.getElementById('tal_terminacion').value} | ${document.getElementById('tal_numerado').value}]`, precio: pre }; }
    function procesarRollos() { let l=parseFloat(document.getElementById('rl_largo').value)||0; if(l<=0) return null; let s=document.getElementById('rl_material'); return { id:Date.now(), descripcion:`${s.options[s.selectedIndex].text} - Largo: ${l}cm`, precio: Math.round(Math.max(30, l)/100 * obtenerPrecioVariable(s.value)) }; }
    function procesarPaquetes() { let t=document.getElementById('pq_tipo').value, c=parseInt(document.getElementById('pq_cant').value)||0; if(c<=0) return null; let s=document.getElementById((t==='tarjetas')?'pq_tarjeta_sel':'pq_volante_sel'), cod=s.value, n=s.options[s.selectedIndex].text, ext=0, det=[]; if(t==='tarjetas'&&document.getElementById('pq_tarj_redondeado').checked) { ext+=obtenerPrecioVariable('tarj_extra_redondeado'); det.push("Puntas Red."); } return { id:Date.now(), descripcion:`${c} Lote(s): ${n} ${det.length?'+'+det.join(','):''}`, precio: c*(obtenerPrecioVariable(cod)+ext) }; }
    function procesarPlanchas() { let cat=document.getElementById('pl_categoria').value, c=parseInt(document.getElementById('pl_cant').value)||0; if(c<=0) return null; let s=document.getElementById(cat==='rigidos'?'pl_sel_rigidos':(cat==='imanes'?'pl_sel_imanes':'pl_sel_stickers')); return { id:Date.now(), descripcion:`${c}x ${s.options[s.selectedIndex].text}`, precio: c*obtenerPrecioVariable(s.value) }; }
    function procesarResmas() { let cCh=document.getElementById('rs_chapa').value, p=document.getElementById('rs_papel').value, c=parseInt(document.getElementById('rs_cant').value)||0, col=parseInt(document.getElementById('rs_colores').value)||1; if(c<=0) return null; let t2=Math.floor(c/2), t1=c%2, p1=obtenerPrecioVariable(`resma_${cCh}_${p}_1`), p2=obtenerPrecioVariable(`resma_${cCh}_${p}_2`); let tot=(t2*p2)+(t1*p1), cCol=(col-1)*Math.ceil(c/2)*obtenerPrecioVariable(`resma_color_extra_${cCh}`), ext=0, det=[]; if(document.getElementById('rs_emblocado').checked) { ext+= (t2*obtenerPrecioVariable('resma_extra_embloc_2'))+(t1*obtenerPrecioVariable('resma_extra_embloc_1')); det.push("Emblocado"); } if(document.getElementById('rs_numerado').checked) { ext+= (t2*obtenerPrecioVariable('resma_extra_num_2'))+(t1*obtenerPrecioVariable('resma_extra_num_1')); det.push("Numerado"); } return { id:Date.now(), descripcion:`${c} Resmas a ${col} col. [${cCh==='nueva'?'Nueva':'Exist'}] ${det.length?'+'+det.join(','):''}`, precio: tot+cCol+ext }; }
</script>