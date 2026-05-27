<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// 1. Seguridad: Igual que en tu listado_clientes.php
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// 2. Includes: Usando tu estructura de carpetas
require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// Configuración de URL de tus fotos
$dominio_base = "https://robertsgrafica.com/img/"; 

// 3. Consulta SQL: Trae producto + categoría concatenada
$sql = "SELECT p.*, 
               (SELECT GROUP_CONCAT(c.nombre SEPARATOR ', ') 
                FROM categorias_prod pc 
                JOIN categorias_prod c ON pc.id_categoria = c.id 
                WHERE pc.id = p.id) as nombre_categoria
        FROM productos p 
        WHERE p.idActivo = 1 
        ORDER BY p.titulo ASC";

$query = mysqli_query($MiConexion, $sql);
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Inventario</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item active">Inventario</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Productos en Stock</h5>
                    <a href="abm_producto.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Nuevo</a>
                </div>

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
                                // Construcción dinámica de la URL de la imagen
                                $img = !empty($row['imagen']) ? $dominio_base . $row['imagen'] : $dominio_base . "productos/sin-imagen.jpg";
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($img) ?>" width="50" class="rounded shadow-sm" onerror="this.src='../img/productos/sin-imagen.jpg'">
                                </td>
                                <td><strong><?= htmlspecialchars($row['titulo']) ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['nombre_categoria']) ?: 'Sin cat.' ?></span></td>
                                <td>
                                    <?php if($row['stock_infinito'] == 1): ?>
                                        <span class="badge bg-info text-dark">Infinito</span>
                                    <?php else: ?>
                                        <?= (int)$row['stock'] ?> u.
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
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