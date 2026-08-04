<?php
// Valores por defecto
$precio_diseno = 0;
$precio_cyber  = 0;
$gracia_diseno = 0;
$gracia_cyber  = 0;

// Consulta dinámica a la tabla config_taximetro
if (isset($MiConexion) && $MiConexion) {
    $queryTax = "SELECT * FROM config_taximetro WHERE id_config = 1";
    $resTax = @mysqli_query($MiConexion, $queryTax);
    
    if ($resTax && mysqli_num_rows($resTax) > 0) {
        $configTax = mysqli_fetch_assoc($resTax);
        $precio_diseno = (float)($configTax['precio_min_diseno'] ?? 0);
        $precio_cyber  = (float)($configTax['precio_min_cyber'] ?? 0);
        $gracia_diseno = (int)($configTax['gracia_diseno'] ?? 0);
        $gracia_cyber  = (int)($configTax['gracia_cyber'] ?? 0);
    }
}
?>

<!-- ESTILOS MINIMALISTAS REFINADOS -->
<style>
    #widget-taximetro {
        position: fixed;
        bottom: 15px;
        right: 80px;
        width: 360px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.18);
        z-index: 9999;
        font-family: 'Open Sans', system-ui, -apple-system, sans-serif;
        transition: all 0.3s ease;
        overflow: hidden;
        border: 1px solid rgba(190, 24, 93, 0.15);
    }
    .tax-header {
        background: linear-gradient(135deg, #be185d 0%, #9d174d 100%);
        color: white;
        padding: 6px 10px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        user-select: none;
        height: 38px; /* Alto ultradelgado */
    }
    .tax-header:hover { opacity: 0.96; }
    .tax-title-text {
        font-size: 0.8rem;
        letter-spacing: -0.2px;
        white-space: nowrap;
    }
    .tax-badges-container {
        display: flex;
        gap: 3px;
        align-items: center;
        justify-content: center;
        flex-grow: 1;
        overflow: hidden;
    }
    .tax-badge-item {
        background-color: rgba(255, 255, 255, 0.22);
        color: #ffffff;
        font-size: 0.63rem;
        font-weight: 600;
        padding: 1px 4px;
        border-radius: 4px;
        white-space: nowrap;
        backdrop-filter: blur(2px);
    }
    .tax-header-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tax-body {
        padding: 8px; /* Reduce la altura total */
        display: none;
        background-color: #fdf2f8;
        max-height: 80vh;
        overflow-y: auto;
    }
    .tax-item {
        background: #ffffff;
        border: 1px solid #fbcfe8;
        border-radius: 8px;
        padding: 6px 10px; /* Tarjeta compacta */
        margin-bottom: 6px;
        box-shadow: 0 1px 3px rgba(190, 24, 93, 0.04);
    }
    .tax-item:last-child { margin-bottom: 0; }
    .tax-title { font-weight: 700; font-size: 0.82rem; color: #374151; display: flex; align-items: center; justify-content: space-between; }
    .tax-displays { display: flex; justify-content: space-between; align-items: center; margin: 2px 0; }
    .tax-time { font-family: 'Courier New', Courier, monospace; font-size: 1.1rem; font-weight: 800; color: #1f2937; }
    .tax-cost { font-size: 1.05rem; font-weight: 800; color: #059669; }
    .tax-controls button { padding: 2px 8px; font-size: 0.78rem; border-radius: 6px; border: none; }
    .btn-pink-start { background-color: #be185d; color: white; }
    .btn-pink-start:hover { background-color: #9d174d; color: white; }
    .status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background-color: #d1d5db; margin-right: 5px; }
    .status-running { background-color: #10b981; animation: blink 1s infinite; }
    .status-paused { background-color: #f59e0b; }
    
    .disc-panel { display: none; margin-top: 4px; padding: 2px 6px; background: #fff5f8; border-radius: 6px; border: 1px dashed #f472b6; }
    .btn-secret { background: none; border: none; color: rgba(255,255,255,0.8); padding: 0; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; }
    .btn-secret:hover { color: #ffffff; }

    @keyframes blink { 50% { opacity: 0.3; } }
</style>

<div id="widget-taximetro">
    <div class="tax-header" id="tax-header-toggle">
        <div class="d-flex align-items-center me-1">
            <i class="bi bi-stopwatch me-1 fs-6"></i>
            <span class="tax-title-text">Tiempo</span>
        </div>
        
        <!-- pastillas ultracompactas en modo minimizado -->
        <div id="tax-mini-badges" class="tax-badges-container"></div>

        <div class="tax-header-controls">
            <button class="btn-secret" onclick="toggleHistorial(event)" title="Ver Historial de Cobros">
                <i class="bi bi-clock-history"></i>
            </button>
            <i class="bi bi-chevron-up" id="tax-icon-toggle"></i>
        </div>
    </div>
    
    <div class="tax-body" id="tax-body">
        
        <!-- VISTA DE RELOJES -->
        <div id="view-timers">
            <!-- Diseño / Acomodo -->
            <div class="tax-item" id="timer-diseno">
                <div class="tax-title">
                    <span><span class="status-dot" id="dot-diseno"></span>Diseño / Acomodo</span>
                    <button class="btn-secret text-muted" onclick="toggleSecretDisc('diseno')" title="Opciones"><i class="bi bi-gear"></i></button>
                </div>
                <div class="disc-panel" id="panel-disc-diseno">
                    <div class="form-check form-check-inline m-0">
                        <input class="form-check-input" type="checkbox" id="disc-diseno" onchange="actualizarVista()" style="cursor:pointer;">
                        <label class="form-check-label small fw-bold text-danger" for="disc-diseno" style="cursor:pointer; font-size: 0.75rem;">Aplicar 10% Descuento</label>
                    </div>
                </div>
                <div class="tax-displays">
                    <span class="tax-time" id="time-diseno">00:00</span>
                    <span class="tax-cost" id="cost-diseno">$0.00</span>
                </div>
                <div class="tax-controls d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-pink-start" onclick="manejarTimer('diseno', 'start')" title="Iniciar"><i class="bi bi-play-fill"></i></button>
                        <button class="btn btn-warning text-white" onclick="manejarTimer('diseno', 'pause')" title="Pausar"><i class="bi bi-pause-fill"></i></button>
                        <button class="btn btn-danger" onclick="manejarTimer('diseno', 'stop')" title="Reiniciar"><i class="bi bi-stop-fill"></i></button>
                    </div>
                    <button class="btn btn-success fw-bold" onclick="agregarAlTotal('diseno')"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
                </div>
            </div>

            <!-- PC 1 -->
            <div class="tax-item" id="timer-pc1">
                <div class="tax-title">
                    <span><span class="status-dot" id="dot-pc1"></span>PC 1</span>
                    <button class="btn-secret text-muted" onclick="toggleSecretDisc('pc1')" title="Opciones"><i class="bi bi-gear"></i></button>
                </div>
                <div class="disc-panel" id="panel-disc-pc1">
                    <div class="form-check form-check-inline m-0">
                        <input class="form-check-input" type="checkbox" id="disc-pc1" onchange="actualizarVista()" style="cursor:pointer;">
                        <label class="form-check-label small fw-bold text-danger" for="disc-pc1" style="cursor:pointer; font-size: 0.75rem;">Aplicar 10% Descuento</label>
                    </div>
                </div>
                <div class="tax-displays">
                    <span class="tax-time" id="time-pc1">00:00</span>
                    <span class="tax-cost" id="cost-pc1">$0.00</span>
                </div>
                <div class="tax-controls d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-pink-start" onclick="manejarTimer('pc1', 'start')" title="Iniciar"><i class="bi bi-play-fill"></i></button>
                        <button class="btn btn-warning text-white" onclick="manejarTimer('pc1', 'pause')" title="Pausar"><i class="bi bi-pause-fill"></i></button>
                        <button class="btn btn-danger" onclick="manejarTimer('pc1', 'stop')" title="Reiniciar"><i class="bi bi-stop-fill"></i></button>
                    </div>
                    <button class="btn btn-success fw-bold" onclick="agregarAlTotal('pc1')"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
                </div>
            </div>

            <!-- PC 2 -->
            <div class="tax-item" id="timer-pc2">
                <div class="tax-title">
                    <span><span class="status-dot" id="dot-pc2"></span>PC 2</span>
                    <button class="btn-secret text-muted" onclick="toggleSecretDisc('pc2')" title="Opciones"><i class="bi bi-gear"></i></button>
                </div>
                <div class="disc-panel" id="panel-disc-pc2">
                    <div class="form-check form-check-inline m-0">
                        <input class="form-check-input" type="checkbox" id="disc-pc2" onchange="actualizarVista()" style="cursor:pointer;">
                        <label class="form-check-label small fw-bold text-danger" for="disc-pc2" style="cursor:pointer; font-size: 0.75rem;">Aplicar 10% Descuento</label>
                    </div>
                </div>
                <div class="tax-displays">
                    <span class="tax-time" id="time-pc2">00:00</span>
                    <span class="tax-cost" id="cost-pc2">$0.00</span>
                </div>
                <div class="tax-controls d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-pink-start" onclick="manejarTimer('pc2', 'start')" title="Iniciar"><i class="bi bi-play-fill"></i></button>
                        <button class="btn btn-warning text-white" onclick="manejarTimer('pc2', 'pause')" title="Pausar"><i class="bi bi-pause-fill"></i></button>
                        <button class="btn btn-danger" onclick="manejarTimer('pc2', 'stop')" title="Reiniciar"><i class="bi bi-stop-fill"></i></button>
                    </div>
                    <button class="btn btn-success fw-bold" onclick="agregarAlTotal('pc2')"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
                </div>
            </div>
        </div>

        <!-- VISTA DE HISTORIAL -->
        <div id="view-history" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small text-pink-800"><i class="bi bi-journal-check me-1"></i> Útimos Cobros del Día</strong>
                <button class="btn btn-sm btn-outline-secondary py-0" onclick="toggleHistorial(event)">Volver</button>
            </div>
            <div id="history-list" class="list-group list-group-flush small rounded bg-white border">
                <div class="p-3 text-center text-muted">No hay cobros registrados hoy.</div>
            </div>
        </div>

    </div>
</div>

<script>
const TAX_CONFIG = {
    diseno: { precioMin: <?php echo $precio_diseno; ?>, graciaMin: <?php echo $gracia_diseno; ?> },
    pc1: { precioMin: <?php echo $precio_cyber; ?>, graciaMin: <?php echo $gracia_cyber; ?> },
    pc2: { precioMin: <?php echo $precio_cyber; ?>, graciaMin: <?php echo $gracia_cyber; ?> }
};

let timers = JSON.parse(localStorage.getItem('taximetro_timers')) || {
    diseno: { state: 'stopped', start: 0, elapsed: 0 },
    pc1: { state: 'stopped', start: 0, elapsed: 0 },
    pc2: { state: 'stopped', start: 0, elapsed: 0 }
};

let historialCobros = JSON.parse(localStorage.getItem('taximetro_historial')) || [];

const btnToggle = document.getElementById('tax-header-toggle');
const bodyTax = document.getElementById('tax-body');
const iconToggle = document.getElementById('tax-icon-toggle');

let isTaxOpen = localStorage.getItem('taximetro_open') === 'true';
if(isTaxOpen) {
    bodyTax.style.display = 'block';
    iconToggle.className = 'bi bi-chevron-down';
}

btnToggle.addEventListener('click', (e) => {
    if(e.target.closest('.btn-secret')) return;
    isTaxOpen = !isTaxOpen;
    bodyTax.style.display = isTaxOpen ? 'block' : 'none';
    iconToggle.className = isTaxOpen ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
    localStorage.setItem('taximetro_open', isTaxOpen);
});

function toggleSecretDisc(tipo) {
    const p = document.getElementById(`panel-disc-${tipo}`);
    p.style.display = p.style.display === 'block' ? 'none' : 'block';
}

function toggleHistorial(e) {
    if(e) e.stopPropagation();
    const vTimers = document.getElementById('view-timers');
    const vHist = document.getElementById('view-history');
    
    if (vHist.style.display === 'none') {
        renderHistorial();
        vTimers.style.display = 'none';
        vHist.style.display = 'block';
        if(!isTaxOpen) {
            bodyTax.style.display = 'block';
            isTaxOpen = true;
        }
    } else {
        vTimers.style.display = 'block';
        vHist.style.display = 'none';
    }
}

function guardarTimers() {
    localStorage.setItem('taximetro_timers', JSON.stringify(timers));
}

function manejarTimer(tipo, accion) {
    const ahora = Date.now();
    const t = timers[tipo];

    if (accion === 'start' && t.state !== 'running') {
        t.start = ahora - t.elapsed;
        t.state = 'running';
    } else if (accion === 'pause' && t.state === 'running') {
        t.elapsed = ahora - t.start;
        t.state = 'paused';
    } else if (accion === 'stop') {
        if(!confirm(`¿Desea reiniciar el contador de ${tipo.toUpperCase()}?`)) return;
        t.elapsed = 0;
        t.start = 0;
        t.state = 'stopped';
    }
    
    guardarTimers();
    actualizarVista();
}

function calcularCostoYMinutos(tipo, tiempoTotalMs) {
    const conf = TAX_CONFIG[tipo];
    let minTotales = Math.floor(tiempoTotalMs / 60000);
    
    let minExcedentes = Math.max(0, minTotales - conf.graciaMin);
    let costo = minExcedentes * conf.precioMin;

    const discCheckbox = document.getElementById(`disc-${tipo}`);
    if (discCheckbox && discCheckbox.checked && costo > 0) {
        costo = costo * 0.90;
    }

    return { minTotales, minExcedentes, costo };
}

function actualizarVista() {
    const ahora = Date.now();
    const miniBadgesContainer = document.getElementById('tax-mini-badges');
    miniBadgesContainer.innerHTML = '';

    Object.keys(timers).forEach(tipo => {
        const t = timers[tipo];
        
        let tiempoTotalMs = t.elapsed;
        if (t.state === 'running') {
            tiempoTotalMs = ahora - t.start;
        }

        let segTotales = Math.floor(tiempoTotalMs / 1000);
        let horas = Math.floor(segTotales / 3600);
        let minutos = Math.floor((segTotales % 3600) / 60);
        let segundos = segTotales % 60;
        
        let strHoras = horas.toString().padStart(2, '0');
        let strMin = minutos.toString().padStart(2, '0');
        let strSeg = segundos.toString().padStart(2, '0');
        
        let strTiempo = horas > 0 ? `${strHoras}:${strMin}:${strSeg}` : `${strMin}:${strSeg}`;
        document.getElementById(`time-${tipo}`).innerText = strTiempo;

        const { costo } = calcularCostoYMinutos(tipo, tiempoTotalMs);
        document.getElementById(`cost-${tipo}`).innerText = `$${costo.toFixed(2)}`;

        const dot = document.getElementById(`dot-${tipo}`);
        dot.className = 'status-dot';
        
        if(t.state === 'running') {
            dot.classList.add('status-running');
            
            // LÍNEAS CORTAS (D para Diseño, PC1 y PC2 cortos):
            let nombreTag = tipo === 'diseno' ? 'D' : tipo.toUpperCase();
            let badge = document.createElement('span');
            badge.className = 'tax-badge-item';
            badge.innerText = `${nombreTag}:${strTiempo}($${costo.toFixed(0)})`;
            miniBadgesContainer.appendChild(badge);

        } else if(t.state === 'paused') {
            dot.classList.add('status-paused');
        }
    });
}

function renderHistorial() {
    const list = document.getElementById('history-list');
    list.innerHTML = '';
    
    if (historialCobros.length === 0) {
        list.innerHTML = '<div class="p-3 text-center text-muted">No hay cobros guardados.</div>';
        return;
    }

    historialCobros.slice(-8).reverse().forEach(h => {
        let item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-2';
        item.innerHTML = `
            <div>
                <strong class="d-block text-dark">${h.tipo} - ${h.minutos} min</strong>
                <small class="text-muted">${h.hora}</small>
            </div>
            <span class="fw-bold text-success fs-6">$${h.costo}</span>
        `;
        list.appendChild(item);
    });
}

function agregarAlTotal(tipo) {
    const t = timers[tipo];
    let tiempoTotalMs = t.state === 'running' ? Date.now() - t.start : t.elapsed;
    const { minTotales, costo } = calcularCostoYMinutos(tipo, tiempoTotalMs);

    if (costo <= 0) {
        alert("El costo actual es $0.00 (aún está en tiempo de gracia o en $0).");
        return;
    }

    let inputValorVis = document.getElementById('valorDinero');
    let inputMontoReal = document.getElementById('MontoReal');
    let inputObservaciones = document.getElementById('observaciones');

    let prefijo = tipo === 'diseno' ? 'Diseño' : 'Uso de ' + tipo.toUpperCase();
    let hasDesc = document.getElementById(`disc-${tipo}`)?.checked ? ' (con 10% desc)' : '';

    if (inputValorVis || inputMontoReal) {
        let montoActual = parseFloat(inputMontoReal ? inputMontoReal.value : 0) || 0;
        let nuevoMonto = montoActual + costo;

        if (inputMontoReal) inputMontoReal.value = nuevoMonto.toFixed(2);
        if (inputValorVis) {
            inputValorVis.value = '$' + nuevoMonto.toFixed(2).replace('.', ',');
            if (typeof formatMoney === 'function') formatMoney(inputValorVis);
        }

        if (inputObservaciones) {
            let textoExtra = ` + ${minTotales} min de ${prefijo}${hasDesc}`;
            inputObservaciones.value = inputObservaciones.value ? inputObservaciones.value + textoExtra : textoExtra.trim();
        }

        alert(`¡Éxito! Se sumaron $${costo.toFixed(2)} al valor actual.`);

        // REGISTRAR Y RESETEAR SOLO SI SE INGRESÓ EN LA VENTA
        const ahoraDate = new Date();
        historialCobros.push({
            tipo: prefijo,
            minutos: minTotales,
            costo: costo.toFixed(2),
            hora: ahoraDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
        localStorage.setItem('taximetro_historial', JSON.stringify(historialCobros));

        if (document.getElementById(`disc-${tipo}`)) document.getElementById(`disc-${tipo}`).checked = false;
        document.getElementById(`panel-disc-${tipo}`).style.display = 'none';

        t.elapsed = 0;
        t.start = 0;
        t.state = 'stopped';
        guardarTimers();
        actualizarVista();
    } else {
        // Fuera de la pantalla de venta: solo avisa y mantiene el reloj contando/congelado sin borrar nada
        alert(`Monto calculado: $${costo.toFixed(2)} (${minTotales} min de ${prefijo}). Dirigite a la pantalla de Agregar Venta para sumarlo.`);
    }
}

setInterval(actualizarVista, 1000);
actualizarVista();
</script>