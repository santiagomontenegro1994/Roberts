<?php 
function DatosLogin($vUsuario, $vClave, $vConexion){
    $Usuario=array();
    
    $SQL="SELECT * FROM usuarios 
     WHERE usuario='$vUsuario' AND clave='$vClave'  ";

    $rs = mysqli_query($vConexion, $SQL);
        
    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $Usuario['ID'] = $data['idUsuario'];
        $Usuario['NOMBRE'] = $data['nombre'];
        $Usuario['APELLIDO'] = $data['apellido'];
        $Usuario['NIVEL'] = $data['idTipoUsuario'];
    }
    return $Usuario;
}

?>