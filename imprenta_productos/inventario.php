<?php
session_start();
date_default_timezone_set('America/Argentina/Cordoba');
// Asegurate de que la ruta a tu auth y conexion sean correctas según donde pongas la carpeta
require_once '../auth_jefes.php';
verificarSesionApp();
require_once '../funciones/conexion.php';

$MiConexion = ConexionBD();

// Traemos todos los productos activos
$sql = "SELECT id, titulo, stock, stock_infinito, precio, imagen FROM productos WHERE idActivo = 1 ORDER BY titulo ASC";
$query = mysqli_query($MiConexion, $sql);

include '../shared/header.inc.php'; // Ajustá la ruta a tu header de NiceAdmin
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Control de Inventario</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                <li class="breadcrumb-item active">Inventario y Stock</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title m-0 p-0 fw-bold"><i class="bi bi-box-seam me-2"></i> Listado de Productos</h5>
                            <a href="abm_producto.php" class="btn btn-primary fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" width="8%">Img</th>
                                        <th scope="col" width="35%">Producto</th>
                                        <th scope="col" width="15%">Precio</th>
                                        <th scope="col" width="20%">Stock Principal</th>
                                        <th scope="col" width="22%">Variantes (Colores)</th>
                                        <th scope="col" width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($query)): 
                                        
                                        // Calcular estado del stock principal
                                        $badgeClass = 'bg-success';
                                        $stockTexto = $row['stock'] . ' unid.';
                                        
                                        if ($row['stock_infinito'] == 1) {
                                            $badgeClass = 'bg-info text-dark';
                                            $stockTexto = '<i class="bi bi-infinity"></i> Infinito / Producción';
                                        } else if ($row['stock'] <= 0) {
                                            $badgeClass = 'bg-danger';
                                            $stockTexto = '¡Agotado! (0)';
                                        } else if ($row['stock'] <= 5) {
                                            $badgeClass = 'bg-warning text-dark';
                                        }

                                        // Buscar si tiene variantes en productos_imagenes
                                        $idProd = $row['id'];
                                        $qVar = mysqli_query($MiConexion, "SELECT id, color_nombre, stock FROM productos_imagenes WHERE id_producto = $idProd");
                                        $tieneVariantes = mysqli_num_rows($qVar) > 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php $img = $row['imagen'] ? "../../img/" . $row['imagen'] : "../../img/productos/sin-imagen.jpg"; ?>
                                                <img src="<?= htmlspecialchars($img) ?>" alt="img" class="rounded" style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #ddd;">
                                            </td>
                                            <td>
                                                <h6 class="m-0 fw-bold text-dark"><?= htmlspecialchars($row['titulo']) ?></h6>
                                                <small class="text-muted">ID: #<?= $row['id'] ?></small>
                                            </td>
                                            <td class="fw-bold text-primary">
                                                $<?= number_format($row['precio'], 0, ',', '.') ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2" style="font-size: 0.9rem;">
                                                    <?= $stockTexto ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($tieneVariantes): ?>
                                                    <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#vars-<?= $row['id'] ?>">
                                                        Ver <?= mysqli_num_rows($qVar) ?> variantes <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                    <div class="collapse mt-2" id="vars-<?= $row['id'] ?>">
                                                        <ul class="list-group list-group-flush small">
                                                            <?php while ($var = mysqli_fetch_assoc($qVar)): 
                                                                $varColor = $var['stock'] <= 2 ? 'text-danger fw-bold' : 'text-muted';
                                                            ?>
                                                                <li class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center">
                                                                    <span><?= htmlspecialchars($var['color_nombre']) ?></span>
                                                                    <span class="<?= $varColor ?>"><?= $var['stock'] ?> u.</span>
                                                                </li>
                                                            <?php endwhile; ?>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="abm_producto.php?id=<?= $row['id'] ?>" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Editar / Ajustar Stock">
                                                    <i class="bi bi-pencil-square text-primary"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require '../shared/footer.inc.php'; // Ajustá la ruta ?>