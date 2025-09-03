<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';

$MiConexion = ConexionBD();
?>
<main id="main" class="main">

<div class="container-fluid px-4">
    <h1 class="mt-4">Reportes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Panel de estadísticas</li>
    </ol>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form class="row g-3" method="GET" id="formFiltros">
                <div class="col-md-3">
                    <label for="fechaInicio" class="form-label">Fecha inicio</label>
                    <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fechaFin" class="form-label">Fecha fin</label>
                    <input type="date" class="form-control" id="fechaFin" name="fechaFin" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6 d-flex justify-content-end align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtro
                    </button>
                    <button type="button" class="btn btn-success" id="btnExcel">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnPDF">
                        <i class="bi bi-filetype-pdf me-1"></i>PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4 shadow">
                <div class="card-body">
                    <h5>Ventas del Mes</h5>
                    <h3>$42.580</h3>
                    <small id="varVentas"></small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4 shadow">
                <div class="card-body">
                    <h5>Gastos del Mes</h5>
                    <h3>$12.340</h3>
                    <small id="varGastos"></small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark mb-4 shadow">
                <div class="card-body">
                    <h5>Clientes Nuevos</h5>
                    <h3>24</h3>
                    <small id="varClientes"></small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4 shadow">
                <div class="card-body">
                    <h5>Cantidad de Pedidos</h5>
                    <h3>18</h3>
                    <small id="varPedidos"></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-graph-up-arrow me-1"></i>
                    Ventas Anuales
                </div>
                <div class="card-body">
                    <canvas id="ventasChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-pie-chart me-1"></i>
                    Distribución de Gastos
                </div>
                <div class="card-body">
                    <canvas id="gastosChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-clock-history me-1"></i>
                    Horarios más concurridos
                </div>
                <div class="card-body">
                    <canvas id="horariosChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-people-fill me-1"></i>
                    Clientes con más pedidos
                </div>
                <div class="card-body">
                    <canvas id="clientesChart" height="240"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-truck me-1"></i>
                    Proveedores principales (compras)
                </div>
                <div class="card-body">
                    <canvas id="proveedoresChart" height="240"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-kanban me-1"></i>
                    Trabajos más pedidos
                </div>
                <div class="card-body">
                    <canvas id="trabajosChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-printer-fill me-1"></i>
                    Tipos de impresiones más pedidas (Monto $)
                </div>
                <div class="card-body">
                    <canvas id="impresionesChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-person-check-fill me-1"></i>
                    Rendimiento por empleado
                </div>
                <div class="card-body">
                    <canvas id="empleadosChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-funnel-fill me-1"></i>
                    Eficiencia de pedidos
                </div>
                <div class="card-body">
                    <canvas id="funnelChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
/* ================== UTILIDADES ================== */
// Efecto pseudo-3D (sombras y relieve) para todos los gráficos
const pseudo3DPlugin = {
    id: 'pseudo3D',
    beforeDatasetsDraw(chart, args, pluginOptions) {
        const ctx = chart.ctx;
        ctx.save();
        ctx.shadowColor = 'rgba(0,0,0,0.25)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetX = 4;
        ctx.shadowOffsetY = 6;
    },
    afterDatasetsDraw(chart, args, pluginOptions) {
        chart.ctx.restore();
    }
};

// Gradiente vertical helper
function verticalGradient(ctx, area, from, to) {
    const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
    g.addColorStop(0, from);
    g.addColorStop(1, to);
    return g;
}

// Formateo moneda
const dinero = (v) => '$' + Number(v).toLocaleString('es-AR');

/* ================== VARIACIONES % ================== */
function setVariacion(actual, anterior, elId){
    const v = anterior === 0 ? 0 : ((actual - anterior) / anterior) * 100;
    const str = (v >= 0)
      ? `<span class="text-light">+${v.toFixed(1)}% vs período anterior</span>`
      : `<span class="text-light">${v.toFixed(1)}% vs período anterior</span>`;
    document.getElementById(elId).innerHTML = str;
}
setVariacion(42580, 34000, 'varVentas');
setVariacion(12340, 13700, 'varGastos');
setVariacion(24, 22, 'varClientes');
setVariacion(18, 19, 'varPedidos');

/* ================== DATOS ESTÁTICOS ================== */
const datosVentas = [32000, 35000, 38000, 37000, 42000, 45000, 47000, 49000, 50000];
const datosVentasPrev = [30000, 33000, 35000, 36000, 39000, 43000, 45000, 46000, 47000];

const gastosLabels = ['Insumos','Sueldos','Servicios','Logística','Varios'];
const gastosData = [40, 25, 18, 10, 7];

const horasLabels = ['08-10hs','10-12hs','12-14hs','14-16hs','16-18hs','18-20hs'];
const horasData = [32, 58, 41, 63, 71, 47];

const clientesLabels = ['María González','Carlos López','Editorial Norte','Ana Martínez','Juan Pérez','Distribuidora Sur'];
const clientesData = [42, 35, 28, 22, 20, 18];

const proveedoresLabels = ['Papelera Central','Impresión Quality','Distribuidora Norte','Servicios Gráficos','Librería Oeste'];
const proveedoresData = [5200, 3800, 2900, 2500, 1840];

const trabajosLabels = ['Impresión Libros','Fotocopias','Encuadernación','Diseño','Afiches'];
const trabajosData = [185, 125, 78, 56, 44];

const impresionesLabels = ['Color','Blanco y Negro','Gran Formato','Digital'];
const impresionesMontos = [18500, 12000, 7000, 5200];

// Nuevos datos estáticos
const empleadosLabels = ['Juan','Ana','Luis','Sofía'];
const empleadosData = [12500, 18200, 9500, 14800]; // Montos de ventas

const funnelLabels = ['Pedidos recibidos', 'Pedidos en proceso', 'Pedidos finalizados', 'Pedidos facturados'];
const funnelData = [250, 180, 150, 140];

/* ================== GRAFICOS ================== */
Chart.register(pseudo3DPlugin);

// Ventas (línea, gradiente + sombra)
(() => {
    const ctx = document.getElementById('ventasChart').getContext('2d');
    const area = ctx.canvas.getBoundingClientRect();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep'],
            datasets: [{
                label: 'Ventas',
                data: datosVentas,
                borderWidth: 3,
                fill: true,
                borderColor: '#0d6efd',
                backgroundColor: (context) => {
                    const {chart} = context;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return 'rgba(13,110,253,0.15)';
                    return verticalGradient(ctx, chartArea, 'rgba(13,110,253,0.35)', 'rgba(13,110,253,0.05)');
                },
                tension: 0.35,
                pointRadius: 4,
                hoverRadius: 6,
            },{
                label: 'Período anterior',
                data: datosVentasPrev,
                borderWidth: 2,
                borderColor: '#6c757d',
                pointRadius: 0,
                fill: false,
                borderDash: [6,6],
                tension: 0.25
            }]
        },
        options: {
            plugins: { 
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${dinero(context.parsed.y)}`
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { drawBorder: false },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-AR');
                        }
                    }
                },
                x: { grid: { display: false } }
            },
            animations: {
                tension: {
                    duration: 1000,
                    easing: 'linear',
                    from: 0.5,
                    to: 0.25,
                    loop: true
                }
            }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// Distribución de gastos (torta con sombra/relieve)
(() => {
    const ctx = document.getElementById('gastosChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: gastosLabels,
            datasets: [{
                data: gastosData,
                borderWidth: 0,
                hoverOffset: 10,
                backgroundColor: ['#0d6efd','#198754','#ffc107','#fd7e14','#6f42c1']
            }]
        },
        options: {
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed}%` } }
            }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// Horarios más concurridos (barras)
(() => {
    const ctx = document.getElementById('horariosChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: horasLabels,
            datasets: [{
                label: 'Clientes atendidos',
                data: horasData,
                borderWidth: 0,
                backgroundColor: 'rgba(13,110,253,0.8)',
                hoverBackgroundColor: '#0d6efd'
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// Clientes con más pedidos (horizontal)
(() => {
    const ctx = document.getElementById('clientesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: clientesLabels,
            datasets: [{
                label: 'Pedidos',
                data: clientesData,
                backgroundColor: 'rgba(32,201,151,0.8)',
                hoverBackgroundColor: '#20c997',
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, grid: { drawBorder: false } },
                y: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// Proveedores principales (horizontal)
(() => {
    const ctx = document.getElementById('proveedoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: proveedoresLabels,
            datasets: [{
                label: 'Compras ($)',
                data: proveedoresData,
                backgroundColor: 'rgba(25,135,84,0.8)',
                hoverBackgroundColor: '#198754',
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { 
                    beginAtZero: true, 
                    grid: { drawBorder: false },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-AR');
                        }
                    }
                },
                y: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${dinero(ctx.parsed.x)}` } }
            }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// Trabajos más pedidos (barras)
(() => {
    const ctx = document.getElementById('trabajosChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trabajosLabels,
            datasets: [{
                label: 'Cantidad',
                data: trabajosData,
                backgroundColor: 'rgba(102,16,242,0.8)',
                hoverBackgroundColor: '#6610f2',
                borderWidth: 0
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// Tipos de impresiones (monto $)
(() => {
    const ctx = document.getElementById('impresionesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: impresionesLabels,
            datasets: [{
                label: 'Monto',
                data: impresionesMontos,
                backgroundColor: 'rgba(253,126,20,0.8)',
                hoverBackgroundColor: '#fd7e14',
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { 
                    beginAtZero: true, 
                    grid: { drawBorder: false },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-AR');
                        }
                    }
                },
                y: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${dinero(ctx.parsed.x)}` } }
            }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// NUEVO: Rendimiento por empleado
(() => {
    const ctx = document.getElementById('empleadosChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: empleadosLabels,
            datasets: [{
                label: 'Monto generado ($)',
                data: empleadosData,
                backgroundColor: 'rgba(13,110,253,0.8)',
                hoverBackgroundColor: '#0d6efd',
                borderWidth: 0
            }]
        },
        options: {
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { drawBorder: false },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-AR');
                        }
                    }
                },
                x: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${dinero(ctx.parsed.y)}` } }
            }
        },
        plugins: [pseudo3DPlugin]
    });
})();

// NUEVO: Eficiencia de pedidos (gráfico de torta)
(() => {
    const ctx = document.getElementById('funnelChart').getContext('2d');
    const backgroundColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545'];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: funnelLabels,
            datasets: [{
                data: funnelData,
                backgroundColor: backgroundColors,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const total = context.chart.data.datasets[0].data.reduce((acc, curr) => acc + curr, 0);
                            const value = context.parsed;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        },
        plugins: [pseudo3DPlugin]
    });
})();

/* ================== EXPORTACIONES ================== */
// PDF rápido: usa el diálogo de impresión (puede guardar como PDF)
document.getElementById('btnPDF').addEventListener('click', () => {
    window.print();
});

// Excel sencillo (CSV) con varias secciones
document.getElementById('btnExcel').addEventListener('click', () => {
    const rows = [];
    const pushSection = (title, headers, dataRows) => {
        rows.push([title]); rows.push([]); rows.push(headers);
        dataRows.forEach(r => rows.push(r));
        rows.push([]); rows.push([]);
    };

    // Ventas (actual y anterior)
    pushSection('Ventas (AR$)',
        ['Mes','Actual','Anterior'],
        ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep'].map((m,i)=>[m, datosVentas[i], datosVentasPrev[i]])
    );

    // Distribución de gastos
    pushSection('Distribución de Gastos (%)',
        ['Categoría','%'],
        gastosLabels.map((l,i)=>[l, gastosData[i]])
    );

    // Horarios
    pushSection('Horarios más concurridos',
        ['Franja','Clientes'],
        horasLabels.map((l,i)=>[l, horasData[i]])
    );

    // Clientes
    pushSection('Clientes con más pedidos',
        ['Cliente','Pedidos'],
        clientesLabels.map((l,i)=>[l, clientesData[i]])
    );

    // Proveedores
    pushSection('Proveedores principales (AR$)',
        ['Proveedor','Compras'],
        proveedoresLabels.map((l,i)=>[l, proveedoresData[i]])
    );

    // Trabajos
    pushSection('Trabajos más pedidos',
        ['Trabajo','Cantidad'],
        trabajosLabels.map((l,i)=>[l, trabajosData[i]])
    );

    // Tipos de impresión
    pushSection('Tipos de impresiones (AR$)',
        ['Tipo','Monto'],
        impresionesLabels.map((l,i)=>[l, impresionesMontos[i]])
    );

    // NUEVO: Rendimiento por empleado
    pushSection('Rendimiento por Empleado (AR$)',
        ['Empleado','Monto Generado'],
        empleadosLabels.map((l,i)=>[l, empleadosData[i]])
    );

    // NUEVO: Eficiencia de pedidos
    pushSection('Eficiencia de Pedidos',
        ['Etapa','Cantidad'],
        funnelLabels.map((l,i)=>[l, funnelData[i]])
    );

    const csv = rows.map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\r\n');
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `reportes_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});
</script>
</main><?php require('../shared/footer.inc.php'); ?>
</body>
</html>