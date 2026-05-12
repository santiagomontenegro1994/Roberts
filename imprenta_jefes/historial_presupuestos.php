<?php
date_default_timezone_set('America/Argentina/Cordoba');
require_once 'auth_jefes.php';
verificarSesionApp();
require_once '../funciones/conexion.php';

$MiConexion = ConexionBD();

// Traemos los últimos 100 presupuestos ordenados del más nuevo al más viejo
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
</style>

<div class="bg-white p-3 shadow-sm sticky-top">
    <div class="d-flex align-items-center mb-3">
        <a href="index.php" class="text-dark fs-3 me-3"><i class="bi bi-arrow-left-short"></i></a>
        <h5 class="m-0 fw-bold">Historial de Presupuestos</h5>
    </div>
    
    <div class="input-group bg-light rounded-pill overflow-hidden border">
        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="buscadorHistorial" class="form-control bg-transparent border-0 shadow-none py-2" placeholder="Buscar por cliente o N°..." onkeyup="filtrarHistorial()">
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
            $horaFmt = date('H:i', strtotime($p['fecha']));
        ?>
            <div class="card card-historial item-presupuesto" data-cliente="<?= strtolower(htmlspecialchars($p['cliente_nombre'])) ?>" data-id="<?= $p['idPresupuesto'] ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted small fw-bold">#<?= $p['idPresupuesto'] ?></span>
                            <h6 class="fw-bold m-0 mt-1 text-dark text-truncate" style="max-width: 200px;"><?= htmlspecialchars($p['cliente_nombre']) ?></h6>
                        </div>
                        <div class="text-end">
                            <span class="fecha-badge mb-1 d-inline-block"><?= $fechaFmt ?></span>
                            <h5 class="fw-bold text-success m-0">$<?= number_format($p['total'], 0, ',', '.') ?></h5>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        <a href="generar_pdf_presupuesto.php?id=<?= $p['idPresupuesto'] ?>" target="_blank" class="btn btn-outline-danger btn-sm w-50 fw-bold rounded-pill">
                            <i class="bi bi-file-pdf-fill me-1"></i> Ver PDF
                        </a>
                        <a href="presupuesto.php?cargar_id=<?= $p['idPresupuesto'] ?>" class="btn btn-primary btn-sm w-50 fw-bold rounded-pill">
                            <i class="bi bi-recycle me-1"></i> Reutilizar
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filtro rápido en JavaScript
    function filtrarHistorial() {
        let query = document.getElementById('buscadorHistorial').value.toLowerCase();
        let items = document.querySelectorAll('.item-presupuesto');
        
        items.forEach(item => {
            let cliente = item.getAttribute('data-cliente');
            let id = item.getAttribute('data-id');
            if (cliente.includes(query) || id.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>