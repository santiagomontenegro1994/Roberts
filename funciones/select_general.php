<?php

function Eliminar_Cliente($vConexion , $vIdConsulta) {


    //soy admin 
        $SQL_MiConsulta="SELECT Id FROM clientes 
                        WHERE Id = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['Id']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "DELETE FROM clientes WHERE Id = $vIdConsulta");
        return true;

    }else {
        return false;
    }
    
}

function Eliminar_Turno($vConexion , $vIdConsulta) {


    //soy admin 
        $SQL_MiConsulta="SELECT IdTurno FROM turnos 
                        WHERE IdTurno = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['IdTurno']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "DELETE FROM turnos WHERE IdTurno = $vIdConsulta");
        return true;

    }else {
        return false;
    }
    
}

function Datos_Cliente($vConexion , $vIdCliente) {
    $DatosCliente  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM clientes 
            WHERE Id = $vIdCliente";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosCliente['ID_CLIENTE'] = $data['id'];
        $DatosCliente['NOMBRE'] = $data['nombre'];
        $DatosCliente['APELLIDO'] = $data['apellido'];
        $DatosCliente['TELEFONO'] = $data['telefono'];
        $DatosCliente['DIRECCION'] = $data['direccion'];
        $DatosCliente['EMAIL'] = $data['email'];
    }
    return $DatosCliente;

}

function Datos_Turno($vConexion , $vIdTurno) {
    $DatosTurno  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM turnos 
            WHERE IdTurno = $vIdTurno";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosTurno['ID_TURNO'] = $data['IdTurno'];
        $DatosTurno['HORARIO'] = $data['Horario'];
        $DatosTurno['FECHA'] = $data['Fecha'];
        $DatosTurno['TIPO_SERVICIO'] = $data['IdTipoServicio'];
        $DatosTurno['ESTILISTA'] = $data['IdEstilista'];
        $DatosTurno['ESTADO'] = $data['IdEstado'];
        $DatosTurno['CLIENTE'] = $data['IdCliente'];
    }
    return $DatosTurno;

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
    if (strlen($_POST['Telefono']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un telefono con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Email']) < 5) {
        $_SESSION['Mensaje'].='Debes ingresar un correo con al menos 5 caracteres. <br />';
    }

    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Validar_Turno(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Fecha']) < 4) {
        $_SESSION['Mensaje'].='Debes seleccionar una fecha. <br />';
    }
    if (strlen($_POST['Horario']) < 4) {
        $_SESSION['Mensaje'].='Debes seleccionar un horario. <br />';
    }
    if ($_POST['TipoServicio'] == 'Selecciona una opcion') {
        $_SESSION['Mensaje'].='Debes seleccionar un Tipo de Servicio. <br />';
    }
    if ($_POST['Estilista'] == 'Selecciona una opcion') {
        $_SESSION['Mensaje'].='Debes seleccionar un Estilista. <br />';
    }
    if ($_POST['Cliente'] == 'Selecciona una opcion') {
        $_SESSION['Mensaje'].='Debes seleccionar un Cliente. <br />';
    }

    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    //foreach($_POST as $Id=>$Valor){
    //    $_POST[$Id] = trim($_POST[$Id]);
    //    $_POST[$Id] = strip_tags($_POST[$Id]);
    //}

    return $_SESSION['Mensaje'];
}

function Modificar_Cliente($vConexion) {
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($vConexion, $_POST['Apellido']);
    $telefono = mysqli_real_escape_string($vConexion, $_POST['Telefono']);
    $direccion = mysqli_real_escape_string($vConexion, $_POST['Direccion']);
    $email = mysqli_real_escape_string($vConexion, $_POST['Email']);
    $idCliente = mysqli_real_escape_string($vConexion, $_POST['IdCliente']);

    $SQL_MiConsulta = "UPDATE clientes 
    SET nombre = '$nombre',
    apellido = '$apellido',
    telefono = '$telefono',
    direccion = '$direccion',
    email = '$email'
    WHERE Id = '$idCliente'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Modificar_Turno($vConexion) {
    $fecha = mysqli_real_escape_string($vConexion, $_POST['Fecha']);
    $horario = mysqli_real_escape_string($vConexion, $_POST['Horario']);
    $tipoServicio = mysqli_real_escape_string($vConexion, $_POST['TipoServicio']);
    $estilista = mysqli_real_escape_string($vConexion, $_POST['Estilista']);
    $cliente = mysqli_real_escape_string($vConexion, $_POST['Cliente']);
    $estado = mysqli_real_escape_string($vConexion, $_POST['Estado']);
    $idTurno = mysqli_real_escape_string($vConexion, $_POST['IdTurno']);

    $SQL_MiConsulta = "UPDATE turnos 
    SET Fecha = '$fecha',
    Horario = '$horario',
    IdTipoServicio = '$tipoServicio',
    IdEstilista = '$estilista',
    IdCliente = '$cliente',
    IdEstado = '$estado'
    WHERE IdTurno = '$idTurno'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Listar_Tipos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT IdTipoServicio , Denominacion
        FROM tipo_servicio
        ORDER BY Denominacion";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['IdTipoServicio'];
            $Listado[$i]['DENOMINACION'] = $data['Denominacion'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}


function Listar_Estilistas($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT IdEstilista , Apellido , Nombre
        FROM estilista
        ORDER BY Apellido";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['IdEstilista'];
            $Listado[$i]['APELLIDO'] = $data['Apellido'];
            $Listado[$i]['NOMBRE'] = $data['Nombre'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Clientes_Turnos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT id , apellido , nombre
        FROM clientes
        ORDER BY Apellido";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['id'];
            $Listado[$i]['APELLIDO'] = $data['apellido'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Estados_Turnos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT IdEstado , Denominacion
        FROM estado
        ORDER BY IdEstado";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['IdEstado'];
            $Listado[$i]['DENOMINACION'] = $data['Denominacion'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Turnos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo

        $SQL = "SELECT T.IdTurno, T.Fecha, T.Horario, C.nombre, C.apellido, E.IdEstado as estado, ES.Nombre, ES.Apellido, T.IdTipoServicio
        FROM clientes C, estado E, estilista ES, turnos T
        WHERE T.IdCliente=C.id AND T.IdEstado=E.IdEstado
        AND T.IdEstilista=ES.IdEstilista ";
        
        if($_SESSION['Usuario_Nivel'] == '2'){
            //si soy estilista solo veo mis consultas
            if($_SESSION['Usuario_Id'] == 3){
                //Listo lo de Lorena
                $SQL .="AND T.IdEstilista=2 ";
            }elseif($_SESSION['Usuario_Id'] == 4){
                //Listo lo de Natalia
                $SQL .="AND T.IdEstilista=1 ";
            }    

        }

        $SQL .= "ORDER BY T.Fecha, T.Horario";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            //paso el contenido del tipo de servicio a un array

            $Listado[$i]['ID_TURNO'] = $data['IdTurno'];
            $Listado[$i]['FECHA'] = $data['Fecha'];
            $Listado[$i]['HORARIO'] = $data['Horario'];
            $Listado[$i]['NOMBRE_C'] = $data['nombre'];
            $Listado[$i]['APELLIDO_C'] = $data['apellido'];
            $Listado[$i]['ESTADO'] = $data['estado'];
            $Listado[$i]['NOMBRE_E'] = $data['Nombre'];
            $Listado[$i]['APELLIDO_E'] = $data['Apellido'];
            $Listado[$i]['TIPO_SERVICIO'] = $data['IdTipoServicio'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Turnos_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo

        switch ($criterio) { 
        case 'Cliente': 
            $SQL = "SELECT T.IdTurno, T.Fecha, T.Horario, C.nombre, C.apellido, E.IdEstado as estado, ES.Nombre, ES.Apellido,T.IdTipoServicio
        FROM clientes C, estado E, estilista ES, turnos T
        WHERE (C.nombre LIKE '%$parametro%' OR C.apellido LIKE '%$parametro%') 
        AND T.IdCliente=C.id AND T.IdEstado=E.IdEstado
        AND T.IdEstilista=ES.IdEstilista
        ORDER BY T.Fecha, T.Horario";
        break;
        case 'Estilista':
            $SQL = "SELECT T.IdTurno, T.Fecha, T.Horario, C.nombre, C.apellido, E.denominacion as estado, ES.Nombre, ES.Apellido,T.IdTipoServicio
        FROM clientes C, estado E, estilista ES, turnos T
        WHERE (ES.Nombre LIKE '%$parametro%' OR ES.Apellido LIKE '%$parametro%') 
        AND T.IdCliente=C.id AND T.IdEstado=E.IdEstado
        AND T.IdEstilista=ES.IdEstilista
        ORDER BY T.Fecha, T.Horario";
        break;
        case 'Fecha':
            $SQL = "SELECT T.IdTurno, T.Fecha, T.Horario, C.nombre, C.apellido, E.denominacion as estado, ES.Nombre, ES.Apellido,T.IdTipoServicio
        FROM clientes C, estado E, estilista ES, turnos T
        WHERE T.Fecha LIKE '%$parametro%' 
        AND T.IdCliente=C.id AND T.IdEstado=E.IdEstado
        AND T.IdEstilista=ES.IdEstilista
        ORDER BY T.Fecha, T.Horario";
        break;
        case 'TipoServicio':
            $SQL = "SELECT T.IdTurno, T.Fecha, T.Horario, C.nombre, C.apellido, E.denominacion as estado, ES.Nombre, ES.Apellido,T.IdTipoServicio
        FROM clientes C, estado E, estilista ES, turnos T
        WHERE TP.Denominacion LIKE '%$parametro%' 
        AND T.IdCliente=C.id AND T.IdEstado=E.IdEstado
        AND T.IdEstilista=ES.IdEstilista
        ORDER BY T.Fecha, T.Horario";
        break;
        }    

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_TURNO'] = $data['IdTurno'];
            $Listado[$i]['FECHA'] = $data['Fecha'];
            $Listado[$i]['HORARIO'] = $data['Horario'];
            $Listado[$i]['NOMBRE_C'] = $data['nombre'];
            $Listado[$i]['APELLIDO_C'] = $data['apellido'];
            $Listado[$i]['ESTADO'] = $data['estado'];
            $Listado[$i]['NOMBRE_E'] = $data['Nombre'];
            $Listado[$i]['APELLIDO_E'] = $data['Apellido'];
            $Listado[$i]['TIPO_SERVICIO'] = $data['IdTipoServicio'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;

}

function InsertarTurnos($vConexion){
    //divido el array a una cadena separada por coma para guardar
    $string = implode(',', $_POST['TipoServicio']);

    $SQL_Insert="INSERT INTO turnos ( Horario, Fecha, IdTipoServicio, IdEstilista, IdEstado, IdCliente)
    VALUES ('".$_POST['Horario']."' , '".$_POST['Fecha']."' , '".$string."', '".$_POST['Estilista']."', '1', '".$_POST['Cliente']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
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