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
            $SQLNuevaCaja = "INSERT INTO caja (Fecha, cajaInicial) 
                             VALUES ('$fechaActual', 19500)";
            
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