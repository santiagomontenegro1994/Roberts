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

$MiConexion = ConexionBD();

// Recibir ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Obtener datos del movimiento actual
$stmt = $MiConexion->prepare("SELECT * FROM movimientos_internos WHERE idMovimientoInterno = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$datos = $res->fetch_assoc();

if (!$datos) {
    $_SESSION['Mensaje'] = "Transferencia no encontrada.";
    header("Location: movimientos_contables.php");
    exit;
}

// 2. Obtener los NOMBRES reales de las cuentas guardadas actualmente
// Esto es vital: El ID guardado (ej: 15) puede ser distinto al ID que mostramos en la lista (ej: 2),
// pero ambos se llaman "Banco". Necesitamos el nombre para marcar el 'selected' correctamente.
function obtenerNombreCuenta($conexion, $id) {
    if (!$id) return '';
    $q = $conexion->query("SELECT denominacion FROM tipo_pago WHERE idTipoPago = $id");
    return ($r = $q->fetch_assoc()) ? $r['denominacion'] : '';
}

$nombreOrigenGuardado = obtenerNombreCuenta($MiConexion, $datos['idOrigen']);
$nombreDestinoGuardado = obtenerNombreCuenta($MiConexion, $datos['idDestino']);

// 3. Obtener Lista de Cuentas (Agrupadas por Nombre para evitar duplicados)
$cuentas = [];
// Usamos MIN(idTipoPago) para tener un ID de referencia, y GROUP BY para unificar nombres
$sql = "SELECT MIN(idTipoPago) as idRef, denominacion 
        FROM tipo_pago 
        WHERE idActivo = 1 
        AND (denominacion LIKE '%Efectivo%' 
             OR denominacion LIKE '%Banco%' 
             OR denominacion LIKE '%Mercado%Pago%'
             OR denominacion LIKE '%Transferencia%')
        AND denominacion NOT LIKE '%Cheque%' 
        AND denominacion NOT LIKE '%Payway%'
        GROUP BY denominacion
        ORDER BY denominacion ASC";

$resCuentas = $MiConexion->query($sql);
while($row = $resCuentas->fetch_assoc()) {
    // Array Clave = ID, Valor = Nombre
    $cuentas[$row['idRef']] = $row['denominacion'];
}

$error = '';

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $monto = floatval($_POST['monto']);
    $idOrigen = intval($_POST['idOrigen']);
    $idDestino = intval($_POST['idDestino']);
    $observacion = trim($_POST['observacion']);

    // Validar Nombres para evitar Banco -> Banco (distintos IDs)
    $nombreOrigenSel = $cuentas[$idOrigen] ?? '';
    $nombreDestinoSel = $cuentas[$idDestino] ?? '';

    if ($monto <= 0) {
        $error = "El monto debe ser mayor a 0.";
    } elseif ($idOrigen == 0 || $idDestino == 0) {
        $error = "Debe seleccionar ambas cuentas.";
    } elseif ($nombreOrigenSel === $nombreDestinoSel) {
        // AQUÍ ESTÁ LA CORRECCIÓN CLAVE: Validamos por nombre
        $error = "El origen y el destino no pueden ser la misma cuenta ($nombreOrigenSel).";
    } else {
        $upd = $MiConexion->prepare("UPDATE movimientos_internos SET fecha=?, monto=?, idOrigen=?, idDestino=?, observacion=? WHERE idMovimientoInterno=?");
        $upd->bind_param("sdiisi", $fecha, $monto, $idOrigen, $idDestino, $observacion, $id);
        
        if ($upd->execute()) {
            $_SESSION['Mensaje'] = "Transferencia actualizada correctamente.";
            $_SESSION['Estilo'] = "success";
            header("Location: movimientos_contables.php");
            exit;
        } else {
            $error = "Error al actualizar la base de datos.";
        }
    }
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Transferencia</h1>
    </div>

    <section class="section">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body mt-3">
                        
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="<?= $datos['fecha'] ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Monto</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" name="monto" class="form-control" value="<?= $datos['monto'] ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-danger">Sale de</label>
                                    <select name="idOrigen" id="idOrigen" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($cuentas as $id => $nom): ?>
                                            <?php 
                                            // Comparamos por NOMBRE para marcar el selected correctamente
                                            $sel = ($nom === $nombreOrigenGuardado) ? 'selected' : ''; 
                                            ?>
                                            <option value="<?= $id ?>" <?= $sel ?>><?= $nom ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-success">Entra a</label>
                                    <select name="idDestino" id="idDestino" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($cuentas as $id => $nom): ?>
                                            <?php 
                                            // Comparamos por NOMBRE
                                            $sel = ($nom === $nombreDestinoGuardado) ? 'selected' : ''; 
                                            ?>
                                            <option value="<?= $id ?>" <?= $sel ?>><?= $nom ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Observación</label>
                                <input type="text" name="observacion" class="form-control" value="<?= htmlspecialchars($datos['observacion']) ?>">
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="movimientos_contables.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
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
    const selOrigen = document.getElementById('idOrigen');
    const selDestino = document.getElementById('idDestino');

    function actualizarDestinos() {
        // Obtenemos el TEXTO de la opción seleccionada en Origen
        const textoOrigen = selOrigen.options[selOrigen.selectedIndex].text;
        
        for (let i = 0; i < selDestino.options.length; i++) {
            const option = selDestino.options[i];
            
            // Si el texto coincide, lo ocultamos (así evitamos Banco -> Banco)
            if (option.text === textoOrigen && selOrigen.value !== "") {
                option.style.display = 'none'; // Ocultar
                option.disabled = true;        // Deshabilitar
                
                // Si justo estaba seleccionado, reseteamos el destino
                if (option.selected) {
                    selDestino.value = "";
                }
            } else {
                option.style.display = 'block';
                option.disabled = false;
            }
        }
    }

    selOrigen.addEventListener('change', actualizarDestinos);
    // Ejecutar al cargar para validar lo que viene de la BD
    actualizarDestinos();
});
</script>
</body>
</html>