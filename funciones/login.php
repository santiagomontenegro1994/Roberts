<?php 
function DatosLogin($vUsuario, $vClave, $vConexion) {
    $Usuario = array();
    
    $SQL = "SELECT * FROM usuarios 
            WHERE usuario='$vUsuario' AND clave='$vClave'";
    
    $rs = mysqli_query($vConexion, $SQL);
    $data = mysqli_fetch_array($rs);
    
    if (!empty($data)) {
        $Usuario['ID'] = $data['idUsuario'];
        $Usuario['NOMBRE'] = $data['nombre'];
        $Usuario['APELLIDO'] = $data['apellido'];
        $Usuario['NIVEL'] = $data['idTipoUsuario'];
        
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
                    WHERE dc.idCaja = c.idCaja AND tm.es_entrada = 1) AS totalEntradas,
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
            }
        }
    }
    
    return $Usuario;
}

?>