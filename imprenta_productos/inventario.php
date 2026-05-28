<?php
session_start();

// 1. Seguridad básica
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// URL BASE
$dominio_base = "https://robertsgrafica.com/img/"; 

// --- LÓGICA DE FILTROS ---
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$where_clause = "WHERE 1=1"; 

if ($filtro_estado === '1') {
    $where_clause .= " AND p.idActivo = 1";
} elseif ($filtro_estado === '2') {
    $where_clause .= " AND p.idActivo = 2";
}

// 2. Consulta SQL Principal
$sql = "SELECT p.*, 
        (SELECT nombre_imagen FROM productos_imagenes WHERE id_producto = p.id LIMIT 1) as imagen_variante
        FROM productos p 
        $where_clause 
        ORDER BY p.titulo ASC";

$query = mysqli_query($MiConexion, $sql);
if (!$query) { die("Error SQL: " . mysqli_error($MiConexion)); }
$total_productos = mysqli_num_rows($query);

// 3. Pre-cargar todas las variantes para el Modal de Previsualización
$sql_variantes = "SELECT * FROM productos_imagenes";
$q_var = mysqli_query($MiConexion, $sql_variantes);
$variantes_por_producto = [];
while($v = mysqli_fetch_assoc($q_var)){
    $variantes_por_producto[$v['id_producto']][] = $v;
}
?>

<style>
    /* Estilos para que el mouse muestre que la imagen es clickeable */
    .img-preview-trigger { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .img-preview-trigger:hover { transform: scale(1.1); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    
    /* Punteros de ordenamiento en la tabla */
    th.sortable { cursor: pointer; position: relative; padding-right: 20px !important; }
    th.sortable::after { content: '↕'; position: absolute; right: 5px; opacity: 0.3; }
    th.sortable.asc::after { content: '↑'; opacity: 1; }
    th.sortable.desc::after { content: '↓'; opacity: 1; }

    /* ESTILOS DEL MODAL (Adaptados del Frontend) */
    .modal-dot { width: 32px; height: 32px; border-radius: 50%; border: 2px solid #ddd; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .modal-dot:hover { transform: scale(1.15); }
    .modal-dot.active { border-color: #00AEEF; box-shadow: 0 0 0 3px #00AEEF inset; transform: scale(1.15); }
    
    #modalPreview { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.65); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
    #modalPreview .modal-content-custom { display: flex; flex-direction: row; width: 90%; max-width: 1000px; max-height: 85vh; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.3); position: relative; }
    #modalPreview .close-modal { position: absolute; top: 15px; right: 20px; font-size: 26px; color: #888; cursor: pointer; z-index: 100; transition: 0.3s; background: #fff; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    #modalPreview .close-modal:hover { color: #e63946; transform: scale(1.1); }
    #modalPreview .modal-left { width: 50%; background: #f8f9fa; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; position: relative; }
    #modalPreview #m-img { width: 100%; max-height: 400px; object-fit: contain; filter: drop-shadow(0 15px 25px rgba(0,0,0,0.1)); transition: transform 0.4s ease; }
    #modalPreview .modal-details { width: 50%; padding: 40px; overflow-y: auto; display: block; max-height: 85vh; }
    #modalPreview #m-titulo { font-size: 1.8rem; font-weight: 700; color: #222; margin-bottom: 20px; line-height: 1.2; }
    #modalPreview #m-precio { font-size: 1.6rem; color: #0d6efd; font-weight: 800; }
    #modalPreview #m-desc { font-size: 0.95rem; color: #555; line-height: 1.7; margin-bottom: 20px; }
    
    @media (max-width: 768px) {
        #modalPreview .modal-content-custom { flex-direction: column; width: 95%; max-height: 90vh; overflow-y: auto; display: block; }
        #modalPreview .modal-left { width: 100%; padding: 40px 20px 10px 20px; background: #fff; }
        #modalPreview .modal-details { width: 100%; padding: 10px 20px 40px 20px; overflow-y: visible; max-height: none; }
    }
</style>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Gestión de Inventario</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item active">Inventario</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body py-3 bg-light rounded">
                <form method="GET" class="row g-3 align-items-end">
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small mb-1"><i class="bi bi-eye"></i> Visibilidad</label>
                        <select name="estado" class="form-select shadow-sm border-secondary" onchange="this.form.submit()">
                            <option value="todos" <?= $filtro_estado == 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="1" <?= $filtro_estado == '1' ? 'selected' : '' ?>>🟢 Publicados</option>
                            <option value="2" <?= $filtro_estado == '2' ? 'selected' : '' ?>>🔴 Ocultos</option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold text-muted small mb-1"><i class="bi bi-search"></i> Buscador Rápido</label>
                        <input type="text" id="buscadorTabla" class="form-control shadow-sm border-primary" placeholder="Escribí para buscar por nombre...">
                    </div>

                    <div class="col-md-4 text-end">
                        <span class="text-muted small me-2"><b><?= $total_productos ?></b> items</span>
                        <a href="abm_producto.php" class="btn btn-primary shadow-sm fw-bold">
                            <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body pt-4">
                
                <?php if (isset($_SESSION['Mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['Estilo'] ?? 'success' ?> alert-dismissible fade show" role="alert">
                        <i class="bi <?= ($_SESSION['Estilo'] == 'danger') ? 'bi-exclamation-octagon' : 'bi-check-circle' ?> me-1"></i>
                        <?= $_SESSION['Mensaje'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                        unset($_SESSION['Mensaje']); 
                        unset($_SESSION['Estilo']);
                    ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaInventario">
                        <thead class="table-light">
                            <tr>
                                <th>IMG</th>
                                <th class="sortable" data-col="1">Producto</th>
                                <th class="sortable" data-col="2">Estado</th>
                                <th class="sortable" data-col="3">Stock</th>
                                <th class="sortable" data-col="4">Precio</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($query)) { 
                                // RUTA DE IMAGEN
                                if (!empty($row['imagen'])) {
                                    $ruta_archivo = $row['imagen'];
                                    if (strpos($ruta_archivo, 'productos/') === false) { $ruta_archivo = 'productos/' . $ruta_archivo; }
                                } elseif (!empty($row['imagen_variante'])) {
                                    $ruta_archivo = 'productos/variantes/' . $row['imagen_variante'];
                                } else {
                                    $ruta_archivo = 'productos/sin-imagen.jpg';
                                }
                                $img_url = $dominio_base . $ruta_archivo;
                                
                                // JSON PARA EL MODAL DE PREVISUALIZACIÓN
                                $variantes = isset($variantes_por_producto[$row['id']]) ? $variantes_por_producto[$row['id']] : [];
                                $prod_data = [
                                    'titulo' => $row['titulo'],
                                    'descripcion' => $row['descripcion'],
                                    'precio' => $row['precio'],
                                    'imagen' => $img_url,
                                    'stock' => $row['stock'],
                                    'stock_infinito' => $row['stock_infinito'],
                                    'color_principal' => $row['color_principal'],
                                    'color_principal_hex' => $row['color_principal_hex'],
                                    'variantes' => $variantes
                                ];
                                $json_data = htmlspecialchars(json_encode($prod_data), ENT_QUOTES, 'UTF-8');

                                $claseFila = ($row['idActivo'] == 2) ? 'table-secondary opacity-75' : '';
                            ?>
                            <tr class="<?= $claseFila ?>">
                                <td style="width: 60px;">
                                    <img src="<?= htmlspecialchars($img_url) ?>" 
                                         class="rounded shadow-sm border img-preview-trigger" 
                                         style="width: 45px; height: 45px; object-fit: cover;"
                                         data-info='<?= $json_data ?>'
                                         title="Clic para previsualizar"
                                         onerror="this.src='../img/productos/sin-imagen.jpg'">
                                </td>
                                <td>
                                    <strong class="d-block text-dark nombre-producto"><?= htmlspecialchars($row['titulo'] ?? 'Sin nombre') ?></strong>
                                    <?php if(!empty($row['destacado'])): ?> <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.65rem;">Destacado</span> <?php endif; ?>
                                    <?php if(!empty($row['nuevo'])): ?> <span class="badge bg-info text-dark border border-info" style="font-size: 0.65rem;">Nuevo</span> <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['idActivo'] == 1): ?>
                                        <span class="badge bg-success-light text-success border border-success"><i class="bi bi-globe"></i> Publicado</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-light text-danger border border-danger"><i class="bi bi-eye-slash"></i> Oculto</span>
                                    <?php endif; ?>
                                </td>
                                <td data-sort="<?= (isset($row['stock_infinito']) && $row['stock_infinito'] == 1) ? 999999 : (int)($row['stock'] ?? 0) ?>">
                                    <?php if(isset($row['stock_infinito']) && $row['stock_infinito'] == 1): ?>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-infinity"></i> A medida</span>
                                    <?php else: ?>
                                        <?php $stock = (int)($row['stock'] ?? 0); ?>
                                        <?php if($stock <= 0): ?>
                                            <span class="badge bg-danger">Sin Stock</span>
                                        <?php elseif($stock <= 5): ?>
                                            <span class="badge bg-warning text-dark"><?= $stock ?> u. (Bajo)</span>
                                        <?php else: ?>
                                            <span class="fw-bold"><?= $stock ?> u.</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success" data-sort="<?= $row['precio'] ?>">
                                    $<?= number_format((float)($row['precio'] ?? 0), 0, ',', '.') ?>
                                </td>
                                <td class="text-end">
                                    <a href="abm_producto.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm shadow-sm" title="Editar Producto">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm shadow-sm ms-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEliminar" 
                                            data-id="<?= $row['id'] ?>"
                                            title="Eliminar Producto">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="procesar_eliminacion.php">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Eliminación</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="id_producto" id="id_producto_a_eliminar">
          <div class="text-center mb-3">
              <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
          </div>
          <p class="text-center mb-4">¿Estás seguro que deseás eliminar este producto? Esta acción borrará sus imágenes y <b>no se puede deshacer</b>.</p>
          <div class="mb-3">
            <label class="form-label fw-bold text-muted">Ingresá tu contraseña para confirmar:</label>
            <input type="password" name="password" class="form-control form-control-lg text-center" required placeholder="••••••••">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-trash3-fill me-1"></i> Eliminar Definitivamente</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="modalPreview">
    <div class="modal-content-custom">
        <span class="close-modal" onclick="cerrarPreview()">×</span>
        
        <div class="modal-left">
            <img id="m-img" src="" alt="Detalle Producto">
            
            <div id="m-colores-wrapper" style="display:none; margin-top: 20px; padding: 15px; background: #fff; border-radius: 12px; border: 1px solid #eaeaea; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                <label style="font-weight:bold; display:block; margin-bottom:12px; font-size: 0.95rem; text-align: center; color: #333;">
                    Color seleccionado: <span id="m-nombre-color" class="text-primary"></span>
                </label>
                <div id="m-lista-colores" style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;"></div>
            </div>
        </div>
        
        <div class="modal-details">
            <span class="badge bg-secondary mb-2"><i class="bi bi-eye"></i> Vista Previa Web</span>
            <h3 id="m-titulo">Titulo</h3>
            
            <div class="mb-3">
                <span id="m-precio">Precio</span>
            </div>

            <p id="m-desc">Descripción.</p>
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                <strong>Disponibilidad:</strong> <span id="m-stock-display"></span>
            </div>
        </div>
    </div>
</div>

<script>
// ==========================================
// 1. BUSCADOR EN TIEMPO REAL
// ==========================================
document.getElementById('buscadorTabla').addEventListener('keyup', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tablaInventario tbody tr');
    
    rows.forEach(row => {
        const nombre = row.querySelector('.nombre-producto').textContent.toLowerCase();
        row.style.display = nombre.includes(term) ? '' : 'none';
    });
});

// ==========================================
// 2. ORDENAMIENTO DE COLUMNAS (CLICK EN TH)
// ==========================================
document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        const colIndex = th.dataset.col;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = th.classList.contains('asc');
        
        // Limpiar iconos de otras columnas
        table.querySelectorAll('th').forEach(h => h.classList.remove('asc', 'desc'));
        th.classList.toggle('asc', !isAsc);
        th.classList.toggle('desc', isAsc);

        rows.sort((a, b) => {
            const cellA = a.children[colIndex];
            const cellB = b.children[colIndex];
            
            // Usamos data-sort si existe (ideal para precios y stock ocultos), si no el texto
            const valA = cellA.hasAttribute('data-sort') ? parseFloat(cellA.dataset.sort) : cellA.textContent.trim().toLowerCase();
            const valB = cellB.hasAttribute('data-sort') ? parseFloat(cellB.dataset.sort) : cellB.textContent.trim().toLowerCase();

            if (valA < valB) return isAsc ? -1 : 1;
            if (valA > valB) return isAsc ? 1 : -1;
            return 0;
        });

        tbody.append(...rows);
    });
});

// ==========================================
// 3. MODAL DE ELIMINACIÓN
// ==========================================
const modalEliminar = document.getElementById('modalEliminar');
if (modalEliminar) {
    modalEliminar.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('id_producto_a_eliminar').value = button.getAttribute('data-id');
    });
}

// ==========================================
// 4. MODAL DE PREVISUALIZACIÓN FRONTEND
// ==========================================
const dominioBase = "<?= $dominio_base ?>";
const modalPreview = document.getElementById("modalPreview");
const mImg = document.getElementById("m-img");
const mTit = document.getElementById("m-titulo");
const mDesc = document.getElementById("m-desc");
const mPrecio = document.getElementById("m-precio");
const mStockDisplay = document.getElementById("m-stock-display");
const mColorWrapper = document.getElementById("m-colores-wrapper");
const mListaColores = document.getElementById("m-lista-colores");
const mNombreColor = document.getElementById("m-nombre-color");

document.querySelectorAll('.img-preview-trigger').forEach(img => {
    img.addEventListener('click', function() {
        const data = JSON.parse(this.getAttribute('data-info'));
        
        mTit.innerText = data.titulo;
        mDesc.innerHTML = data.descripcion;
        
        const precioNum = parseFloat(data.precio);
        if(precioNum > 0) {
            mPrecio.innerText = "$ " + new Intl.NumberFormat('es-AR').format(precioNum);
        } else {
            mPrecio.innerText = "Consultar Precio";
        }

        // Armar lista de colores
        let todosLosColores = [];
        todosLosColores.push({
            nombre: data.color_principal || 'Original',
            hex: data.color_principal_hex || '#000000',
            imagen: data.imagen,
            stock: parseInt(data.stock),
            infinito: parseInt(data.stock_infinito)
        });

        if(data.variantes && data.variantes.length > 0) {
            data.variantes.forEach(v => {
                todosLosColores.push({
                    nombre: v.color_nombre,
                    hex: v.color_hex,
                    imagen: dominioBase + "productos/variantes/" + v.nombre_imagen,
                    stock: parseInt(v.stock),
                    infinito: 0 
                });
            });
        }

        mListaColores.innerHTML = ''; 
        if(todosLosColores.length > 1) { 
            mColorWrapper.style.display = 'block';
            todosLosColores.forEach((c, index) => {
                const dot = document.createElement('div');
                dot.className = 'modal-dot';
                dot.style.backgroundColor = c.hex;
                dot.title = c.nombre; 
                dot.onclick = () => aplicarColor(c, dot);
                if(index === 0) aplicarColor(c, dot);
                mListaColores.appendChild(dot);
            });
        } else {
            mColorWrapper.style.display = 'none';
            aplicarColor(todosLosColores[0], null);
        }

        modalPreview.style.display = "flex";
        document.body.style.overflow = "hidden";
    });
});

function aplicarColor(colorData, dotElement) {
    if(dotElement) {
        const dots = mListaColores.querySelectorAll('.modal-dot');
        dots.forEach(d => d.classList.remove('active'));
        dotElement.classList.add('active');
    }

    mImg.src = colorData.imagen;
    mNombreColor.innerText = colorData.nombre;

    if (colorData.infinito == 1) {
        mStockDisplay.innerHTML = '<span class="text-primary fw-bold"><i class="bi bi-infinity"></i> A Medida / Disponible</span>';
    } else if (colorData.stock > 0) {
        mStockDisplay.innerHTML = `<span class="text-success fw-bold">${colorData.stock} unidades</span>`;
    } else {
        mStockDisplay.innerHTML = '<span class="text-danger fw-bold">AGOTADO</span>';
    }
}

function cerrarPreview() {
    modalPreview.style.display = "none";
    document.body.style.overflow = "auto";
}

// Cerrar al tocar el fondo oscuro
modalPreview.addEventListener("click", (e) => { 
    if (e.target === modalPreview) cerrarPreview(); 
});
</script>

<?php require ('../shared/footer.inc.php'); ?>