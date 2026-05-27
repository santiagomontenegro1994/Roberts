<?php
session_start();
if (empty($_SESSION['Usuario_Nombre'])) { header('Location: ../core/cerrarsesion.php'); exit; }

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// URL BASE (Ajustala a tu dominio real)
$dominio_base = "https://robertsgrafica.com/img/"; 

// SQL Mejorado: Trae el producto y, si no tiene imagen, intenta buscar la primera variante
$sql = "SELECT p.*, 
        (SELECT GROUP_CONCAT(c.nombre SEPARATOR ', ') 
         FROM categorias_prod c 
         JOIN producto_categoria pc ON c.id = pc.id_categoria 
         WHERE pc.id_producto = p.id) as nombre_categoria,
        (SELECT nombre_imagen FROM productos_imagenes WHERE id_producto = p.id LIMIT 1) as imagen_variante
        FROM productos p 
        WHERE p.idActivo = 1 
        ORDER BY p.titulo ASC";

$query = mysqli_query($MiConexion, $sql);
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Inventario</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Productos en Stock</h5>
                
                <div class="table-responsive">
                    <table class="table table-striped datatable">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($query)) { 
                                // LÓGICA DE IMAGEN: Prioridad 1: Imagen Principal | Prioridad 2: Imagen de Variante | Prioridad 3: Placeholder
                                $img_archivo = !empty($row['imagen']) ? $row['imagen'] : $row['imagen_variante'];
                                $img_url = !empty($img_archivo) ? $dominio_base . $img_archivo : $dominio_base . "productos/sin-imagen.jpg";
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($img_url) ?>" style="width: 50px; height: 50px; object-fit: cover;" class="rounded shadow-sm" onerror="this.src='../img/productos/sin-imagen.jpg'">
                                </td>
                                <td><strong><?= htmlspecialchars($row['titulo'] ?? '') ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['nombre_categoria'] ?? 'Sin cat.') ?></span></td>
                                <td>
                                    <?php if($row['stock_infinito'] == 1): ?>
                                        <span class="badge bg-info text-dark">Infinito</span>
                                    <?php else: ?>
                                        <?= (int)$row['stock'] ?> u.
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format((float)$row['precio'], 0, ',', '.') ?></td>
                                <td>
                                    <a href="abm_producto.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-xs me-1"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="inventario.php?accion=borrar&id=<?= $row['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('¿Confirma eliminar?');"><i class="bi bi-trash-fill"></i></a>
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

<?php require ('../shared/footer.inc.php'); ?>