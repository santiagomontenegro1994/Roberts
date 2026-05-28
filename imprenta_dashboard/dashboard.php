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

        <div class="d-flex justify-content-end mb-3">
            <div class="form-check form-switch shadow-sm bg-white px-4 py-2 rounded border border-2 border-primary" style="display: inline-block;">
                <input class="form-check-input ms-0 me-2" type="checkbox" id="toggleCombinado" style="cursor: pointer; transform: scale(1.3); margin-top: 5px;">
                <label class="form-check-label fw-bold text-dark ms-2" for="toggleCombinado" style="cursor: pointer;">Superponer Gráficos (Ventas vs Salidas)</label>
            </div>
        </div>

        <div class="row mb-4" id="contenedorSeparados">
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-white fw-bold text-primary">
                        <i class="bi bi-activity me-1"></i> <span id="tituloChartVentas">Evolución de Ventas</span>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 250px; width: 100%;">
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
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="chartGastos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4" id="contenedorCombinado" style="display: none;">
            <div class="col-12 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-intersect me-1"></i> <span id="tituloChartCombinado">Comparativa: Ventas vs Salidas</span></div>
                        <div class="small">
                            <span style="color:#6ea8fe;"><i class="bi bi-circle-fill"></i> Ventas</span> &nbsp;&nbsp;|&nbsp;&nbsp; 
                            <span style="color:#ea868f;"><i class="bi bi-circle-fill"></i> Salidas</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 350px; width: 100%;">
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
                        <i class="bi bi-list-check me-1"></i> Desglose de Salidas <span class="small fw-normal">(% sobre salidas totales)</span>
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

function formatearFecha(fechaStr) {
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const partes = fechaStr.split('-');
    if(partes.length !== 3) return fechaStr;
    return `${partes[2]} ${meses[parseInt(partes[1])-1]}`;
}

function formatearFechaMes(fechaStr) {
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const partes = fechaStr.split('-');
    if(partes.length !== 3) return fechaStr;
    return `${meses[parseInt(partes[1])-1]} ${partes[0]}`;
}

// Función súper mejorada para renderizar listas con Desplegables
function renderizarLista(idLista, datos, claseBadge) {
    const lista = document.getElementById(idLista);
    lista.innerHTML = '';
    
    if (datos && datos.length > 0) {
        datos.forEach((item, index) => {
            const tieneSubitems = item.subitems && item.subitems.length > 0;
            const idColapso = `collapse-${idLista}-${index}`;
            
            let htmlSubitems = '';
            if (tieneSubitems) {
                htmlSubitems = `<div class="collapse" id="${idColapso}">
                                  <ul class="list-group list-group-flush border-top">`;
                item.subitems.forEach(sub => {
                    htmlSubitems += `
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 py-2 ps-4" style="font-size: 0.85rem;">
                            <span class="text-secondary fw-semibold"><i class="bi bi-arrow-return-right me-1"></i> ${sub.nombre}</span>
                            <div>
                                <span class="text-muted small me-2">${sub.porcentaje}</span>
                                <span class="fw-bold text-dark">${dinero(sub.monto)}</span>
                            </div>
                        </li>
                    `;
                });
                htmlSubitems += `</ul></div>`;
            }

            lista.innerHTML += `
                <li class="list-group-item p-0 border-bottom">
                    <div class="d-flex justify-content-between align-items-center p-3" 
                         ${tieneSubitems ? `data-bs-toggle="collapse" href="#${idColapso}" style="cursor: pointer;" title="Ver detalles"` : ''}>
                        <span class="fw-bold text-dark">
                            ${tieneSubitems ? '<i class="bi bi-chevron-down text-secondary me-2" style="font-size: 0.8rem;"></i>' : '<i class="bi bi-dash text-secondary me-2"></i>'}
                            ${item.concepto}
                        </span>
                        <div>
                            <span class="text-muted small me-2 fw-bold">${item.porcentaje}</span>
                            <span class="badge ${claseBadge} rounded-pill fs-6 shadow-sm">${dinero(item.monto)}</span>
                        </div>
                    </div>
                    ${htmlSubitems}
                </li>
            `;
        });
    } else {
        lista.innerHTML = '<li class="list-group-item text-center text-muted py-4">No hay movimientos en este período</li>';
    }
}

document.getElementById('toggleCombinado').addEventListener('change', function() {
    if(this.checked) {
        document.getElementById('contenedorSeparados').style.display = 'none';
        document.getElementById('contenedorCombinado').style.display = 'flex';
    } else {
        document.getElementById('contenedorSeparados').style.display = 'flex';
        document.getElementById('contenedorCombinado').style.display = 'none';
    }
});

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

        let labelVentas = 'Ventas del día';
        let labelGastos = 'Salidas del día';
        
        if (data.agrupado_mensual) {
            document.getElementById('tituloChartVentas').innerText = "Evolución de Ventas (Mensuales)";
            document.getElementById('tituloChartGastos').innerText = "Evolución de Salidas (Mensuales)";
            document.getElementById('tituloChartCombinado').innerText = "Comparativa Mensual: Ventas vs Salidas";
            labelVentas = 'Ventas del mes';
            labelGastos = 'Salidas del mes';
        } else if (data.agrupado_semanal) {
            document.getElementById('tituloChartVentas').innerText = "Evolución de Ventas (Semanales)";
            document.getElementById('tituloChartGastos').innerText = "Evolución de Salidas (Semanales)";
            document.getElementById('tituloChartCombinado').innerText = "Comparativa Semanal: Ventas vs Salidas";
            labelVentas = 'Ventas de la semana';
            labelGastos = 'Salidas de la semana';
        } else {
            document.getElementById('tituloChartVentas').innerText = "Evolución de Ventas (Diarias)";
            document.getElementById('tituloChartGastos').innerText = "Evolución de Salidas (Diarias)";
            document.getElementById('tituloChartCombinado').innerText = "Comparativa Diaria: Ventas vs Salidas";
        }

        const labelsGrafico = data.graficos.labels.map(f => {
            if (data.agrupado_mensual) return formatearFechaMes(f);
            else if (data.agrupado_semanal) return `Sem. ${formatearFecha(f)}`;
            else return formatearFecha(f);
        });

        const numPuntos = labelsGrafico.length;
        let mostrarPuntos = 4;
        let grosorLinea = 3;

        if (data.agrupado_mensual) {
            mostrarPuntos = 5; 
            grosorLinea = 3;
        } else if (numPuntos > 30) {
            mostrarPuntos = 0; 
            grosorLinea = 2;
        }
        
        const opcionesEjeX = {
            ticks: { autoSkip: true, maxTicksLimit: 12, maxRotation: 0 },
            grid: { display: false }
        };

        const configBase = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false, 
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${dinero(ctx.parsed.y)}` } }
                },
                scales: {
                    x: opcionesEjeX,
                    y: { beginAtZero: true, ticks: { callback: v => dinero(v) } }
                }
            }
        };

        if(chartV) chartV.destroy();
        chartV = new Chart(document.getElementById('chartVentas').getContext('2d'), {
            ...configBase,
            data: {
                labels: labelsGrafico,
                datasets: [{
                    label: labelVentas, data: data.graficos.ventas,
                    borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: grosorLinea, fill: true, tension: 0.4, spanGaps: true,
                    pointRadius: mostrarPuntos, pointHoverRadius: 6
                }]
            }
        });

        if(chartG) chartG.destroy();
        chartG = new Chart(document.getElementById('chartGastos').getContext('2d'), {
            ...configBase,
            data: {
                labels: labelsGrafico,
                datasets: [{
                    label: labelGastos, data: data.graficos.gastos,
                    borderColor: '#dc3545', backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: grosorLinea, fill: true, tension: 0.4, spanGaps: true,
                    pointRadius: mostrarPuntos, pointHoverRadius: 6
                }]
            }
        });

        if(chartC) chartC.destroy();
        chartC = new Chart(document.getElementById('chartCombinado').getContext('2d'), {
            ...configBase,
            data: {
                labels: labelsGrafico,
                datasets: [
                    {
                        label: labelVentas, data: data.graficos.ventas,
                        borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.15)',
                        borderWidth: grosorLinea, fill: true, tension: 0.4, spanGaps: true,
                        pointRadius: mostrarPuntos, pointHoverRadius: 6
                    },
                    {
                        label: labelGastos, data: data.graficos.gastos,
                        borderColor: '#dc3545', backgroundColor: 'rgba(220, 53, 69, 0.15)',
                        borderWidth: grosorLinea, fill: true, tension: 0.4, spanGaps: true,
                        pointRadius: mostrarPuntos, pointHoverRadius: 6
                    }
                ]
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