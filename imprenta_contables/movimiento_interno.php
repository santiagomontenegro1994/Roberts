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

// --- CONFIGURACIÓN DE CUENTAS PERMITIDAS ---
// TRUCO: Usamos GROUP BY denominacion para que "Banco" aparezca UNA sola vez.
// Seleccionamos MIN(idTipoPago) solo para tener un ID de referencia al guardar.
$cuentas = [];
$sql = "SELECT MIN(idTipoPago) as idRef, denominacion 
        FROM tipo_pago 
        WHERE idActivo = 1 
        AND (denominacion LIKE '%Efectivo%' 
             OR denominacion LIKE '%Banco%' 
             OR denominacion LIKE '%Mercado%Pago%')
        AND denominacion NOT LIKE '%Cheque%' 
        AND denominacion NOT LIKE '%Payway%'
        GROUP BY denominacion 
        ORDER BY denominacion ASC";

$res = $MiConexion->query($sql);
while($row = $res->fetch_assoc()) {
    // Guardamos el ID de referencia y el nombre
    $cuentas[$row['idRef']] = $row['denominacion'];
}

$mensaje = '';
$estilo = '';

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $monto = floatval($_POST['monto'] ?? 0); 
    $idOrigen = intval($_POST['idOrigen'] ?? 0);
    $idDestino = intval($_POST['idDestino'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $idUsuario = $_SESSION['Usuario_Id'] ?? 0;

    // VALIDACIÓN INTELIGENTE DE NOMBRES
    // Como los IDs pueden ser distintos pero llamarse igual (el problema que tenías),
    // verificamos los nombres antes de guardar.
    $nombreOrigen = $cuentas[$idOrigen] ?? '';
    $nombreDestino = $cuentas[$idDestino] ?? '';

    if ($monto <= 0) {
        $mensaje = "El monto debe ser mayor a 0.";
        $estilo = "danger";
    } elseif ($idOrigen == 0 || $idDestino == 0) {
        $mensaje = "Debe seleccionar cuenta de origen y destino.";
        $estilo = "danger";
    } elseif ($nombreOrigen === $nombreDestino) {
        // AQUÍ BLOQUEAMOS BANCO A BANCO
        $mensaje = "No puede transferir a la misma cuenta ($nombreOrigen).";
        $estilo = "warning";
    } else {
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
                                <label class="col-sm-3 col-form-label fw-bold">Monto</label>
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
                                    <label class="form-label fw-bold text-danger">Sale de (Origen)</label>
                                    <select name="idOrigen" id="idOrigen" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($cuentas as $id => $nombre): ?>
                                            <option value="<?= $id ?>"><?= $nombre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-success">Entra en (Destino)</label>
                                    <select name="idDestino" id="idDestino" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach($cuentas as $id => $nombre): ?>
                                            <option value="<?= $id ?>"><?= $nombre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Observación</label>
                                <div class="col-sm-9">
                                    <input type="text" name="observacion" class="form-control">
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
    // 1. MONEDA
    const inputVisual = document.getElementById('monto_visual');
    const inputReal = document.getElementById('monto_real');
    if(inputVisual && inputReal) {
        inputVisual.addEventListener('input', function(e) {
            let valor = this.value.replace(/[^0-9,]/g, '');
            if (valor === '') { this.value = ''; inputReal.value = ''; return; }
            if ((valor.match(/,/g) || []).length > 1) { valor = valor.substring(0, valor.lastIndexOf(',')); }
            let partes = valor.split(',');
            let parteEntera = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            let parteDecimal = partes.length > 1 ? ',' + partes[1].substring(0, 2) : '';
            this.value = parteEntera + parteDecimal;
            inputReal.value = valor.replace(/\./g, '').replace(',', '.');
        });
    }

    // 2. EXCLUSIÓN DINÁMICA
    const selOrigen = document.getElementById('idOrigen');
    const selDestino = document.getElementById('idDestino');

    function actualizarDestinos() {
        // En este caso, como los valores (value) son IDs, comparamos el TEXTO visible
        // para evitar el problema de los IDs diferentes con mismo nombre.
        const textoOrigen = selOrigen.options[selOrigen.selectedIndex].text;
        
        for (let i = 0; i < selDestino.options.length; i++) {
            const option = selDestino.options[i];
            
            // Comparamos el TEXTO, no el ID
            if (option.text === textoOrigen && selOrigen.value !== "") {
                option.style.display = 'none';
                option.disabled = true;
                if (option.selected) selDestino.value = "";
            } else {
                option.style.display = 'block';
                option.disabled = false;
            }
        }
    }

    selOrigen.addEventListener('change', actualizarDestinos);
    actualizarDestinos();
});
</script>
</body>
</html>