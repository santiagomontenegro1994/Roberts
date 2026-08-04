<?php
// Consultar la configuración del taxímetro
$queryTax = "SELECT * FROM config_taximetro WHERE id_config = 1";
$resTax = mysqli_query($MiConexion, $queryTax);
$configTax = mysqli_fetch_assoc($resTax);

$precio_diseno = $configTax['precio_min_diseno'] ?? 0;
$precio_cyber = $configTax['precio_min_cyber'] ?? 0;
$gracia_diseno = $configTax['gracia_diseno'] ?? 0;
$gracia_cyber = $configTax['gracia_cyber'] ?? 0;
?>

<!-- ESTILOS DEL WIDGET FLOTANTE -->
<style>
    #widget-taximetro {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 9999;
        font-family: 'Open Sans', sans-serif;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .tax-header {
        background-color: #0d6efd;
        color: white;
        padding: 10px 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
    }
    .tax-header:hover { background-color: #0b5ed7; }
    .tax-body {
        padding: 15px;
        display: none; /* Oculto por defecto (minimizado) */
    }
    .tax-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    .tax-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .tax-title { font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; color: #333; }
    .tax-displays { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .tax-time { font-family: monospace; font-size: 1.2rem; font-weight: bold; color: #555; }
    .tax-cost { font-size: 1.1rem; font-weight: bold; color: #198754; }
    .tax-controls button { padding: 2px 8px; font-size: 0.8rem; margin-right: 2px; }
    .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: #ccc; margin-right: 5px; }
    .status-running { background-color: #198754; animation: blink 1s infinite; }
    .status-paused { background-color: #ffc107; }
    
    @keyframes blink { 50% { opacity: 0.5; } }
</style>

<div id="widget-taximetro">
    <div class="tax-header" id="tax-header-toggle">
        <span><i class="bi bi-stopwatch"></i> Taxímetro / Ciber</span>
        <i class="bi bi-chevron-up" id="tax-icon-toggle"></i>
    </div>
    
    <div class="tax-body" id="tax-body">
        <!-- Diseño / Acomodo -->
        <div class="tax-item" id="timer-diseno">
            <div class="tax-title"><span class="status-dot" id="dot-diseno"></span> Diseño / Acomodo</div>
            <div class="tax-displays">
                <span class="tax-time" id="time-diseno">00:00:00</span>
                <span class="tax-cost" id="cost-diseno">$0.00</span>
            </div>
            <div class="tax-controls">
                <button class="btn btn-primary" onclick="manejarTimer('diseno', 'start')"><i class="bi bi-play-fill"></i></button>
                <button class="btn btn-warning" onclick="manejarTimer('diseno', 'pause')"><i class="bi bi-pause-fill"></i></button>
                <button class="btn btn-danger" onclick="manejarTimer('diseno', 'stop')"><i class="bi bi-stop-fill"></i></button>
                <button class="btn btn-success float-end" onclick="agregarAlTotal('diseno')"><i class="bi bi-plus-circle"></i> Agregar</button>
            </div>
        </div>

        <!-- PC 1 -->
        <div class="tax-item" id="timer-pc1">
            <div class="tax-title"><span class="status-dot" id="dot-pc1"></span> PC 1</div>
            <div class="tax-displays">
                <span class="tax-time" id="time-pc1">00:00:00</span>
                <span class="tax-cost" id="cost-pc1">$0.00</span>
            </div>
            <div class="tax-controls">
                <button class="btn btn-primary" onclick="manejarTimer('pc1', 'start')"><i class="bi bi-play-fill"></i></button>
                <button class="btn btn-warning" onclick="manejarTimer('pc1', 'pause')"><i class="bi bi-pause-fill"></i></button>
                <button class="btn btn-danger" onclick="manejarTimer('pc1', 'stop')"><i class="bi bi-stop-fill"></i></button>
                <button class="btn btn-success float-end" onclick="agregarAlTotal('pc1')"><i class="bi bi-plus-circle"></i> Agregar</button>
            </div>
        </div>

        <!-- PC 2 -->
        <div class="tax-item" id="timer-pc2">
            <div class="tax-title"><span class="status-dot" id="dot-pc2"></span> PC 2</div>
            <div class="tax-displays">
                <span class="tax-time" id="time-pc2">00:00:00</span>
                <span class="tax-cost" id="cost-pc2">$0.00</span>
            </div>
            <div class="tax-controls">
                <button class="btn btn-primary" onclick="manejarTimer('pc2', 'start')"><i class="bi bi-play-fill"></i></button>
                <button class="btn btn-warning" onclick="manejarTimer('pc2', 'pause')"><i class="bi bi-pause-fill"></i></button>
                <button class="btn btn-danger" onclick="manejarTimer('pc2', 'stop')"><i class="bi bi-stop-fill"></i></button>
                <button class="btn btn-success float-end" onclick="agregarAlTotal('pc2')"><i class="bi bi-plus-circle"></i> Agregar</button>
            </div>
        </div>
    </div>
</div>

<script>
// CONFIGURACIÓN DESDE PHP
const TAX_CONFIG = {
    diseno: { precioMin: <?php echo $precio_diseno; ?>, graciaMin: <?php echo $gracia_diseno; ?> },
    pc1: { precioMin: <?php echo $precio_cyber; ?>, graciaMin: <?php echo $gracia_cyber; ?> },
    pc2: { precioMin: <?php echo $precio_cyber; ?>, graciaMin: <?php echo $gracia_cyber; ?> }
};

// ESTADO INICIAL DE LOS TIMERS (Buscamos si ya hay algo en la memoria del navegador)
let timers = JSON.parse(localStorage.getItem('taximetro_timers')) || {
    diseno: { state: 'stopped', start: 0, elapsed: 0 },
    pc1: { state: 'stopped', start: 0, elapsed: 0 },
    pc2: { state: 'stopped', start: 0, elapsed: 0 }
};

// Toggle Maximizar/Minimizar
const btnToggle = document.getElementById('tax-header-toggle');
const bodyTax = document.getElementById('tax-body');
const iconToggle = document.getElementById('tax-icon-toggle');

// Leer preferencia de abierto/cerrado de memoria
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

// Guardar en memoria
function guardarTimers() {
    localStorage.setItem('taximetro_timers', JSON.stringify(timers));
}

// Lógica de botones
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
        if(!confirm(`¿Estás seguro de reiniciar el contador de ${tipo.toUpperCase()}?`)) return;
        t.elapsed = 0;
        t.start = 0;
        t.state = 'stopped';
    }
    
    guardarTimers();
    actualizarVista();
}

// Bucle visual (se ejecuta 1 vez por segundo)
function actualizarVista() {
    const ahora = Date.now();
    
    Object.keys(timers).forEach(tipo => {
        const t = timers[tipo];
        const conf = TAX_CONFIG[tipo];
        
        let tiempoTotalMs = t.elapsed;
        if (t.state === 'running') {
            tiempoTotalMs = ahora - t.start;
        }

        // Calcular Horas, Minutos, Segundos
        let segTotales = Math.floor(tiempoTotalMs / 1000);
        let horas = Math.floor(segTotales / 3600);
        let minutos = Math.floor((segTotales % 3600) / 60);
        let segundos = segTotales % 60;
        
        // Formatear texto (ej: 00:05:03)
        let strHoras = horas.toString().padStart(2, '0');
        let strMin = minutos.toString().padStart(2, '0');
        let strSeg = segundos.toString().padStart(2, '0');
        document.getElementById(`time-${tipo}`).innerText = `${strHoras}:${strMin}:${strSeg}`;

        // Calcular costo
        let minTotales = Math.floor(tiempoTotalMs / 60000);
        let costo = 0;
        if (minTotales >= conf.graciaMin) {
            costo = minTotales * conf.precioMin;
        }
        document.getElementById(`cost-${tipo}`).innerText = `$${costo.toFixed(2)}`;

        // Actualizar el puntito de estado
        const dot = document.getElementById(`dot-${tipo}`);
        dot.className = 'status-dot';
        if(t.state === 'running') dot.classList.add('status-running');
        if(t.state === 'paused') dot.classList.add('status-paused');
    });
}

// Función para inyectar la plata en el formulario de ventas
function agregarAlTotal(tipo) {
    const t = timers[tipo];
    const minTotales = Math.floor((t.state === 'running' ? Date.now() - t.start : t.elapsed) / 60000);
    const costo = minTotales >= TAX_CONFIG[tipo].graciaMin ? minTotales * TAX_CONFIG[tipo].precioMin : 0;

    if (costo <= 0) {
        alert("El costo actual es $0 (o está en tiempo de gracia). No hay nada que sumar.");
        return;
    }

    // BUSCAR CAMPOS EN LA PÁGINA (Ajustá estos selectores según cómo se llamen tus inputs en Agregar Venta)
    // Busca inputs que se llamen 'monto', 'precio', 'total', etc.
    const inputMonto = document.querySelector('input[name="monto"], input#monto, input#precio, input[name="precio"]');
    const inputDetalle = document.querySelector('input[name="detalle"], input#detalle, textarea#detalle, input[name="observaciones"]');

    if (inputMonto) {
        let montoActual = parseFloat(inputMonto.value) || 0;
        inputMonto.value = (montoActual + costo).toFixed(2);
        
        if (inputDetalle) {
            let prefijo = tipo === 'diseno' ? 'Diseño' : 'Uso de ' + tipo.toUpperCase();
            let textoExtra = ` + ${minTotales} min de ${prefijo}`;
            inputDetalle.value = inputDetalle.value + textoExtra;
        }
        
        alert(`Se han sumado $${costo} al total con éxito.`);
        manejarTimer(tipo, 'stop'); // Lo reinicia después de cobrar
    } else {
        alert(`El costo es $${costo}. (No se encontró un campo de precio en esta pantalla para sumarlo automáticamente)`);
    }
}

// Iniciar el reloj
setInterval(actualizarVista, 1000);
actualizarVista();
</script>