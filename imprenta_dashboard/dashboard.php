<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
?>

<style>
    /* CONTROLES DE ALTURA PARA LOS GRÁFICOS (Evita que se aplasten en celular) */
    .chart-wrapper {
        position: relative;
        width: 100%;
    }
    
    .chart-separated {
        height: 250px; /* Altura para PC de los separados */
    }
    
    .chart-combined {
        height: 380px; /* Altura para PC del combinado (bien amplio) */
    }

    /* Ajuste para celulares */
    @media (max-width: 768px) {
        .chart-separated {
            height: 220px;
        }
        .chart-combined {
            height: 320px; /* Le damos buena altura en celular para que no se vea aplastado */
        }
    }
</style>

<main id="main" class="main">
    <div class="container-fluid px-4">
<<<<<<< HEAD
        <h1 class="mt-4">Dashboard Estratégico A</h1>
=======
        <h1 class="mt-4">Dashboard Estratégico ASD</h1>
>>>>>>> 2006941cd8b3f1a3279d43288fe4aec9234462db
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">Análisis de Crecimiento y Rentabilidad</li>
        </ol>

        <div class="card shadow mb-4 border-0">
            <div class="card-body bg-light rounded">
                <form class="row g-3 align-items-end" id="formFiltros">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">Fecha Inicio</label>
                        <input type="date" class="form-control shadow-sm" id="fechaInicio" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">Fecha Fin</label>
                        <input type="date" class="form-control shadow-sm" id="fechaFin" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-primary shadow-sm fw-bold">
                            <i class="bi bi-funnel-fill me-1"></i> Aplicar Rango
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card border-start border-4 border-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Ventas Totales</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800" id="totVentas">$0</div>
                            </div>
                            <div class="col-auto"><i class="bi bi-graph-up-arrow fs-1 text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card border-start border-4 border-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Salidas Totales</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800" id="totGastos">$0</div>
                            </div>
                            <div class="col-auto"><i class="bi bi-graph-down-arrow fs-1 text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card border-start border-4 border-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ganancia Neta</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800" id="totGanancia">$0</div>
                            </div>
                            <div class="col-auto"><i class="bi bi-cash-stack fs-1 text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-dark shadow px-4 py-2 fw-bold rounded-pill text-uppercase" id="btnToggleChart" onclick="toggleChartMode(event)" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                <i class="bi bi-layer-forward me-2 fs-5 align-middle"></i> Comparar en un solo gráfico
            </button>
        </div>

        <div class="row mb-4" id="contenedorSeparados">
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white fw-bold text-primary">
                        <i class="bi bi-activity me-1"></i> <span id="tituloChartVentas">Evolución de Ventas</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper chart-separated">
                            <canvas id="chartVentas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white fw-bold text-danger">
                        <i class="bi bi-activity me-1"></i> <span id="tituloChartGastos">Evolución de Salidas</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper chart-separated">
                            <canvas id="chartGastos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 d-none" id="contenedorCombinado">
            <div class="col-12">
                <div class="card shadow h-100">
                    <div class="card-header bg-white fw-bold text-dark">
                        <i class="bi bi-bar-chart-fill me-1"></i> <span id="tituloChartCombinado">Comparativa de Ventas vs Salidas</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper chart-combined">
                            <canvas id="chartCombinado"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-list-check me-1"></i> Desglose de Ingresos
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="listaRubrosVentas">
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="bi bi-list-check me-1"></i> Desglose de Salidas <span class="small fw-normal">(% sobre ventas)</span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="listaRubrosGastos">
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
const API_URL = 'procesar_dashboard.php';
const dinero = (valor) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(valor);

let chartV = null;
let chartG = null;
let chartC = null; 
let modoCombinado = false; 

function formatearFecha(fechaStr, isMensual) {
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const partes = fechaStr.split('-');
    if(partes.length !== 3) return fechaStr;
    
    if (isMensual) return `${meses[parseInt(partes[1])-1]} ${partes[0]}`;
    return `${partes[2]} ${meses[parseInt(partes[1])-1]}`;
}

function renderizarLista(idLista, datos, claseBadge) {
    const lista = document.getElementById(idLista);
    lista.innerHTML = '';
    if (datos && datos.length > 0) {
        datos.forEach(item => {
            lista.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark">${item.concepto}</span>
                    <div>
                        <span class="text-muted small me-2 fw-bold">${item.porcentaje}</span>
                        <span class="badge ${claseBadge} rounded-pill fs-6 shadow-sm">${dinero(item.monto)}</span>
                    </div>
                </li>
            `;
        });
    } else {
        lista.innerHTML = '<li class="list-group-item text-center text-muted py-4">No hay movimientos en este período</li>';
    }
}

// Función actualizada
function toggleChartMode(e) {
    e.preventDefault();
    modoCombinado = !modoCombinado;
    const btn = document.getElementById('btnToggleChart');
    
    if (modoCombinado) {
        document.getElementById('contenedorSeparados').classList.add('d-none');
        document.getElementById('contenedorCombinado').classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-grid-1x2 me-2 fs-5 align-middle"></i> Ver gráficos separados';
        btn.className = 'btn btn-primary shadow px-4 py-2 fw-bold rounded-pill text-uppercase';
        
        // Forzamos al gráfico a actualizarse para eliminar cualquier resize residual
        if(chartC) chartC.update('none'); 
    } else {
        document.getElementById('contenedorCombinado').classList.add('d-none');
        document.getElementById('contenedorSeparados').classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-layer-forward me-2 fs-5 align-middle"></i> Comparar en un solo gráfico';
        btn.className = 'btn btn-dark shadow px-4 py-2 fw-bold rounded-pill text-uppercase';
        
        if(chartV) chartV.update('none');
        if(chartG) chartG.update('none');
    }
}

async function cargarDatos(e) {
    if(e) e.preventDefault();
    
    const btnSubmit = document.querySelector('#formFiltros button[type="submit"]');
    const txtOriginal = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...';
    btnSubmit.disabled = true;

    const fInicio = document.getElementById('fechaInicio').value;
    const fFin = document.getElementById('fechaFin').value;

    try {
        const response = await fetch(`${API_URL}?fechaInicio=${fInicio}&fechaFin=${fFin}`);
        const data = await response.json();

        if (!data.ok) {
            alert("Error: " + data.msg);
            return;
        }

        document.getElementById('totVentas').innerText = dinero(data.totales.ingresos);
        document.getElementById('totGastos').innerText = dinero(data.totales.gastos);
        
        const gNeta = data.totales.ganancia;
        const elGanancia = document.getElementById('totGanancia');
        elGanancia.innerText = dinero(gNeta);
        elGanancia.className = gNeta >= 0 ? 'h3 mb-0 font-weight-bold text-success' : 'h3 mb-0 font-weight-bold text-danger';

        renderizarLista('listaRubrosVentas', data.rubros.ingresos, 'bg-primary');
        renderizarLista('listaRubrosGastos', data.rubros.gastos, 'bg-danger');

        let txtPeriodo = "Diarias";
        if(data.agrupado_mensual) txtPeriodo = "Mensuales";
        else if(data.agrupado_semanal) txtPeriodo = "Semanales";

        document.getElementById('tituloChartVentas').innerText = `Evolución de Ventas (${txtPeriodo})`;
        document.getElementById('tituloChartGastos').innerText = `Evolución de Salidas (${txtPeriodo})`;
        document.getElementById('tituloChartCombinado').innerText = `Comparativa Ventas vs Salidas (${txtPeriodo})`;

        const labelsGrafico = data.graficos.labels.map(f => {
            if (data.agrupado_mensual) return formatearFecha(f, true);
            const formateada = formatearFecha(f, false);
            return data.agrupado_semanal ? `Sem. ${formateada}` : formateada;
        });

        const numPuntos = labelsGrafico.length;
        const mostrarPuntos = numPuntos > 30 ? 0 : 4; 
        const grosorLinea = numPuntos > 30 ? 2 : 3;
        
        const opcionesEjeX = {
            ticks: { autoSkip: true, maxTicksLimit: 12, maxRotation: 0 },
            grid: { display: false }
        };

        // OPCIONES COMPARTIDAS (Matamos animaciones y forzamos proporciones controladas por CSS)
        const chartOptions = {
            animation: false, // Apaga animaciones de carga
            maintainAspectRatio: false, // FUNDAMENTAL: Permite que el CSS controle la altura real en celular
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => dinero(ctx.parsed.y) } } },
            scales: { x: opcionesEjeX, y: { beginAtZero: true, ticks: { callback: v => dinero(v) } } }
        };

        // 1. GRÁFICO VENTAS (SEPARADO)
        if(chartV) chartV.destroy();
        const ctxV = document.getElementById('chartVentas').getContext('2d');
        chartV = new Chart(ctxV, {
            type: 'line',
            data: {
                labels: labelsGrafico,
                datasets: [{
                    label: 'Ventas',
                    data: data.graficos.ventas,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: grosorLinea,
                    fill: true,
                    tension: 0.4,
                    spanGaps: true,
                    pointRadius: mostrarPuntos,
                    pointHoverRadius: 6
                }]
            },
            options: chartOptions
        });

        // 2. GRÁFICO GASTOS (SEPARADO)
        if(chartG) chartG.destroy();
        const ctxG = document.getElementById('chartGastos').getContext('2d');
        chartG = new Chart(ctxG, {
            type: 'line',
            data: {
                labels: labelsGrafico,
                datasets: [{
                    label: 'Salidas',
                    data: data.graficos.gastos,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: grosorLinea,
                    fill: true,
                    tension: 0.4,
                    spanGaps: true,
                    pointRadius: mostrarPuntos,
                    pointHoverRadius: 6
                }]
            },
            options: chartOptions
        });

        // 3. GRÁFICO COMBINADO
        if(chartC) chartC.destroy();
        const ctxC = document.getElementById('chartCombinado').getContext('2d');
        
        // Copiamos las opciones compartidas pero le encendemos la leyenda a este solo
        const combinedOptions = Object.assign({}, chartOptions);
        combinedOptions.plugins = { 
            legend: { display: true, position: 'top' }, 
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + dinero(ctx.parsed.y) } } 
        };

        chartC = new Chart(ctxC, {
            type: 'line',
            data: {
                labels: labelsGrafico,
                datasets: [
                    {
                        label: 'Ventas',
                        data: data.graficos.ventas,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: grosorLinea,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                        pointRadius: mostrarPuntos,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Salidas',
                        data: data.graficos.gastos,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: grosorLinea,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                        pointRadius: mostrarPuntos,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: combinedOptions
        });

    } catch (error) {
        console.error('Error:', error);
        alert("Ocurrió un error al cargar los datos.");
    } finally {
        btnSubmit.innerHTML = txtOriginal;
        btnSubmit.disabled = false;
    }
}

document.getElementById('formFiltros').addEventListener('submit', cargarDatos);
document.addEventListener('DOMContentLoaded', () => cargarDatos());
</script>

<?php require('../shared/footer.inc.php'); ?>