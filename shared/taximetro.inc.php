<?php
$precio_diseno = 0;
$precio_cyber = 0;
$gracia_diseno = 0;
$gracia_cyber = 0;

if (isset($MiConexion) && $MiConexion) {
    $queryTax = "SELECT * FROM config_taximetro WHERE id_config = 1";
    $resTax = @mysqli_query($MiConexion, $queryTax);
    
    if ($resTax && mysqli_num_rows($resTax) > 0) {
        $configTax = mysqli_fetch_assoc($resTax);
        $precio_diseno = $configTax['precio_min_diseno'] ?? 0;
        $precio_cyber = $configTax['precio_min_cyber'] ?? 0;
        $gracia_diseno = $configTax['gracia_diseno'] ?? 0;
        $gracia_cyber = $configTax['gracia_cyber'] ?? 0;
    }
}
?>

<!-- ESTILOS Y DISEÑO MEJORADO -->
<style>
    #widget-taximetro {
        position: fixed;
        bottom: 20px;
        right: 35px; /* Movido 15px a la izquierda */
        width: 360px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        z-index: 9999;
        font-family: 'Open Sans', sans-serif;
        transition: all 0.3s ease;
        overflow: hidden;
        border: 1px solid #0d6efd;
    }
    .tax-header {
        background-color: #0d6efd; /* Mismo color que botón iniciar */
        color: white;
        padding: 10px 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        user-select: none;
    }
    .tax-header:hover { background-color: #0b5ed7; }
    .tax-header-info {
        font-size: 0.8rem;
        background: rgba(255,255,255,0.2);
        padding: 2px 8px;
        border-radius: 12px;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tax-body {
        padding: 15px;
        display: none;
        background-color: #f8f9fa;
    }
    .tax-item {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .tax-item:last-child { margin-bottom: 0; }
    .tax-title { font-weight: bold; font-size: 0.9rem; color: #333; display: flex; align-items: center; justify-content: space-between; }
    .tax-displays { display: flex; justify-content: space-between; align-items: center; margin: 5px 0; }
    .tax-time { font-family: 'Courier New', Courier, monospace; font-size: 1.25rem; font-weight: bold; color: #212529; }
    .tax-cost { font-size: 1.15rem; font-weight: bold; color: #198754; }
    .tax-controls button { padding: 3px 9px; font-size: 0.82rem; margin-right: 2px; }
    .status-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background-color: #adb5bd; margin-right: 6px; }
    .status-running { background-color: #198754; animation: blink 1s infinite; }
    .status-paused { background-color: #ffc107; }
    
    @keyframes blink { 50% { opacity: 0.4; } }
</style>

<div id="widget-taximetro">
    <div class="tax-header" id="tax-header-toggle">
        <div class="d-flex align-items-center">
            <i class="bi bi-stopwatch me-1"></i> Taxímetro
            <span id="tax-mini-summary" class="tax-header-info ms-2" style="display: none;"></span>
        </div>
        <i class="bi bi-chevron-up" id="tax-icon-toggle"></i>
    </div>
    
    <div class="tax-body" id="tax-body">
        <!-- Diseño / Acomodo -->
        <div class="tax-item" id="timer-diseno">
            <div class="tax-title">
                <span><span class="status-dot" id="dot-diseno"></span>Diseño / Acomodo</span>
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="disc-diseno" onchange="actualizarVista()" style="cursor:pointer;">
                    <label class="form-check-label small text-muted" for="disc-diseno" style="cursor:pointer; font-size: 0.75rem;">10% desc.</label>
                </div>
            </div>
            <div class="tax-displays">
                <span class="tax-time" id="time-diseno">00:00:00</span>
                <span class="tax-cost" id="cost-diseno">$0.00</span>
            </div>
            <div class="tax-controls">
                <button class="btn btn-primary" onclick="manejarTimer('diseno', 'start')" title="Iniciar/Continuar"><i class="bi bi-play-fill"></i></button>
                <button class="btn btn-warning text-white" onclick="manejarTimer('diseno', 'pause')" title="Pausar"><i class="bi bi-pause-fill"></i></button>
                <button class="btn btn-danger" onclick="manejarTimer('diseno', 'stop')" title="Reiniciar"><i class="bi bi-stop-fill"></i></button>
                <button class="btn btn-success float-end fw-bold" onclick="agregarAlTotal('diseno')"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
            </div>
        </div>

        <!-- PC 1 -->
        <div class="tax-item" id="timer-pc1">
            <div class="tax-title">
                <span><span class="status-dot" id="dot-pc1"></span>PC 1</span>
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="disc-pc1" onchange="actualizarVista()" style="cursor:pointer;">
                    <label class="form-check-label small text-muted" for="disc-pc1" style="cursor:pointer; font-size: 0.75rem;">10% desc.</label>
                </div>
            </div>
            <div class="tax-displays">
                <span class="tax-time" id="time-pc1">00:00:00</span>
                <span class="tax-cost" id="cost-pc1">$0.00</span>
            </div>
            <div class="tax-controls">
                <button class="btn btn-primary" onclick="manejarTimer('pc1', 'start')" title="Iniciar/Continuar"><i class="bi bi-play-fill"></i></button>
                <button class="btn btn-warning text-white" onclick="manejarTimer('pc1', 'pause')" title="Pausar"><i class="bi bi-pause-fill"></i></button>
                <button class="btn btn-danger" onclick="manejarTimer('pc1', 'stop')" title="Reiniciar"><i class="bi bi-stop-fill"></i></button>
                <button class="btn btn-success float-end fw-bold" onclick="agregarAlTotal('pc1')"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
            </div>
        </div>

        <!-- PC 2 -->
        <div class="tax-item" id="timer-pc2">
            <div class="tax-title">
                <span><span class="status-dot" id="dot-pc2"></span>PC 2</span>
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="disc-pc2" onchange="actualizarVista()" style="cursor:pointer;">
                    <label class="form-check-label small text-muted" for="disc-pc2" style="cursor:pointer; font-size: 0.75rem;">10% desc.</label>
                </div>
            </div>
            <div class="tax-displays">
                <span class="tax-time" id="time-pc2">00:00:00</span>
                <span class="tax-cost" id="cost-pc2">$0.00</span>
            </div>
            <div class="tax-controls">
                <button class="btn btn-primary" onclick="manejarTimer('pc2', 'start')" title="Iniciar/Continuar"><i class="bi bi-play-fill"></i></button>
                <button class="btn btn-warning text-white" onclick="manejarTimer('pc2', 'pause')" title="Pausar"><i class="bi bi-pause-fill"></i></button>
                <button class="btn btn-danger" onclick="manejarTimer('pc2', 'stop')" title="Reiniciar"><i class="bi bi-stop-fill"></i></button>
                <button class="btn btn-success float-end fw-bold" onclick="agregarAlTotal('pc2')"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
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

// Control Maximizado / Minimizado
const btnToggle = document.getElementById('tax-header-toggle');
const bodyTax = document.getElementById('tax-body');
const iconToggle = document.getElementById('tax-icon-toggle');

let isTaxOpen = localStorage.getItem('taximetro_open') === 'true';
if(isTaxOpen) {
    bodyTax.style.display = 'block';
    iconToggle.className = 'bi bi-chevron-down';
}

btnToggle.addEventListener('click', () => {
    isTaxOpen = !isTaxOpen;
    bodyTax.style.display = isTaxOpen ? 'block' : 'none';
    iconToggle.className = isTaxOpen ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
    localStorage.setItem('taximetro_open', isTaxOpen);
});

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
    
    // SÓLO SE COBRAN LOS MINUTOS EXCEDENTES
    let minExcedentes = Math.max(0, minTotales - conf.graciaMin);
    let costo = minExcedentes * conf.precioMin;

    // Aplicar 10% de descuento si está tildado el checkbox
    const discCheckbox = document.getElementById(`disc-${tipo}`);
    if (discCheckbox && discCheckbox.checked && costo > 0) {
        costo = costo * 0.90;
    }

    return { minTotales, minExcedentes, costo };
}

function actualizarVista() {
    const ahora = Date.now();
    let resumenActivos = [];

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

        // Obtener costo procesado
        const { costo } = calcularCostoYMinutos(tipo, tiempoTotalMs);
        document.getElementById(`cost-${tipo}`).innerText = `$${costo.toFixed(2)}`;

        // Actualizar estados visuales
        const dot = document.getElementById(`dot-${tipo}`);
        dot.className = 'status-dot';
        if(t.state === 'running') {
            dot.classList.add('status-running');
            let nombreTag = tipo === 'diseno' ? 'Diseño' : tipo.toUpperCase();
            resumenActivos.push(`${nombreTag}: ${strTiempo} ($${costo.toFixed(0)})`);
        } else if(t.state === 'paused') {
            dot.classList.add('status-paused');
        }
    });

    // Actualizar resumen en la barra azul minimizada
    const elSummary = document.getElementById('tax-mini-summary');
    if (resumenActivos.length > 0) {
        elSummary.innerText = resumenActivos.join(' | ');
        elSummary.style.display = 'inline-block';
    } else {
        elSummary.style.display = 'none';
        elSummary.innerText = '';
    }
}

// Inyección inteligente compatible con tu agregar_venta.php
function agregarAlTotal(tipo) {
    const t = timers[tipo];
    let tiempoTotalMs = t.state === 'running' ? Date.now() - t.start : t.elapsed;
    const { minTotales, costo } = calcularCostoYMinutos(tipo, tiempoTotalMs);

    if (costo <= 0) {
        alert("El costo actual es $0.00 (aún está en tiempo de gracia o en $0).");
        return;
    }

    // Identificamos tus campos específicos de agregar_venta.php
    let inputValorVis = document.getElementById('valorDinero');
    let inputMontoReal = document.getElementById('MontoReal');
    let inputObservaciones = document.getElementById('observaciones');

    if (inputValorVis || inputMontoReal) {
        // Obtenemos el monto numérico real actual
        let montoActual = parseFloat(inputMontoReal ? inputMontoReal.value : 0) || 0;
        let nuevoMonto = montoActual + costo;

        // Inyectamos en ambos campos
        if (inputMontoReal) inputMontoReal.value = nuevoMonto.toFixed(2);
        if (inputValorVis) {
            inputValorVis.value = '$' + nuevoMonto.toFixed(2).replace('.', ',');
            // Si la función formatMoney existe en la pantalla, la ejecutamos para dar formato
            if (typeof formatMoney === 'function') {
                formatMoney(inputValorVis);
            }
        }

        // Anotamos en las observaciones
        if (inputObservaciones) {
            let prefijo = tipo === 'diseno' ? 'Diseño' : 'Uso de ' + tipo.toUpperCase();
            let hasDesc = document.getElementById(`disc-${tipo}`)?.checked ? ' (con 10% desc)' : '';
            let textoExtra = ` + ${minTotales} min de ${prefijo}${hasDesc}`;
            inputObservaciones.value = inputObservaciones.value ? inputObservaciones.value + textoExtra : textoExtra.trim();
        }

        alert(`¡Éxito! Se sumaron $${costo.toFixed(2)} al valor actual.`);

        // Desmarcamos el descuento y reiniciamos el contador cobrado
        if (document.getElementById(`disc-${tipo}`)) document.getElementById(`disc-${tipo}`).checked = false;

        t.elapsed = 0;
        t.start = 0;
        t.state = 'stopped';
        guardarTimers();
        actualizarVista();
    } else {
        // Si no está en agregar_venta.php, avisa el monto calculado
        alert(`El valor a cobrar es $${costo.toFixed(2)} (${minTotales} min), pero no te encontrás en la pantalla de Agregar Venta.`);
    }
}

setInterval(actualizarVista, 1000);
actualizarVista();
</script>