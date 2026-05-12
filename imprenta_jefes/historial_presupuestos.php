<?php
date_default_timezone_set('America/Argentina/Cordoba');
require_once 'auth_jefes.php';
verificarSesionApp();
require_once '../funciones/conexion.php';

$MiConexion = ConexionBD();

// Traemos los últimos 100 presupuestos
$query = mysqli_query($MiConexion, "SELECT * FROM presupuestos_historial ORDER BY idPresupuesto DESC LIMIT 100");
$presupuestos = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $presupuestos[] = $row;
    }
}

include 'header_mobile.php';
?>
<style>
    body { background-color: #f0f2f5; padding-bottom: 30px; }
    .card-historial { border: none; border-radius: 15px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: transform 0.2s; }
    .card-historial:active { transform: scale(0.98); }
    .fecha-badge { background-color: #e7f1ff; color: #0d6efd; padding: 4px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: bold; }
    .item-resumen { font-size: 0.85rem; color: #6c757d; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<div class="bg-white p-3 shadow-sm sticky-top">
    <div class="d-flex align-items-center mb-3">
        <a href="index.php" class="text-dark fs-3 me-3"><i class="bi bi-arrow-left-short"></i></a>
        <h5 class="m-0 fw-bold">Historial</h5>
    </div>
    
    <div class="input-group bg-light rounded-pill overflow-hidden border">
        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="buscadorHistorial" class="form-control bg-transparent border-0 shadow-none py-2" placeholder="Ej: Miska Tarjetas..." onkeyup="filtrarHistorial()">
    </div>
</div>

<div class="container py-4" id="listaHistorial">
    <?php if (empty($presupuestos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2">Todavía no hay presupuestos guardados.</p>
        </div>
    <?php else: ?>
        <?php foreach ($presupuestos as $p): 
            $fechaFmt = date('d/m/Y', strtotime($p['fecha']));
            $cliente = htmlspecialchars($p['cliente_nombre']);
            
            // Extraer info de los items
            $items = json_decode($p['items_json'], true) ?? [];
            $descripciones = [];
            foreach ($items as $item) {
                $descripciones[] = $item['descripcion'];
            }
            $textoItems = htmlspecialchars(implode(" • ", $descripciones));
            $textoBusqueda = strtolower($p['idPresupuesto'] . " " . $cliente . " " . $textoItems);
        ?>
            <div class="card card-historial item-presupuesto" data-search="<?= $textoBusqueda ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <span class="text-muted small fw-bold">#<?= $p['idPresupuesto'] ?></span>
                            <h6 class="fw-bold m-0 mt-1 text-dark"><?= $cliente ?></h6>
                        </div>
                        <div class="text-end">
                            <span class="fecha-badge mb-1 d-inline-block"><?= $fechaFmt ?></span>
                            <h5 class="fw-bold text-success m-0">$<?= number_format($p['total'], 0, ',', '.') ?></h5>
                        </div>
                    </div>
                    
                    <div class="item-resumen mt-2 mb-3 pe-2">
                        <i class="bi bi-card-text me-1 opacity-50"></i> <?= $textoItems ?: 'Sin detalle' ?>
                    </div>
                    
                    <div class="d-flex gap-2 pt-3 border-top">
                        <a href="generar_pdf_presupuesto.php?id=<?= $p['idPresupuesto'] ?>" target="_blank" class="btn btn-outline-danger btn-sm w-50 fw-bold rounded-pill">
                            <i class="bi bi-file-pdf-fill me-1"></i> PDF
                        </a>
                        <a href="presupuesto.php?cargar_id=<?= $p['idPresupuesto'] ?>" class="btn btn-primary btn-sm w-50 fw-bold rounded-pill">
                            <i class="bi bi-pencil-square me-1"></i> Editar / Usar
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Buscador Inteligente (Múltiples palabras)
    function filtrarHistorial() {
        // Obtenemos lo que escribió el usuario y lo dividimos por espacios (ej: ["miska", "tarjetas"])
        let queryTerms = document.getElementById('buscadorHistorial').value.toLowerCase().trim().split(/\s+/);
        let items = document.querySelectorAll('.item-presupuesto');
        
        items.forEach(item => {
            let textToSearch = item.getAttribute('data-search');
            
            // Verificamos si TODAS las palabras escritas están adentro del texto del presupuesto
            let matchesAll = queryTerms.every(term => textToSearch.includes(term));
            
            item.style.display = matchesAll ? 'block' : 'none';
        });
    }
</script>
</body>
</html>