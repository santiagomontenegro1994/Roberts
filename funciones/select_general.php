<?php
function InsertarClientes($vConexion){
    
    $SQL_Insert="INSERT INTO clientes (nombre, apellido, dni, direccion, telefono)
    VALUES ('".$_POST['Nombre']."' , '".$_POST['Apellido']."' , '".$_POST['DNI']."', '".$_POST['Direccion']."', '".$_POST['Telefono']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function Listar_Clientes($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT * FROM clientes";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_CLIENTE'] = $data['idCliente'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['APELLIDO'] = $data['apellido'];
            $Listado[$i]['TELEFONO'] = $data['telefono'];
            $Listado[$i]['DIRECCION'] = $data['direccion'];
            $Listado[$i]['DNI'] = $data['dni'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Clientes_Pedidos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT idCLiente , apellido , nombre
        FROM clientes
        ORDER BY apellido";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['idCLiente'];
            $Listado[$i]['APELLIDO'] = $data['apellido'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Clientes_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo segun el parametro
        $sql = "SELECT * FROM clientes";
        switch ($criterio) { 
        case 'Nombre': 
        $sql = "SELECT * FROM clientes WHERE nombre LIKE '%$parametro%'";
        break;
        case 'Apellido':
        $sql = "SELECT * FROM clientes WHERE apellido LIKE '%$parametro%'";
        break;
        case 'Telefono':
        $sql = "SELECT * FROM clientes WHERE telefono LIKE '%$parametro%'";
        break;
        case 'DNI':
        $sql = "SELECT * FROM clientes WHERE dni LIKE '%$parametro%'";
        break;
        }    
        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $sql);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_CLIENTE'] = $data['idCliente'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['APELLIDO'] = $data['apellido'];
            $Listado[$i]['TELEFONO'] = $data['telefono'];
            $Listado[$i]['DIRECCION'] = $data['direccion'];
            $Listado[$i]['DNI'] = $data['dni'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Eliminar_Cliente($vConexion , $vIdConsulta) {


    //soy admin 
        $SQL_MiConsulta="SELECT idCliente FROM clientes 
                        WHERE idCliente = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idCliente']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "DELETE FROM clientes WHERE idCliente = $vIdConsulta");
        return true;

    }else {
        return false;
    }
    
}

function Datos_Cliente($vConexion , $vIdCliente) {
    $DatosCliente  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM clientes 
            WHERE idCliente = $vIdCliente";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosCliente['ID_CLIENTE'] = $data['idCliente'];
        $DatosCliente['NOMBRE'] = $data['nombre'];
        $DatosCliente['APELLIDO'] = $data['apellido'];
        $DatosCliente['TELEFONO'] = $data['telefono'];
        $DatosCliente['DIRECCION'] = $data['direccion'];
        $DatosCliente['DNI'] = $data['dni'];
    }
    return $DatosCliente;

}

function Validar_Cliente(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Nombre']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un nombre con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Apellido']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un apellido con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Direccion']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar una direccion con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Telefono']) < 10) {
        $_SESSION['Mensaje'].='Debes ingresar un telefono con al menos 10 caracteres. <br />';
    }
    if (strlen($_POST['DNI']) < 8) {
        $_SESSION['Mensaje'].='Debes ingresar un DNI con al menos 8 caracteres. <br />';
    }

    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Modificar_Cliente($vConexion) {
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($vConexion, $_POST['Apellido']);
    $telefono = mysqli_real_escape_string($vConexion, $_POST['Telefono']);
    $direccion = mysqli_real_escape_string($vConexion, $_POST['Direccion']);
    $dni = mysqli_real_escape_string($vConexion, $_POST['DNI']);
    $idCliente = mysqli_real_escape_string($vConexion, $_POST['IdCliente']);

    $SQL_MiConsulta = "UPDATE clientes 
    SET nombre = '$nombre',
    apellido = '$apellido',
    telefono = '$telefono',
    direccion = '$direccion',
    dni = '$dni'
    WHERE idCliente = '$idCliente'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function InsertarLibros($vConexion){
    
    $SQL_Insert="INSERT INTO libros (isbn, titulo, autor, editorial, precio)
    VALUES ('".$_POST['ISBN']."' , '".$_POST['Titulo']."' , '".$_POST['Autor']."', '".$_POST['Editorial']."', '".$_POST['Precio']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function Validar_Libros(){
    $_SESSION['ISBN']='';
    if (strlen($_POST['ISBN']) < 5) {
        $_SESSION['Mensaje'].='Debes ingresar un ISBN con al menos 5 caracteres. <br />';
    }
    if (strlen($_POST['Titulo']) < 5) {
        $_SESSION['Mensaje'].='Debes ingresar un Titulo con al menos 5 caracteres. <br />';
    }
    if (strlen($_POST['Autor']) < 5) {
        $_SESSION['Mensaje'].='Debes ingresar un Autor con al menos 5 caracteres. <br />';
    }
    if (strlen($_POST['Editorial']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar una Editorial con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Precio']) < 2) {
        $_SESSION['Mensaje'].='Debes ingresar un Precio con al menos 2 caracteres. <br />';
    }

    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Listar_Libros($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT * FROM libros";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_LIBRO'] = $data['idLibros'];
            $Listado[$i]['ISBN'] = $data['isbn'];
            $Listado[$i]['TITULO'] = $data['titulo'];
            $Listado[$i]['AUTOR'] = $data['autor'];
            $Listado[$i]['EDITORIAL'] = $data['editorial'];
            $Listado[$i]['PRECIO'] = $data['precio'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Libros_Pedidos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT idLibros , titulo , autor , precio
        FROM libros
        ORDER BY titulo";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['idLibros'];
            $Listado[$i]['TITULO'] = $data['titulo'];
            $Listado[$i]['AUTOR'] = $data['autor'];
            $Listado[$i]['PRECIO'] = $data['precio'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function ColorDeFila($vFecha,$vEstado) {
    $Title='';
    $Color=''; 
    $FechaActual = date("Y-m-d");

    if ($vFecha < $FechaActual && $vEstado!=3){
        //la fecha del viaje es mayor a mañana?
        $Title='Turno Vencido';
        $Color='table-danger'; 
    
    } else if ($vEstado == 2){
        //Turno en Curso
        $Title='Turno en Curso';
        $Color='table-warning'; 
    } else if ($vEstado==3){
        //Turno Completado
        $Title='Turno Completado';
        $Color='table-success'; 
    } else if ($vEstado == 1){
        //Turno pendiente
        $Title='Turno Pendiente';
        $Color='table-primary';
    }
        
    
    return [$Title, $Color];

}


?>