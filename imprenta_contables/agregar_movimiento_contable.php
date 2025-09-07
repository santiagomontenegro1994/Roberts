<?php
ob_start();
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (empty($_SESSION['Usuario_Nombre'])) {
    header('Location: ../core/cerrarsesion.php');
    exit;
}

require('../shared/encabezado.inc.php');
require('../shared/barraLateral.inc.php');
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

$MiConexion = ConexionBD();

// Obtener opciones para select
$usuarios = [];
$res = $MiConexion->query("SELECT idUsuario, nombre, apellido FROM usuarios WHERE idActivo=1");
while($row = $res->fetch_assoc()) $usuarios[$row['idUsuario']] = $row['nombre'] . ' ' . $row['apellido'];

$proveedores = [];
$res = $MiConexion->query("SELECT idProveedor, nombre FROM proveedores WHERE idActivo=1");
while($row = $res->fetch_assoc()) $proveedores[$row['idProveedor']] = $row['nombre'];

// Insumos y Servicios
$proveedoresInsumos = [];
$res = $MiConexion->query("SELECT idProveedorInsumo, nombre FROM proveedores_insumos WHERE idActivo=1");
while($row = $res->fetch_assoc()) $proveedoresInsumos[$row['idProveedorInsumo']] = $row['nombre'];

$categoriasInsumo = [];
$res = $MiConexion->query("SELECT idInsumo, denominacion FROM insumos");
while($row = $res->fetch_assoc()) $categoriasInsumo[$row['idInsumo']] = $row['denominacion'];

$tiposServicios = [];
$res = $MiConexion->query("SELECT idTipoServicio, denominacion FROM tipo_servicio WHERE idActivo=1");
while($row = $res->fetch_assoc()) $tiposServicios[$row['idTipoServicio']] = $row['denominacion'];

// Obtener métodos de pago para salidas
$metodosPago = Listar_Tipos_Pagos_Salida($MiConexion);

// Mapeo de tipos de retiro a tipos de movimiento
$tipoMovimientoMap = [
    'insumos' => 18,
    'proveedores' => 16,
    'servicios' => 20,
    'sueldos' => 17,
    'varios' => 19
];

// Procesar envío del formulario
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $monto = floatval($_POST['monto'] ?? 0);
    $idTipoPago = intval($_POST['metodo_pago'] ?? 0);
    $subtipo = $_POST['subtipo'] ?? '';
    $detalle = $_POST['detalle'] ?? '';

    $usuarioId = $_SESSION['Usuario_Id'] ?? 0;
    
    if (!$usuarioId) {
        $mensaje = "No se pudo obtener el usuario de sesión.";
    } elseif ($monto <= 0) {
        $mensaje = "Debe completar el monto.";
    } elseif (empty($subtipo)) {
        $mensaje = "Debe seleccionar un tipo de retiro.";
    } else {
        // Obtener el idTipoMovimiento según el tipo de retiro seleccionado
        $idTipoMovimiento = $tipoMovimientoMap[$subtipo] ?? 0;
        
        if ($idTipoMovimiento === 0) {
            $mensaje = "Tipo de retiro no válido.";
        } else {
            // Insertar en tabla retiros
            $stmt = $MiConexion->prepare("INSERT INTO retiros (fecha, monto, idUsuario, idTipoMovimiento, idTipoPago, facturado) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("sdiii", $fecha, $monto, $usuarioId, $idTipoMovimiento, $idTipoPago);
            $stmt->execute();
            $idRetiro = $stmt->insert_id;
            $stmt->close();

            // Insertar detalle según subtipo
            switch($subtipo) {
                case 'insumos':
                    $idProveedorInsumo = intval($_POST['idProveedorInsumo'] ?? 0);
                    $idCategoria = intval($_POST['idCategoriaInsumo'] ?? 0);
                    $detalle_insumo = $_POST['detalle_insumo'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_insumos (idRetiro, idProveedorInsumo, categoria, detalle_insumo) VALUES (?, ?, ?, ?)");
                    $categoriaNombre = $categoriasInsumo[$idCategoria] ?? '';
                    $stmt->bind_param("iiss", $idRetiro, $idProveedorInsumo, $categoriaNombre, $detalle_insumo);
                    $stmt->execute();
                    $stmt->close();
                    break;
                case 'proveedores':
                    $idProveedor = intval($_POST['idProveedor'] ?? 0);
                    $detalle_proveedor = $_POST['detalle_proveedor'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_proveedores (idRetiro, idProveedor, detalle_proveedor) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $idRetiro, $idProveedor, $detalle_proveedor);
                    $stmt->execute();
                    $stmt->close();
                    break;
                case 'servicios':
                    $idTipoServicio = intval($_POST['tipo_servicio'] ?? 0);
                    $detalle_servicio = $_POST['detalle_servicio'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_servicios (idRetiro, tipo_servicio, detalle_servicio) VALUES (?, ?, ?)");
                    $tipoServicioNombre = $tiposServicios[$idTipoServicio] ?? '';
                    $stmt->bind_param("iss", $idRetiro, $tipoServicioNombre, $detalle_servicio);
                    $stmt->execute();
                    $stmt->close();
                    break;
                case 'sueldos':
                    $idUsuarioSueldo = intval($_POST['idUsuarioSueldo'] ?? 0);
                    $detalle_sueldo = $_POST['detalle_sueldo'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_sueldos (idRetiro, idUsuarioSueldo, detalle_sueldo) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $idRetiro, $idUsuarioSueldo, $detalle_sueldo);
                    $stmt->execute();
                    $stmt->close();
                    break;
                case 'varios':
                    $detalle_vario = $_POST['detalle_vario'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_varios (idRetiro, categoria, detalle_vario) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $idRetiro, $detalle, $detalle_vario);
                    $stmt->execute();
                    $stmt->close();
                    break;
            }

            $mensaje = "Movimiento contable registrado correctamente.";
        }
    }
}

$MiConexion->close();
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Retiro con Movimiento Contable</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../core/index.php">Menu</a></li>
                <li class="breadcrumb-item"><a href="movimientos_contables.php">Retiro con Movimiento Contable</a></li>
                <li class="breadcrumb-item active">Retiro</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">

                        <?php if($mensaje): ?>
                            <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
                        <?php endif; ?>

                        <form method="POST" id="formMovimiento">
                            <div class="mb-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Monto</label>
                                <input type="number" step="0.01" name="monto" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Método de Pago</label>
                                <select name="metodo_pago" class="form-select" required>
                                    <option value="">Seleccione un método de pago</option>
                                    <?php foreach($metodosPago as $pago): ?>
                                        <option value="<?= $pago['idTipoPago'] ?>">
                                            <?= htmlspecialchars($pago['denominacion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Retiro</label>
                                <select name="subtipo" id="subtipo" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <option value="insumos">Insumos</option>
                                    <option value="proveedores">Proveedores</option>
                                    <option value="servicios">Servicios</option>
                                    <option value="sueldos">Sueldos</option>
                                    <option value="varios">Varios</option>
                                </select>
                            </div>

                            <div id="detalles-dinamicos"></div>

                            <button type="submit" class="btn btn-success">Guardar Movimiento</button>
                            <a href="movimientos_contables.php" class="btn btn-secondary">Cancelar</a>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require('../shared/footer.inc.php'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subtipoSelect = document.getElementById('subtipo');
    const detallesDiv = document.getElementById('detalles-dinamicos');

    const usuarios = <?= json_encode($usuarios) ?>;
    const proveedores = <?= json_encode($proveedores) ?>;
    const proveedoresInsumos = <?= json_encode($proveedoresInsumos) ?>;
    const categoriasInsumo = <?= json_encode($categoriasInsumo) ?>;
    const tiposServicios = <?= json_encode($tiposServicios) ?>;

    subtipoSelect.addEventListener('change', function() {
        let html = '';
        switch(this.value) {
            case 'insumos':
                html += `<div class="mb-3">
                            <label class="form-label">Proveedor Insumo</label>
                            <select name="idProveedorInsumo" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in proveedoresInsumos) {
                    html += `<option value="${id}">${proveedoresInsumos[id]}</option>`;
                }
                html += `</select>
                         </div>
                         <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="idCategoriaInsumo" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in categoriasInsumo) {
                    html += `<option value="${id}">${categoriasInsumo[id]}</option>`;
                }
                html += `</select>
                         <div class="mb-3">
                            <label class="form-label">Detalle Insumo (opcional)</label>
                            <input type="text" name="detalle_insumo" class="form-control">
                         </div>`;
                break;
            case 'proveedores':
                html += `<div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="idProveedor" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in proveedores) {
                    html += `<option value="${id}">${proveedores[id]}</option>`;
                }
                html += `</select>
                         <div class="mb-3">
                            <label class="form-label">Detalle Proveedor (opcional)</label>
                            <input type="text" name="detalle_proveedor" class="form-control">
                         </div>`;
                break;
            case 'servicios':
                html += `<div class="mb-3">
                            <label class="form-label">Tipo Servicio</label>
                            <select name="tipo_servicio" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in tiposServicios) {
                    html += `<option value="${id}">${tiposServicios[id]}</option>`;
                }
                html += `</select>
                         <div class="mb-3">
                            <label class="form-label">Detalle Servicio (opcional)</label>
                            <input type="text" name="detalle_servicio" class="form-control">
                         </div>`;
                break;
            case 'sueldos':
                html += `<div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <select name="idUsuarioSueldo" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in usuarios) {
                    html += `<option value="${id}">${usuarios[id]}</option>`;
                }
                html += `</select>
                         <div class="mb-3">
                            <label class="form-label">Detalle Sueldo (opcional)</label>
                            <input type="text" name="detalle_sueldo" class="form-control">
                         </div>`;
                break;
            case 'varios':
                html += `<div class="mb-3">
                            <label class="form-label">Categoría / Detalle (opcional)</label>
                            <input type="text" name="detalle_vario" class="form-control">
                         </div>`;
                break;
        }
        detallesDiv.innerHTML = html;
    });
});
</script>
</body>
</html>