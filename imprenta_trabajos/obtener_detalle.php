<?php
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Validar sesión
session_start();
if (empty($_SESSION['Usuario_Nombre'])) exit('Acceso denegado');

$idDetalle = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$MiConexion = ConexionBD();

// Consulta
$sql = "SELECT * FROM detalle_trabajos WHERE idDetalleTrabajo = ?";
$stmt = $MiConexion->prepare($sql);
$stmt->bind_param("i", $idDetalle);
$stmt->execute();
$detalle = $stmt->get_result()->fetch_assoc();

if (!$detalle) exit('<div class="alert alert-danger">Detalle no encontrado</div>');

// Listas
$estados = Datos_Estados_Trabajo($MiConexion);
$trabajos = Datos_Trabajos($MiConexion);
$proveedores = Listar_Proveedores($MiConexion);
$tiposFactura = Listar_Tipos_Factura($MiConexion);

// Verificar facturado
$isFacturado = ($detalle['facturado'] == 1);
?>

<form id="formEditarDetalle" action="procesar_detalle.php" method="post">
    <input type="hidden" name="accion" value="editar">
    <input type="hidden" name="idDetalle" value="<?php echo $detalle['idDetalleTrabajo']; ?>">
    <input type="hidden" name="IdPedido" value="<?php echo $detalle['id_pedido_trabajos']; ?>">
    
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select class="form-select" name="idEstadoTrabajo" required>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?php echo $estado['idEstado']; ?>" <?php echo ($estado['idEstado'] == $detalle['idEstadoTrabajo']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($estado['denominacion']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Trabajo</label>
            <select class="form-select" name="idTrabajo" required>
                <?php foreach ($trabajos as $trabajo): ?>
                    <option value="<?php echo $trabajo['idTipoTrabajo']; ?>" <?php echo ($trabajo['idTipoTrabajo'] == $detalle['idTrabajo']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($trabajo['denominacion']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Descripción</label>
            <input type="text" class="form-control" name="descripcion" value="<?php echo htmlspecialchars($detalle['descripcion'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Enviado a</label>
            <select class="form-select" name="idProveedor" required>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['ID_PROVEEDOR']; ?>" <?php echo ($proveedor['ID_PROVEEDOR'] == $detalle['idProveedor']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proveedor['NOMBRE']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fecha Entrega</label>
            <input type="date" class="form-control" name="fechaEntrega" value="<?php echo htmlspecialchars($detalle['fechaEntrega']); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Hora Entrega</label>
            <select class="form-select" name="horaEntrega">
                <?php
                $horas = ['08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30'];
                foreach ($horas as $hora): ?>
                    <option value="<?php echo $hora; ?>" <?php echo ($hora == $detalle['horaEntrega']) ? 'selected' : ''; ?>>
                        <?php echo $hora; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Precio ($)</label>
            <input type="number" class="form-control" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($detalle['precio']); ?>">
        </div>
    </div>   
        
    <div class="mb-3 form-check form-switch">
        <input class="form-check-input" type="checkbox" id="facturadoEditar" name="facturado" value="1" <?php echo $isFacturado ? 'checked' : ''; ?>>
        <label class="form-check-label fw-bold" for="facturadoEditar">¿Facturado?</label>
    </div>

    <div id="facturacionFieldsEditar" style="display: <?php echo $isFacturado ? 'block' : 'none'; ?>;" class="bg-light p-3 rounded border">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label">Tipo de Factura</label>
                <select class="form-select" id="tipo_factura_editar" name="idTipoFactura" <?php echo $isFacturado ? 'required' : 'disabled'; ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tiposFactura as $tipo): ?>
                        <option value="<?php echo $tipo['idTipoFactura']; ?>" <?php echo ($tipo['idTipoFactura'] == $detalle['idTipoFactura']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['denominacion']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Número de Factura</label>
                <input type="text" class="form-control" id="numero_factura_editar" name="numeroFactura" 
                    value="<?php echo htmlspecialchars($detalle['numeroFactura']); ?>" <?php echo $isFacturado ? 'required' : 'disabled'; ?>>
            </div>
        </div>
    </div>
    
    <div class="modal-footer mt-3">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning">Guardar Cambios</button>
    </div>
</form>