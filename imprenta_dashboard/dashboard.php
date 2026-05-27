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

<main id="main" class="main">
    <div class="container-fluid px-4">
        <h1 class="mt-4">Dashboard Estratégico</h1>
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

        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white fw-bold text-primary">
                        <i class="bi bi-activity me-1"></i> <span id="tituloChartVentas">Evolución de Ventas</span>
                    </div>
                    <div class="card-body">
                        <canvas id="chartVentas" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white fw-bold text-danger">
                        <i class="bi bi-activity me-1"></i> <span id="tituloChartGastos">Evolución de Salidas</span>
                    </div>
                    <div class="card-body">
                        <canvas id="chartGastos" height="200"></canvas>
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

function formatearFecha(fechaStr) {
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const partes = fechaStr.split('-');
    if(partes.length !== 3) return fechaStr;
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

        // Cambiar dinámicamente títulos si es semanal
        document.getElementById('tituloChartVentas').innerText = data.agrupado_semanal ? "Evolución de Ventas (Semanales)" : "Evolución de Ventas (Diarias)";
        document.getElementById('tituloChartGastos').innerText = data.agrupado_semanal ? "Evolución de Salidas (Semanales)" : "Evolución de Salidas (Diarias)";

        // Etiquetas inteligentes
        const labelsGrafico = data.graficos.labels.map(f => {
            const formateada = formatearFecha(f);
            return data.agrupado_semanal ? `Sem. ${formateada}` : formateada;
        });

        const numPuntos = labelsGrafico.length;
        
        // Si hay muchísimos puntos ocultamos el circulo del vértice para limpiar visualmente
        const mostrarPuntos = numPuntos > 30 ? 0 : 4; 
        const grosorLinea = numPuntos > 30 ? 2 : 3;
        
        const opcionesEjeX = {
            ticks: {
                autoSkip: true,
                maxTicksLimit: 12,
                maxRotation: 0 
            },
            grid: { display: false }
        };

        if(chartV) chartV.destroy();
        const ctxV = document.getElementById('chartVentas').getContext('2d');
        chartV = new Chart(ctxV, {
            type: 'line',
            data: {
                labels: labelsGrafico,
                datasets: [{
                    label: data.agrupado_semanal ? 'Ventas de la semana' : 'Ventas del día',
                    data: data.graficos.ventas,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: grosorLinea,
                    fill: true,
                    tension: 0.4, // Curva suavizada spline
                    spanGaps: true,
                    pointRadius: mostrarPuntos,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => dinero(ctx.parsed.y) } }
                },
                scales: {
                    x: opcionesEjeX,
                    y: { beginAtZero: true, ticks: { callback: v => dinero(v) } }
                }
            }
        });

        if(chartG) chartG.destroy();
        const ctxG = document.getElementById('chartGastos').getContext('2d');
        chartG = new Chart(ctxG, {
            type: 'line',
            data: {
                labels: labelsGrafico,
                datasets: [{
                    label: data.agrupado_semanal ? 'Salidas de la semana' : 'Salidas del día',
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
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => dinero(ctx.parsed.y) } }
                },
                scales: {
                    x: opcionesEjeX,
                    y: { beginAtZero: true, ticks: { callback: v => dinero(v) } }
                }
            }
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