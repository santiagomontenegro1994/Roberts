<?php
session_start();
require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// Verificar si estamos editando (recibimos ID) o creando uno nuevo
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$producto = null;

if ($id) {
    $res = mysqli_query($MiConexion, "SELECT * FROM productos WHERE id = $id");
    $producto = mysqli_fetch_assoc($res);
}

// Lógica de GUARDAR (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = mysqli_real_escape_string($MiConexion, $_POST['titulo']);
    $stock = (int)$_POST['stock'];
    $precio = (float)$_POST['precio'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id) {
        // UPDATE
        $sql = "UPDATE productos SET titulo='$titulo', stock=$stock, precio=$precio, idActivo=$activo WHERE id=$id";
    } else {
        // INSERT
        $sql = "INSERT INTO productos (titulo, stock, precio, idActivo) VALUES ('$titulo', $stock, $precio, $activo)";
    }

    if (mysqli_query($MiConexion, $sql)) {
        $_SESSION['Mensaje'] = "Producto guardado con éxito.";
        header("Location: inventario.php");
        exit;
    }
}
?>

<?php require ('../shared/encabezado.inc.php'); ?>
<main id="main" class="main">
    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= $id ? 'Editar Producto' : 'Nuevo Producto' ?></h5>
                
                <form method="POST">
                    <div class="mb-3">
                        <label>Nombre del Producto</label>
                        <input type="text" name="titulo" class="form-control" value="<?= $producto['titulo'] ?? '' ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Stock</label>
                            <input type="number" name="stock" class="form-control" value="<?= $producto['stock'] ?? 0 ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Precio</label>
                            <input type="number" step="0.01" name="precio" class="form-control" value="<?= $producto['precio'] ?? 0 ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="activo" <?= (!isset($producto) || $producto['idActivo'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label">Producto Activo</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Guardar Producto</button>
                    <a href="inventario.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require ('../shared/footer.inc.php'); ?>