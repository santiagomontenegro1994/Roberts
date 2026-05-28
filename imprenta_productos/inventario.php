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

$where_clause = "WHERE 1=1"; // Condición base (trae todo)

if ($filtro_estado === '1') {
    $where_clause .= " AND p.idActivo = 1"; // Solo publicados
} elseif ($filtro_estado === '2') {
    $where_clause .= " AND p.idActivo = 2"; // Solo ocultos
}

/**
 * 2. Consulta SQL Dinámica:
 */
$sql = "SELECT p.*, 
        (SELECT nombre_imagen FROM productos_imagenes WHERE id_producto = p.id LIMIT 1) as imagen_variante
        FROM productos p 
        $where_clause 
        ORDER BY p.titulo ASC";

$query = mysqli_query($MiConexion, $sql);
if (!$query) { die("Error SQL: " . mysqli_error($MiConexion)); }

// Contadores rápidos para la interfaz
$total_productos = mysqli_num_rows($query);
?>

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
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small mb-1"><i class="bi bi-eye"></i> Filtrar por Visibilidad</label>
                        <select name="estado" class="form-select shadow-sm border-secondary" onchange="this.form.submit()">
                            <option value="todos" <?= $filtro_estado == 'todos' ? 'selected' : '' ?>>Todos los productos</option>
                            <option value="1" <?= $filtro_estado == '1' ? 'selected' : '' ?>>🟢 Solo Publicados en Web</option>
                            <option value="2" <?= $filtro_estado == '2' ? 'selected' : '' ?>>🔴 Solo Ocultos</option>
                        </select>
                    </div>
                    <div class="col-md-8 text-end mt-4 mt-md-0">
                        <span class="text-muted small me-3">Mostrando <b><?= $total_productos ?></b> productos</span>
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
                    <table class="table table-hover align-middle datatable">
                        <thead class="table-light">
                            <tr>
                                <th>IMG</th>
                                <th>Producto</th>
                                <th>Estado</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($query)) { 
                                // LÓGICA DE RUTA MEJORADA
                                if (!empty($row['imagen'])) {
                                    $ruta_archivo = $row['imagen'];
                                    if (strpos($ruta_archivo, 'productos/') === false) { $ruta_archivo = 'productos/' . $ruta_archivo; }
                                } elseif (!empty($row['imagen_variante'])) {
                                    $ruta_archivo = 'productos/variantes/' . $row['imagen_variante'];
                                } else {
                                    $ruta_archivo = 'productos/sin-imagen.jpg';
                                }
                                
                                $img_url = $dominio_base . $ruta_archivo;
                                
                                // Determinar si está activo para opacar la fila si está oculto
                                $claseFila = ($row['idActivo'] == 2) ? 'table-secondary opacity-75' : '';
                            ?>
                            <tr class="<?= $claseFila ?>">
                                <td style="width: 60px;">
                                    <img src="<?= htmlspecialchars($img_url) ?>" 
                                         style="width: 45px; height: 45px; object-fit: cover;" 
                                         class="rounded shadow-sm border" 
                                         onerror="this.src='../img/productos/sin-imagen.jpg'">
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($row['titulo'] ?? 'Sin nombre') ?></strong>
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
                                <td>
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
                                <td class="fw-bold text-success">
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

<script>
// Pasar el ID al modal cuando se presiona el botón de borrar
const modalEliminar = document.getElementById('modalEliminar');
if (modalEliminar) {
    modalEliminar.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      document.getElementById('id_producto_a_eliminar').value = id;
    });
}
</script>

<?php require ('../shared/footer.inc.php'); ?>