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

// Obtener Cuentas Disponibles (Caja, Banco, MP, etc.)
// Usamos la tabla tipo_pago
$cuentas = [];
$sql = "SELECT idTipoPago, denominacion FROM tipo_pago WHERE idActivo = 1 ORDER BY denominacion ASC";
$res = $MiConexion->query($sql);
while($row = $res->fetch_assoc()) {
    $cuentas[$row['idTipoPago']] = $row['denominacion'];
}

$mensaje = '';
$estilo = '';

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $monto = floatval($_POST['monto'] ?? 0); // Recibe valor limpio del hidden
    $idOrigen = intval($_POST['idOrigen'] ?? 0);
    $idDestino = intval($_POST['idDestino'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $idUsuario = $_SESSION['Usuario_Id'] ?? 0;

    // Validaciones
    if ($monto <= 0) {
        $mensaje = "El monto debe ser mayor a 0.";
        $estilo = "danger";
    } elseif ($idOrigen == 0 || $idDestino == 0) {
        $mensaje = "Debe seleccionar cuenta de origen y destino.";
        $estilo = "danger";
    } elseif ($idOrigen === $idDestino) {
        $mensaje = "El origen y el destino no pueden ser la misma cuenta.";
        $estilo = "warning";
    } else {
        // Insertar Movimiento Interno
        $sqlInsert = "INSERT INTO movimientos_internos (fecha, monto, idOrigen, idDestino, idUsuario, observacion) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $MiConexion->prepare($sqlInsert);
        $stmt->bind_param("sdiiis", $fecha, $monto, $idOrigen, $idDestino, $idUsuario, $observacion);

        if ($stmt->execute()) {
            $_SESSION['Mensaje'] = "Transferencia registrada con éxito.";
            $_SESSION['Estilo'] = "success";
            header("Location: movimientos_contables.php");
            exit;
        } else {
            $mensaje = "Error al registrar: " . $stmt->error;
            $estilo = "danger";
        }
        $stmt->close();
    }
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Movimiento Interno de Fondos</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="movimientos_contables.php">Contabilidad</a></li>
                <li class="breadcrumb-item active">Nueva Transferencia</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-arrow-repeat"></i> Registrar Transferencia entre Cuentas
                    </div>
                    <div class="card-body mt-3">

                        <?php if($mensaje): ?>
                            <div class="alert alert-<?= $estilo ?> alert-dismissible fade show" role="alert">
                                <?= $mensaje ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="formTransferencia">
                            
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Fecha</label>
                                <div class="col-sm-9">
                                    <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label fw-bold">Monto a Transferir</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="monto_visual" class="form-control fs-5 fw-bold" placeholder="0,00" autocomplete="off" required>
                                        <input type="hidden" name="monto" id="monto_real">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-danger"><i class="bi bi-box-arrow-up"></i> Sale de (Origen)</label>
                                    <select name="idOrigen" id="idOrigen" class="form-select" required>
                                        <option value="">Seleccione cuenta...</option>
                                        <?php foreach($cuentas as $id => $nombre): ?>
                                            <option value="<?= $id ?>"><?= $nombre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-success"><i class="bi bi-box-arrow-in-down"></i> Entra en (Destino)</label>
                                    <select name="idDestino" id="idDestino" class="form-select" required>
                                        <option value="">Seleccione cuenta...</option>
                                        <?php foreach($cuentas as $id => $nombre): ?>
                                            <option value="<?= $id ?>"><?= $nombre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Observación</label>
                                <div class="col-sm-9">
                                    <input type="text" name="observacion" class="form-control" placeholder="Ej: Depósito de ventas del día">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="movimientos_contables.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-info text-white">Confirmar Transferencia</button>
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
    
    // 1. LÓGICA DE MONEDA (Igual a tu sistema actual)
    const inputVisual = document.getElementById('monto_visual');
    const inputReal = document.getElementById('monto_real');

    if(inputVisual && inputReal) {
        inputVisual.addEventListener('input', function(e) {
            let valor = this.value.replace(/[^0-9,]/g, '');
            if (valor === '') { this.value = ''; inputReal.value = ''; return; }
            if ((valor.match(/,/g) || []).length > 1) { valor = valor.substring(0, valor.lastIndexOf(',')); }
            let partes = valor.split(',');
            let parteEntera = partes[0];
            let parteDecimal = partes.length > 1 ? ',' + partes[1].substring(0, 2) : '';
            parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            this.value = parteEntera + parteDecimal;
            let valorParaBD = valor.replace(/\./g, '').replace(',', '.');
            inputReal.value = valorParaBD;
        });
    }

    // 2. VALIDACIÓN VISUAL: No permitir mismo origen y destino
    const selOrigen = document.getElementById('idOrigen');
    const selDestino = document.getElementById('idDestino');

    function validarCuentas() {
        // Restaurar colores
        selOrigen.classList.remove('is-invalid');
        selDestino.classList.remove('is-invalid');

        if(selOrigen.value !== "" && selDestino.value !== "") {
            if(selOrigen.value === selDestino.value) {
                alert("No puede transferir a la misma cuenta.");
                selDestino.value = ""; // Resetear destino
                selDestino.classList.add('is-invalid');
            }
        }
    }

    selOrigen.addEventListener('change', validarCuentas);
    selDestino.addEventListener('change', validarCuentas);
});
</script>
</body>
</html>