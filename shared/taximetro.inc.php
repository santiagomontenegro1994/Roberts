<?php
// Obtener ID del usuario activo en sesión
$id_usuario_actual = $_SESSION['Usuario_Id'] ?? 0;

// VALIDADOR AJAX (Procesa guardado y lectura de la BD sin recargar página)
if (isset($_REQUEST['tax_action'])) {
    if (ob_get_length()) {
        @ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($MiConexion) || !$MiConexion) {
        echo json_encode(['success' => false, 'error' => 'No hay conexión a la base de datos']);
        exit;
    }
    
    // Accion: Guardar nuevo cobro en MySQL
    if ($_REQUEST['tax_action'] === 'guardar') {
        $tipo = mysqli_real_escape_string($MiConexion, $_POST['tipo'] ?? '');
        $minutos = (int)($_POST['minutos'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);
        $descuento = (int)($_POST['descuento'] ?? 0);
        
        if (!empty($tipo) && $costo > 0 && $id_usuario_actual > 0) {
            $sqlInsert = "INSERT INTO historial_taximetro (id_usuario, tipo_servicio, minutos_totales, monto_cobrado, con_descuento, fecha_hora) 
                          VALUES ($id_usuario_actual, '$tipo', $minutos, $costo, $descuento, NOW())";
            $status = @mysqli_query($MiConexion, $sqlInsert);
            echo json_encode(['success' => (bool)$status]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Datos inválidos o sesión no iniciada']);
        }
        exit;
    }

    // Accion: Consultar cobros del día para el usuario logueado
    if ($_REQUEST['tax_action'] === 'obtener') {
        $sqlObtener = "SELECT tipo_servicio, minutos_totales, monto_cobrado, DATE_FORMAT(fecha_hora, '%H:%i') AS hora 
                       FROM historial_taximetro 
                       WHERE id_usuario = $id_usuario_actual AND DATE(fecha_hora) = CURDATE() 
                       ORDER BY id_cobro DESC LIMIT 10";
        $resHist = @mysqli_query($MiConexion, $sqlObtener);
        $cobros = [];
        if ($resHist) {
            while ($row = mysqli_fetch_assoc($resHist)) {
                $cobros[] = [
                    'tipo' => $row['tipo_servicio'],
                    'minutos' => $row['minutos_totales'],
                    'costo' => $row['monto_cobrado'],
                    'hora' => $row['hora']
                ];
            }
        }
        echo json_encode(['success' => true, 'data' => $cobros]);
        exit;
    }
}

// Valores por defecto para la configuracion de precios
$precio_diseno = 0;
$precio_cyber  = 0;
$gracia_diseno = 0;
$gracia_cyber  = 0;

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

    <!-- ESTILOS MINIMALISTAS (CÁPSULA -> EXPANDIBLE) -->
<style>
    #widget-taximetro {
        position: fixed;
        bottom: 15px;
        right: 70px;
        width: auto; /* Se ajusta automáticamente al contenido */
        max-width: 340px;
        background-color: #ffffff;
        border-radius: 20px; /* Forma de cápsula/botón */
        box-shadow: 0 4px 15px rgba(0,0,0,0.18);
        z-index: 9999;
        font-family: 'Open Sans', system-ui, -apple-system, sans-serif;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        border: 1px solid rgba(190, 24, 93, 0.2);
    }
    
    /* Al abrirse expande el ancho normal */
    #widget-taximetro.is-open {
        width: 360px;
        max-width: 360px;
        border-radius: 12px;
    }

    .tax-header {
        background: linear-gradient(135deg, #be185d 0%, #9d174d 100%);
        color: white;
        padding: 4px 10px;
        cursor: pointer;
        display: flex;
        flex-direction: row; /* Horizontal: ya no choca hacia arriba */
        align-items: center;
        gap: 6px;
        user-select: none;
        height: 36px; /* Altura ultra delgada fija */
    }
    .tax-header:hover { opacity: 0.96; }

    .tax-title-text {
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .tax-badges-container {
        display: flex;
        flex-direction: row;
        gap: 4px;
        align-items: center;
    }
    
    #widget-taximetro.is-open .tax-badges-container {
        display: none !important;
    }

    .tax-badge-item {
        background-color: rgba(255, 255, 255, 0.22);
        color: #ffffff;
        font-size: 0.62rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 10px;
        white-space: nowrap;
    }

    .tax-header-controls {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tax-body {
        padding: 8px;
        display: none;
        background-color: #fdf2f8;
        max-height: 80vh;
        overflow-y: auto;
    }
    .tax-item {
        background: #ffffff;
        border: 1px solid #fbcfe8;
        border-radius: 8px;
        padding: 6px 10px;
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

    /* Oculta el botón de historial mientras esté minimizado */
    #widget-taximetro:not(.is-open) .tax-header-controls .btn-secret {
        display: none !important;
    }

    /* Oculta la palabra "Tiempo" cuando está minimizado */
    #widget-taximetro:not(.is-open) .tax-title-text {
        display: none !important;
    }
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

const widgetEl = document.getElementById('widget-taximetro');
const btnToggle = document.getElementById('tax-header-toggle');
const bodyTax = document.getElementById('tax-body');
const iconToggle = document.getElementById('tax-icon-toggle');

let isTaxOpen = localStorage.getItem('taximetro_open') === 'true';
if(isTaxOpen) {
    bodyTax.style.display = 'block';
    widgetEl.classList.add('is-open');
    iconToggle.className = 'bi bi-chevron-down';
}

btnToggle.addEventListener('click', (e) => {
    if(e.target.closest('.btn-secret')) return;
    isTaxOpen = !isTaxOpen;
    bodyTax.style.display = isTaxOpen ? 'block' : 'none';
    
    if(isTaxOpen) {
        widgetEl.classList.add('is-open');
        iconToggle.className = 'bi bi-chevron-down';
    } else {
        widgetEl.classList.remove('is-open');
        iconToggle.className = 'bi bi-chevron-up';
    }
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
            widgetEl.classList.add('is-open');
            iconToggle.className = 'bi bi-chevron-down'; // Giro visual de la flechita
            isTaxOpen = true;
            localStorage.setItem('taximetro_open', true);
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
            let iconoTag = tipo === 'diseno' ? '🎨' : (tipo === 'pc1' ? '💻1' : '💻2');
            let badge = document.createElement('span');
            badge.className = 'tax-badge-item';
            badge.innerText = `${iconoTag} ${strTiempo} ($${costo.toFixed(0)})`;
            miniBadgesContainer.appendChild(badge);

        } else if(t.state === 'paused') {
            dot.classList.add('status-paused');
        }
    });
    if (miniBadgesContainer.children.length === 0) {
        miniBadgesContainer.style.display = 'none';
    } else {
        miniBadgesContainer.style.display = 'flex';
    }
}

function renderHistorial() {
    const list = document.getElementById('history-list');
    list.innerHTML = '<div class="p-3 text-center text-muted"><span class="spinner-border spinner-border-sm me-1"></span> Cargando cobros...</div>';

    fetch('?tax_action=obtener')
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                // Si PHP devuelve un error o HTML en vez de JSON, lo muestra en pantalla
                throw new Error("El servidor no devolvió un JSON válido. Respuesta: " + text);
            }
        })
        .then(res => {
            list.innerHTML = '';
            if (!res.success) {
                list.innerHTML = `<div class="p-3 text-center text-danger small">Error del servidor: ${res.error || 'Desconocido'}</div>`;
                return;
            }
            if (!res.data || res.data.length === 0) {
                list.innerHTML = '<div class="p-3 text-center text-muted">No hay cobros registrados hoy.</div>';
                return;
            }

            res.data.forEach(h => {
                let item = document.createElement('div');
                item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-2';
                item.innerHTML = `
                    <div>
                        <strong class="d-block text-dark">${h.tipo} - ${h.minutos} min</strong>
                        <small class="text-muted">${h.hora} hs</small>
                    </div>
                    <span class="fw-bold text-success fs-6">$${parseFloat(h.costo).toFixed(2)}</span>
                `;
                list.appendChild(item);
            });
        })
        .catch(err => {
            list.innerHTML = `<div class="p-3 text-center text-danger small" style="word-break: break-all; max-height: 150px; overflow-y: auto;">${err.message}</div>`;
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
    let esDescuento = document.getElementById(`disc-${tipo}`)?.checked ? 1 : 0;
    let hasDesc = esDescuento ? ' (con 10% desc)' : '';

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

        // GUARDAR REGISTRO EN BASE DE DATOS PARA MÉTRICAS
        fetch('?tax_action=guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'tipo': prefijo,
                'minutos': minTotales,
                'costo': costo.toFixed(2),
                'descuento': esDescuento
            })
        });

        alert(`¡Éxito! Se sumaron $${costo.toFixed(2)} al valor actual.`);

        if (document.getElementById(`disc-${tipo}`)) document.getElementById(`disc-${tipo}`).checked = false;
        document.getElementById(`panel-disc-${tipo}`).style.display = 'none';

        t.elapsed = 0;
        t.start = 0;
        t.state = 'stopped';
        guardarTimers();
        actualizarVista();
    } else {
        alert(`Monto calculado: $${costo.toFixed(2)} (${minTotales} min de ${prefijo}). Dirigite a la pantalla de Agregar Venta para sumarlo.`);
    }
}

setInterval(actualizarVista, 1000);
actualizarVista();
</script>