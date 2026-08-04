<?php
session_start();
require_once(__DIR__ . '/../funciones/conexion.php'); // Verificá que esta ruta apunte bien a tu archivo de conexión

$MiConexion = ConexionBD();
if ($MiConexion) {
    $MiConexion->set_charset("utf8mb4");
}
date_default_timezone_set('America/Argentina/Cordoba');

$id_usuario_actual = $_SESSION['Usuario_Id'] ?? 0;
$accion = $_REQUEST['tax_action'] ?? '';

// Forzar encabezado JSON puro y limpiar cualquier búfer previo
if (ob_get_length()) {
    @ob_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($MiConexion) || !$MiConexion) {
    echo json_encode(['success' => false, 'error' => 'No hay conexión a la base de datos']);
    exit;
}

// Acción: Guardar nuevo cobro en MySQL
if ($accion === 'guardar') {
    $tipo = mysqli_real_escape_string($MiConexion, $_POST['tipo'] ?? '');
    $minutos = (int)($_POST['minutos'] ?? 0);
    $costo = (float)($_POST['costo'] ?? 0);
    $descuento = (int)($_POST['descuento'] ?? 0);
    
    if (!empty($tipo) && $costo > 0 && $id_usuario_actual > 0) {
        $sqlInsert = "INSERT INTO historial_taximetro (id_usuario, tipo_servicio, minutos_totales, monto_cobrado, con_descuento, fecha_hora) 
                      VALUES ($id_usuario_actual, '$tipo', $minutos, $costo, $descuento, NOW())";
        $status = @mysqli_query($MiConexion, $sqlInsert);
        echo json_encode(['success' => (bool)$status]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos o sesión no iniciada']);
    }
    exit;
}

// Acción: Consultar cobros del día para el usuario logueado
if ($accion === 'obtener') {
    $sqlObtener = "SELECT tipo_servicio, minutos_totales, monto_cobrado, DATE_FORMAT(fecha_hora, '%H:%i') AS hora 
                   FROM historial_taximetro 
                   WHERE id_usuario = $id_usuario_actual AND DATE(fecha_hora) = CURDATE() 
                   ORDER BY id_cobro DESC LIMIT 10";
    $resHist = @mysqli_query($MiConexion, $sqlObtener);
    $cobros = [];
    if ($resHist) {
        while ($row = mysqli_fetch_assoc($resHist)) {
            $cobros[] = [
                'tipo' => $row['tipo_servicio'],
                'minutos' => $row['minutos_totales'],
                'costo' => $row['monto_cobrado'],
                'hora' => $row['hora']
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $cobros]);
    exit;
}
?>