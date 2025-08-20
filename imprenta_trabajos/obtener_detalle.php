<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Validar sesión y permisos
if (empty($_SESSION['Usuario_Nombre'])) {
    $_SESSION['Mensaje'] = 'Acceso no autorizado';
    $_SESSION['Estilo'] = 'danger';
    header('Location: ../core/cerrarsesion.php');
    exit;
}

// Obtener ID del detalle a editar
$idDetalle = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idDetalle <= 0) {
    die('<div class="alert alert-danger">ID de detalle inválido</div>');
}

$conexion = ConexionBD();
if (!$conexion) {
    die('<div class="alert alert-danger">Error de conexión a la base de datos</div>');
}

// Obtener datos del detalle
$detalle = Obtener_Detalle_Trabajo($conexion, $idDetalle);
if (!$detalle) {
    die('<div class="alert alert-danger">Detalle no encontrado</div>');
}

// Datos adicionales
$estados = Datos_Estados_Trabajo($conexion);
$trabajos = Datos_Trabajos($conexion);
$proveedores = Listar_Proveedores($conexion);
$tiposFactura = Listar_Tipos_Factura($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Trabajo</title>
    <style>
        .facturacion-section {
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="modal-header">
        <h5 class="modal-title">Editar Trabajo del Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <form id="formEditarDetalle" action="procesar_detalle.php" method="post">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="idDetalle" value="<?php echo $detalle['idDetalleTrabajo']; ?>">
            <input type="hidden" name="IdPedido" value="<?php echo $detalle['id_pedido_trabajos']; ?>">
            <input type="hidden" name="facturado" value="0" id="hiddenFacturado">

            <!-- Primera fila: Estado, Trabajo y Descripción -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="estado_trabajo" class="form-label">Estado</label>
                    <select class="form-select" id="estado_trabajo" name="idEstadoTrabajo" required>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo $estado['idEstado']; ?>"
                                <?php echo ($estado['idEstado'] == $detalle['idEstadoTrabajo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($estado['denominacion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="tipo_trabajo" class="form-label">Trabajo</label>
                    <select class="form-select" id="tipo_trabajo" name="idTrabajo" required>
                        <?php foreach ($trabajos as $trabajo): ?>
                            <option value="<?php echo $trabajo['idTipoTrabajo']; ?>"
                                <?php echo ($trabajo['idTipoTrabajo'] == $detalle['idTrabajo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($trabajo['denominacion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" 
                        value="<?php echo htmlspecialchars($detalle['descripcion_trabajo']); ?>">
                </div>
            </div>
            
            <!-- Segunda fila: Proveedor, Fecha y Hora -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="enviado" class="form-label">Enviado a</label>
                    <select class="form-select" id="enviado" name="idProveedor" required>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?php echo $proveedor['ID_PROVEEDOR']; ?>"
                                <?php echo ($proveedor['ID_PROVEEDOR'] == $detalle['idProveedor']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proveedor['NOMBRE']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha Entrega</label>
                    <input type="date" class="form-control" id="fecha_entrega" name="fechaEntrega"
                        value="<?php echo htmlspecialchars($detalle['fecha_entrega']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hora Entrega</label>
                    <select class="form-select" id="hora_entrega" name="horaEntrega">
                        <?php
                        $horas = ['08:30','09:00','09:30','10:00','10:30','11:00','11:30',
                                  '12:00','12:30','16:00','16:30','17:00','17:30',
                                  '18:00','18:30','19:00','19:30'];
                        $horaActual = $detalle['hora_entrega'] ?? '';
                        foreach ($horas as $hora): ?>
                            <option value="<?php echo $hora; ?>" <?php echo ($hora == $horaActual) ? 'selected' : ''; ?>>
                                <?php echo $hora; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Precio -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="precio" class="form-label">Precio ($)</label>
                    <input type="number" class="form-control" id="precio" name="precio" 
                        step="0.01" min="0" value="<?php echo htmlspecialchars($detalle['precio']); ?>">
                </div>
            </div>   
                
            <!-- Checkbox facturado -->
            <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" id="facturado" name="facturado" value="1"
                    <?php echo ($detalle['facturado'] == 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="facturado">Facturado</label>
            </div>

            <!-- Información de facturación -->
            <div id="facturacionFields" style="display:none;">
                <div class="mb-3">
                    <label for="tipo_factura" class="form-label">Tipo de Factura</label>
                    <select class="form-select" id="tipo_factura" name="idTipoFactura" disabled>
                        <option value="">Seleccione...</option>
                        <?php foreach ($tiposFactura as $tipo): ?>
                            <option value="<?php echo $tipo['idTipoFactura']; ?>"
                                <?php echo ($tipo['idTipoFactura'] == $detalle['idTipoFactura']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['denominacion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="numero_factura" class="form-label">Número de Factura</label>
                    <input type="text" class="form-control" id="numero_factura" name="numeroFactura" 
                        value="<?php echo htmlspecialchars($detalle['numeroFactura']); ?>" disabled>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const facturado = document.getElementById('facturado');
        const formEditar = document.getElementById('formEditarDetalle');
        const tipoFactura = document.getElementById('tipo_factura');
        const numeroFactura = document.getElementById('numero_factura');
        const hiddenFacturado = document.getElementById('hiddenFacturado');
        const facturacionFields = document.getElementById('facturacionFields');

        function toggleCamposFacturacion() {
            if (facturado.checked) {
                facturacionFields.style.display = 'block';
                tipoFactura.disabled = false;
                numeroFactura.disabled = false;
                tipoFactura.required = true;
                numeroFactura.required = true;
            } else {
                facturacionFields.style.display = 'none';
                tipoFactura.disabled = true;
                numeroFactura.disabled = true;
                tipoFactura.required = false;
                numeroFactura.required = false;
                tipoFactura.value = '';
                numeroFactura.value = '';
            }
            hiddenFacturado.value = facturado.checked ? '1' : '0';
        }

        // Inicializar al cargar
        toggleCamposFacturacion();

        facturado.addEventListener('change', toggleCamposFacturacion);

        formEditar.addEventListener('submit', function(e) {
            hiddenFacturado.value = facturado.checked ? '1' : '0';
            if (facturado.checked) {
                if (!tipoFactura.value) {
                    e.preventDefault();
                    alert("Debe seleccionar un tipo de factura.");
                    tipoFactura.focus();
                    return false;
                }
                if (!numeroFactura.value.trim()) {
                    e.preventDefault();
                    alert("Debe ingresar un número de factura.");
                    numeroFactura.focus();
                    return false;
                }
            }
        });
    });
    </script>
</body>
</html>
