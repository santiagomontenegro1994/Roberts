<?php
ob_start();
session_start();

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// Obtener ID de retiro (acepta id o ID_MOVIMIENTO)
$idRetiro = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['ID_MOVIMIENTO']) ? intval($_GET['ID_MOVIMIENTO']) : 0);
if ($idRetiro <= 0) {
    $_SESSION['Mensaje'] = "ID de retiro inválido";
    $_SESSION['Estilo'] = "danger";
    header("Location: movimientos_contables.php"); 
    exit;
}

// Traer datos actuales
$datosActuales = Datos_Movimiento_Contable($MiConexion, $idRetiro);

if (!$datosActuales) {
    $_SESSION['Mensaje'] = "Movimiento no encontrado";
    $_SESSION['Estilo'] = "danger";
    header("Location: movimientos_contables.php");
    exit;
}

$errores = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datosNuevos = [
        'idRetiro'        => $idRetiro,
        'fecha'           => $_POST['fecha'],
        'monto'           => $_POST['monto'],
        'idTipoPago'      => $_POST['tipoPago'],
        'idTipoMovimiento'=> $_POST['tipoMovimiento'],
        'subtipo'         => $_POST['subtipo'],
        'detalle'         => $_POST['detalle'] ?? []
    ];

    $errores = Validar_Modificar_Movimiento_Contable($datosNuevos);

    if (empty($errores)) {
        $resultado = Modificar_Movimiento_Contable($MiConexion, $datosNuevos);
        if ($resultado === true) {
            $_SESSION['Mensaje'] = "Movimiento modificado correctamente";
            $_SESSION['Estilo'] = "success";
            header("Location: movimientos_contables.php");
            exit;
        } else {
            $errores = array_merge($errores, $resultado);
        }
    }
}

// Listas para selects
$tiposPago = Listar_Tipos_Pagos_Salida($MiConexion);

// Obtener usuarios y proveedores para los selects
$usuarios = [];
$res = $MiConexion->query("SELECT idUsuario, nombre, apellido FROM usuarios WHERE idActivo=1");
while($row = $res->fetch_assoc()) $usuarios[$row['idUsuario']] = $row['nombre'] . ' ' . $row['apellido'];

$proveedores = [];
$res = $MiConexion->query("SELECT idProveedor, nombre FROM proveedores WHERE idActivo=1");
while($row = $res->fetch_assoc()) $proveedores[$row['idProveedor']] = $row['nombre'];

// Subtipos disponibles
$subtipos = [
    "insumos" => "Insumos",
    "proveedores" => "Proveedores",
    "servicios" => "Servicios",
    "sueldos" => "Sueldos",
    "varios" => "Varios"
];
?>

<main id="main" class="main">
<div class="container mt-4">
    <h3>Modificar Movimiento Contable</h3>

    <?php if (!empty($errores)) : ?>
        <div class="alert alert-danger">
            <?php foreach ($errores as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" 
                           value="<?= htmlspecialchars($datosActuales['cabecera']['fecha']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Monto</label>
                    <input type="number" step="0.01" name="monto" class="form-control" 
                           value="<?= htmlspecialchars($datosActuales['cabecera']['monto']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Pago</label>
                    <select name="tipoPago" class="form-select" required>
                        <?php foreach ($tiposPago as $tp) : ?>
                            <option value="<?= $tp['idTipoPago'] ?>" 
                                <?= $tp['idTipoPago'] == $datosActuales['cabecera']['idTipoPago'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tp['denominacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subtipo</label>
                    <select name="subtipo" id="subtipo" class="form-select" onchange="mostrarSubform()" required>
                        <?php foreach ($subtipos as $clave => $nombre) : ?>
                            <option value="<?= $clave ?>" 
                                <?= $clave == $datosActuales['subtipo'] ? 'selected' : '' ?>>
                                <?= $nombre ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Subformularios dinámicos -->
                <div id="subformularios">
                    <?php
                    $subtipo = $datosActuales['subtipo'];
                    $detalle = $datosActuales['detalle'];
                    ?>

                    <div id="form-insumos" class="subform" style="display: <?= $subtipo == 'insumos' ? 'block' : 'none' ?>">
                        <h5>Detalle Insumos</h5>
                        <input type="text" name="detalle[idProveedorInsumo]" class="form-control mb-2" placeholder="Proveedor" 
                               value="<?= htmlspecialchars($detalle['idProveedorInsumo'] ?? '') ?>">
                        <input type="text" name="detalle[categoria]" class="form-control mb-2" placeholder="Categoría" 
                               value="<?= htmlspecialchars($detalle['categoria'] ?? '') ?>">
                        <input type="text" name="detalle[detalle_insumo]" class="form-control mb-2" placeholder="Descripción" 
                               value="<?= htmlspecialchars($detalle['detalle_insumo'] ?? '') ?>">
                    </div>

                    <div id="form-proveedores" class="subform" style="display: <?= $subtipo == 'proveedores' ? 'block' : 'none' ?>">
                        <h5>Detalle Proveedores</h5>
                        <select name="detalle[idProveedor]" class="form-select mb-2">
                            <option value="">Seleccione proveedor</option>
                            <?php
                            foreach($proveedores as $id => $nombre) {
                                $selected = ($detalle['idProveedor'] ?? 0) == $id ? 'selected' : '';
                                echo "<option value='$id' $selected>$nombre</option>";
                            }
                            ?>
                        </select>
                        <input type="text" name="detalle[detalle_proveedor]" class="form-control mb-2" placeholder="Detalle" 
                               value="<?= htmlspecialchars($detalle['detalle_proveedor'] ?? '') ?>">
                    </div>

                    <div id="form-servicios" class="subform" style="display: <?= $subtipo == 'servicios' ? 'block' : 'none' ?>">
                        <h5>Detalle Servicios</h5>
                        <input type="text" name="detalle[tipo_servicio]" class="form-control mb-2" placeholder="Tipo de Servicio" 
                               value="<?= htmlspecialchars($detalle['tipo_servicio'] ?? '') ?>">
                        <input type="text" name="detalle[detalle_servicio]" class="form-control mb-2" placeholder="Detalle" 
                               value="<?= htmlspecialchars($detalle['detalle_servicio'] ?? '') ?>">
                    </div>

                    <div id="form-sueldos" class="subform" style="display: <?= $subtipo == 'sueldos' ? 'block' : 'none' ?>">
                        <h5>Detalle Sueldos</h5>
                        <select name="detalle[idUsuarioSueldo]" class="form-select mb-2">
                            <option value="">Seleccione usuario</option>
                            <?php
                            foreach($usuarios as $id => $nombre) {
                                $selected = ($detalle['idUsuarioSueldo'] ?? 0) == $id ? 'selected' : '';
                                echo "<option value='$id' $selected>$nombre</option>";
                            }
                            ?>
                        </select>
                        <input type="text" name="detalle[detalle_sueldo]" class="form-control mb-2" placeholder="Detalle" 
                               value="<?= htmlspecialchars($detalle['detalle_sueldo'] ?? '') ?>">
                    </div>

                    <div id="form-varios" class="subform" style="display: <?= $subtipo == 'varios' ? 'block' : 'none' ?>">
                        <h5>Detalle Varios</h5>
                        <input type="text" name="detalle[categoria]" class="form-control mb-2" placeholder="Categoría" 
                               value="<?= htmlspecialchars($detalle['categoria'] ?? '') ?>">
                        <input type="text" name="detalle[detalle_vario]" class="form-control mb-2" placeholder="Detalle" 
                               value="<?= htmlspecialchars($detalle['detalle_vario'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="movimientos_contables.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
</main>

<script>
function mostrarSubform() {
    let valor = document.getElementById('subtipo').value;
    document.querySelectorAll('.subform').forEach(div => div.style.display = 'none');
    let activo = document.getElementById('form-' + valor);
    if (activo) activo.style.display = 'block';
}
</script>

<?php
require('../shared/footer.inc.php');
$MiConexion->close();
ob_end_flush();
?>
</body>
</html>
