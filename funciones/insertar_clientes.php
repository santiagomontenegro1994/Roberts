<?php 
function InsertarClientes($vConexion){
    
    $SQL_Insert="INSERT INTO clientes (nombre, apellido, telefono, direccion, email)
    VALUES ('".$_POST['Nombre']."' , '".$_POST['Apellido']."' , '".$_POST['Telefono']."', '".$_POST['Direccion']."', '".$_POST['Email']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}
?>