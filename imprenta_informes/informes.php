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
                            <div class="col-auto"><i class="bi bi-bank fs-2 text-gray-300"></i></div>
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
                            <div class="col-auto"><i class="bi bi-qr-code-scan fs-2 text-gray-300"></i></div>
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
                            <div class="col-auto"><i class="bi bi-cash-stack fs-2 text-gray-300"></i></div>
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
                                <div id="porcTotalVentas" class="mb-2"></div>
                                
                                <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#detalleVentas" aria-expanded="false">
                                    Ver Origen <i class="bi bi-chevron-down"></i>
                                </button>
                                
                                <div class="collapse mt-3" id="detalleVentas">
                                    <ul class="list-group list-group-flush text-start small" id="listaDetalleVentas">
                                        <li class="list-group-item">Cargando...</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3 border-start border-end">
                                <h5 class="text-muted">Salidas Totales</h5>
                                <h2 class="text-danger fw-bold" id="valTotalGastos">$0</h2>
                                <div id="porcTotalGastos" class="mb-2"></div>
                                
                                <button class="btn btn-sm btn-outline-danger mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#detalleGastos" aria-expanded="false">
                                    Ver Detalle <i class="bi bi-chevron-down"></i>
                                </button>
                                
                                <div class="collapse mt-3" id="detalleGastos">
                                    <ul class="list-group list-group-flush text-start small" id="listaDetalleGastos">
                                        <li class="list-group-item">Cargando...</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <h5 class="text-muted">Ganancia Neta</h5>
                                <h2 class="text-success fw-bold" id="valGanancia">$0</h2>
                                <div id="porcGanancia" class="mb-2"></div>
                                <small class="text-muted d-block mt-2">Ingresos - Egresos</small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</main>

<script>
// --- CONFIGURACIÓN ---
const API_URL = 'procesar_informe.php';
// Formateador de moneda Argentina
const dinero = (valor) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(valor);

// --- FUNCIONES AUXILIARES ---

// Genera el HTML del badge de porcentaje (+X% verde / -X% rojo)
function htmlVariacion(actual, anterior, invertido = false) {
    if (anterior === 0) return '<span class="badge bg-secondary">Sin datos previos</span>';
    
    let dif = ((actual - anterior) / anterior) * 100;
    
    // Si invertido es true (para gastos): Si sube es "malo" (rojo), si baja es "bueno" (verde)
    let colorClass = 'text-success';
    let iconClass = 'bi-arrow-up';
    
    if (!invertido) {
        // Lógica normal (Ventas/Ganancia): Más es mejor
        if (dif < 0) { colorClass = 'text-danger'; iconClass = 'bi-arrow-down'; }
    } else {
        // Lógica gastos: Más es peor
        if (dif > 0) { colorClass = 'text-danger'; iconClass = 'bi-arrow-up'; }
        else { colorClass = 'text-success'; iconClass = 'bi-arrow-down'; }
    }

    const signo = dif >= 0 ? '+' : '';
    return `<span class="${colorClass} fw-bold"><i class="bi ${iconClass}"></i> ${signo}${dif.toFixed(1)}%</span> <span class="text-muted small">vs mes anterior</span>`;
}

// Renderiza una lista en el UL correspondiente
function renderizarLista(idLista, datos, claseBadge = 'bg-primary') {
    const lista = document.getElementById(idLista);
    lista.innerHTML = '';
    
    if (datos && datos.length > 0) {
        datos.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center bg-light';
            
            // --- AQUI ESTA LA MODIFICACION DEL PORCENTAJE ---
            // Usamos item.porcentaje que ahora viene del backend
            li.innerHTML = `
                ${item.concepto} 
                <div>
                    <span class="text-muted small me-2" style="font-size:0.85em;">(${item.porcentaje})</span>
                    <span class="badge ${claseBadge} rounded-pill">${dinero(item.monto)}</span>
                </div>
            `;
            lista.appendChild(li);
        });
    } else {
        lista.innerHTML = '<li class="list-group-item text-center text-muted">Sin movimientos</li>';
    }
}

// --- LÓGICA PRINCIPAL ---

async function cargarDatos() {
    const mesInput = document.getElementById('mesSeleccionado').value;
    if(!mesInput) return;

    try {
        const response = await fetch(`${API_URL}?periodo=${mesInput}`);
        const data = await response.json();

        if (!data.ok) {
            console.error('Error:', data.msg);
            return;
        }

        const d = data.datos;   // Datos Mes Actual (Antes era data.actual, ahora corregido a data.datos)
        const p = data.previo;  // Datos Mes Anterior

        // 1. Tarjetas Superiores
        document.getElementById('valBanco').innerText = dinero(d.banco);
        document.getElementById('porcBanco').innerHTML = htmlVariacion(d.banco, p.banco);

        document.getElementById('valMP').innerText = dinero(d.mp);
        document.getElementById('porcMP').innerHTML = htmlVariacion(d.mp, p.mp);

        document.getElementById('valEfectivo').innerText = dinero(d.efectivo);
        document.getElementById('porcEfectivo').innerHTML = htmlVariacion(d.efectivo, p.efectivo);

        // 2. Panel Central
        
        // Ventas Totales
        document.getElementById('valTotalVentas').innerText = dinero(d.totalIngresos);
        document.getElementById('porcTotalVentas').innerHTML = htmlVariacion(d.totalIngresos, p.totalIngresos);
        renderizarLista('listaDetalleVentas', d.desgloseIngresos, 'bg-primary');

        // Salidas Totales
        document.getElementById('valTotalGastos').innerText = dinero(d.totalGastos);
        document.getElementById('porcTotalGastos').innerHTML = htmlVariacion(d.totalGastos, p.totalGastos, true); 
        renderizarLista('listaDetalleGastos', d.desgloseGastos, 'bg-danger');

        // Ganancia Neta
        const gananciaActual = d.totalIngresos - d.totalGastos;
        const gananciaPrevio = p.totalIngresos - p.totalGastos;
        
        const divGanancia = document.getElementById('valGanancia');
        divGanancia.innerText = dinero(gananciaActual);
        divGanancia.className = gananciaActual >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
        
        document.getElementById('porcGanancia').innerHTML = htmlVariacion(gananciaActual, gananciaPrevio);

        // Guardar datos en memoria para el Excel
        window.datosReporte = { mes: mesInput, d, gananciaActual };

    } catch (error) {
        console.error('Error al cargar datos:', error);
    }
}

// --- EVENTOS ---

document.getElementById('mesSeleccionado').addEventListener('change', cargarDatos);
document.addEventListener('DOMContentLoaded', cargarDatos);

// Botón PDF
document.getElementById('btnPDF').addEventListener('click', () => {
    const mesInput = document.getElementById('mesSeleccionado').value; 
    const [anio, mes] = mesInput.split('-');
    window.open(`imprimir_informe.php?mes=${mes}&anio=${anio}`, '_blank');
});

// Botón Excel
document.getElementById('btnExcel').addEventListener('click', () => {
    if(!window.datosReporte) return;
    const { mes, d, gananciaActual } = window.datosReporte;
    
    let csv = [];
    csv.push(`"REPORTE FINANCIERO";"${mes}"`);
    csv.push(``); 
    
    // Sección Ingresos
    csv.push(`"DETALLE INGRESOS";"MONTO";"% DEL TOTAL"`);
    if(d.desgloseIngresos) {
        d.desgloseIngresos.forEach(item => {
            csv.push(`"${item.concepto}";"${item.monto}";"${item.porcentaje}"`);
        });
    }
    csv.push(`"TOTAL VENTAS";"${d.totalIngresos}";"100%"`);
    csv.push(``);

    // Sección Egresos
    csv.push(`"DETALLE SALIDAS";"MONTO";"% S/INGRESOS"`);
    if(d.desgloseGastos) {
        d.desgloseGastos.forEach(item => {
            csv.push(`"${item.concepto}";"${item.monto}";"${item.porcentaje}"`);
        });
    }
    csv.push(`"TOTAL SALIDAS";"${d.totalGastos}"`);
    csv.push(``);
    
    csv.push(`"GANANCIA NETA";"${gananciaActual}"`);

    const csvString = csv.join("\n");
    const blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", `informe_${mes}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>

<?php require('../shared/footer.inc.php'); ?>