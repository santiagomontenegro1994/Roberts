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

/**
 * 2. Consulta SQL:
 * Traemos el producto y la primera imagen de variante encontrada.
 */
$sql = "SELECT p.*, 
        (SELECT nombre_imagen FROM productos_imagenes WHERE id_producto = p.id LIMIT 1) as imagen_variante
        FROM productos p 
        WHERE p.idActivo = 1 
        ORDER BY p.titulo ASC";

$query = mysqli_query($MiConexion, $sql);
if (!$query) { die("Error SQL: " . mysqli_error($MiConexion)); }
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Inventario</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                
                <?php if (isset($_SESSION['Mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['Estilo'] ?? 'success' ?> alert-dismissible fade show mt-3" role="alert">
                        <?= $_SESSION['Mensaje'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                        unset($_SESSION['Mensaje']); 
                        unset($_SESSION['Estilo']);
                    ?>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <h5 class="card-title m-0">Listado de Productos</h5>
                    <a href="abm_producto.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Nuevo</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped datatable">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Acciones</th>
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
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($img_url) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover;" 
                                         class="rounded shadow-sm" 
                                         onerror="this.src='../img/productos/sin-imagen.jpg'">
                                </td>
                                <td><strong><?= htmlspecialchars($row['titulo'] ?? 'Sin nombre') ?></strong></td>
                                <td>
                                    <?php if(isset($row['stock_infinito']) && $row['stock_infinito'] == 1): ?>
                                        <span class="badge bg-info text-dark">Infinito</span>
                                    <?php else: ?>
                                        <?= (int)($row['stock'] ?? 0) ?> u.
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format((float)($row['precio'] ?? 0), 0, ',', '.') ?></td>
                                <td>
                                    <a href="abm_producto.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-xs me-1" title="Modificar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    
                                    <button type="button" class="btn btn-danger btn-xs" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEliminar" 
                                            data-id="<?= $row['id'] ?>"
                                            title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
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
  <div class="modal-dialog">
    <form method="POST" action="procesar_eliminacion.php">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar Eliminación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_producto" id="id_producto_a_eliminar">
          <p>Para eliminar este producto, ingresá tu contraseña de usuario:</p>
          <input type="password" name="password" class="form-control" required placeholder="Tu contraseña">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Eliminar Definitivamente</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Pasar el ID al modal cuando se presiona el botón de borrar
const modalEliminar = document.getElementById('modalEliminar');
modalEliminar.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button.getAttribute('data-id');
  document.getElementById('id_producto_a_eliminar').value = id;
});
</script>

<?php require ('../shared/footer.inc.php'); ?>