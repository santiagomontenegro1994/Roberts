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

// Obtener datos actuales
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

// Obtener Cuentas Permitidas (Efectivo, Banco, MP)
$cuentas = [];
$sql = "SELECT idTipoPago, denominacion 
        FROM tipo_pago 
        WHERE idActivo = 1 
        AND (denominacion LIKE '%Efectivo%' 
             OR denominacion LIKE '%Banco%' 
             OR denominacion LIKE '%Mercado%Pago%')
        AND denominacion NOT LIKE '%Cheque%' 
        AND denominacion NOT LIKE '%Payway%'
        ORDER BY denominacion ASC";

$resCuentas = $MiConexion->query($sql);
while($row = $resCuentas->fetch_assoc()) {
    $cuentas[$row['idTipoPago']] = $row['denominacion'];
}

// Procesar Actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $monto = floatval($_POST['monto']);
    $idOrigen = intval($_POST['idOrigen']);
    $idDestino = intval($_POST['idDestino']);
    $observacion = trim($_POST['observacion']);

    if ($monto > 0 && $idOrigen != $idDestino) {
        $upd = $MiConexion->prepare("UPDATE movimientos_internos SET fecha=?, monto=?, idOrigen=?, idDestino=?, observacion=? WHERE idMovimientoInterno=?");
        $upd->bind_param("sdiisi", $fecha, $monto, $idOrigen, $idDestino, $observacion, $id);
        
        if ($upd->execute()) {
            $_SESSION['Mensaje'] = "Transferencia actualizada.";
            $_SESSION['Estilo'] = "success";
            header("Location: movimientos_contables.php");
            exit;
        } else {
            $error = "Error al actualizar.";
        }
    } else {
        $error = "Verifique los datos (Monto > 0 y cuentas distintas).";
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
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="<?= $datos['fecha'] ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Monto</label>
                                <input type="number" step="0.01" name="monto" class="form-control" value="<?= $datos['monto'] ?>" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-danger">Sale de</label>
                                    <select name="idOrigen" class="form-select" required>
                                        <?php foreach($cuentas as $id => $nom): ?>
                                            <option value="<?= $id ?>" <?= ($datos['idOrigen'] == $id) ? 'selected' : '' ?>><?= $nom ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-success">Entra a</label>
                                    <select name="idDestino" class="form-select" required>
                                        <?php foreach($cuentas as $id => $nom): ?>
                                            <option value="<?= $id ?>" <?= ($datos['idDestino'] == $id) ? 'selected' : '' ?>><?= $nom ?></option>
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