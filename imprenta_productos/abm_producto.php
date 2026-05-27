<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// 1. Seguridad básica
if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require_once '../funciones/conexion.php';
$MiConexion = ConexionBD();

// RUTA ABSOLUTA DEL SERVIDOR
$ruta_servidor_img = "/home/u922707138/domains/robertsgrafica.com/public_html/img/";

// Inicializar variables por defecto
$producto = [
    'id' => '', 'titulo' => '', 'descripcion' => '', 'precio' => 0, 
    'stock' => 0, 'stock_infinito' => 0, 
    'imagen' => '', 
    'color_principal' => 'Original',
    'color_principal_hex' => '#000000',
    'destacado' => 0, 'nuevo' => 0, 'categoria' => 0
];
$categorias_seleccionadas = [];

// --- 0. ELIMINAR VARIANTE ---
if (isset($_GET['borrar_color']) && isset($_GET['id_prod'])) {
    $id_color = (int)$_GET['borrar_color'];
    $id_prod_redirect = (int)$_GET['id_prod'];

    $res_img = mysqli_query($MiConexion, "SELECT nombre_imagen FROM productos_imagenes WHERE id = $id_color");
    if ($res_img) {
        $img_var = mysqli_fetch_assoc($res_img);
        if ($img_var) {
            if (!empty($img_var['nombre_imagen']) && file_exists($ruta_servidor_img . $img_var['nombre_imagen'])) {
                unlink($ruta_servidor_img . $img_var['nombre_imagen']);
            }
            mysqli_query($MiConexion, "DELETE FROM productos_imagenes WHERE id = $id_color");
        }
    }
    header("Location: abm_producto.php?id=" . $id_prod_redirect . "#areaColores");
    exit;
}

// 1. CARGAR DATOS DEL PRODUCTO (Si es edición)
if (isset($_GET['id'])) {
    $id_get = (int)$_GET['id'];
    $res_prod = mysqli_query($MiConexion, "SELECT * FROM productos WHERE id = $id_get");
    
    if ($res_prod) {
        $producto_bd = mysqli_fetch_assoc($res_prod);
        if($producto_bd) {
            $producto = array_merge($producto, $producto_bd);
        } else {
            die("Producto no encontrado");
        }
    }

    if(empty($producto['color_principal'])) $producto['color_principal'] = 'Original';
    if(empty($producto['color_principal_hex'])) $producto['color_principal_hex'] = '#000000';

    $res_cats = @mysqli_query($MiConexion, "SELECT id_categoria FROM producto_categoria WHERE id_producto = $id_get");
    if ($res_cats) {
        while ($row_cat = mysqli_fetch_assoc($res_cats)) {
            $categorias_seleccionadas[] = $row_cat['id_categoria'];
        }
    } else {
        if (!empty($producto['categoria'])) $categorias_seleccionadas[] = $producto['categoria'];
    }
}

// 2. PROCESAR POST (Guardar producto o variante)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- A. GUARDAR PRODUCTO PRINCIPAL ---
    if (isset($_POST['accion']) && $_POST['accion'] == 'guardar_producto') {
        $titulo = mysqli_real_escape_string($MiConexion, $_POST['titulo'] ?? '');
        $desc = mysqli_real_escape_string($MiConexion, $_POST['descripcion'] ?? '');
        $categorias_post = isset($_POST['categorias']) ? $_POST['categorias'] : [];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $nuevo = isset($_POST['nuevo']) ? 1 : 0;
        $id_post = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $stock = !empty($_POST['stock']) ? (int)$_POST['stock'] : 0;
        $stock_infinito = isset($_POST['stock_infinito']) ? 1 : 0;
        
        $color_principal = !empty($_POST['color_principal']) ? mysqli_real_escape_string($MiConexion, $_POST['color_principal']) : 'Original';
        $color_principal_hex = !empty($_POST['color_principal_hex']) ? mysqli_real_escape_string($MiConexion, $_POST['color_principal_hex']) : '#000000';

        $nombre_imagen = $producto['imagen']; 
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $dir_destino = $ruta_servidor_img . "productos/"; 
            if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
            $info = pathinfo($_FILES['imagen']['name']);
            $ext = strtolower($info['extension']);
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $nom = time() . "_" . uniqid() . "." . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dir_destino . $nom)) {
                    if (!empty($producto['imagen']) && file_exists($ruta_servidor_img . $producto['imagen']) && $producto['imagen'] != 'productos/sin-imagen.jpg') {
                        unlink($ruta_servidor_img . $producto['imagen']);
                    }
                    $nombre_imagen = "productos/" . $nom; 
                }
            }
        }
        if (empty($nombre_imagen)) $nombre_imagen = "productos/sin-imagen.jpg";

        $cat_legacy = !empty($categorias_post) ? (int)$categorias_post[0] : 0;

        if ($id_post > 0) {
            $sql = "UPDATE productos SET titulo='$titulo', descripcion='$desc', stock=$stock, stock_infinito=$stock_infinito, categoria=$cat_legacy, imagen='$nombre_imagen', color_principal='$color_principal', color_principal_hex='$color_principal_hex', destacado=$destacado, nuevo=$nuevo WHERE id=$id_post";
            mysqli_query($MiConexion, $sql);
            $id_retorno = $id_post;

            @mysqli_query($MiConexion, "DELETE FROM producto_categoria WHERE id_producto = $id_retorno");
        } else {
            $sql = "INSERT INTO productos (titulo, descripcion, stock, stock_infinito, categoria, imagen, color_principal, color_principal_hex, destacado, nuevo, precio) VALUES ('$titulo', '$desc', $stock, $stock_infinito, $cat_legacy, '$nombre_imagen', '$color_principal', '$color_principal_hex', $destacado, $nuevo, 0)";
            mysqli_query($MiConexion, $sql);
            $id_retorno = mysqli_insert_id($MiConexion);
        }

        if (!empty($categorias_post)) {
            foreach ($categorias_post as $id_cat) {
                $id_cat = (int)$id_cat;
                @mysqli_query($MiConexion, "INSERT INTO producto_categoria (id_producto, id_categoria) VALUES ($id_retorno, $id_cat)");
            }
        }

        header("Location: abm_producto.php?id=" . $id_retorno . "&msg=ok");
        exit;
    }

    // --- B. AGREGAR VARIANTE ---
    if (isset($_POST['accion']) && $_POST['accion'] == 'agregar_color') {
        $id_prod = (int)$_POST['id_producto'];
        $color_nombre = mysqli_real_escape_string($MiConexion, $_POST['color_nombre'] ?? '');
        $color_hex = mysqli_real_escape_string($MiConexion, $_POST['color_hex'] ?? '#000000');
        $stock_var = !empty($_POST['stock_var']) ? (int)$_POST['stock_var'] : 0;
        
        if (isset($_FILES['foto_color']) && $_FILES['foto_color']['error'] === UPLOAD_ERR_OK) {
            $dir = $ruta_servidor_img . "productos/variantes/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $info = pathinfo($_FILES['foto_color']['name']);
            $ext = strtolower($info['extension']);
            $nom = "var_" . time() . "_" . uniqid() . "." . $ext;
            
            if (move_uploaded_file($_FILES['foto_color']['tmp_name'], $dir . $nom)) {
                $ruta_db = "productos/variantes/" . $nom;
                $sql = "INSERT INTO productos_imagenes (id_producto, nombre_imagen, color_hex, color_nombre, stock) VALUES ($id_prod, '$ruta_db', '$color_hex', '$color_nombre', $stock_var)";
                mysqli_query($MiConexion, $sql);
            }
        }
        header("Location: abm_producto.php?id=" . $id_prod . "#areaColores");
        exit;
    }

    // --- C. ACTUALIZAR VARIANTE ---
    if (isset($_POST['accion']) && $_POST['accion'] == 'actualizar_variante') {
        $id_var = (int)$_POST['id_variante'];
        $id_prod = (int)$_POST['id_producto'];
        $nombre = mysqli_real_escape_string($MiConexion, $_POST['color_nombre'] ?? '');
        $hex = mysqli_real_escape_string($MiConexion, $_POST['color_hex'] ?? '#000000');
        $stock_var = (int)$_POST['stock_var'];

        $sql = "UPDATE productos_imagenes SET color_nombre = '$nombre', color_hex = '$hex', stock = $stock_var WHERE id = $id_var";
        mysqli_query($MiConexion, $sql);
        
        header("Location: abm_producto.php?id=" . $id_prod . "#areaColores");
        exit;
    }
}

// -------------------------------------------------------------------------
// 3. RECUPERAR TODOS LOS DATOS PARA EL HTML *ANTES* DE INCLUIR EL ENCABEZADO
// -------------------------------------------------------------------------

// A. Lista de todos los productos (Para el generador de botones)
$todos_productos = [];
$res_all = mysqli_query($MiConexion, "SELECT id, titulo FROM productos ORDER BY titulo ASC");
if ($res_all) {
    while ($row_all = mysqli_fetch_assoc($res_all)) {
        $todos_productos[] = $row_all;
    }
}

// B. Lista de Categorías
$categorias_bd = [];
$res_cat = @mysqli_query($MiConexion, "SELECT * FROM categorias_prod ORDER BY nombre ASC");
if ($res_cat) {
    while ($c_row = mysqli_fetch_assoc($res_cat)) { 
        $categorias_bd[] = $c_row; 
    }
}

// C. Lista de Variantes del producto actual
$variantes = [];
if (!empty($producto['id'])) {
    $res_var = @mysqli_query($MiConexion, "SELECT * FROM productos_imagenes WHERE id_producto = " . (int)$producto['id']);
    if ($res_var) {
        while ($v_row = mysqli_fetch_assoc($res_var)) { 
            $variantes[] = $v_row; 
        }
    }
}

// -------------------------------------------------------------------------
// RECIÉN AHORA DIBUJAMOS EL HTML (Ya tenemos todo guardado en memoria)
// -------------------------------------------------------------------------

require ('../shared/encabezado.inc.php'); 
require ('../shared/barraLateral.inc.php'); 
?>

<style>
    .form-check-input { cursor: pointer; }
    .color-preview { width: 30px; height: 30px; border-radius: 50%; border: 1px solid #ddd; display: inline-block; }
    
    .categorias-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        max-height: 250px;
        overflow-y: auto;
        padding: 15px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    .cat-checkbox-item {
        background: white;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        transition: 0.2s;
    }
    .cat-checkbox-item:hover {
        border-color: #0d6efd;
        box-shadow: 0 2px 5px rgba(13,110,253,0.1);
    }
</style>

<main id="main" class="main">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8"> 
            
            <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-check"></i> Cambios guardados correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <?php echo $producto['id'] ? '<i class="fa-solid fa-pen-to-square me-2"></i>Editar Producto' : '<i class="fa-solid fa-box me-2"></i>Nuevo Producto'; ?>
                    </h5>
                    <a href="inventario.php" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                </div>
                
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="guardar_producto">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id'] ?? ''); ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Título del Producto <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control form-control-lg" value="<?php echo htmlspecialchars($producto['titulo'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="campo_descripcion" class="form-control" rows="5" required><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                            
                            <div class="mt-2 p-3 bg-white rounded border border-primary shadow-sm">
                                <label class="form-label fw-bold text-primary mb-1"><i class="fa-solid fa-wand-magic-sparkles"></i> Agregar botón a otro producto</label>
                                <p class="small text-muted mb-2">Elegí un producto y tocá "Insertar". El sistema escribirá el código por vos.</p>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="small fw-bold text-dark">¿A qué producto lleva?</label>
                                        <select id="gen_prod" class="form-select form-select-sm border-primary">
                                            <option value="">Buscar producto...</option>
                                            <?php foreach($todos_productos as $tp): ?>
                                                <option value="<?php echo $tp['id']; ?>"><?php echo htmlspecialchars($tp['titulo'] ?? ''); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="small fw-bold text-dark">Texto del botón:</label>
                                        <input type="text" id="gen_texto" class="form-control form-control-sm border-primary" placeholder="Ej: Ver Taza Estampada">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-primary w-100 fw-bold" onclick="agregarBotonDesc()"><i class="fa-solid fa-plus"></i> Insertar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text text-muted mt-2">Aclaración: El precio se administra automáticamente desde el Excel.</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label fw-bold text-success"><i class="fa-solid fa-file-excel"></i> Precio (BD)</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light text-success">$</span>
                                    <input type="text" class="form-control bg-light text-success fw-bold" 
                                           value="<?php echo number_format((float)($producto['precio'] ?? 0), 0, ',', '.'); ?>" 
                                           readonly title="El precio no se actualiza desde aquí.">
                                </div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-bold">Stock (Color Principal)</label>
                                <div class="d-flex gap-3 align-items-start flex-wrap">
                                    <input type="number" name="stock" id="inputStock" class="form-control shadow-sm" value="<?php echo (int)($producto['stock'] ?? 0); ?>" style="max-width: 120px;">
                                    <div class="form-check bg-light p-2 rounded border mt-1 shadow-sm">
                                        <input class="form-check-input ms-1" type="checkbox" name="stock_infinito" id="checkInfinito" value="1" <?php if(!empty($producto['stock_infinito'])) echo 'checked'; ?>>
                                        <label class="form-check-label small ms-2" for="checkInfinito">Stock Infinito / A medida</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Categorías <span class="text-danger">*</span></label>
                            <div class="categorias-grid">
                                <?php 
                                // Usamos la variable en memoria, ya no hay consultas aquí abajo
                                if (count($categorias_bd) > 0) {
                                    foreach($categorias_bd as $c): 
                                        $checked = in_array($c['id'], $categorias_seleccionadas) ? 'checked' : '';
                                ?>
                                    <div class="cat-checkbox-item form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="<?php echo $c['id']; ?>" id="cat_<?php echo $c['id']; ?>" <?php echo $checked; ?>>
                                        <label class="form-check-label w-100" style="cursor:pointer;" for="cat_<?php echo $c['id']; ?>">
                                            <?php echo htmlspecialchars($c['nombre']); ?>
                                        </label>
                                    </div>
                                <?php 
                                    endforeach; 
                                } else {
                                    echo "<p class='text-muted small m-0'>No hay categorías creadas o detectadas.</p>";
                                }
                                ?>
                            </div>
                        </div>

                        <div class="mb-4 p-3 bg-light rounded border">
                            <label class="form-label fw-bold">Imagen Principal</label>
                            <input type="file" name="imagen" class="form-control mb-2" accept="image/*">
                            
                            <div class="row g-2 mt-2 align-items-center">
                                <div class="col-md-8">
                                    <label class="small fw-bold">Nombre del Color Principal:</label>
                                    <input type="text" name="color_principal" class="form-control form-control-sm" 
                                           value="<?php echo htmlspecialchars($producto['color_principal'] ?? ''); ?>" placeholder="Ej: Blanco">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Hexadecimal:</label>
                                    <input type="color" name="color_principal_hex" class="form-control form-control-color w-100" 
                                           value="<?php echo htmlspecialchars($producto['color_principal_hex'] ?? '#000000'); ?>">
                                </div>
                            </div>

                            <?php if(!empty($producto['imagen']) && $producto['imagen'] != 'productos/sin-imagen.jpg'): ?>
                                <img src="https://robertsgrafica.com/img/<?php echo $producto['imagen']; ?>" height="60" class="mt-2 rounded border bg-white shadow-sm">
                            <?php endif; ?>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="p-3 border rounded border-warning h-100 d-flex align-items-center bg-white shadow-sm">
                                    <div class="form-check form-switch w-100 text-center">
                                        <input class="form-check-input scale-125 float-none ms-0" type="checkbox" name="destacado" id="dest" <?php if(!empty($producto['destacado'])) echo 'checked'; ?>>
                                        <label class="form-check-label fw-bold ms-2 d-inline-block" for="dest">Destacado (Inicio)</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded border-info h-100 d-flex align-items-center bg-white shadow-sm">
                                    <div class="form-check form-switch w-100 text-center">
                                        <input class="form-check-input scale-125 float-none ms-0" type="checkbox" name="nuevo" id="nuev" <?php if(!empty($producto['nuevo'])) echo 'checked'; ?>>
                                        <label class="form-check-label fw-bold ms-2 d-inline-block" for="nuev">Etiqueta "Nuevo"</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="fa-solid fa-save"></i> GUARDAR PRODUCTO</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if(!empty($producto['id'])): ?>
            <div class="card shadow" id="areaColores">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 text-white"><i class="fa-solid fa-palette me-2"></i> Variantes de Color</h5>
                </div>
                <div class="card-body">
                    
                    <?php if(count($variantes) > 0): ?>
                        <div class="mb-4">
                            <label class="fw-bold mb-2">Variantes Existentes (Editar):</label>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach($variantes as $v): ?>
                                <form method="POST" class="d-flex align-items-center gap-2 p-2 border rounded bg-white">
                                    <input type="hidden" name="accion" value="actualizar_variante">
                                    <input type="hidden" name="id_variante" value="<?php echo $v['id']; ?>">
                                    <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                                    
                                    <img src="https://robertsgrafica.com/img/<?php echo $v['nombre_imagen']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;" onerror="this.style.display='none'">
                                    
                                    <div class="flex-grow-1">
                                        <div class="row g-1 align-items-center">
                                            <div class="col-md-5">
                                                <input type="text" name="color_nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($v['color_nombre'] ?? ''); ?>" placeholder="Nombre">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" name="stock_var" class="form-control form-control-sm" value="<?php echo (int)($v['stock'] ?? 0); ?>" placeholder="Stock">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="color" name="color_hex" class="form-control form-control-color form-control-sm w-100" value="<?php echo htmlspecialchars($v['color_hex'] ?? '#000000'); ?>" title="Cambiar Color">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-1">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Actualizar Datos"><i class="fa-solid fa-rotate"></i></button>
                                        <a href="abm_producto.php?borrar_color=<?php echo $v['id']; ?>&id_prod=<?php echo $producto['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Borrar esta variante?')" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="p-3 bg-light border rounded">
                        <h6 class="fw-bold mb-3">Agregar Nueva Variante</h6>
                        <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                            <input type="hidden" name="accion" value="agregar_color">
                            <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                            
                            <div class="col-md-3">
                                <label class="small fw-bold">Nombre</label>
                                <input type="text" name="color_nombre" class="form-control" placeholder="Ej: Rojo" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Stock</label>
                                <input type="number" name="stock_var" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Color</label>
                                <input type="color" name="color_hex" class="form-control form-control-color w-100" value="#ff0000">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Foto</label>
                                <input type="file" name="foto_color" class="form-control" accept="image/*" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-plus"></i> Agregar</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php require ('../shared/footer.inc.php'); ?>

<script>
    function agregarBotonDesc() {
        const select = document.getElementById('gen_prod');
        const prodId = select.value;
        const texto = document.getElementById('gen_texto').value;
        const textarea = document.getElementById('campo_descripcion');

        if(!prodId || !texto) {
            alert("⚠️ Por favor, seleccioná un producto de la lista y escribí el texto que va a decir el botón.");
            return;
        }

        const botonHTML = `\n<a href="productos.php?ver_producto=${prodId}" class="btn btn-outline-primary btn-sm mt-2 mb-1" style="width:100%; text-align:center;">\n    <i class="fa-solid fa-wand-magic-sparkles"></i> ${texto}\n</a>\n`;

        textarea.value += botonHTML;

        select.value = '';
        document.getElementById('gen_texto').value = '';
        
        textarea.focus();
        textarea.selectionStart = textarea.value.length;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const check = document.getElementById('checkInfinito');
        const input = document.getElementById('inputStock');
        function toggleStockInput() {
            if(check.checked) {
                input.readOnly = true; input.classList.add('bg-light');
                if(input.value == 0) input.value = 999; 
            } else {
                input.readOnly = false; input.classList.remove('bg-light');
                if(input.value == 999) input.value = 0;
            }
        }
        if(check) {
            check.addEventListener('change', toggleStockInput);
            toggleStockInput(); 
        }

        const formProducto = document.querySelector('form[action=""]'); 
        if(formProducto && formProducto.querySelector('input[name="accion"][value="guardar_producto"]')) {
            formProducto.addEventListener('submit', function(e) {
                const checkboxes = document.querySelectorAll('input[name="categorias[]"]:checked');
                if (document.querySelectorAll('input[name="categorias[]"]').length > 0 && checkboxes.length === 0) {
                    e.preventDefault();
                    alert("Por favor, seleccioná al menos una categoría para el producto.");
                }
            });
        }
    });
</script>