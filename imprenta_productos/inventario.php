<?php
session_start();

// 1. Seguridad y Conexión (Igual que en listado_clientes.php)
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require ('../shared/encabezado.inc.php');
require ('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// 2. Consulta de productos (usando la estructura de tu tabla actual)
// Traemos productos y la info de stock
$sql = "SELECT id, titulo, stock, stock_infinito, precio, imagen 
        FROM productos 
        WHERE idActivo = 1 
        ORDER BY titulo ASC";

$query = mysqli_query($MiConexion, $sql);
?>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Inventario de Productos</h1>
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
                <h5 class="card-title">Stock Actual en Sistema</h5>
                
                <div class="table-responsive">
                    <table class="table table-striped datatable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($query)) { 
                                $stock = (int)$row['stock'];
                                $es_infinito = ($row['stock_infinito'] == 1);
                            ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <?php 
                                    $img = (!empty($row['imagen']) && file_exists("../img/" . $row['imagen'])) 
                                           ? "../img/" . $row['imagen'] 
                                           : "../img/productos/sin-imagen.jpg"; 
                                    ?>
                                    <img src="<?= $img ?>" width="50" class="rounded">
                                </td>
                                <td><strong><?= htmlspecialchars($row['titulo']) ?></strong></td>
                                <td>
                                    <?php if($es_infinito): ?>
                                        <span class="badge bg-info">Infinito</span>
                                    <?php elseif($stock <= 0): ?>
                                        <span class="badge bg-danger">Agotado</span>
                                    <?php else: ?>
                                        <?= $stock ?> unid.
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
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