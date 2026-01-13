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
        'monto'           => $_POST['monto'], // Recibe el valor limpio del input hidden
        'idTipoPago'      => $_POST['tipoPago'],
        'idTipoMovimiento'=> $_POST['tipoMovimiento'], // viene del hidden
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

$proveedoresInsumos = [];
$res = $MiConexion->query("SELECT idProveedorInsumo, nombre FROM proveedores_insumos");
while($row = $res->fetch_assoc()) $proveedoresInsumos[$row['idProveedorInsumo']] = $row['nombre'];

$insumos = [];
$res = $MiConexion->query("SELECT idInsumo, denominacion FROM insumos");
while($row = $res->fetch_assoc()) $insumos[$row['idInsumo']] = $row['denominacion'];

$servicios = [];
$res = $MiConexion->query("SELECT idServicio, denominacion FROM servicios");
while($row = $res->fetch_assoc()) $servicios[$row['idServicio']] = $row['denominacion'];

// Subtipos disponibles
$subtipos = [
    "insumos" => "Insumos",
    "proveedores" => "Proveedores",
    "servicios" => "Servicios",
    "sueldos" => "Sueldos",
    "varios" => "Varios"
];

// Mapeo de tipos de retiro a tipos de movimiento
$tipoMovimientoMap = [
    'insumos'     => 18,
    'proveedores' => 16,
    'servicios'   => 22,
    'sueldos'     => 17,
    'varios'      => 19
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
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" id="monto_visual" class="form-control" 
                               value="<?= number_format($datosActuales['cabecera']['monto'], 2, ',', '.') ?>" 
                               required placeholder="0,00">
                        
                        <input type="hidden" name="monto" id="monto_hidden" 
                               value="<?= $datosActuales['cabecera']['monto'] ?>">
                    </div>
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

                <input type="hidden" name="tipoMovimiento" id="tipoMovimiento" 
                       value="<?= $tipoMovimientoMap[$datosActuales['subtipo']] ?? '' ?>">

                <div id="subformularios">
                    <?php
                    $subtipo = $datosActuales['subtipo'];
                    $detalle = $datosActuales['detalle'];
                    ?>

                    <div id="form-insumos" class="subform" style="display: <?= $subtipo == 'insumos' ? 'block' : 'none' ?>">
                        <h5>Detalle Insumos</h5>
                        <select name="detalle[idProveedorInsumo]" class="form-select mb-2">
                            <option value="">Seleccione proveedor</option>
                            <?php
                            foreach($proveedoresInsumos as $id => $nombre) {
                                $selected = ($detalle['idProveedorInsumo'] ?? 0) == $id ? 'selected' : '';
                                echo "<option value='$id' $selected>$nombre</option>";
                            }
                            ?>
                        </select>

                        <select name="detalle[idInsumo]" class="form-select mb-2">
                            <option value="">Seleccione insumo</option>
                            <?php
                            foreach($insumos as $id => $nombre) {
                                $selected = ($detalle['idInsumo'] ?? 0) == $id ? 'selected' : '';
                                echo "<option value='$id' $selected>$nombre</option>";
                            }
                            ?>
                        </select>

                        <input type="text" name="detalle[detalle_insumo]" class="form-control mb-2" placeholder="Detalle Insumo" 
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
                        <select name="detalle[idServicio]" class="form-select mb-2">
                            <option value="">Seleccione servicio</option>
                            <?php
                            foreach($servicios as $id => $nombre) {
                                $selected = ($detalle['idServicio'] ?? 0) == $id ? 'selected' : '';
                                echo "<option value='$id' $selected>$nombre</option>";
                            }
                            ?>
                        </select>
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
const tipoMovimientoMap = {
    insumos: 18,
    proveedores: 16,
    servicios: 22,
    sueldos: 17,
    varios: 19
};

function mostrarSubform() {
    let valor = document.getElementById('subtipo').value;
    document.querySelectorAll('.subform').forEach(div => {
        div.style.display = 'none';
    });

    let activo = document.getElementById('form-' + valor);
    if (activo) {
        activo.style.display = 'block';
    }

    // actualizar hidden con el idTipoMovimiento correspondiente
    document.getElementById('tipoMovimiento').value = tipoMovimientoMap[valor] ?? '';
}

// Ejecutar al cargar para aplicar el valor inicial
document.addEventListener("DOMContentLoaded", mostrarSubform);

// --- SCRIPTS DE FORMATO DE MONEDA ---
document.addEventListener('DOMContentLoaded', function() {
    const inputVisual = document.getElementById('monto_visual');
    const inputHidden = document.getElementById('monto_hidden');

    inputVisual.addEventListener('input', function(e) {
        let valor = e.target.value;

        // 1. Eliminar todo lo que no sea número o coma
        valor = valor.replace(/[^0-9,]/g, '');

        // 2. Asegurar que solo haya una coma
        const partes = valor.split(',');
        if (partes.length > 2) {
            valor = partes[0] + ',' + partes.slice(1).join('');
        }

        // 3. Formatear la parte entera con puntos de mil
        let parteEntera = partes[0];
        let parteDecimal = partes.length > 1 ? ',' + partes[1] : '';

        // Eliminar ceros a la izquierda innecesarios
        if (parteEntera.length > 1 && parteEntera.startsWith('0')) {
            parteEntera = parteEntera.replace(/^0+/, '');
        }
        if (parteEntera === '') parteEntera = '0';

        // Agregar puntos de mil
        parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

        // 4. Actualizar el input visual
        e.target.value = parteEntera + parteDecimal;

        // 5. Actualizar el input hidden (formato SQL: 1500.50)
        // Quitamos puntos y cambiamos coma por punto
        let valorLimpio = (parteEntera.replace(/\./g, '') + parteDecimal).replace(',', '.');
        inputHidden.value = valorLimpio;
    });

    // Validar al enviar el formulario (por seguridad)
    document.querySelector('form').addEventListener('submit', function() {
        if (!inputHidden.value || inputHidden.value === '') {
             // Si por alguna razón está vacío, intentar recuperarlo del visual
             let val = inputVisual.value.replace(/\./g, '').replace(',', '.');
             inputHidden.value = val;
        }
    });
});
</script>

<?php
require('../shared/footer.inc.php');
$MiConexion->close();
ob_end_flush();
?>
</body>
</html>