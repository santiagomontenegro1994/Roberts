<?php
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

$dominio_base = "https://robertsgrafica.com/img/"; 

/**
 * SQL CORREGIDO:
 * 1. Obtenemos la imagen principal de la tabla productos.
 * 2. Si no existe, buscamos la primera de la tabla productos_imagenes.
 * 3. Usamos COALESCE para manejar los nulos y evitar errores.
 */
$sql = "SELECT p.*, 
        COALESCE(p.imagen, (SELECT nombre_imagen FROM productos_imagenes WHERE id_producto = p.id LIMIT 1)) as imagen_final,
        c.nombre as nombre_categoria
        FROM productos p 
        LEFT JOIN categorias_prod c ON p.categoria = c.id
        WHERE p.idActivo = 1 
        ORDER BY p.titulo ASC";

$query = mysqli_query($MiConexion, $sql);
if (!$query) { die("Error SQL: " . mysqli_error($MiConexion)); }
?>

<main id="main" class="main">
    <div class="pagetitle"><h1>Inventario</h1></div>
    <section class="section">
        <div class="card">
            <div class="card-body">
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
                                // LOGICA DE RUTA: 
                                // Si la imagen guardada no empieza con 'productos/', se lo agregamos para completar la URL
                                $nombre_archivo = $row['imagen_final'] ?? 'productos/sin-imagen.jpg';
                                if (strpos($nombre_archivo, 'productos/') === false) {
                                    $nombre_archivo = 'productos/' . $nombre_archivo;
                                }
                                $img_url = $dominio_base . $nombre_archivo;
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($img_url) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover;" 
                                         class="rounded shadow-sm" 
                                         onerror="this.src='../img/productos/sin-imagen.jpg'">
                                </td>
                                <td><strong><?= htmlspecialchars($row['titulo'] ?? 'Sin nombre') ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['nombre_categoria'] ?? 'Sin cat.') ?></span></td>
                                <td><?= (isset($row['stock_infinito']) && $row['stock_infinito'] == 1) ? 'Infinito' : (int)($row['stock'] ?? 0) . ' u.' ?></td>
                                <td>$<?= number_format((float)($row['precio'] ?? 0), 0, ',', '.') ?></td>
                                <td>
                                    <a href="abm_producto.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-xs"><i class="bi bi-pencil-fill"></i></a>
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