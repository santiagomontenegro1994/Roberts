<?php
date_default_timezone_set('America/Argentina/Cordoba');
require_once 'auth_jefes.php';
verificarSesionApp();

// --- CARGAMOS LAS BASES DE DATOS DEL EXCEL (JSON) ---
$path_variables = __DIR__ . '/../datos/variables_imprenta.json';
$json_variables = file_exists($path_variables) ? file_get_contents($path_variables) : '[]';

$path_escalas = __DIR__ . '/../datos/escalas_cantidades.json';
$json_escalas = file_exists($path_escalas) ? file_get_contents($path_escalas) : '[]';

$path_productos = __DIR__ . '/../datos/productos_simples.json';
$json_productos = file_exists($path_productos) ? file_get_contents($path_productos) : '[]';

// --- LÓGICA DE REUTILIZACIÓN DE PRESUPUESTOS ---
$clienteCargado = '';
$carritoCargadoJSON = '[]';

if (!empty($_GET['cargar_id'])) {
    $idCarga = (int)$_GET['cargar_id'];
    require_once '../funciones/conexion.php';
    $MiConexionCarga = ConexionBD();
    
    $qCarga = mysqli_query($MiConexionCarga, "SELECT * FROM presupuestos_historial WHERE idPresupuesto = $idCarga");
    if ($qCarga && $rowCarga = mysqli_fetch_assoc($qCarga)) {
        $clienteCargado = htmlspecialchars($rowCarga['cliente_nombre']);
        $carritoCargadoJSON = $rowCarga['items_json'];
    }
}
// -----------------------------------------------

include 'header_mobile.php';
?>
<style>
    body { padding-bottom: 90px; }
    .cart-item { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); position: relative; }
    .cart-item-title { font-weight: bold; font-size: 0.95rem; color: #333; margin-bottom: 5px; padding-right: 25px;}
    .cart-item-price { font-size: 1.1rem; font-weight: bold; color: #0d6efd; }
    .btn-remove-item { position: absolute; top: 10px; right: 10px; color: #dc3545; background: none; border: none; font-size: 1.2rem; }
    .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; padding: 15px 20px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; z-index: 1000; border-top-left-radius: 20px; border-top-right-radius: 20px; }
    .fab-button { position: fixed; bottom: 100px; right: 20px; width: 60px; height: 60px; border-radius: 30px; background: #0d6efd; color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4); z-index: 999; border: none; transition: transform 0.2s; }
    .fab-button:active { transform: scale(0.9); }
    .catalogo-item { cursor: pointer; transition: background 0.2s; }
    .catalogo-item:active { background: #f8f9fa; }
    .seccion-armador { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #00AEEF; }
    .seccion-armador h6 { color: #333; font-weight: bold; margin-bottom: 10px; }
</style>

<div class="bg-white p-3 d-flex align-items-center shadow-sm sticky-top">
    <a href="index.php" class="text-dark fs-3 me-3"><i class="bi bi-arrow-left-short"></i></a>
    <h5 class="m-0 fw-bold">Nuevo Presupuesto</h5>
    <a href="historial_presupuestos.php" class="ms-auto text-primary fs-4" title="Historial"><i class="bi bi-clock-history"></i></a>
</div>

<div class="container py-3">
    <div class="card card-custom p-3 mb-4">
        <div class="row g-2">
            <div class="col-8">
                <label class="form-label small fw-bold text-muted mb-1">Cliente (Para el PDF)</label>
                <input type="text" id="nombreCliente" class="form-control form-control-lg border-0 bg-light" placeholder="Ej: Juan Perez" style="font-size: 1.1rem;" value="<?= $clienteCargado ?>">
            </div>
            <div class="col-4">
                <label class="form-label small fw-bold text-muted mb-1">Fecha</label>
                <input type="date" id="fechaPresupuesto" class="form-control form-control-lg border-0 bg-light px-2" value="<?= date('Y-m-d') ?>" style="font-size: 1rem;">
            </div>
        </div>
    </div>

    <h6 class="fw-bold text-muted mb-3 ps-1">Ítems del Presupuesto</h6>
    
    <div id="carritoVacio" class="text-center py-5 text-muted">
        <i class="bi bi-cart-x" style="font-size: 3rem; opacity: 0.5;"></i>
        <p class="mt-2">El presupuesto está vacío.<br>Toca el botón <b>+</b> para empezar.</p>
    </div>

    <div id="listaCarrito"></div>
</div>

<button class="fab-button" data-bs-toggle="modal" data-bs-target="#modalOpcionesAgregar">
    <i class="bi bi-plus"></i>
</button>

<div class="bottom-bar">
    <div>
        <small class="text-muted d-block" style="line-height: 1;">Total Estimado</small>
        <h3 class="m-0 fw-bold text-dark" id="txtTotalFinal">$0.00</h3>
    </div>
    <button class="btn btn-primary btn-lg rounded-pill px-4" onclick="guardarYGenerarPDF()">
        Crear PDF <i class="bi bi-file-pdf-fill ms-1"></i>
    </button>
</div>

<?php 
// Traemos todo el HTML de las ventanitas emergentes
include 'modals_presupuesto.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php 
// Traemos todo el cerebro matemático y de interacción
include 'scripts_presupuesto.php'; 
?>

</body>
</html>