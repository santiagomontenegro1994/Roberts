<?php
date_default_timezone_set('America/Argentina/Cordoba');
require_once 'auth_jefes.php';
verificarSesionApp();

include 'header_mobile.php';
?>

<div class="bg-white p-3 d-flex align-items-center shadow-sm sticky-top">
    <a href="index.php" class="text-dark fs-3 me-3"><i class="bi bi-arrow-left-short"></i></a>
    <h5 class="m-0 fw-bold">Hacer Factura</h5>
</div>

<div class="container py-5 text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 75vh;">
    <div class="rounded-circle d-flex align-items-center justify-content-center mb-4 shadow-sm" style="width: 120px; height: 120px; background-color: #f3e8ff;">
        <i class="bi bi-tools text-purple" style="font-size: 3.5rem; color: #6f42c1;"></i>
    </div>
    
    <h3 class="fw-bold text-dark mb-2">Módulo en Construcción</h3>
    <p class="text-muted mb-5 px-3" style="font-size: 1.1rem;">
        Estamos conectando los engranajes de facturación. Pronto podrás emitir comprobantes directamente desde tu celular.
    </p>
    
    <a href="index.php" class="btn btn-dark btn-lg rounded-pill px-5 fw-bold shadow-sm">
        <i class="bi bi-house-door-fill me-2"></i> Volver al Menú
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>