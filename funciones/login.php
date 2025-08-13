<?php 
function DatosLogin($vUsuario, $vClave, $vConexion) {
    $Usuario = array();
    
    // 1. Validación básica de inputs
    $vUsuario = trim($vUsuario);
    $vClave = trim($vClave);
    
    if (empty($vUsuario) || empty($vClave)) {
        error_log("Intento de login con campos vacíos");
        return $Usuario;
    }

    // 2. Consulta preparada para obtener usuario
    $SQL = "SELECT u.*, tu.denominacion AS tipo_usuario 
            FROM usuarios u
            JOIN tipo_usuario tu ON u.idTipoUsuario = tu.idTipoUsuario
            WHERE u.usuario = ?";
    
    // 3. Preparamos y ejecutamos la consulta de forma segura
    $stmt = $vConexion->prepare($SQL);
    if (!$stmt) {
        error_log("Error preparando consulta: " . $vConexion->error);
        return $Usuario;
    }
    
    $stmt->bind_param("s", $vUsuario);
    if (!$stmt->execute()) {
        error_log("Error ejecutando consulta: " . $stmt->error);
        return $Usuario;
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    // 4. Verificación de usuario y contraseña
    if (!empty($data)) {
        // Debug (puedes comentar estas líneas en producción)
        error_log("Intento de login para usuario: " . $vUsuario);
        error_log("Hash almacenado: " . $data['clave']);
        
        // Primero verificar si la cuenta está activa
        if ($data['idActivo'] != 1) {
            error_log("Intento de login en cuenta inactiva: $vUsuario");
            return $Usuario;
        }
        
        // Luego verificar la contraseña
        if (md5($vClave) === $data['clave']) {
            // 5. Datos básicos del usuario
            $Usuario['ID'] = $data['idUsuario'];
            $Usuario['NOMBRE'] = $data['nombre'];
            $Usuario['APELLIDO'] = $data['apellido'];
            $Usuario['NIVEL'] = $data['idTipoUsuario'];
            $Usuario['TIPO_USUARIO'] = $data['tipo_usuario'];
            
            // Obtener la fecha actual
            $fechaActual = date('Y-m-d');
            
            // Verificar si existe una caja para hoy
            $SQLCaja = "SELECT idCaja FROM caja WHERE Fecha = '$fechaActual'";
            $rsCaja = mysqli_query($vConexion, $SQLCaja);
            $cajaData = mysqli_fetch_array($rsCaja);
            
            if (!empty($cajaData)) {
                // Si existe, asignar el ID de la caja
                $Usuario['ID_CAJA'] = $cajaData['idCaja'];
            } else {
                // Si no existe, crear una nueva caja
                // Buscar la caja anterior (la de la fecha más reciente anterior a hoy)
                $SQLCajaAnterior = "SELECT cajaInicial, 
                    (SELECT IFNULL(SUM(dc.monto),0) FROM detalle_caja dc 
                        JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento 
                        JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                        WHERE dc.idCaja = c.idCaja AND tm.es_entrada = 1 AND tp.denominacion = 'Efectivo') AS totalEntradas,
                    (SELECT IFNULL(SUM(dc.monto),0) FROM detalle_caja dc 
                        JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento 
                        WHERE dc.idCaja = c.idCaja AND tm.es_salida = 1) AS totalRetiros
                    FROM caja c
                    WHERE Fecha < '$fechaActual'
                    ORDER BY Fecha DESC
                    LIMIT 1";
                $rsCajaAnterior = mysqli_query($vConexion, $SQLCajaAnterior);
                $cajaAnterior = mysqli_fetch_assoc($rsCajaAnterior);
                
                if ($cajaAnterior) {
                    $cajaInicialAnterior = (float)$cajaAnterior['cajaInicial'];
                    $totalEntradas = (float)$cajaAnterior['totalEntradas'];
                    $totalRetiros = (float)$cajaAnterior['totalRetiros'];
                    $cajaEfectivoAnterior = $cajaInicialAnterior + $totalEntradas - $totalRetiros;
                } else {
                    $cajaEfectivoAnterior = 19500; // Valor por defecto si no hay caja anterior
                }
                
                $SQLNuevaCaja = "INSERT INTO caja (Fecha, cajaInicial) 
                                 VALUES ('$fechaActual', $cajaEfectivoAnterior)";
                
                if (mysqli_query($vConexion, $SQLNuevaCaja)) {
                    $idNuevaCaja = mysqli_insert_id($vConexion);
                    $Usuario['ID_CAJA'] = $idNuevaCaja;
                } else {
                    // Error al crear la caja
                    $Usuario['ID_CAJA'] = 0;
                    error_log("Error creando caja: " . mysqli_error($vConexion));
                }
            }
        } else {
            error_log("Contraseña incorrecta para usuario: $vUsuario");
        }
    } else {
        error_log("Usuario no encontrado: $vUsuario");
    }
    
    return $Usuario;
}
?>