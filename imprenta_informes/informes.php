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
                        
                        <button type="button" class="btn btn-danger" id="btnImprimir">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Imprimir PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card bg-success text-white mb-4 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-white-50">INGRESOS TOTALES</div>
                            <div class="fs-4 fw-bold" id="lblIngresos">$ 0,00</div>
                        </div>
                        <i class="bi bi-graph-up fs-1 text-white-50"></i>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between small">
                        <span id="varIngresos" class="text-white">Variación: 0%</span>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6">
                <div class="card bg-danger text-white mb-4 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-white-50">EGRESOS TOTALES</div>
                            <div class="fs-4 fw-bold" id="lblGastos">$ 0,00</div>
                        </div>
                        <i class="bi bi-graph-down fs-1 text-white-50"></i>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between small">
                        <span id="varGastos" class="text-white">Variación: 0%</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card bg-primary text-white mb-4 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-white-50">GANANCIA NETA</div>
                            <div class="fs-4 fw-bold" id="lblGanancia">$ 0,00</div>
                        </div>
                        <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between small">
                        <span id="varGanancia" class="text-white">Variación: 0%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Banco (Transf.)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="lblBanco">$ 0,00</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-bank fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">MercadoPago</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="lblMP">$ 0,00</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-phone fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Efectivo</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="lblEfectivo">$ 0,00</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-cash-stack fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">Detalle de Ingresos (Agrupado)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-hover" id="tablaIngresos" width="100%" cellspacing="0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto y %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-danger">Detalle de Salidas (Agrupado)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-hover" id="tablaGastos" width="100%" cellspacing="0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto y %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require('../shared/pie.inc.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Referencias
    const inputMes = document.getElementById('mesSeleccionado');
    const btnExcel = document.getElementById('btnExcel');
    const btnImprimir = document.getElementById('btnImprimir');
    
    // Cargar datos iniciales
    cargarDatos(inputMes.value);
    
    // Evento cambio de mes
    inputMes.addEventListener('change', function() {
        cargarDatos(this.value);
    });

    // Evento Imprimir PDF
    btnImprimir.addEventListener('click', function() {
        const [anio, mes] = inputMes.value.split('-');
        window.open(`imprimir_informe.php?anio=${anio}&mes=${mes}`, '_blank');
    });

    // Variable global para guardar datos actuales (para el excel)
    let datosActuales = null;

    // Función principal de carga
    function cargarDatos(periodo) {
        fetch(`procesar_informe.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                if(data.ok) {
                    actualizarInterfaz(data);
                    datosActuales = data.actual; // Guardamos para el Excel
                } else {
                    alert('Error al cargar datos: ' + data.msg);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Actualizar DOM
    function actualizarInterfaz(data) {
        const d = data.actual;
        const v = data.variaciones;

        // Formateador de moneda
        const fmt = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });

        // 1. Tarjetas Superiores
        document.getElementById('lblIngresos').innerText = fmt.format(d.totalIngresos);
        document.getElementById('lblGastos').innerText = fmt.format(d.totalGastos);
        document.getElementById('lblGanancia').innerText = fmt.format(d.totalIngresos - d.totalGastos);

        // 2. Variaciones (Flechas y colores)
        pintarVariacion('varIngresos', v.ingresos);
        pintarVariacion('varGastos', v.gastos);
        pintarVariacion('varGanancia', v.ganancia);

        // 3. Contadores Específicos (CORREGIDOS)
        document.getElementById('lblBanco').innerText = fmt.format(d.banco);
        document.getElementById('lblMP').innerText = fmt.format(d.mp);
        document.getElementById('lblEfectivo').innerText = fmt.format(d.efectivo);

        // 4. Tablas Detalle
        renderTabla('tablaIngresos', d.desgloseIngresos, fmt);
        renderTabla('tablaGastos', d.desgloseGastos, fmt);
    }

    // Función auxiliar para pintar variación
    function pintarVariacion(idElemento, valor) {
        const el = document.getElementById(idElemento);
        const icono = valor >= 0 ? '<i class="bi bi-caret-up-fill"></i>' : '<i class="bi bi-caret-down-fill"></i>';
        const signo = valor >= 0 ? '+' : '';
        el.innerHTML = `${icono} ${signo}${valor}% vs mes anterior`;
    }

    // Función auxiliar para renderizar tablas
    function renderTabla(idTabla, lista, fmt) {
        const tbody = document.querySelector(`#${idTabla} tbody`);
        tbody.innerHTML = ''; // Limpiar

        if(!lista || lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Sin movimientos</td></tr>';
            return;
        }

        lista.forEach(item => {
            const tr = document.createElement('tr');
            // AQUÍ ESTÁ EL CAMBIO DEL PORCENTAJE
            tr.innerHTML = `
                <td>${item.concepto}</td>
                <td class="text-end">
                    <span class="fw-bold">${fmt.format(item.monto)}</span>
                    <span class="text-secondary small ms-2" style="font-size:0.85em;">(${item.porcentaje})</span>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Evento Excel (Sin tocar lógica, solo añadiendo el campo porcentaje al CSV)
    btnExcel.addEventListener('click', function() {
        if(!datosActuales) return;

        const d = datosActuales;
        const mes = inputMes.value;
        const gananciaActual = d.totalIngresos - d.totalGastos;

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
        
        // Resultado
        csv.push(`"GANANCIA NETA";"${gananciaActual}"`);

        const csvString = csv.join("\n");
        const blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `informe_financiero_${mes}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

});
</script>