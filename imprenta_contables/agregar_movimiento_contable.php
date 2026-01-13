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
$res = $MiConexion->query("SELECT idServicio, denominacion FROM servicios");
while($row = $res->fetch_assoc()) $tiposServicios[$row['idServicio']] = $row['denominacion'];

// Obtener métodos de pago para salidas
$metodosPago = Listar_Tipos_Pagos_Salida($MiConexion);

// Mapeo de tipos de retiro a tipos de movimiento
$tipoMovimientoMap = [
    'insumos' => 18,
    'proveedores' => 16,
    'servicios' => 22,
    'sueldos' => 17,
    'varios' => 19
];

// Procesar envío del formulario
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    // Aquí el PHP recibe el valor limpio del input hidden (ej: 20000.50)
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
                    $idCategoria = intval($_POST['idInsumo'] ?? 0); // Corregido según tu código anterior
                    $detalle_insumo = $_POST['detalle_insumo'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_insumos (idRetiro, idProveedorInsumo, idInsumo, detalle_insumo) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiis", $idRetiro, $idProveedorInsumo, $idCategoria, $detalle_insumo);
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
                    $idTipoServicio = intval($_POST['idServicio'] ?? 0); // Corregido según tu código anterior
                    $detalle_servicio = $_POST['detalle_servicio'] ?? '';
                    $stmt = $MiConexion->prepare("INSERT INTO retiros_servicios (idRetiro, idServicio, detalle_servicio) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $idRetiro, $idTipoServicio, $detalle_servicio);
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
                    $detalle = "varios";
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
                                <label for="monto_visual" class="form-label">Monto</label>
                                <div class="input-group">
                                    <input type="text" id="monto_visual" class="form-control" placeholder="$ 0,00" autocomplete="off" required>
                                    
                                    <input type="hidden" name="monto" id="monto_real">
                                </div>
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
    // --- LÓGICA DE FORMATO DE MONEDA ($ 20.000,00) ---
    const inputVisual = document.getElementById('monto_visual');
    const inputReal = document.getElementById('monto_real');

    if(inputVisual && inputReal) {
        inputVisual.addEventListener('input', function(e) {
            // 1. Obtener valor y dejar solo números y coma
            let valor = this.value.replace(/[^0-9,]/g, '');

            if (valor === '') {
                this.value = '';
                inputReal.value = '';
                return;
            }

            // 2. Controlar que solo haya una coma
            if ((valor.match(/,/g) || []).length > 1) {
                valor = valor.substring(0, valor.lastIndexOf(','));
            }

            // 3. Separar enteros y decimales
            let partes = valor.split(',');
            let parteEntera = partes[0];
            let parteDecimal = partes.length > 1 ? ',' + partes[1].substring(0, 2) : '';

            // 4. Agregar puntos de mil a la parte entera
            parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

            // 5. Mostrar en pantalla con el signo $
            this.value = '$ ' + parteEntera + parteDecimal;

            // 6. Guardar en el hidden el valor limpio para la BD (Punto como decimal)
            // Quitamos puntos visuales y cambiamos coma por punto
            let valorParaBD = valor.replace(/\./g, '').replace(',', '.');
            inputReal.value = valorParaBD;
        });
    }
    // --- FIN LÓGICA MONEDA ---


    // --- LÓGICA DE SUBTIPOS (ORIGINAL) ---
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
                            <select name="idInsumo" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in categoriasInsumo) {
                    html += `<option value="${id}">${categoriasInsumo[id]}</option>`;
                }
                html += `</select>
                         </div>
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
                         </div>
                         <div class="mb-3">
                            <label class="form-label">Detalle Proveedor (opcional)</label>
                            <input type="text" name="detalle_proveedor" class="form-control">
                         </div>`;
                break;
            case 'servicios':
                html += `<div class="mb-3">
                            <label class="form-label">Tipo Servicio</label>
                            <select name="idServicio" class="form-select">
                                <option value="">Seleccione</option>`;
                for(const id in tiposServicios) {
                    html += `<option value="${id}">${tiposServicios[id]}</option>`;
                }
                html += `</select>
                         </div>
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
                         </div>
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