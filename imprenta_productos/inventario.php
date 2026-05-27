<?php
session_start();

// 1. Seguridad y Conexión
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// URL BASE (Esta es la URL que tu web usa para cargar fotos)
$dominio_base = "https://robertsgrafica.com/img/"; 

/**
 * CONSULTA SQL CORREGIDA:
 * Agregamos la subconsulta 'imagen_variante' para que, si no hay foto principal, 
 * tengamos el nombre del archivo de la variante.
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">Listado de Productos</h5>
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
                                // LÓGICA DE RUTA: 
                                // 1. Prioridad: imagen principal. 2. Si no, imagen_variante. 3. Si no, sin-imagen.
                                $archivo_img = !empty($row['imagen']) ? $row['imagen'] : ($row['imagen_variante'] ?? 'productos/sin-imagen.jpg');
                                
                                // Si el archivo no empieza con 'productos/', se lo agregamos para que la ruta sea completa
                                if (strpos($archivo_img, 'productos/') === false) {
                                    $archivo_img = 'productos/' . $archivo_img;
                                }
                                
                                // Construimos la URL completa para forzar al navegador a ir al servidor correcto
                                $img_url = $dominio_base . $archivo_img;
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
                                    <a href="abm_producto.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-xs me-1"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="inventario.php?accion=borrar&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('¿Confirma eliminar?');"><i class="bi bi-trash-fill"></i></a>
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