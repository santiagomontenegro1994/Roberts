<?php
ob_start();
session_start();

// Validación de sesión (igual que tu ejemplo)
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
// La conexión la usaremos dentro de procesar_informe.php mediante AJAX
?>

<main id="main" class="main">
    <div class="container-fluid px-4">
        <h1 class="mt-4">Informe Financiero Mensual</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">Resumen de Ingresos y Egresos</li>
        </ol>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end" id="formFiltros">
                    <div class="col-md-4">
                        <label for="mesSeleccionado" class="form-label">Seleccionar Mes</label>
                        <input type="month" class="form-control" id="mesSeleccionado" value="<?php echo date('Y-m'); ?>">
                    </div>
                    <div class="col-md-8 d-flex justify-content-end gap-2">
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
            <div class="col-xl-4 col-md-6">
                <div class="card border-start border-4 border-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Banco</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800" id="valBanco">$0</div>
                                <small id="porcBanco" class="text-muted"></small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-bank fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card border-start border-4 border-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">MercadoPago</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800" id="valMP">$0</div>
                                <small id="porcMP" class="text-muted"></small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-qr-code-scan fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card border-start border-4 border-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Efectivo</div>
                                <div class="h3 mb-0 font-weight-bold text-gray-800" id="valEfectivo">$0</div>
                                <small id="porcEfectivo" class="text-muted"></small>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-cash-stack fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-calculator me-1"></i> Balance del Mes
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <h5 class="text-muted">Ventas Totales</h5>
                                <h2 class="text-primary fw-bold" id="valTotalVentas">$0</h2>
                                <small id="porcTotalVentas"></small>
                            </div>
                            
                            <div class="col-md-4 mb-3 border-start border-end">
                                <h5 class="text-muted">Salidas Totales</h5>
                                <h2 class="text-danger fw-bold" id="valTotalGastos">$0</h2>
                                
                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#detalleGastos" aria-expanded="false">
                                    Ver Detalle <i class="bi bi-chevron-down"></i>
                                </button>
                                
                                <div class="collapse mt-3" id="detalleGastos">
                                    <ul class="list-group list-group-flush text-start small" id="listaDetalleGastos">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">Cargando...</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <h5 class="text-muted">Ganancia Neta</h5>
                                <h2 class="text-success fw-bold" id="valGanancia">$0</h2>
                                <small class="text-muted">Total Ventas - Salidas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</main>

<script>
// URL del backend
const API_URL = 'procesar_informe.php';

// Formateador de dinero
const dinero = (valor) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(valor);

// Función para calcular porcentaje y generar HTML del badge
function htmlVariacion(actual, anterior) {
    if (anterior === 0) return '<span class="badge bg-secondary">Sin datos previos</span>';
    const dif = ((actual - anterior) / anterior) * 100;
    const icono = dif >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
    const color = dif >= 0 ? 'text-success' : 'text-danger';
    const signo = dif >= 0 ? '+' : '';
    return `<span class="${color} fw-bold"><i class="bi ${icono}"></i> ${signo}${dif.toFixed(1)}%</span> <span class="text-muted small">vs mes anterior</span>`;
}

// Función principal para cargar datos
async function cargarDatos() {
    const mes = document.getElementById('mesSeleccionado').value;
    if(!mes) return;

    try {
        const response = await fetch(`${API_URL}?periodo=${mes}`);
        const data = await response.json();

        if (!data.ok) {
            console.error('Error del servidor:', data.msg);
            return;
        }

        const d = data.datos;
        const p = data.previo; // Datos del mes anterior para comparar

        // 1. Actualizar Tarjetas Superiores
        document.getElementById('valBanco').innerText = dinero(d.banco);
        document.getElementById('porcBanco').innerHTML = htmlVariacion(d.banco, p.banco);

        document.getElementById('valMP').innerText = dinero(d.mp);
        document.getElementById('porcMP').innerHTML = htmlVariacion(d.mp, p.mp);

        document.getElementById('valEfectivo').innerText = dinero(d.efectivo);
        document.getElementById('porcEfectivo').innerHTML = htmlVariacion(d.efectivo, p.efectivo);

        // 2. Actualizar Balance
        const totalVentas = d.banco + d.mp + d.efectivo;
        const totalVentasPrev = p.banco + p.mp + p.efectivo;
        
        document.getElementById('valTotalVentas').innerText = dinero(totalVentas);
        document.getElementById('porcTotalVentas').innerHTML = htmlVariacion(totalVentas, totalVentasPrev);

        document.getElementById('valTotalGastos').innerText = dinero(d.totalGastos);
        
        // Ganancia
        const ganancia = totalVentas - d.totalGastos;
        document.getElementById('valGanancia').innerText = dinero(ganancia);
        
        // Colorear ganancia (rojo si es negativa)
        document.getElementById('valGanancia').className = ganancia >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';

        // 3. Llenar detalle de gastos
        const lista = document.getElementById('listaDetalleGastos');
        lista.innerHTML = '';
        if (d.desgloseGastos && d.desgloseGastos.length > 0) {
            d.desgloseGastos.forEach(g => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center bg-light';
                li.innerHTML = `${g.concepto} <span class="badge bg-danger rounded-pill">${dinero(g.monto)}</span>`;
                lista.appendChild(li);
            });
        } else {
            lista.innerHTML = '<li class="list-group-item text-center text-muted">No hay salidas registradas</li>';
        }
        
        // Guardamos datos globales para exportar si se requiere
        window.datosReporteActual = { mes, d, totalVentas, ganancia };

    } catch (error) {
        console.error('Error al cargar datos:', error);
    }
}

// Event Listeners
document.getElementById('mesSeleccionado').addEventListener('change', cargarDatos);

// Carga inicial
document.addEventListener('DOMContentLoaded', cargarDatos);

// Exportar PDF (Simple print window por ahora, se puede mejorar con librerías jsPDF)
document.getElementById('btnPDF').addEventListener('click', () => {
    // Expandimos los gastos para que salgan en el PDF
    const collapseElement = document.getElementById('detalleGastos');
    const bsCollapse = new bootstrap.Collapse(collapseElement, { toggle: false });
    bsCollapse.show();
    
    setTimeout(() => { window.print(); }, 500);
});

// Exportar Excel (CSV Generado en cliente)
document.getElementById('btnExcel').addEventListener('click', () => {
    if(!window.datosReporteActual) return;
    const { mes, d, totalVentas, ganancia } = window.datosReporteActual;
    
    const rows = [
        ['REPORTE FINANCIERO', mes],
        [],
        ['CONCEPTO', 'MONTO'],
        ['Ingresos Banco', d.banco],
        ['Ingresos MercadoPago', d.mp],
        ['Ingresos Efectivo', d.efectivo],
        ['-----------------', '-------'],
        ['TOTAL VENTAS', totalVentas],
        [],
        ['DETALLE DE SALIDAS', ''],
    ];

    d.desgloseGastos.forEach(g => {
        rows.push([g.concepto, g.monto]);
    });

    rows.push(['-----------------', '-------']);
    rows.push(['TOTAL SALIDAS', d.totalGastos]);
    rows.push([]);
    rows.push(['GANANCIA NETA', ganancia]);

    const csvContent = "data:text/csv;charset=utf-8," 
        + rows.map(e => e.join(",")).join("\n");
        
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `reporte_financiero_${mes}.csv`);
    document.body.appendChild(link);
    link.click();
});
</script>

<?php require('../shared/footer.inc.php'); ?>