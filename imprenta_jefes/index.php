<?php
require_once 'auth_jefes.php';
verificarSesionApp();

include 'header_mobile.php';
?>

<div class="top-navbar d-flex justify-content-between align-items-center">
    <div>
        <h5 class="m-0 fw-bold">Hola, <?= htmlspecialchars($_SESSION['Usuario_Nombre']) ?></h5>
        <small class="text-muted">Gráfica Roberts</small>
    </div>
    <a href="#" class="text-danger text-decoration-none" style="font-size: 1.5rem;" data-bs-toggle="modal" data-bs-target="#modalSalir">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</div>

<div class="main-menu">
    
    <a href="presupuesto.php" class="menu-btn w-100">
        <div class="btn-icon icon-blue"><i class="bi bi-calculator-fill"></i></div>
        <div class="btn-text text-start">
            <h5>Generar Presupuesto</h5>
            <p>Cotizador y lista de precios (PDF)</p>
        </div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
    </a>

    <a href="carga_rapida.php" class="menu-btn w-100">
        <div class="btn-icon icon-green"><i class="bi bi-lightning-charge-fill"></i></div>
        <div class="btn-text text-start">
            <h5>Carga Rápida</h5>
            <p>Ingresar trabajo simplificado</p>
        </div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
    </a>

    <a href="facturar.php" class="menu-btn w-100">
        <div class="btn-icon icon-purple"><i class="bi bi-receipt"></i></div>
        <div class="btn-text text-start">
            <h5>Hacer Factura</h5>
            <p>Emisión rápida</p>
        </div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
    </a>

</div>

<div class="modal fade" id="modalSalir" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 15px;">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">¿Cerrar Sesión?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pb-4">
        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
        <p class="mt-3 mb-0">Si cierras sesión, tendrás que volver a ingresar tu usuario y contraseña la próxima vez.</p>
      </div>
      <div class="modal-footer border-0 d-flex justify-content-between">
        <button type="button" class="btn btn-light w-45" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <a href="logout.php" class="btn btn-danger w-45" style="border-radius: 10px;">Sí, salir</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js');
  }
</script>
</body>
</html>