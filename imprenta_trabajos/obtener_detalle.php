<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/imprenta.php';

// Validar sesión y permisos
if (empty($_SESSION['Usuario_Nombre'])) {
    die(json_encode(['error' => 'Acceso no autorizado']));
}

// Obtener ID del detalle a editar
$idDetalle = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idDetalle <= 0) {
    die(json_encode(['error' => 'ID de detalle inválido']));
}

$conexion = ConexionBD();
if (!$conexion) {
    die(json_encode(['error' => 'Error de conexión a la base de datos']));
}

// Obtener datos del detalle
$detalle = Obtener_Detalle_Trabajo($conexion, $idDetalle);
if (!$detalle) {
    die(json_encode(['error' => 'Detalle no encontrado']));
}

// Obtener datos adicionales necesarios
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
        .toggle-facturacion {
            cursor: pointer;
            color: #0d6efd;
        }
        .toggle-facturacion:hover {
            color: #0a58ca;
        }
        .facturacion-content {
            display: none;
        }
        .facturacion-content.show {
            display: block;
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
            
            <!-- Primera fila: Estado, Trabajo y Descripción -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="estado_trabajo" class="form-label">Estado</label>
                    <div class="select-container">
                        <select class="form-select select-expandido" id="estado_trabajo" name="idEstadoTrabajo" required>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?php echo $estado['idEstado']; ?>"
                                    <?php echo ($estado['idEstado'] == $detalle['idEstadoTrabajo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($estado['denominacion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="tipo_trabajo" class="form-label">Trabajo</label>
                    <div class="select-container">
                        <select class="form-select select-expandido" id="tipo_trabajo" name="idTrabajo" required>
                            <?php foreach ($trabajos as $trabajo): ?>
                                <option value="<?php echo $trabajo['idTipoTrabajo']; ?>"
                                    <?php echo ($trabajo['idTipoTrabajo'] == $detalle['idTrabajo']) ? 'selected' : ''; ?>
                                    data-precio="<?php echo htmlspecialchars($trabajo['precio_base'] ?? $detalle['precio']); ?>">
                                    <?php echo htmlspecialchars($trabajo['denominacion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                    <div class="select-container">
                        <select class="form-select select-expandido" id="enviado" name="idProveedor" required>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['ID_PROVEEDOR']; ?>"
                                    <?php echo ($proveedor['ID_PROVEEDOR'] == $detalle['idProveedor']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proveedor['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                        $horas = ['08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', 
                                 '12:00', '12:30', '16:00', '16:30', '17:00', '17:30', 
                                 '18:00', '18:30', '19:00', '19:30'];
                        $horaActual = isset($detalle['hora_entrega']) ? $detalle['hora_entrega'] : '';
                        ?>
                        <?php foreach ($horas as $hora): ?>
                            <option value="<?php echo $hora; ?>"
                                <?php echo ($hora == $horaActual) ? 'selected' : ''; ?>>
                                <?php echo $hora; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Tercera fila: Precio -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="precio" class="form-label">Precio ($)</label>
                    <input type="number" class="form-control" id="precio" name="precio" 
                        step="0.01" min="0" value="<?php echo htmlspecialchars($detalle['precio']); ?>">
                </div>
            </div>
            
            <!-- Sección de Facturación -->
            <div class="facturacion-section">
                <h6 class="toggle-facturacion" onclick="toggleFacturacion()">
                    <i class="bi bi-receipt"></i> Información de Facturación 
                    <i class="bi bi-chevron-down" id="facturacionIcon"></i>
                </h6>
                
                <div class="facturacion-content" id="facturacionContent">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="facturado" name="facturado" 
                                    <?php echo ($detalle['facturado'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="facturado">¿Facturado?</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3" id="facturacionFields" style="<?php echo ($detalle['facturado'] == 1) ? '' : 'display: none;'; ?>">
                        <div class="col-md-6">
                            <label for="tipo_factura" class="form-label">Tipo de Factura</label>
                            <select class="form-select" id="tipo_factura" name="idTipoFactura">
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($tiposFactura as $tipo): ?>
                                    <option value="<?php echo $tipo['idTipoFactura']; ?>"
                                        <?php echo ($tipo['idTipoFactura'] == $detalle['idTipoFactura']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['denominacion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="numero_factura" class="form-label">Número de Factura</label>
                            <input type="text" class="form-control" id="numero_factura" name="numeroFactura" 
                                value="<?php echo htmlspecialchars($detalle['numeroFactura']); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <!-- Template Main CSS File -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <script>
    function toggleFacturacion() {
        const content = document.getElementById('facturacionContent');
        const icon = document.getElementById('facturacionIcon');
        
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            icon.className = 'bi bi-chevron-down';
        } else {
            content.classList.add('show');
            icon.className = 'bi bi-chevron-up';
        }
    }
    
    // Mostrar/ocultar campos de facturación según el checkbox
    document.getElementById('facturado').addEventListener('change', function() {
        const facturacionFields = document.getElementById('facturacionFields');
        if (this.checked) {
            facturacionFields.style.display = 'block';
        } else {
            facturacionFields.style.display = 'none';
            document.getElementById('tipo_factura').value = '';
            document.getElementById('numero_factura').value = '';
        }
    });
    
    // Inicializar estado de la sección de facturación
    document.addEventListener('DOMContentLoaded', function() {
        const facturado = document.getElementById('facturado');
        const facturacionFields = document.getElementById('facturacionFields');
        const facturacionContent = document.getElementById('facturacionContent');
        const icon = document.getElementById('facturacionIcon');
        
        // Si ya está facturado, mostrar la sección expandida
        if (facturado.checked) {
            facturacionContent.classList.add('show');
            icon.className = 'bi bi-chevron-up';
            facturacionFields.style.display = 'block';
        } else {
            facturacionContent.classList.remove('show');
            icon.className = 'bi bi-chevron-down';
            facturacionFields.style.display = 'none';
        }
    });
    </script>
</body>
</html>