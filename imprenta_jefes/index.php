<?php
date_default_timezone_set('America/Argentina/Cordoba');
require_once 'auth_jefes.php';
verificarSesionApp();

include 'header_mobile.php';

// Leemos la fecha de última modificación del JSON
$ruta_json = __DIR__ . '/../datos/productos_simples.json';
$ultima_sync = file_exists($ruta_json) ? date('d/m/Y - H:i', filemtime($ruta_json)) : 'Nunca';
?>

<style>
    .top-navbar { background: #fff; padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
    
    .main-menu { padding: 20px 20px 30px 20px; }
    .menu-btn { 
        background: #fff; border: none; border-radius: 16px; padding: 25px 20px; 
        margin-bottom: 20px; display: flex; align-items: center; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.04); transition: transform 0.2s;
        text-decoration: none; color: #333;
    }
    .menu-btn:active { transform: scale(0.98); }
    
    .btn-icon { 
        width: 60px; height: 60px; border-radius: 15px; 
        display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-right: 15px; 
    }
    .icon-blue { background: #e7f1ff; color: #0d6efd; }
    .icon-green { background: #e8f8f5; color: #198754; }
    .icon-purple { background: #f3e8ff; color: #6f42c1; }
    
    .btn-text h5 { margin: 0; font-weight: bold; font-size: 1.1rem; }
    .btn-text p { margin: 0; font-size: 0.85rem; color: #6c757d; }

    /* Estilo para el contenedor de instalación (Oculto por defecto) */
    #installAppContainer { display: none; }
</style>

<div class="top-navbar d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="m-0 fw-bold">Hola, <?= htmlspecialchars($_SESSION['Usuario_Nombre']) ?></h5>
        <small class="text-muted">Gráfica Roberts</small>
    </div>
    <a href="#" class="text-danger text-decoration-none" style="font-size: 1.5rem;" data-bs-toggle="modal" data-bs-target="#modalSalir">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</div>

<div class="px-4 mt-2">
    <a href="sincronizar.php" class="btn btn-dark w-100 p-3 rounded-4 d-flex align-items-center justify-content-between" style="box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <div class="text-start">
            <h6 class="m-0 fw-bold text-white"><i class="bi bi-arrow-repeat me-2"></i>Sincronizar Precios</h6>
            <small style="color: #adb5bd; font-size: 0.75rem;">Última vez: <?= $ultima_sync ?> hs</small>
        </div>
        <i class="bi bi-cloud-download text-white fs-4"></i>
    </a>
</div>

<div class="main-menu">
    
    <div id="installAppContainer" class="mb-4">
        <button id="btnInstalarApp" class="btn btn-dark w-100 p-3 rounded-4 fw-bold d-flex align-items-center justify-content-center" style="box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
            <i class="bi bi-download fs-4 me-2"></i> Instalar App en el Celular
        </button>
    </div>

    <a href="presupuesto.php" class="menu-btn w-100">
        <div class="btn-icon icon-blue"><i class="bi bi-calculator-fill"></i></div>
        <div class="btn-text text-start">
            <h5>Generar Presupuesto</h5>
            <p>Cotizador y lista de precios</p>
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
    // 1. Registrar el Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js');
    }

    // 2. Lógica del Botón de Instalación
    let deferredPrompt;
    const installContainer = document.getElementById('installAppContainer');
    const installBtn = document.getElementById('btnInstalarApp');

    // Comprobamos si el celular ya lo tiene instalado (modo standalone)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

    if (!isStandalone) {
        // Atrapamos el evento que lanza Google Chrome / Android cuando la web se puede instalar
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevenimos que salga el cartel feo automático del navegador
            e.preventDefault();
            // Guardamos el evento para usarlo al tocar nuestro botón
            deferredPrompt = e;
            // Mostramos nuestro botón negro bonito
            installContainer.style.display = 'block';
        });

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                // Lanzamos el cartel de instalación nativo
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    // Si aceptó instalar, ocultamos el botón
                    installContainer.style.display = 'none';
                }
                deferredPrompt = null;
            }
        });
    }
</script>
</body>
</html>