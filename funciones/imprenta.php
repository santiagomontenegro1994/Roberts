<?php
function InsertarClientes($vConexion) {
    // 1. Primero verificamos si el teléfono ya existe
    $telefono = mysqli_real_escape_string($vConexion, $_POST['Telefono']);
    $SQL_Check = "SELECT idCliente FROM clientes WHERE telefono = '$telefono' LIMIT 1";
    
    $resultado = mysqli_query($vConexion, $SQL_Check);
    
    if (mysqli_num_rows($resultado) > 0) {
        // Si existe un cliente con ese teléfono, retornamos un error
        return "Ya existe un cliente registrado con este número de teléfono";
    }
    
    // 2. Si no existe, procedemos con la inserción
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($vConexion, $_POST['Apellido']);
    
    $SQL_Insert = "INSERT INTO clientes (nombre, apellido, telefono)
                  VALUES ('$nombre', '$apellido', '$telefono')";
    
    if (!mysqli_query($vConexion, $SQL_Insert)) {
        die('<h4>Error al intentar insertar el registro.</h4>');
    }
    
    return true;
}

function Listar_Clientes($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT * FROM clientes WHERE idActivo=1";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_CLIENTE'] = $data['idCliente'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['APELLIDO'] = $data['apellido'];
            $Listado[$i]['TELEFONO'] = $data['telefono'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Anular_Cliente($vConexion , $vIdConsulta) {


    //soy admin 
        $SQL_MiConsulta="SELECT idCliente FROM clientes 
                        WHERE idCliente = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idCliente']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "UPDATE clientes SET idActivo = 2 WHERE idCliente = $vIdConsulta");
        
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
    }
    return $DatosCliente;

}

function Validar_Cliente(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Nombre']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un nombre con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Telefono']) < 10) {
        $_SESSION['Mensaje'].='Debes ingresar un telefono con al menos 10 caracteres. <br />';
    }

    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Listar_Clientes_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo segun el parametro
        $sql = "";
        switch ($criterio) { 
            case 'Nombre': 
        // Divide el parámetro en partes (nombre y apellido)
        $partes = explode(' ', trim($parametro));
        $nombre = isset($partes[0]) ? $partes[0] : '';
        $apellido = isset($partes[1]) ? $partes[1] : '';
        
        if ($nombre && $apellido) {
            // Si hay nombre y apellido (ej: "karen ba")
            $sql = "SELECT * FROM clientes 
                    WHERE (nombre LIKE '$nombre%' AND apellido LIKE '$apellido%') 
                    AND idActivo = 1";
        } else {
            // Si solo hay un término (ej: "baz")
            $sql = "SELECT * FROM clientes 
                    WHERE (nombre LIKE '%$parametro%' OR apellido LIKE '%$parametro%') 
                    AND idActivo = 1";
        }
        break;
        case 'idCliente':
        $sql = "SELECT * FROM clientes WHERE idCliente LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'Telefono':
        $sql = "SELECT * FROM clientes WHERE telefono LIKE '%$parametro%' AND idActivo = 1";
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
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Modificar_Cliente($vConexion) {
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($vConexion, $_POST['Apellido']);
    $telefono = mysqli_real_escape_string($vConexion, $_POST['Telefono']);
    $idCliente = mysqli_real_escape_string($vConexion, $_POST['IdCliente']);

    $SQL_MiConsulta = "UPDATE clientes 
    SET nombre = '$nombre',
    apellido = '$apellido',
    telefono = '$telefono'
    WHERE idCliente = '$idCliente'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Listar_Proveedores($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT * FROM proveedores WHERE idActivo=1";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_PROVEEDOR'] = $data['idProveedor'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['CONTACTO'] = $data['contacto'];
            $Listado[$i]['CUIT'] = $data['CUIT'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Proveedores_Insumos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT * FROM proveedores_insumos WHERE idActivo=1";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_PROVEEDOR'] = $data['idProveedorInsumo'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['CONTACTO'] = $data['contacto'];
            $Listado[$i]['CUIT'] = $data['CUIT'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function InsertarProveedores($vConexion){
    
    $SQL_Insert="INSERT INTO proveedores (nombre, CUIT, contacto)
    VALUES ('".$_POST['Nombre']."' , '".$_POST['CUIT']."', '".$_POST['Contacto']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function InsertarProveedoresInsumos($vConexion){
    
    $SQL_Insert="INSERT INTO proveedores_insumos (nombre, CUIT, contacto)
    VALUES ('".$_POST['Nombre']."' , '".$_POST['CUIT']."', '".$_POST['Contacto']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function Listar_Proveedores_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo segun el parametro
        $sql = "SELECT * FROM proveedores";
        switch ($criterio) { 
        case 'Nombre': 
        $sql = "SELECT * FROM proveedores WHERE nombre LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'Contacto':
        $sql = "SELECT * FROM proveedores WHERE contacto LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'CUIT':
        $sql = "SELECT * FROM proveedores WHERE CUIT LIKE '%$parametro%' AND idActivo = 1";
        break;
        }    
        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $sql);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_PROVEEDOR'] = $data['idProveedor'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['CONTACTO'] = $data['contacto'];
            $Listado[$i]['CUIT'] = $data['CUIT'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Proveedores_Insumos_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo segun el parametro
        $sql = "SELECT * FROM proveedores_insumos";
        switch ($criterio) { 
        case 'Nombre': 
        $sql = "SELECT * FROM proveedores_insumos WHERE nombre LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'Contacto':
        $sql = "SELECT * FROM proveedores_insumos WHERE contacto LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'CUIT':
        $sql = "SELECT * FROM proveedores_insumos WHERE CUIT LIKE '%$parametro%' AND idActivo = 1";
        break;
        }    
        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $sql);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_PROVEEDOR'] = $data['idProveedorInsumo'];
            $Listado[$i]['NOMBRE'] = $data['nombre'];
            $Listado[$i]['CONTACTO'] = $data['contacto'];
            $Listado[$i]['CUIT'] = $data['CUIT'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Anular_Proveedor($vConexion , $vIdConsulta) {

        $SQL_MiConsulta="SELECT idProveedor FROM proveedores 
                        WHERE idProveedor = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idProveedor']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "UPDATE proveedores SET idActivo = 2 WHERE idProveedor = $vIdConsulta");
        
        return true;

    }else {
        return false;
    }
    
}

function Anular_Proveedor_Insumos($vConexion , $vIdConsulta) {

        $SQL_MiConsulta="SELECT idProveedorInsumo FROM proveedores_insumos 
                        WHERE idProveedorInsumo = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idProveedorInsumo']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "UPDATE proveedores_insumos SET idActivo = 2 WHERE idProveedorInsumo = $vIdConsulta");
        
        return true;

    }else {
        return false;
    }
    
}

function Datos_Proveedor($vConexion , $vIdProveedor) {
    $DatosProveedor  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM proveedores
            WHERE idProveedor = $vIdProveedor";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosProveedor['ID_PROVEEDOR'] = $data['idProveedor'];
        $DatosProveedor['NOMBRE'] = $data['nombre'];
        $DatosProveedor['CONTACTO'] = $data['contacto'];
        $DatosProveedor['CUIT'] = $data['CUIT'];
    }
    return $DatosProveedor;

}

function Datos_Proveedor_Insumos($vConexion , $vIdProveedor) {
    $DatosProveedor  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM proveedores_insumos
            WHERE idProveedorInsumo = $vIdProveedor";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosProveedor['ID_PROVEEDOR'] = $data['idProveedorInsumo'];
        $DatosProveedor['NOMBRE'] = $data['nombre'];
        $DatosProveedor['CONTACTO'] = $data['contacto'];
        $DatosProveedor['CUIT'] = $data['CUIT'];
    }
    return $DatosProveedor;

}

function Validar_Proveedor(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Nombre']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un nombre con al menos 3 caracteres. <br />';
    }
  
    if (strlen($_POST['Contacto']) < 10) {
        $_SESSION['Mensaje'].='Debes ingresar un contacto con al menos 10 caracteres. <br />';
    }
    if (strlen($_POST['CUIT']) < 8) {
        $_SESSION['Mensaje'].='Debes ingresar un CUIT con al menos 8 caracteres. <br />';
    }

    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Modificar_Proveedor($vConexion) {
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $contacto = mysqli_real_escape_string($vConexion, $_POST['Contacto']);
    $cuit = mysqli_real_escape_string($vConexion, $_POST['CUIT']);
    $idProveedor = mysqli_real_escape_string($vConexion, $_POST['IdProveedor']);

    $SQL_MiConsulta = "UPDATE proveedores 
    SET nombre = '$nombre',
    contacto = '$contacto',
    CUIT = '$cuit'
    WHERE idProveedor = '$idProveedor'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Modificar_Proveedor_Insumo($vConexion) {
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $contacto = mysqli_real_escape_string($vConexion, $_POST['Contacto']);
    $cuit = mysqli_real_escape_string($vConexion, $_POST['CUIT']);
    $idProveedor = mysqli_real_escape_string($vConexion, $_POST['IdProveedor']);

    $SQL_MiConsulta = "UPDATE proveedores_insumos 
    SET nombre = '$nombre',
    contacto = '$contacto',
    CUIT = '$cuit'
    WHERE idProveedorInsumo = '$idProveedor'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Listar_Tipos_Pagos_Entrada($conexion) {
    $sql = "SELECT idTipoPago, denominacion FROM tipo_pago WHERE idActivo = 1 AND esEntrada = 1";
    $resultado = mysqli_query($conexion, $sql);

    $tiposPagos = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tiposPagos[] = $fila;
        }
    }
    return $tiposPagos;
}

function Listar_Tipos_Pagos_Salida($conexion) {
    $sql = "SELECT idTipoPago, denominacion FROM tipo_pago WHERE idActivo = 1 AND esSalida = 1";
    $resultado = mysqli_query($conexion, $sql);

    $tiposPagos = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tiposPagos[] = $fila;
        }
    }
    return $tiposPagos;
}

function Listar_Tipos_Pagos_Contable($conexion) {
    $sql = "SELECT idTipoPago, denominacion FROM tipo_pago WHERE idActivo = 1 AND esSalida = 0 AND esEntrada = 0";
    $resultado = mysqli_query($conexion, $sql);

    $tiposPagos = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tiposPagos[] = $fila;
        }
    }
    return $tiposPagos;
}

function Validar_Tipos_Pago(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Denominacion']) < 1) {
        $_SESSION['Mensaje'].='Debes agregar un metodo de pago';
    }
    
    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function InsertarTipoPago($vConexion){
    
    $SQL_Insert="INSERT INTO tipo_pago (denominacion, esEntrada, esSalida, idActivo) 
             VALUES ('".$_POST['Denominacion']."', 1, 0, 1)";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function InsertarTipoPagoContables($vConexion){
    
    $SQL_Insert="INSERT INTO tipo_pago (denominacion, esEntrada, esSalida, idActivo) 
             VALUES ('".$_POST['Denominacion']."', 0, 0, 1)";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function Anular_Tipo_Pago($vConexion , $vIdConsulta) { 
    $SQL_MiConsulta="SELECT idTipoPago FROM tipo_pago 
                    WHERE idTipoPago = $vIdConsulta "; 

    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idTipoPago']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "UPDATE tipo_pago SET idActivo = 2 WHERE idTipoPago = $vIdConsulta");
        
    return true;

    }else {
        return false;
    }

}

function Modificar_Tipo_Pago($vConexion) {
    $denominacion = mysqli_real_escape_string($vConexion, $_POST['Denominacion']);
    $idMetodoPago = mysqli_real_escape_string($vConexion, $_POST['IdTipoPago']);

    $SQL_MiConsulta = "UPDATE tipo_pago
    SET denominacion = '$denominacion'
    WHERE idTipoPago = '$idMetodoPago'"; 

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Datos_Tipo_Pago($vConexion , $vIdTipoPago) {
    $DatosMetodoPago  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM tipo_pago 
            WHERE idTipoPago = $vIdTipoPago";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosMetodoPago['IdTipoPago'] = $data['idTipoPago'];
        $DatosMetodoPago['Denominacion'] = $data['denominacion'];
    }
    return $DatosMetodoPago;

}

function Listar_Tipos_Servicios($vConexion) {
    $SQL = "SELECT idTipoServicio, denominacion FROM tipo_servicio WHERE idActivo = 1";
    $rs = mysqli_query($vConexion, $SQL);

    $tiposServicios = array();
    while ($row = mysqli_fetch_assoc($rs)) {
        $tiposServicios[] = $row;
    }

    return $tiposServicios;
}

function InsertarTipoServicio($vConexion) {
    $SQL_Insert = "INSERT INTO tipo_servicio (denominacion)
    VALUES ('" . $_POST['Denominacion'] . "')";

    if (!mysqli_query($vConexion, $SQL_Insert)) {
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function Modificar_Tipo_Servicio($vConexion) {
    $denominacion = mysqli_real_escape_string($vConexion, $_POST['Denominacion']);
    $idTipoServicio = mysqli_real_escape_string($vConexion, $_POST['IdTipoServicio']);

    $SQL_MiConsulta = "UPDATE tipo_servicio
    SET denominacion = '$denominacion'
    WHERE idTipoServicio = '$idTipoServicio'";

    if (mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    } else {
        return false;
    }
}

function Anular_Tipo_Servicio($vConexion, $vIdConsulta) {
    $SQL_MiConsulta = "SELECT idTipoServicio FROM tipo_servicio 
                    WHERE idTipoServicio = $vIdConsulta";

    $rs = mysqli_query($vConexion, $SQL_MiConsulta);

    $data = mysqli_fetch_array($rs);

    if (!empty($data['idTipoServicio'])) {
        // Si se cumple todo, entonces desactivo:
        mysqli_query($vConexion, "UPDATE tipo_servicio SET idActivo = 2 WHERE idTipoServicio = $vIdConsulta");
        return true;
    } else {
        return false;
    }
}

function Validar_Tipos_Servicio() {
    $_SESSION['Mensaje'] = '';
    if (strlen($_POST['Denominacion']) < 1) {
        $_SESSION['Mensaje'] .= 'Debes agregar un tipo de servicio.';
    }

    // Limpiar espacios y caracteres no deseados
    foreach ($_POST as $Id => $Valor) {
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Datos_Tipo_Servicio($vConexion, $vIdTipoServicio) {
    $DatosTipoServicio = array();
    // Asegurarse de que la consulta exista
    $SQL = "SELECT * FROM tipo_servicio 
            WHERE idTipoServicio = $vIdTipoServicio";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosTipoServicio['IdTipoServicio'] = $data['idTipoServicio'];
        $DatosTipoServicio['Denominacion'] = $data['denominacion'];
    }
    return $DatosTipoServicio;
}

function ObtenerInfoCaja($vConexion, $idCaja) {
    $infoCaja = "Sin caja seleccionada";
    
    if (!empty($idCaja)) {
        $query = "SELECT Fecha, idTurno FROM caja WHERE idCaja = ?";
        $stmt = $vConexion->prepare($query);
        $stmt->bind_param("i", $idCaja);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $infoCaja = "Caja Actual: " . $fila['Fecha'] . " - Turno " . $fila['idTurno'];
        }
        
        $stmt->close();
    }
    
    return $infoCaja;
}

function Listar_Cajas($Conexion) {
    $sql = "SELECT caja.*
            FROM caja 
            ORDER BY caja.Fecha DESC";
            
    $resultado = $Conexion->query($sql);
    $Listado = [];
    while ($fila = $resultado->fetch_assoc()) {
        $Listado[] = $fila;
    }
    return $Listado;
}

function Listar_Cajas_Parametro($Conexion, $Criterio, $Parametro) {
    $sql = "SELECT caja.*
            FROM caja 
            WHERE caja.$Criterio LIKE ? 
            ORDER BY caja.Fecha DESC";
    
    $stmt = $Conexion->prepare($sql);
    $Parametro = "%$Parametro%";
    $stmt->bind_param("s", $Parametro);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $Listado = [];
    while ($fila = $resultado->fetch_assoc()) {
        $Listado[] = $fila;
    }
    return $Listado;
}

function Obtener_Info_Caja($Conexion, $idCaja) {
    $sql = "SELECT c.Fecha
            FROM caja c
            WHERE c.idCaja = ?";
    $stmt = $Conexion->prepare($sql);
    $stmt->bind_param("i", $idCaja);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc();
    }

    return null; // Si no se encuentra la caja
}

function InsertarCaja($vConexion, $Fecha, $cajaInicial) {

    // Verificar si ya existe una caja para la misma fecha
    $SQL_Verificar = "SELECT COUNT(*) AS total FROM caja WHERE DATE(Fecha) = '$Fecha'";
    $resultado = mysqli_query($vConexion, $SQL_Verificar);

    if (!$resultado) {
        return [
            'success' => false,
            'message' => 'Error al verificar caja existente: ' . mysqli_error($vConexion),
            'style' => 'danger'
        ];
    }

    $data = mysqli_fetch_assoc($resultado);
    $total = isset($data['total']) ? (int)$data['total'] : 0;

    if ($total > 0) {
        return [
            'success' => false,
            'message' => 'Error: Ya existe una caja para esta fecha.',
            'style' => 'warning'
        ];
    }

    // Ejecutar la inserción
    $SQL_Insert = "INSERT INTO caja (Fecha, cajaInicial) VALUES ('$Fecha', $cajaInicial)";
    $resultado_insert = mysqli_query($vConexion, $SQL_Insert);

    if (!$resultado_insert) {
        return [
            'success' => false,
            'message' => 'Error al intentar insertar la caja: ' . mysqli_error($vConexion),
            'style' => 'danger'
        ];
    }

    return [
        'success' => true,
        'message' => 'Caja insertada correctamente.',
        'style' => 'success'
    ];
}

function Validar_Caja(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Fecha']) < 1) {
        $_SESSION['Mensaje'].='Debes seleccionar una fecha. <br />';
    }
    if (strlen($_POST['cajaIncial']) < 0) {
        $_SESSION['Mensaje'].='Debes poner una caja inicial. <br />';
    }
    
    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Datos_Caja($vConexion, $vIdCaja) {
    $DatosCaja = array();
    // Asegurarse de que la consulta exista
    $SQL = "SELECT * FROM caja WHERE idCaja = $vIdCaja";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs);
    if (!empty($data)) {
        $DatosCaja['IDCAJA'] = $data['idCaja'];
        $DatosCaja['FECHA'] = $data['Fecha'];
        $DatosCaja['CAJA_INICIAL'] = $data['cajaInicial'];
    }
    return $DatosCaja;
}

function ObtenerDetallesCaja($vConexion, $idCaja) {
    $query = "SELECT 
                dc.idDetalleCaja, 
                dc.idCaja, 
                dc.idTipoMovimiento, 
                tp.denominacion AS metodoPago, 
                tm.denominacion AS detalle,         
                tm.es_entrada, 
                tm.es_salida, 
                u.nombre AS usuario, 
                dc.monto, 
                dc.observaciones
              FROM detalle_caja dc
              JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
              JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
              JOIN usuarios u ON dc.idUsuario = u.idUsuario
              WHERE dc.idCaja = ?
              ORDER BY dc.idDetalleCaja DESC";

    $stmt = $vConexion->prepare($query);
    $stmt->bind_param("i", $idCaja);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if (!$resultado) {
        die('<div class="alert alert-danger">Error en la consulta de detalle_caja: ' . $vConexion->error . '</div>');
    }

    return $resultado;
}

function Modificar_Caja($vConexion) {
    $idCaja = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
    $Fecha = mysqli_real_escape_string($vConexion, $_POST['Fecha']);
    $cajaInicial = mysqli_real_escape_string($vConexion, $_POST['cajaInicial']);

    // Verificar si ya existe una caja para la misma fecha, excluyendo la caja actual
    $SQL_Verificar = "SELECT COUNT(*) AS total 
                      FROM caja 
                      WHERE DATE(Fecha) = '$Fecha' 
                      AND idCaja != $idCaja";
    $resultado = mysqli_query($vConexion, $SQL_Verificar);

    if (!$resultado) {
        return [
            'success' => false,
            'message' => 'Error al verificar caja existente: ' . mysqli_error($vConexion),
            'style' => 'danger'
        ];
    }

    $data = mysqli_fetch_assoc($resultado);
    $total = isset($data['total']) ? (int)$data['total'] : 0;

    if ($total > 0) {
        return [
            'success' => false,
            'message' => 'Error: Ya existe una caja para esta fecha y turno.',
            'style' => 'warning'
        ];
    }

    // Ejecutar la modificación
    $SQL_MiConsulta = "UPDATE caja
                       SET Fecha = '$Fecha',
                           cajaInicial = $cajaInicial
                       WHERE idCaja = $idCaja";

    if (mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return [
            'success' => true,
            'message' => 'La caja se ha modificado correctamente.',
            'style' => 'success'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al intentar modificar la caja: ' . mysqli_error($vConexion),
            'style' => 'danger'
        ];
    }
}

function Anular_Caja($vConexion, $vIdCaja) {
    // Verificar si la caja existe
    $SQL_MiConsulta = "SELECT idCaja FROM caja WHERE idCaja = $vIdCaja AND idActivo = 1";
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idCaja'])) {
        // Usar transacción para asegurar integridad
        mysqli_begin_transaction($vConexion);

        try {
            // Anular la caja
            $SQL_Update_Caja = "UPDATE caja SET idActivo = 2 WHERE idCaja = $vIdCaja";
            mysqli_query($vConexion, $SQL_Update_Caja);

            // Anular los detalles de esa caja
            $SQL_Update_Detalle = "UPDATE detalle_caja SET idActivo = 2 WHERE idCaja = $vIdCaja";
            mysqli_query($vConexion, $SQL_Update_Detalle);

            // Confirmar cambios
            mysqli_commit($vConexion);
            return true;

        } catch (Exception $e) {
            // Revertir cambios si algo falla
            mysqli_rollback($vConexion);
            return false;
        }
    } else {
        return false; // No se encontró la caja activa
    }
}

function Anular_Venta($vConexion, $vIdConsulta) {
    // Verificar si el registro existe en la tabla detalle_caja
    $SQL_MiConsulta = "SELECT * FROM detalle_caja WHERE idDetalleCaja = $vIdConsulta";
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
    $data = mysqli_fetch_assoc($rs);

    if (!empty($data['idDetalleCaja'])) {
        // Verificar si existe un retiro asociado
        $SQL_Retiro = "SELECT idRetiro FROM retiros WHERE idRetiro = (SELECT idRetiro FROM detalle_caja WHERE idDetalleCaja = $vIdConsulta)";
        $rsRetiro = mysqli_query($vConexion, $SQL_Retiro);
        $dataRetiro = mysqli_fetch_assoc($rsRetiro);

        if (!empty($dataRetiro['idRetiro'])) {
            $idRetiro = $dataRetiro['idRetiro'];

            // Determinar la subtabla del detalle del retiro
            $sqlSub = "SELECT * FROM retiros_proveedores WHERE idRetiro = $idRetiro
                       UNION SELECT * FROM retiros_sueldos WHERE idRetiro = $idRetiro
                       UNION SELECT * FROM retiros_servicios WHERE idRetiro = $idRetiro
                       UNION SELECT * FROM retiros_insumos WHERE idRetiro = $idRetiro
                       UNION SELECT * FROM retiros_varios WHERE idRetiro = $idRetiro";
            $rsSub = mysqli_query($vConexion, $sqlSub);
            if ($rsSub && $rsSub->num_rows > 0) {
                $rowSub = mysqli_fetch_assoc($rsSub);
                if (isset($rowSub['idProveedor'])) $subtabla = 'retiros_proveedores';
                elseif (isset($rowSub['idUsuarioSueldo'])) $subtabla = 'retiros_sueldos';
                elseif (isset($rowSub['tipo_servicio'])) $subtabla = 'retiros_servicios';
                elseif (isset($rowSub['categoria']) && isset($rowSub['detalle_insumo'])) $subtabla = 'retiros_insumos';
                else $subtabla = 'retiros_varios';

                // Eliminar detalle del retiro
                mysqli_query($vConexion, "DELETE FROM $subtabla WHERE idRetiro = $idRetiro");
            }

            // Eliminar el retiro
            mysqli_query($vConexion, "DELETE FROM retiros WHERE idRetiro = $idRetiro");
        }

        // Finalmente eliminar el detalle de caja
        $SQL_Delete = "DELETE FROM detalle_caja WHERE idDetalleCaja = $vIdConsulta";
        if (mysqli_query($vConexion, $SQL_Delete)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false; // No se encontró el registro
    }
}

function Modificar_Venta($vConexion) {
    mysqli_begin_transaction($vConexion);
    try {
        // --- Inputs ---
        $idDetalleCaja     = mysqli_real_escape_string($vConexion, $_POST['idDetalleCaja']);
        $idCaja            = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
        $idTipoPago        = mysqli_real_escape_string($vConexion, $_POST['idTipoPago']);
        $idTipoMovimiento  = mysqli_real_escape_string($vConexion, $_POST['idTipoMovimiento']);
        $idUsuario         = mysqli_real_escape_string($vConexion, $_POST['idUsuario']);

        // Monto real (numérico)
        $monto = floatval($_POST['MontoReal']);
        $monto = mysqli_real_escape_string($vConexion, $monto);

        $observaciones = isset($_POST['Observaciones']) ? mysqli_real_escape_string($vConexion, $_POST['Observaciones']) : null;

        $facturado           = isset($_POST['facturado']) ? 1 : 0;
        $facturadoAnterior   = isset($_POST['facturado_anterior']) ? (int)$_POST['facturado_anterior'] : 0;
        $idTipoFactura       = isset($_POST['idTipoFactura']) && $_POST['idTipoFactura'] !== '' ? mysqli_real_escape_string($vConexion, $_POST['idTipoFactura']) : null;
        $numeroFactura       = isset($_POST['numeroFactura']) && $_POST['numeroFactura'] !== '' ? mysqli_real_escape_string($vConexion, $_POST['numeroFactura']) : null;

        // --- Si se desmarcó facturación, quitarla ---
        if ($facturadoAnterior === 1 && $facturado === 0) {
            $SQL_Call = "CALL quitar_factura('caja', '$idDetalleCaja')";
            if (!mysqli_query($vConexion, $SQL_Call)) {
                throw new Exception("Error al quitar factura: " . mysqli_error($vConexion));
            }
        }

        // --- UPDATE detalle_caja ---
        $SQL_Det = "
            UPDATE detalle_caja
               SET idCaja         = '$idCaja',
                   idTipoPago     = '$idTipoPago',
                   idTipoMovimiento = '$idTipoMovimiento',
                   idUsuario      = '$idUsuario',
                   monto          = '$monto',
                   observaciones  = " . ($observaciones !== null ? "'$observaciones'" : "NULL") . ",
                   facturado      = '$facturado',
                   idTipoFactura  = " . ($idTipoFactura !== null ? "'$idTipoFactura'" : "NULL") . ",
                   numeroFactura  = " . ($numeroFactura !== null ? "'$numeroFactura'" : "NULL") . "
             WHERE idDetalleCaja  = '$idDetalleCaja'
        ";
        if (!mysqli_query($vConexion, $SQL_Det)) {
            throw new Exception("Error al actualizar detalle_caja: " . mysqli_error($vConexion));
        }

        // --- ¿Este movimiento tiene retiro asociado? ---
        $sqlRetiro = "
            SELECT r.idRetiro
              FROM detalle_caja dc
              INNER JOIN retiros r ON dc.idRetiro = r.idRetiro
             WHERE dc.idDetalleCaja = '$idDetalleCaja'
             LIMIT 1
        ";
        $rsRet = mysqli_query($vConexion, $sqlRetiro);
        if ($rsRet && ($rowRet = mysqli_fetch_assoc($rsRet))) {
            $idRetiro = $rowRet['idRetiro'];

            // --- UPDATE retiros (monto + tipo) ---
            $SQL_Ret = "
                UPDATE retiros
                   SET monto = '$monto',
                       idTipoMovimiento = '$idTipoMovimiento'
                 WHERE idRetiro = '$idRetiro'
            ";
            if (!mysqli_query($vConexion, $SQL_Ret)) {
                throw new Exception("Error al actualizar retiros: " . mysqli_error($vConexion));
            }

            // --- Denominación del NUEVO tipo (para decidir subtabla destino) ---
            $sqlNuevoMov = "SELECT LOWER(denominacion) AS denom FROM tipo_movimiento WHERE idTipoMovimiento = '$idTipoMovimiento' LIMIT 1";
            $rsNuevoMov  = mysqli_query($vConexion, $sqlNuevoMov);
            $denominacionNueva = '';
            if ($rsNuevoMov && ($mov = mysqli_fetch_assoc($rsNuevoMov))) {
                $denominacionNueva = $mov['denom'];
            }

            // --- Detectar subtabla ACTUAL (sin usar UNION * ) ---
            $subActual = null;
            $checkTabs = [
                'retiros_proveedores' => "SELECT 1 FROM retiros_proveedores WHERE idRetiro = '$idRetiro' LIMIT 1",
                'retiros_sueldos'     => "SELECT 1 FROM retiros_sueldos     WHERE idRetiro = '$idRetiro' LIMIT 1",
                'retiros_servicios'   => "SELECT 1 FROM retiros_servicios   WHERE idRetiro = '$idRetiro' LIMIT 1",
                'retiros_insumos'     => "SELECT 1 FROM retiros_insumos     WHERE idRetiro = '$idRetiro' LIMIT 1",
                'retiros_varios'      => "SELECT 1 FROM retiros_varios      WHERE idRetiro = '$idRetiro' LIMIT 1",
            ];
            foreach ($checkTabs as $tabla => $sql) {
                $rs = mysqli_query($vConexion, $sql);
                if ($rs && mysqli_num_rows($rs) > 0) { $subActual = $tabla; break; }
            }

            // --- Determinar subtabla DESTINO según la nueva denominación ---
            $destino = 'retiros_varios';
            if (strpos($denominacionNueva, 'proveedor') !== false) {
                $destino = 'retiros_proveedores';
            } elseif (strpos($denominacionNueva, 'sueldo') !== false) {
                $destino = 'retiros_sueldos';
            } elseif (strpos($denominacionNueva, 'servicio') !== false) {
                $destino = 'retiros_servicios';
            } elseif (strpos($denominacionNueva, 'insumo') !== false || strpos($denominacionNueva, 'material') !== false) {
                $destino = 'retiros_insumos';
            }

            // --- Si cambió la categoría, borrar de la subtabla anterior (si existe) ---
            if ($subActual && $subActual !== $destino) {
                $SQL_DelSub = "DELETE FROM $subActual WHERE idRetiro = '$idRetiro'";
                if (!mysqli_query($vConexion, $SQL_DelSub)) {
                    throw new Exception("Error al limpiar subtabla anterior ($subActual): " . mysqli_error($vConexion));
                }
                $subActual = null; // forzar INSERT en destino
            }

            // --- Armar datos destino desde POST (con defaults) ---
            $obsSQL = ($observaciones !== null ? "'$observaciones'" : "NULL");

            if ($destino === 'retiros_proveedores') {
                $idProveedor = !empty($_POST['proveedor']) ? (int)$_POST['proveedor'] : 1;

                if ($subActual === 'retiros_proveedores') {
                    $SQL = "UPDATE retiros_proveedores
                               SET idProveedor = '$idProveedor',
                                   detalle_proveedor = $obsSQL
                             WHERE idRetiro = '$idRetiro'";
                } else {
                    $SQL = "INSERT INTO retiros_proveedores (idRetiro, idProveedor, detalle_proveedor)
                            VALUES ('$idRetiro', '$idProveedor', $obsSQL)";
                }

            } elseif ($destino === 'retiros_sueldos') {
                $idUsuarioSueldo = !empty($_POST['usuarioSueldo']) ? (int)$_POST['usuarioSueldo'] : (int)$idUsuario;

                if ($subActual === 'retiros_sueldos') {
                    $SQL = "UPDATE retiros_sueldos
                               SET idUsuarioSueldo = '$idUsuarioSueldo',
                                   detalle_sueldo = $obsSQL
                             WHERE idRetiro = '$idRetiro'";
                } else {
                    $SQL = "INSERT INTO retiros_sueldos (idRetiro, idUsuarioSueldo, detalle_sueldo)
                            VALUES ('$idRetiro', '$idUsuarioSueldo', $obsSQL)";
                }

            } elseif ($destino === 'retiros_servicios') {
                $tipoServicio = !empty($_POST['servicio']) ? mysqli_real_escape_string($vConexion, $_POST['servicio']) : 'Servicio';

                if ($subActual === 'retiros_servicios') {
                    $SQL = "UPDATE retiros_servicios
                               SET tipo_servicio = '$tipoServicio',
                                   detalle_servicio = $obsSQL
                             WHERE idRetiro = '$idRetiro'";
                } else {
                    $SQL = "INSERT INTO retiros_servicios (idRetiro, tipo_servicio, detalle_servicio)
                            VALUES ('$idRetiro', '$tipoServicio', $obsSQL)";
                }

            } elseif ($destino === 'retiros_insumos') {
                $tipoInsumo = !empty($_POST['insumo']) ? mysqli_real_escape_string($vConexion, $_POST['insumo']) : 'Insumo';

                if ($subActual === 'retiros_insumos') {
                    $SQL = "UPDATE retiros_insumos
                               SET categoria = '$tipoInsumo',
                                   detalle_insumo = $obsSQL
                             WHERE idRetiro = '$idRetiro'";
                } else {
                    $SQL = "INSERT INTO retiros_insumos (idRetiro, categoria, detalle_insumo)
                            VALUES ('$idRetiro', '$tipoInsumo', $obsSQL)";
                }

            } else { // retiros_varios
                $categoriaVarios = !empty($_POST['categoriaVarios'])
                    ? mysqli_real_escape_string($vConexion, $_POST['categoriaVarios'])
                    : $denominacionNueva;

                if ($subActual === 'retiros_varios') {
                    $SQL = "UPDATE retiros_varios
                               SET categoria = '$categoriaVarios',
                                   detalle_vario = $obsSQL
                             WHERE idRetiro = '$idRetiro'";
                } else {
                    $SQL = "INSERT INTO retiros_varios (idRetiro, categoria, detalle_vario)
                            VALUES ('$idRetiro', '$categoriaVarios', $obsSQL)";
                }
            }

            if (!mysqli_query($vConexion, $SQL)) {
                throw new Exception("Error en subtabla ($destino): " . mysqli_error($vConexion));
            }
        }

        mysqli_commit($vConexion);
        return true;

    } catch (Throwable $e) {
        mysqli_rollback($vConexion);
        $_SESSION['Mensaje'] = "Error al modificar el movimiento: " . $e->getMessage();
        return false;
    }
}

function Validar_Venta() {
    $_SESSION['Mensaje'] = '';
    $_SESSION['Estilo'] = 'danger';

    if (empty($_POST['idTipoPago'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de pago. <br />';
    }
    if (empty($_POST['idTipoMovimiento'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de entrada. <br />';
    }
    
    if (empty($_POST['MontoReal'])) {
        $_SESSION['Mensaje'] .= 'Debes ingresar un monto válido. <br />';
    } else {
        $monto = (float)$_POST['MontoReal'];
        if ($monto <= 0) {
            $_SESSION['Mensaje'] .= 'El monto debe ser mayor a cero. <br />';
        }
    }

    if (isset($_POST['facturar']) && $_POST['facturar'] == 'on') {
        if (empty($_POST['numeroFactura'])) {
            $_SESSION['Mensaje'] .= 'Debes ingresar un número de factura. <br />';
        }
    }

    foreach ($_POST as $Id => $Valor) {
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    if (empty($_SESSION['Mensaje'])) {
        unset($_SESSION['Estilo']);
    }

    return $_SESSION['Mensaje'];
}

function Validar_Modificar_Venta() {
    $_SESSION['Mensaje'] = '';
    $_SESSION['Estilo'] = 'danger';

    // Validaciones básicas
    if (empty($_POST['idTipoPago'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de pago. <br />';
    }
    if (empty($_POST['idTipoMovimiento'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de movimiento. <br />';
    }
    
    if (empty($_POST['MontoReal']) || floatval($_POST['MontoReal']) <= 0) {
        $_SESSION['Mensaje'] .= 'Debes ingresar un monto válido mayor a cero. <br />';
    }
    
    // Validación específica para facturación
    if (isset($_POST['facturado']) && $_POST['facturado'] == 1) {
        if (empty($_POST['idTipoFactura'])) {
            $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de factura. <br />';
        }
        if (empty($_POST['numeroFactura'])) {
            $_SESSION['Mensaje'] .= 'Debes ingresar un número de factura. <br />';
        }
    }

    // Limpieza de datos
    foreach ($_POST as $Id => $Valor) {
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    if (empty($_SESSION['Mensaje'])) {
        unset($_SESSION['Estilo']);
    }
}

function Datos_Venta($vConexion, $vIdDetalleCaja) {
    // Traer el movimiento base de detalle_caja
    $SQL = "SELECT dc.*, tm.denominacion as tipo_movimiento, tm.es_salida 
            FROM detalle_caja dc
            INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
            WHERE dc.idDetalleCaja = $vIdDetalleCaja";
    
    $rs = mysqli_query($vConexion, $SQL);
    $data = mysqli_fetch_assoc($rs);

    if (empty($data)) {
        return array();
    }

    $resultado = array(
        'idDetalleCaja' => $data['idDetalleCaja'],
        'idCaja' => $data['idCaja'],
        'idRetiro' => $data['idRetiro'], // ← Este campo SÍ existe en detalle_caja
        'idTipoPago' => $data['idTipoPago'],
        'idTipoMovimiento' => $data['idTipoMovimiento'],
        'Monto' => $data['monto'],
        'observaciones' => $data['observaciones'],
        'facturado' => $data['facturado'],
        'idTipoFactura' => $data['idTipoFactura'],
        'numeroFactura' => $data['numeroFactura'],
        'es_salida' => $data['es_salida'],
        'tipo_movimiento' => strtolower($data['tipo_movimiento'])
    );

    // Si es salida y tiene un retiro asociado
    if (!empty($data['es_salida']) && !empty($data['idRetiro'])) {
        $idRetiro = $data['idRetiro'];
        $tipoMovimiento = strtolower($data['tipo_movimiento']);
        
        // Buscar en las tablas específicas de retiros
        if (strpos($tipoMovimiento, 'sueldo') !== false) {
            $sqlExtra = "SELECT * FROM retiros_sueldos WHERE idRetiro = $idRetiro";
            $rsExtra = mysqli_query($vConexion, $sqlExtra);
            if ($rowExtra = mysqli_fetch_assoc($rsExtra)) {
                $resultado['idUsuarioSueldo'] = $rowExtra['idUsuarioSueldo'];
                $resultado['detalle_sueldo'] = $rowExtra['detalle_sueldo'];
            }
        } 
        elseif (strpos($tipoMovimiento, 'proveedor') !== false) {
            $sqlExtra = "SELECT * FROM retiros_proveedores WHERE idRetiro = $idRetiro";
            $rsExtra = mysqli_query($vConexion, $sqlExtra);
            if ($rowExtra = mysqli_fetch_assoc($rsExtra)) {
                $resultado['idProveedor'] = $rowExtra['idProveedor'];
                $resultado['detalle_proveedor'] = $rowExtra['detalle_proveedor'];
            }
        }
        elseif (strpos($tipoMovimiento, 'servicio') !== false) {
            $sqlExtra = "SELECT * FROM retiros_servicios WHERE idRetiro = $idRetiro";
            $rsExtra = mysqli_query($vConexion, $sqlExtra);
            if ($rowExtra = mysqli_fetch_assoc($rsExtra)) {
                $resultado['tipo_servicio'] = $rowExtra['tipo_servicio'];
                $resultado['detalle_servicio'] = $rowExtra['detalle_servicio'];
            }
        }
        elseif (strpos($tipoMovimiento, 'insumo') !== false) {
            $sqlExtra = "SELECT * FROM retiros_insumos WHERE idRetiro = $idRetiro";
            $rsExtra = mysqli_query($vConexion, $sqlExtra);
            if ($rowExtra = mysqli_fetch_assoc($rsExtra)) {
                $resultado['categoria'] = $rowExtra['categoria'];
                $resultado['detalle_insumo'] = $rowExtra['detalle_insumo'];
            }
        }
        // Para retiros varios
        else {
            $sqlExtra = "SELECT * FROM retiros_varios WHERE idRetiro = $idRetiro";
            $rsExtra = mysqli_query($vConexion, $sqlExtra);
            if ($rowExtra = mysqli_fetch_assoc($rsExtra)) {
                $resultado['categoria'] = $rowExtra['categoria'];
                $resultado['detalle_vario'] = $rowExtra['detalle_vario'];
            }
        }
    }

    return $resultado;
}

function Obtener_Tipo_Movimiento($conexion, $idTipoMovimiento) {
    $idTipoMovimiento = intval($idTipoMovimiento);
    $sql = "SELECT denominacion, es_entrada, es_salida 
            FROM tipo_movimiento 
            WHERE idTipoMovimiento = $idTipoMovimiento AND idActivo = 1";
    $rs = mysqli_query($conexion, $sql);
    if ($rs && $row = mysqli_fetch_assoc($rs)) {
        return [
            'denominacion' => strtolower($row['denominacion']),
            'es_entrada' => !empty($row['es_entrada']),
            'es_salida' => !empty($row['es_salida'])
        ];
    }
    return null;
}

function InsertarMovimiento($vConexion) {
    $idCaja = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
    $idTipoPago = mysqli_real_escape_string($vConexion, $_POST['idTipoPago']);
    $idTipoMovimiento = mysqli_real_escape_string($vConexion, $_POST['idTipoMovimiento']);
    $idUsuario = isset($_SESSION['Usuario_Id']) ? mysqli_real_escape_string($vConexion, $_SESSION['Usuario_Id']) : null;
    
    $monto = 0;
    if (isset($_POST['MontoReal']) && is_numeric($_POST['MontoReal'])) {
        $monto = (float)$_POST['MontoReal'];
        $monto = number_format($monto, 2, '.', '');
    }

    $observaciones = !empty($_POST['Observaciones']) ? mysqli_real_escape_string($vConexion, $_POST['Observaciones']) : null;
    
    $facturar = isset($_POST['facturar']) && $_POST['facturar'] == 'on';
    $idTipoFactura = $facturar ? mysqli_real_escape_string($vConexion, $_POST['idTipoFactura']) : null;
    $numeroFactura = $facturar ? mysqli_real_escape_string($vConexion, $_POST['numeroFactura']) : null;

    if ($idUsuario === null) {
        die('<h4>Error: No se encontró un usuario en la sesión.</h4>');
    }

    // Primero insertamos el movimiento básico
    $SQL_Insert = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones)
                   VALUES ('$idCaja', '$idTipoPago', '$idTipoMovimiento', '$idUsuario', '$monto', " . 
                   ($observaciones !== null ? "'$observaciones'" : "NULL") . ")";

    if (!mysqli_query($vConexion, $SQL_Insert)) {
        die('<h4>Error al intentar insertar la venta: ' . mysqli_error($vConexion) . '</h4>');
    }

    // Obtenemos el ID del movimiento recién insertado
    $idDetalleCaja = mysqli_insert_id($vConexion);

    // Si se marcó para facturar, actualizamos con los datos de facturación
    if ($facturar && $idTipoFactura && $numeroFactura) {
        $SQL_Update = "UPDATE detalle_caja 
                      SET facturado = 1,
                          idTipoFactura = '$idTipoFactura',
                          numeroFactura = '$numeroFactura'
                      WHERE idDetalleCaja = $idDetalleCaja";
        
        if (!mysqli_query($vConexion, $SQL_Update)) {
            die('<h4>Error al asociar factura: ' . mysqli_error($vConexion) . '</h4>');
        }
    }

    return true;
}

function InsertarMovimientoRetiro($vConexion) {
    $idCaja = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
    $idTipoPago = mysqli_real_escape_string($vConexion, $_POST['idTipoPago']);
    $idTipoMovimiento = mysqli_real_escape_string($vConexion, $_POST['idTipoMovimiento']);
    $idUsuario = isset($_SESSION['Usuario_Id']) ? mysqli_real_escape_string($vConexion, $_SESSION['Usuario_Id']) : null;
    
    $monto = 0;
    if (isset($_POST['MontoReal']) && is_numeric($_POST['MontoReal'])) {
        $monto = (float)$_POST['MontoReal'];
        $monto = number_format($monto, 2, '.', '');
    }

    $observaciones = !empty($_POST['Observaciones']) ? mysqli_real_escape_string($vConexion, $_POST['Observaciones']) : null;
    
    $facturar = isset($_POST['facturar']) && $_POST['facturar'] == 'on';
    $idTipoFactura = $facturar ? mysqli_real_escape_string($vConexion, $_POST['idTipoFactura']) : null;
    $numeroFactura = $facturar ? mysqli_real_escape_string($vConexion, $_POST['numeroFactura']) : null;

    if ($idUsuario === null) {
        die('<h4>Error: No se encontró un usuario en la sesión.</h4>');
    }

    // 🔹 1. Insertar en detalle_caja
    $SQL_Insert = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones)
                   VALUES ('$idCaja', '$idTipoPago', '$idTipoMovimiento', '$idUsuario', '$monto', " . 
                   ($observaciones !== null ? "'$observaciones'" : "NULL") . ")";
    if (!mysqli_query($vConexion, $SQL_Insert)) {
        die('<h4>Error al intentar insertar el retiro: ' . mysqli_error($vConexion) . '</h4>');
    }
    $idDetalleCaja = mysqli_insert_id($vConexion);

    // 🔹 2. Si hay factura, actualizar
    if ($facturar && $idTipoFactura && $numeroFactura) {
        $SQL_Update = "UPDATE detalle_caja 
                      SET facturado = 1,
                          idTipoFactura = '$idTipoFactura',
                          numeroFactura = '$numeroFactura'
                      WHERE idDetalleCaja = $idDetalleCaja";
        if (!mysqli_query($vConexion, $SQL_Update)) {
            die('<h4>Error al asociar factura: ' . mysqli_error($vConexion) . '</h4>');
        }
    }

    // 🔹 3. Insertar en retiros
    $SQL_Retiro = "INSERT INTO retiros (fecha, monto, idUsuario, idTipoMovimiento, idTipoPago, facturado, idTipoFactura, numeroFactura, idActivo)
                   VALUES (NOW(), '$monto', '$idUsuario', '$idTipoMovimiento', '$idTipoPago',
                           " . ($facturar ? 1 : 0) . ",
                           " . ($idTipoFactura ? "'$idTipoFactura'" : "NULL") . ",
                           " . ($numeroFactura ? "'$numeroFactura'" : "NULL") . ", 1)";
    if (!mysqli_query($vConexion, $SQL_Retiro)) {
        die('<h4>Error al intentar insertar en retiros: ' . mysqli_error($vConexion) . '</h4>');
    }
    $idRetiro = mysqli_insert_id($vConexion);

    // 🔹 4. Vincular detalle_caja con retiro
    $SQL_UpdateDC = "UPDATE detalle_caja SET idRetiro = '$idRetiro' WHERE idDetalleCaja = '$idDetalleCaja'";
    if (!mysqli_query($vConexion, $SQL_UpdateDC)) {
        die('<h4>Error al vincular detalle con retiro: ' . mysqli_error($vConexion) . '</h4>');
    }

    // 🔹 5. Insertar en subtabla correspondiente
    $nombreMovimiento = strtolower($_POST['nombreMovimiento']); // viene de un hidden input
    if (strpos($nombreMovimiento, 'sueldo') !== false && !empty($_POST['usuarioSueldo'])) {
        mysqli_query($vConexion, "INSERT INTO retiros_sueldos (idRetiro, idUsuarioSueldo, detalle_sueldo)
                                  VALUES ($idRetiro, " . intval($_POST['usuarioSueldo']) . ", " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    } elseif (strpos($nombreMovimiento, 'proveedor') !== false && !empty($_POST['proveedor'])) {
        mysqli_query($vConexion, "INSERT INTO retiros_proveedores (idRetiro, idProveedor, detalle_proveedor)
                                  VALUES ($idRetiro, " . intval($_POST['proveedor']) . ", " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    } elseif (strpos($nombreMovimiento, 'servicio') !== false) {
        mysqli_query($vConexion, "INSERT INTO retiros_servicios (idRetiro, tipo_servicio, detalle_servicio)
                                  VALUES ($idRetiro, 'Servicio', " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    } elseif (strpos($nombreMovimiento, 'insumo') !== false) {
        mysqli_query($vConexion, "INSERT INTO retiros_insumos (idRetiro, categoria, detalle_insumo)
                                  VALUES ($idRetiro, 'Insumo', " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    } elseif ($nombreMovimiento == 'caja fuerte') {
        mysqli_query($vConexion, "INSERT INTO retiros_varios (idRetiro, categoria, detalle_vario)
                                  VALUES ($idRetiro, 'Caja Fuerte', " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    } elseif ($nombreMovimiento == 'diferencia de caja') {
        mysqli_query($vConexion, "INSERT INTO retiros_varios (idRetiro, categoria, detalle_vario)
                                  VALUES ($idRetiro, 'Diferencia de Caja', " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    } else {
        mysqli_query($vConexion, "INSERT INTO retiros_varios (idRetiro, categoria, detalle_vario)
                                  VALUES ($idRetiro, 'Varios', " . ($observaciones ? "'$observaciones'" : "NULL") . ")");
    }

    return true;
}

function ColorDeFilaCaja($idTipoMovimiento) {
    // Conexión a la base de datos (ajusta según tu contexto)
    $conexion = ConexionBD(); // O usa $GLOBALS['MiConexion'] si ya está abierta

    $query = "SELECT es_entrada, es_salida FROM tipo_movimiento WHERE idTipoMovimiento = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $idTipoMovimiento);
    $stmt->execute();
    $stmt->bind_result($es_entrada, $es_salida);
    $stmt->fetch();
    $stmt->close();

    $Title = '';
    $Color = '';

    if ($es_entrada) {
        $Title = 'Entrada';
        $Color = 'table-success';
    } else if ($es_salida) {
        $Title = 'Salida';
        $Color = 'table-danger';
    }
    // Si ambos son false, no asigna color ni título

    return [$Title, $Color];
}

function ColorDeFilaPedidoTrabajo($vEstado) {
    $Title='';
    $Color=''; 

    if ($vEstado == '5'){
    //Estado listo para retirar
        $Title='Cuenta Corriente';
        $Color='table-ctacte'; 
    } else if ($vEstado == '4'){
    //Estado entregado
        $Title='Entregado';
        $Color='table-entregado'; 
    } else if ($vEstado == '3'){
    //Estado listo para retirar
        $Title='Listo';
        $Color='table-listo'; 
    } else if ($vEstado == '2'){
    //Estado en proceso
        $Title='En proceso';
        $Color='table-proceso'; 
    } else if ($vEstado == '1'){
    //Estado pendiente
    $Title='Pendiente';
    $Color='table-pendiente'; 
    }      
    
    return [$Title, $Color];

}

function ColorDeFilaTrabajo($vEstado) {
    $Title='';
    $Color=''; 

    switch ($vEstado) {
        case '1':
            $Title = 'Pendiente';
            $Color = 'table-pendiente';
            break;
        case '2':
            $Title = 'Diseño Empezado';
            $Color = 'table-proceso';
            break;
        case '3':
            $Title = 'Muestra Enviada';
            $Color = 'table-proceso';
            break;
        case '4':
            $Title = 'En Taller';
            $Color = 'table-proceso';
            break;
        case '5':
            $Title = 'Enviado';        
            $Color = 'table-proceso';
            break;
        case '6':
            $Title = 'Listo';        
            $Color = 'table-listo';
            break;
        case '7':
            $Title = 'Entregado';     
            $Color = 'table-entregado';
            break;
        case '8':
            $Title = 'Cuenta Corriente';     
            $Color = 'table-ctacte';
            break;
        default:
            $Title = 'Error';
            $Color = '';
            break;
    }
    return [$Title, $Color];

}

function Datos_Estados_Trabajo($vConexion) {
    $estados = array();
    
    $SQL = "SELECT idEstado, denominacion FROM estado_trabajo ORDER BY idEstado";
    $rs = mysqli_query($vConexion, $SQL);
    
    if (!$rs) {
        die("Error en la consulta: " . mysqli_error($vConexion));
    }
    
    while ($data = mysqli_fetch_assoc($rs)) {
        $estados[] = $data; // Agrega todos los estados al array
    }
    
    mysqli_free_result($rs);
    return $estados;
}

function Datos_Trabajos($vConexion) {
    $trabajos = array();
    
    // Consulta corregida: WHERE antes de ORDER BY
    $SQL = "SELECT idTipoTrabajo, denominacion FROM tipo_trabajo WHERE idActivo = 1 ORDER BY denominacion";
    
    $rs = mysqli_query($vConexion, $SQL);
    
    if (!$rs) {
        die("Error en la consulta: " . mysqli_error($vConexion));
    }
    
    while ($data = mysqli_fetch_assoc($rs)) {
        $trabajos[] = $data;
    }
    
    mysqli_free_result($rs);
    return $trabajos;
}

function Listar_Pedidos_Trabajos($vConexion) {

    $Listado = array();

    $SQL = "SELECT 
                C.nombre, 
                C.apellido, 
                PT.idPedidoTrabajos, 
                PT.fecha, 
                PT.senia, 
                ET.idEstado, 
                US.usuario, 
                ET.denominacion AS estado_nombre,
                COALESCE(SUM(DT.precio), 0) AS precio_total,  -- Sumamos los precios de detalle_trabajos
                COUNT(DT.idDetalleTrabajo) AS cantidad_trabajos  -- Contamos los trabajos asociados
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_trabajo ET ON PT.idEstado = ET.idEstado
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            LEFT JOIN detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos  -- JOIN con la tabla correcta
            WHERE PT.idActivo = 1
            GROUP BY PT.idPedidoTrabajos, C.nombre, C.apellido, PT.fecha, PT.senia, ET.idEstado, US.usuario, ET.denominacion
            ORDER BY PT.idPedidoTrabajos DESC";

    $rs = mysqli_query($vConexion, $SQL);

    if (!$rs) {
        die("Error en la consulta: " . mysqli_error($vConexion));
    }

    $i = 0;
    while ($data = mysqli_fetch_assoc($rs)) {
        $Listado[$i]['ID'] = $data['idPedidoTrabajos'];
        $Listado[$i]['CLIENTE_N'] = $data['nombre'];
        $Listado[$i]['CLIENTE_A'] = $data['apellido'];
        $Listado[$i]['FECHA'] = $data['fecha'];
        $Listado[$i]['PRECIO'] = $data['precio_total'];  // Precio total sumado
        $Listado[$i]['SEÑA'] = $data['senia'];
        $Listado[$i]['ESTADO'] = $data['idEstado'];
        $Listado[$i]['USUARIO'] = $data['usuario'];
        $Listado[$i]['ESTADO_NOMBRE'] = $data['estado_nombre'];
        $Listado[$i]['CANTIDAD_TRABAJOS'] = $data['cantidad_trabajos'];  // N° de trabajos
        $i++;
    }

    return $Listado;
}

function Listar_Pedidos_Trabajo_Parametro($vConexion, $criterio, $parametro) {
    
    $Listado = array();
    $whereClause = "WHERE PT.idActivo = 1"; // Filtro base para todos los casos
    
    switch ($criterio) {
        case 'Fecha':
            $whereClause .= " AND PT.fecha LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
        case 'Id':
            $whereClause = "WHERE PT.idPedidoTrabajos LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
        case 'Estado':
            $whereClause .= " AND ET.denominacion LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
        case 'Cliente':
            $parametro = strtolower($parametro);
            $nombreApellido = explode(' ', $parametro);
            if (count($nombreApellido) >= 2) {
                $whereClause .= " AND (
                    (LOWER(C.nombre) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[0])."%' 
                    AND LOWER(C.apellido) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[1])."%') 
                    OR 
                    (LOWER(C.nombre) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[1])."%' 
                    AND LOWER(C.apellido) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[0])."%')
                )";
            } else {
                $whereClause .= " AND (
                    LOWER(C.nombre) LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%' 
                    OR LOWER(C.apellido) LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'
                )";
            }
            break;
        case 'Telefono':
            $whereClause .= " AND C.telefono LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
    }

    $SQL = "SELECT 
                C.nombre, 
                C.apellido,
                C.telefono,
                PT.idPedidoTrabajos, 
                PT.fecha, 
                PT.senia, 
                ET.idEstado, 
                US.usuario, 
                ET.denominacion AS estado_nombre,
                COALESCE(SUM(DT.precio), 0) AS precio_total,
                COUNT(DT.idDetalleTrabajo) AS cantidad_trabajos
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_trabajo ET ON PT.idEstado = ET.idEstado
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            LEFT JOIN detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos
            $whereClause
            GROUP BY PT.idPedidoTrabajos, C.nombre, C.apellido, C.telefono, PT.fecha, PT.senia, ET.idEstado, US.usuario, ET.denominacion
            ORDER BY PT.idPedidoTrabajos DESC";

    $rs = mysqli_query($vConexion, $SQL);

    if (!$rs) {
        error_log("Error en la consulta SQL: " . mysqli_error($vConexion));
        die("Ocurrió un error al obtener los datos. Por favor, intente más tarde.");
    }

    $i = 0;
    while ($data = mysqli_fetch_assoc($rs)) {
        $Listado[$i]['ID'] = $data['idPedidoTrabajos'];
        $Listado[$i]['CLIENTE_N'] = $data['nombre'];
        $Listado[$i]['CLIENTE_A'] = $data['apellido'];
        $Listado[$i]['TELEFONO'] = $data['telefono'];
        $Listado[$i]['FECHA'] = $data['fecha'];
        $Listado[$i]['PRECIO'] = $data['precio_total'];
        $Listado[$i]['SEÑA'] = $data['senia'];
        $Listado[$i]['ESTADO'] = $data['idEstado'];
        $Listado[$i]['USUARIO'] = $data['usuario'];
        $Listado[$i]['ESTADO_NOMBRE'] = $data['estado_nombre'];
        $Listado[$i]['CANTIDAD_TRABAJOS'] = $data['cantidad_trabajos'];
        $i++;
    }

    return $Listado;
}

function Anular_Pedidos_Trabajo($vConexion, $vIdConsulta) {

    //soy admin 
        $SQL_MiConsulta="SELECT idPedidoTrabajos FROM pedido_trabajos  
                        WHERE idPedidoTrabajos = $vIdConsulta ";
   
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($vIdConsulta) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "UPDATE pedido_trabajos SET idActivo = 2 WHERE idPedidoTrabajos = $vIdConsulta");
        
        return true;

    }else {
        return false;
    }
    
}

function Validar_Pedido_Trabajo() {
    $_SESSION['Mensaje'] = '';
    
    if (empty($_POST['Cliente'])) {
        $_SESSION['Mensaje'] .= "Debe seleccionar un cliente.<br>";
    }
    
    if (empty($_POST['Fecha'])) {
        $_SESSION['Mensaje'] .= "Debe ingresar una fecha.<br>";
    }
    
    if (!is_numeric($_POST['PrecioTotal']) || $_POST['PrecioTotal'] <= 0) {
        $_SESSION['Mensaje'] .= "El precio total debe ser un número positivo.<br>";
    }
    
    if (!is_numeric($_POST['Senia']) || $_POST['Senia'] < 0) {
        $_SESSION['Mensaje'] .= "La seña debe ser un número positivo o cero.<br>";
    }
    
    if (empty($_POST['Estado'])) {
        $_SESSION['Mensaje'] .= "Debe seleccionar un estado.<br>";
    }
}

//------------------------------------------------------MODIFICAR PEDIDO DE TRABAJO------------------------------------------------------

function Datos_Pedido_Trabajo($conexion, $idPedido) {
    
    // Validar y sanear el ID del pedido para seguridad
    $idPedidoSeguro = intval($idPedido);

    // Consulta SQL modificada para calcular el precio total
    $sql = "SELECT 
                PT.idPedidoTrabajos,
                PT.fecha,
                PT.senia,
                C.nombre AS CLIENTE,
                C.apellido AS CLIENTE_A,
                C.telefono AS TELEFONO,
                E.denominacion AS ESTADO,
                E.idEstado AS ESTADO_ID,
                C.idCliente,
                -- Se calcula el precio total sumando los detalles del pedido
                COALESCE(SUM(DT.precio), 0) AS precioTotalCalculado
            FROM 
                pedido_trabajos PT
            INNER JOIN 
                clientes C ON PT.idCliente = C.idCliente
            INNER JOIN 
                estado_trabajo E ON PT.idEstado = E.idEstado
            LEFT JOIN 
                -- Se une con detalle_trabajos para poder sumar los precios
                detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos
            WHERE 
                PT.idPedidoTrabajos = $idPedidoSeguro
            GROUP BY
                -- Agrupamos para que SUM() funcione sobre un único pedido
                PT.idPedidoTrabajos,
                PT.fecha,
                PT.senia,
                C.nombre,
                C.apellido,
                C.telefono,
                E.denominacion,
                E.idEstado,
                C.idCliente";

    $rs = mysqli_query($conexion, $sql);
    
    if (!$rs || mysqli_num_rows($rs) == 0) {
        // Si no hay resultado, devuelve null o un array vacío para evitar errores
        return null;
    }

    $data = mysqli_fetch_assoc($rs);

    // Retornamos el array con el PRECIO_TOTAL usando el valor calculado
    // Los nombres de las claves se mantienen para que el código del PDF no se rompa.
    return array(
        'ID' => $data['idPedidoTrabajos'],
        'FECHA' => $data['fecha'],
        'PRECIO_TOTAL' => $data['precioTotalCalculado'], // ¡Usamos el valor calculado aquí!
        'SENIA' => $data['senia'],
        'CLIENTE_ID' => $data['idCliente'],
        'CLIENTE' => $data['CLIENTE'],
        'CLIENTE_A' => $data['CLIENTE_A'],
        'ESTADO' => $data['ESTADO'],
        'ESTADO_ID' => $data['ESTADO_ID'],
        'TELEFONO' => $data['TELEFONO']
    );
}

function Detalles_Pedido_Trabajo($conexion, $idPedido) {
    $sql = "SELECT 
                DT.idDetalleTrabajo,
                TT.denominacion AS TRABAJO,
                P.nombre AS PROVEEDOR,
                DT.descripcion AS DESCRIPCION,
                DT.fechaEntrega AS FECHA_ENTREGA,
                DT.horaEntrega AS HORA_ENTREGA,
                DT.precio AS PRECIO,
                ET.denominacion AS ESTADO,
                ET.idEstado AS ESTADO_ID,
                DT.facturado AS FACTURADO,              -- AÑADIDO
                DT.idTipoFactura AS ID_TIPO_FACTURA,    -- AÑADIDO
                DT.numeroFactura AS NUMERO_FACTURA,     -- AÑADIDO
                TF.denominacion AS TIPO_FACTURA         -- AÑADIDO (opcional)
            FROM detalle_trabajos DT
            INNER JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
            INNER JOIN estado_trabajo ET ON DT.idEstadoTrabajo = ET.idEstado
            INNER JOIN proveedores P ON DT.idProveedor = P.idProveedor
            LEFT JOIN tipo_factura TF ON DT.idTipoFactura = TF.idTipoFactura  -- AÑADIDO
            WHERE DT.id_pedido_trabajos = " . intval($idPedido) . " AND DT.idActivo = 1";
    
    $rs = mysqli_query($conexion, $sql);
    
    if (!$rs) {
        error_log("Error en consulta Detalles_Pedido_Trabajo: " . mysqli_error($conexion));
        return array();
    }

    $detalles = array();
    while ($data = mysqli_fetch_assoc($rs)) {
        $detalles[] = array(
            'TRABAJO' => $data['TRABAJO'],
            'ID_DETALLE' => $data['idDetalleTrabajo'],
            'DESCRIPCION' => $data['DESCRIPCION'],
            'FECHA_ENTREGA' => $data['FECHA_ENTREGA'],
            'HORA_ENTREGA' => $data['HORA_ENTREGA'],
            'PRECIO' => $data['PRECIO'],
            'PROVEEDOR' => $data['PROVEEDOR'],
            'ESTADO_ID' => $data['ESTADO_ID'],
            'ESTADO' => $data['ESTADO'],
            'FACTURADO' => $data['FACTURADO'],              // AÑADIDO
            'ID_TIPO_FACTURA' => $data['ID_TIPO_FACTURA'],  // AÑADIDO
            'NUMERO_FACTURA' => $data['NUMERO_FACTURA'],    // AÑADIDO
            'TIPO_FACTURA' => $data['TIPO_FACTURA']         // AÑADIDO
        );
    }
    return $detalles;
}

function Actualizar_Estado_Detalle($conexion, $idDetalle, $nuevoEstado) {
    $sql = "UPDATE detalle_trabajos 
            SET idEstadoTrabajo = " . intval($nuevoEstado) . "
            WHERE idDetalleTrabajo = " . intval($idDetalle);
    
    $resultado = mysqli_query($conexion, $sql);
    
    if (!$resultado) {
        error_log("Error en Actualizar_Estado_Detalle: " . mysqli_error($conexion));
        return false;
    }
    
    return true;
}

function Marcar_Pedido_Como_Pagado($conexion, $idPedido) {
    // 1. Calcular el precio total sumando los detalles
    $sql = "SELECT SUM(precio) AS precioTotal 
            FROM detalle_trabajos 
            WHERE id_pedido_trabajos = " . intval($idPedido) . "
            AND idActivo = 1";
    
    $resultado = mysqli_query($conexion, $sql);
    
    if (!$resultado) {
        error_log("Error al calcular precio total: " . mysqli_error($conexion));
        return false;
    }
    
    $data = mysqli_fetch_assoc($resultado);
    $precioTotal = $data['precioTotal'] ?? 0;
    
    // 2. Actualizar la seña al precio total
    $sqlUpdate = "UPDATE pedido_trabajos 
                 SET senia = " . floatval($precioTotal) . "
                 WHERE idPedidoTrabajos = " . intval($idPedido);
    
    if (!mysqli_query($conexion, $sqlUpdate)) {
        error_log("Error al actualizar seña: " . mysqli_error($conexion));
        return false;
    }
    
    return true;
}

function Modificar_Senia_Pedido($conexion, $idPedido, $montoOperacion, $idTipoPago = null, $esReduccion = false) {
    // Validaciones básicas
    if ($idPedido <= 0) {
        error_log("Error: ID de pedido inválido");
        return ['success' => false, 'error' => 'ID de pedido inválido'];
    }
    
    if ($montoOperacion <= 0) {
        error_log("Error: Monto de operación inválido");
        return ['success' => false, 'error' => 'Monto de operación inválido'];
    }

    try {
        $conexion->begin_transaction();

        // 1. Obtener la seña actual
        $query = "SELECT senia FROM pedido_trabajos WHERE idPedidoTrabajos = ? FOR UPDATE";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param('i', $idPedido);
        $stmt->execute();
        $stmt->bind_result($seniaActual);
        $stmt->fetch();
        $stmt->close();

        // 2. Calcular nueva seña
        $nuevaSenia = $esReduccion ? ($seniaActual - $montoOperacion) : ($seniaActual + $montoOperacion);
        
        if ($nuevaSenia < 0) {
            throw new Exception("No se puede tener seña negativa");
        }

        // 3. Actualizar la seña
        $query = "UPDATE pedido_trabajos SET senia = ? WHERE idPedidoTrabajos = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param('di', $nuevaSenia, $idPedido);
        $stmt->execute();
        $stmt->close();

        // 4. Registrar movimiento en caja y retiros (solo para reducciones)
        $idUsuario = $_SESSION['Usuario_Id'] ?? 0;
        $idCaja = $_SESSION['Id_Caja'] ?? 0;
        
        if ($idUsuario <= 0 || $idCaja <= 0) {
            throw new Exception("Datos de sesión inválidos para registrar movimiento");
        }
        
        if ($idTipoPago) {
            $observaciones = $esReduccion ? 
                "Devolución de seña del pedido #$idPedido" : 
                "Pago de seña del pedido #$idPedido";
            
            // Tipo de movimiento (3=Entrada, 13=Salida)
            $idTipoMovimiento = $esReduccion ? 13 : 3;
            
            if ($esReduccion) {
                // 🔹 PARA REDUCCIONES (QUITAR SEÑA) - Crear retiro completo
                
                // 4.1. Insertar en retiros
                $queryRetiro = "INSERT INTO retiros (fecha, monto, idUsuario, idTipoMovimiento, idTipoPago, facturado, idActivo)
                               VALUES (NOW(), ?, ?, ?, ?, 0, 1)";
                $stmt = $conexion->prepare($queryRetiro);
                $stmt->bind_param('diii', $montoOperacion, $idUsuario, $idTipoMovimiento, $idTipoPago);
                $stmt->execute();
                $idRetiro = $conexion->insert_id;
                $stmt->close();
                
                // 4.2. Insertar en retiros_varios (categoría "Devolución de Seña")
                $queryRetiroVarios = "INSERT INTO retiros_varios (idRetiro, categoria, detalle_vario)
                                     VALUES (?, 'Devolución de Seña', ?)";
                $stmt = $conexion->prepare($queryRetiroVarios);
                $stmt->bind_param('is', $idRetiro, $observaciones);
                $stmt->execute();
                $stmt->close();
                
                // 4.3. Insertar en detalle_caja vinculado al retiro
                $queryDetalleCaja = "INSERT INTO detalle_caja (
                    idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones, idRetiro
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($queryDetalleCaja);
                $stmt->bind_param('iiiidsi', $idCaja, $idTipoPago, $idTipoMovimiento, $idUsuario, $montoOperacion, $observaciones, $idRetiro);
                $stmt->execute();
                $stmt->close();
                
            } else {
                // 🔹 PARA AUMENTOS (AGREGAR SEÑA) - Solo detalle_caja normal
                $queryDetalleCaja = "INSERT INTO detalle_caja (
                    idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($queryDetalleCaja);
                $stmt->bind_param('iiiids', $idCaja, $idTipoPago, $idTipoMovimiento, $idUsuario, $montoOperacion, $observaciones);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conexion->commit();
        return ['success' => true, 'nueva_senia' => $nuevaSenia];
        
    } catch (Exception $e) {
        $conexion->rollback();
        error_log("Error en Modificar_Senia_Pedido: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function Obtener_Detalle_Trabajo($conexion, $idDetalle) {
    // Validar parámetros
    if ($idDetalle <= 0) {
        error_log("ID de detalle inválido: $idDetalle");
        return false;
    }

    // Consulta para obtener los datos del detalle con información del pedido
    $query = "SELECT 
                dt.idDetalleTrabajo, 
                dt.id_pedido_trabajos, 
                dt.idTrabajo, 
                dt.descripcion AS descripcion_trabajo, 
                dt.precio, 
                DATE_FORMAT(dt.fechaEntrega, '%Y-%m-%d') AS fecha_entrega,
                TIME_FORMAT(dt.horaEntrega, '%H:%i') AS hora_entrega,
                dt.idProveedor, 
                dt.idEstadoTrabajo, 
                dt.idActivo,
                dt.facturado,                    -- AÑADIDO
                dt.idTipoFactura,                -- AÑADIDO
                dt.numeroFactura,                -- AÑADIDO
                pt.idPedidoTrabajos,
                pt.idCliente,
                DATE_FORMAT(pt.fecha, '%Y-%m-%d') AS fecha_pedido,
                pt.senia,
                pt.idUsuario,
                pt.idEstado,
                pt.idActivo AS pedido_activo
              FROM detalle_trabajos dt
              JOIN pedido_trabajos pt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos
              WHERE dt.idDetalleTrabajo = ?";

    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        error_log("Error al preparar la consulta: " . $conexion->error);
        return false;
    }

    $stmt->bind_param('i', $idDetalle);
    
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $detalle = $resultado->fetch_assoc();
    $stmt->close();

    return $detalle;
}

function Procesar_Detalle_Trabajo($conexion, $accion, $datos) {
    // Validar parámetros básicos
    if (!in_array($accion, ['agregar', 'editar', 'eliminar'])) {
        error_log("Acción no válida: $accion");
        return false;
    }

    try {
        switch ($accion) {
            case 'editar':
                // CONSULTA ACTUALIZADA PARA INCLUIR CAMPOS DE FACTURACIÓN
                $query = "UPDATE detalle_trabajos SET 
                         idTrabajo = ?, 
                         precio = ?, 
                         fechaEntrega = ?, 
                         horaEntrega = ?, 
                         descripcion = ?,
                         idProveedor = ?,
                         idEstadoTrabajo = ?,
                         facturado = ?,           -- NUEVO CAMPO
                         idTipoFactura = ?,       -- NUEVO CAMPO
                         numeroFactura = ?        -- NUEVO CAMPO
                         WHERE idDetalleTrabajo = ?";
                
                $stmt = $conexion->prepare($query);
                if (!$stmt) {
                    error_log("Error al preparar la consulta: " . $conexion->error);
                    return false;
                }
                
                // Manejar valores NULL para campos de facturación
                $idTipoFactura = !empty($datos['idTipoFactura']) ? $datos['idTipoFactura'] : null;
                $numeroFactura = !empty($datos['numeroFactura']) ? $datos['numeroFactura'] : null;
                
                $stmt->bind_param('idsssiiissi', 
                    $datos['idTrabajo'], 
                    $datos['precio'], 
                    $datos['fechaEntrega'],
                    $datos['horaEntrega'],
                    $datos['descripcion'],
                    $datos['idProveedor'],
                    $datos['idEstadoTrabajo'],
                    $datos['facturado'],        // NUEVO
                    $idTipoFactura,             // NUEVO
                    $numeroFactura,             // NUEVO
                    $datos['idDetalle']
                );
                break;
                
            case 'agregar':
                // CONSULTA ACTUALIZADA PARA INCLUIR CAMPOS DE FACTURACIÓN
                $query = "INSERT INTO detalle_trabajos (
                    id_pedido_trabajos, 
                    idTrabajo, 
                    precio, 
                    fechaEntrega, 
                    horaEntrega, 
                    descripcion,
                    idProveedor,
                    idEstadoTrabajo,
                    facturado,           -- NUEVO CAMPO
                    idTipoFactura,       -- NUEVO CAMPO
                    numeroFactura        -- NUEVO CAMPO
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conexion->prepare($query);
                if (!$stmt) {
                    error_log("Error al preparar la consulta: " . $conexion->error);
                    return false;
                }
                
                // Manejar valores NULL para campos de facturación
                $idTipoFactura = !empty($datos['idTipoFactura']) ? $datos['idTipoFactura'] : null;
                $numeroFactura = !empty($datos['numeroFactura']) ? $datos['numeroFactura'] : null;
                
                $stmt->bind_param('iidsssiiiss',
                    $datos['id_pedido_trabajos'],
                    $datos['idTrabajo'],
                    $datos['precio'],
                    $datos['fechaEntrega'],
                    $datos['horaEntrega'],
                    $datos['descripcion'],
                    $datos['idProveedor'],
                    $datos['idEstadoTrabajo'],
                    $datos['facturado'],        // NUEVO
                    $idTipoFactura,             // NUEVO
                    $numeroFactura              // NUEVO
                );
                break;
                
            case 'eliminar':
                $query = "DELETE FROM detalle_trabajos WHERE idDetalleTrabajo = ?";
                $stmt = $conexion->prepare($query);
                if (!$stmt) {
                    error_log("Error al preparar la consulta: " . $conexion->error);
                    return false;
                }
                
                $stmt->bind_param('i', $datos['idDetalle']);
                break;
                
            default:
                return false;
        }

        $resultado = $stmt->execute();
        if (!$resultado) {
            error_log("Error al ejecutar la consulta: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $filasAfectadas = $stmt->affected_rows;
        $stmt->close();
        
        // Llamar a ActualizarEstadoPedido después de ejecutar la consulta
        if ($filasAfectadas > 0 && isset($datos['id_pedido_trabajos'])) {
            ActualizarEstadoPedido($conexion, $datos['id_pedido_trabajos']);
        }
        
        return $filasAfectadas > 0;
        
    } catch (Exception $e) {
        error_log("Excepción al procesar detalle: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        return false;
    }
}

function ActualizarEstadoPedido($conexion, $idPedido) {
   
    // 1. Obtener estados de los detalles
    $sql = "SELECT idEstadoTrabajo FROM detalle_trabajos 
            WHERE id_pedido_trabajos = " . intval($idPedido) . " 
            AND idActivo = 1";
    
    $resultado = mysqli_query($conexion, $sql);
    
    if (!$resultado) {
        $error = mysqli_error($conexion);
        error_log("ERROR en consulta SQL: " . $error);
        return false;
    }

    $estados = array();
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $estados[] = $fila['idEstadoTrabajo'];
    }
    
    // 2. Determinar el nuevo estado
    $nuevoEstado = null;
    $reglaAplicada = '';

    // Regla 1: Si algún detalle tiene estado 1 (pendiente)
    if (in_array(1, $estados)) {
        $nuevoEstado = 1;
        $reglaAplicada = "Regla 1 - Hay detalles pendientes (estado 1)";
    }
    // Regla 2: Si algún detalle tiene estado 2, 3, 4 o 5 (en proceso)
    elseif (array_intersect([2, 3, 4, 5], $estados)) {
        $nuevoEstado = 2;
        $reglaAplicada = "Regla 2 - Hay detalles en proceso (estados 2-5)";
    }
    // Regla 3: Si algún detalle tiene estado 6 (listo para entregar)
    elseif (in_array(6, $estados)) {
        $nuevoEstado = 3;
        $reglaAplicada = "Regla 3 - Hay detalles listos para entregar (estado 6)";
    }
    // Regla 4: Si algún detalle tiene estado 8 (cta cte)
    elseif (in_array(8, $estados)) {
        $nuevoEstado = 5;
        $reglaAplicada = "Regla 4 - Hay detalles cta (estado 8)";
    }
    // Regla 5: Si algún detalle tiene estado 7 (entregado)
    elseif (in_array(7, $estados)) {
        $nuevoEstado = 4;
        $reglaAplicada = "Regla 5 - Hay detalles entregados (estado 7)";
    }
    // Si no cumple ninguna regla (no debería pasar si hay detalles)
    else {
        $nuevoEstado = 0;
        $reglaAplicada = "Regla por defecto - No se cumplieron otras reglas";
    }
    
    // 3. Actualizar el estado del pedido
    $sqlUpdate = "UPDATE pedido_trabajos 
                 SET idEstado = " . intval($nuevoEstado) . "
                 WHERE idPedidoTrabajos = " . intval($idPedido);
    
    $resultadoUpdate = mysqli_query($conexion, $sqlUpdate);
    
    if (!$resultadoUpdate) {
        $error = mysqli_error($conexion);
        return false;
    }

    $filasAfectadas = mysqli_affected_rows($conexion);
    
    return true;
}

function Listar_Tipos_Movimiento_Entrada($conexion) {
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_entrada = 1 AND idActivo = 1";
    $resultado = mysqli_query($conexion, $sql);

    $tiposMovimiento = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tiposMovimiento[] = $fila;
        }
    }
    return $tiposMovimiento;
}

function Listar_Tipos_Movimiento_Salida($conexion) {
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_salida = 1 AND idActivo = 1";
    $resultado = mysqli_query($conexion, $sql);

    $tiposMovimiento = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tiposMovimiento[] = $fila;
        }
    }
    return $tiposMovimiento;
}

function Listar_Tipos_Movimiento_Contable($conexion) {
    $sql = "SELECT idTipoMovimiento, denominacion FROM tipo_movimiento WHERE es_salida = 0 AND es_entrada = 0 AND idActivo = 1";
    $resultado = mysqli_query($conexion, $sql);

    $tiposMovimiento = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tiposMovimiento[] = $fila;
        }
    }
    return $tiposMovimiento;
}

function InsertarTipoMovimientoEntrada($vConexion) {
    $denominacion = trim(strip_tags($_POST['Denominacion'] ?? ''));
    $sql = "INSERT INTO tipo_movimiento (denominacion, es_entrada, es_salida, idActivo) VALUES (?, 1, 0, 1)";
    $stmt = $vConexion->prepare($sql);
    $stmt->bind_param("s", $denominacion);
    if (!$stmt->execute()) {
        return false;
    }
    $stmt->close();
    return true;
}

function InsertarTipoMovimientoSalida($vConexion) {
    $denominacion = trim(strip_tags($_POST['Denominacion'] ?? ''));
    $sql = "INSERT INTO tipo_movimiento (denominacion, es_entrada, es_salida, idActivo) VALUES (?, 0, 1, 1)";
    $stmt = $vConexion->prepare($sql);
    $stmt->bind_param("s", $denominacion);
    if (!$stmt->execute()) {
        return false;
    }
    $stmt->close();
    return true;
}

function InsertarTipoMovimientoContable($vConexion) {
    $denominacion = trim(strip_tags($_POST['Denominacion'] ?? ''));
    $sql = "INSERT INTO tipo_movimiento (denominacion, es_entrada, es_salida, idActivo) VALUES (?, 0, 0, 1)";
    $stmt = $vConexion->prepare($sql);
    $stmt->bind_param("s", $denominacion);
    if (!$stmt->execute()) {
        return false;
    }
    $stmt->close();
    return true;
}

function Validar_Tipo_Movimiento() {
    $Mensaje = '';
    if (empty(trim($_POST['Denominacion'] ?? ''))) {
        $Mensaje .= 'Debes agregar una denominación.<br />';
    }
    return $Mensaje;
}

function Datos_Tipo_Movimiento($vConexion, $vIdTipoMovimiento) {
    $DatosTipoMovimiento = array();
    $SQL = "SELECT * FROM tipo_movimiento WHERE idTipoMovimiento = $vIdTipoMovimiento";
    $rs = mysqli_query($vConexion, $SQL);
    $data = mysqli_fetch_array($rs);
    if (!empty($data)) {
        $DatosTipoMovimiento['IdTipoMovimiento'] = $data['idTipoMovimiento'];
        $DatosTipoMovimiento['Denominacion'] = $data['denominacion'];
    }
    return $DatosTipoMovimiento;
}

function Modificar_Tipo_Movimiento($vConexion) {
    $denominacion = mysqli_real_escape_string($vConexion, $_POST['Denominacion']);
    $idTipoMovimiento = mysqli_real_escape_string($vConexion, $_POST['IdTipoMovimiento']);
    $SQL_MiConsulta = "UPDATE tipo_movimiento
    SET denominacion = '$denominacion'
    WHERE idTipoMovimiento = '$idTipoMovimiento'";
    if (mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    } else {
        return false;
    }
}

function Anular_Tipo_Movimiento($vConexion, $vIdConsulta) {
    $SQL_MiConsulta = "SELECT idTipoMovimiento FROM tipo_movimiento 
                    WHERE idTipoMovimiento = $vIdConsulta";

    $rs = mysqli_query($vConexion, $SQL_MiConsulta);

    $data = mysqli_fetch_array($rs);

    if (!empty($data['idTipoMovimiento'])) {
        // Si se cumple todo, entonces desactivo:
        mysqli_query($vConexion, "UPDATE tipo_movimiento SET idActivo = 2 WHERE idTipoMovimiento = $vIdConsulta");
        return true;
    } else {
        return false;
    }
}

    // Obtener todos los movimientos contables históricos con fecha de caja
// Listar movimientos contables con filtros y paginación

function Listar_Movimientos_Contables($conexion, $filtros = [], $offset = 0, $limite = 50) {
    $movimientos = [];

    // Movimientos de detalle_caja (entradas reales)
    $detalleCajaQuery = "
        SELECT 
            dc.idDetalleCaja AS idMovimiento,
            c.Fecha AS fecha,
            dc.monto,
            tp.denominacion AS metodo_pago,
            CONCAT(u.nombre,' ',u.apellido) AS usuario, 
            1 AS es_entrada,
            0 AS es_salida,
            dc.observaciones AS detalle
        FROM detalle_caja dc
        JOIN caja c ON dc.idCaja = c.idCaja
        JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
        JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
        LEFT JOIN usuarios u ON dc.idUsuario = u.idUsuario
        WHERE tm.es_entrada = 1 
          AND tp.denominacion NOT LIKE '%Efectivo%' 
    ";

    // Filtros sobre detalle_caja
    if (!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $detalleCajaQuery .= " AND c.Fecha >= '$fecha_desde'";
    }
    if (!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $detalleCajaQuery .= " AND c.Fecha <= '$fecha_hasta'";
    }
    if (!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $detalleCajaQuery .= " AND tp.denominacion = '$metodo_pago'";
    }
    
    // Excluir detalle_caja para "Retiros Contables"
    if (!empty($filtros['tipo_movimiento'])) {
        if ($filtros['tipo_movimiento'] === 'Salida' || $filtros['tipo_movimiento'] === 'Retiros Contables') {
            $detalleCajaQuery .= " AND 0";
        }
    }

    // Movimientos de retiros (incluye Caja Fuerte y Contables)
    $retirosQuery = "
        SELECT 
            r.idRetiro AS idMovimiento,
            r.fecha,
            r.monto,
            tp.denominacion AS metodo_pago,
            CONCAT(uCreador.nombre,' ',uCreador.apellido) AS usuario, 
            CASE 
                WHEN rv.idRetiro IS NOT NULL AND rv.categoria = 'Caja Fuerte' THEN 1 
                ELSE 0 
            END AS es_entrada,
            CASE 
                WHEN rv.idRetiro IS NOT NULL AND rv.categoria = 'Caja Fuerte' THEN 0
                WHEN tm.es_entrada = 0 AND tm.es_salida = 0 THEN 0
                ELSE 1 
            END AS es_salida,
            CASE
                WHEN ri.idRetiro IS NOT NULL THEN CONCAT('Compra de insumos a ', pi.nombre)
                WHEN rp.idRetiro IS NOT NULL THEN CONCAT('Pago a proveedor ', p.nombre)
                WHEN rs.idRetiro IS NOT NULL THEN CONCAT('Pago de servicio: ', rs.tipo_servicio)
                WHEN rsu.idRetiro IS NOT NULL THEN CONCAT('Pago de sueldo a ', CONCAT(uSueldo.nombre,' ',uSueldo.apellido))
                WHEN rv.idRetiro IS NOT NULL THEN CONCAT(rv.categoria, ': ', rv.detalle_vario)
                WHEN rv.idRetiro IS NOT NULL AND rv.categoria = 'Caja Fuerte' THEN 'Depósito en Caja Fuerte'
                ELSE 'Retiro Contable'
            END AS detalle
        FROM retiros r
        LEFT JOIN retiros_insumos ri ON r.idRetiro = ri.idRetiro
        LEFT JOIN proveedores_insumos pi ON ri.idProveedorInsumo = pi.idProveedorInsumo
        LEFT JOIN retiros_proveedores rp ON r.idRetiro = rp.idRetiro
        LEFT JOIN proveedores p ON rp.idProveedor = p.idProveedor
        LEFT JOIN retiros_servicios rs ON r.idRetiro = rs.idRetiro
        LEFT JOIN retiros_sueldos rsu ON r.idRetiro = rsu.idRetiro
        LEFT JOIN usuarios uSueldo ON rsu.idUsuarioSueldo = uSueldo.idUsuario
        LEFT JOIN retiros_varios rv ON r.idRetiro = rv.idRetiro
        LEFT JOIN tipo_pago tp ON r.idTipoPago = tp.idTipoPago
        LEFT JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
        LEFT JOIN usuarios uCreador ON r.idUsuario = uCreador.idUsuario
        WHERE 1=1
    ";

    // Filtros sobre retiros
    if (!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $retirosQuery .= " AND r.fecha >= '$fecha_desde'";
    }
    if (!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $retirosQuery .= " AND r.fecha <= '$fecha_hasta'";
    }
    if (!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $retirosQuery .= " AND tp.denominacion = '$metodo_pago'";
    }
    
    // Filtro de tipo de movimiento completo
    if (!empty($filtros['tipo_movimiento'])) {
        if ($filtros['tipo_movimiento'] === 'Entrada') {
            $retirosQuery .= " AND (rv.idRetiro IS NOT NULL AND rv.categoria = 'Caja Fuerte')";
        } elseif ($filtros['tipo_movimiento'] === 'Salida') {
            $retirosQuery .= " AND ((rv.idRetiro IS NULL OR rv.categoria != 'Caja Fuerte') AND NOT (tm.es_entrada = 0 AND tm.es_salida = 0))";
        } elseif ($filtros['tipo_movimiento'] === 'Retiros Contables') {
            $retirosQuery .= " AND (tm.es_entrada = 0 AND tm.es_salida = 0)";
        }
    }

    // Unión de consultas
    $finalQuery = "($detalleCajaQuery) UNION ALL ($retirosQuery) 
                   ORDER BY fecha DESC, idMovimiento DESC 
                   LIMIT $offset, $limite";

    $result = $conexion->query($finalQuery);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $esRetiroContable = ($row['es_entrada'] == 0 && $row['es_salida'] == 0);
            $row['tipo'] = $esRetiroContable ? 'Retiros Contables' : ($row['es_entrada'] ? 'Entrada' : 'Salida');
            $movimientos[] = $row;
        }
    }

    return $movimientos;
}

function Contar_Movimientos_Contables($conexion, $filtros = []) {
    $total = 0;

    // detalle_caja
    $detalleCajaQuery = "SELECT COUNT(*) as total
        FROM detalle_caja dc
        JOIN caja c ON dc.idCaja = c.idCaja
        JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
        JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
        WHERE tm.es_entrada = 1 AND tp.denominacion NOT LIKE '%Efectivo%'";

    if (!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $detalleCajaQuery .= " AND c.Fecha >= '$fecha_desde'";
    }
    if (!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $detalleCajaQuery .= " AND c.Fecha <= '$fecha_hasta'";
    }
    if (!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $detalleCajaQuery .= " AND tp.denominacion = '$metodo_pago'";
    }
    
    // ✅ EXCLUIR detalle_caja CUANDO SE FILTRA POR "Salida" o "Retiros Contables"
    if (!empty($filtros['tipo_movimiento'])) {
        if ($filtros['tipo_movimiento'] === 'Salida' || $filtros['tipo_movimiento'] === 'Retiros Contables') {
            $detalleCajaQuery .= " AND 0"; // excluir detalle_caja
        }
    }

    $result = $conexion->query($detalleCajaQuery);
    if ($result && $row = $result->fetch_assoc()) {
        $total += intval($row['total']);
    }

    // retiros
    $retirosQuery = "SELECT COUNT(*) as total
        FROM retiros r
        LEFT JOIN retiros_varios rv ON r.idRetiro = rv.idRetiro
        LEFT JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
        LEFT JOIN tipo_pago tp ON r.idTipoPago = tp.idTipoPago
        WHERE 1=1";

    if (!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $retirosQuery .= " AND r.fecha >= '$fecha_desde'";
    }
    if (!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $retirosQuery .= " AND r.fecha <= '$fecha_hasta'";
    }
    if (!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $retirosQuery .= " AND tp.denominacion = '$metodo_pago'";
    }

    // ✅ FILTRO DE TIPO DE MOVIMIENTO COMPLETO
    if (!empty($filtros['tipo_movimiento'])) {
        if ($filtros['tipo_movimiento'] === 'Entrada') {
            $retirosQuery .= " AND rv.idRetiro IS NOT NULL AND rv.categoria = 'Caja Fuerte'";
        } elseif ($filtros['tipo_movimiento'] === 'Salida') {
            $retirosQuery .= " AND ( (rv.idRetiro IS NULL OR rv.categoria != 'Caja Fuerte') 
                                    AND NOT (tm.es_entrada = 0 AND tm.es_salida = 0) )";
        } elseif ($filtros['tipo_movimiento'] === 'Retiros Contables') {
            $retirosQuery .= " AND (tm.es_entrada = 0 AND tm.es_salida = 0)";
        }
    }

    $result = $conexion->query($retirosQuery);
    if ($result && $row = $result->fetch_assoc()) {
        $total += intval($row['total']);
    }

    return $total;
}

// Función para Tipo Especial
function Listar_Tipos_Especiales($conexion) {
    $tipos = [];
    $query = "SELECT DISTINCT denominacion 
              FROM tipo_movimiento
              WHERE es_salida = 1 OR (es_entrada = 0 AND es_salida = 0)
              ORDER BY denominacion ASC";
    $res = $conexion->query($query);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $tipos[$row['denominacion']] = $row['denominacion'];
        }
    }
    return $tipos;
}

function Obtener_Total_Caja_Fuerte($conexion, $filtros = []) {
    // Retiros con detalle "Caja Fuerte" (esto será el nuevo total de entrada)
    $queryRetirosCajaFuerte = "SELECT SUM(r.monto) as total
                              FROM retiros r
                              JOIN tipo_pago tp ON r.idTipoPago = tp.idTipoPago
                              JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                              JOIN retiros_varios rv ON r.idRetiro = rv.idRetiro
                              WHERE rv.categoria LIKE '%Caja Fuerte%'";

    // Aplicar filtros a retiros Caja Fuerte
    if(!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $queryRetirosCajaFuerte .= " AND r.fecha >= '$fecha_desde'";
    }
    if(!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $queryRetirosCajaFuerte .= " AND r.fecha <= '$fecha_hasta'";
    }
    if(!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $queryRetirosCajaFuerte .= " AND tp.denominacion = '$metodo_pago'";
    }

    $resultRetirosCajaFuerte = $conexion->query($queryRetirosCajaFuerte);
    $totalEntrada = ($resultRetirosCajaFuerte && $row = $resultRetirosCajaFuerte->fetch_assoc()) ? floatval($row['total']) : 0;

    // Retiros contables en efectivo (movimientos donde es_entrada = 0 y es_salida = 0)
    $queryRetirosContables = "SELECT SUM(r.monto) as total
                              FROM retiros r
                              JOIN tipo_pago tp ON r.idTipoPago = tp.idTipoPago
                              JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                              WHERE tm.es_entrada = 0 AND tm.es_salida = 0 
                              AND tp.denominacion LIKE '%Efectivo%'";

    // Aplicar filtros a retiros contables
    if(!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $queryRetirosContables .= " AND r.fecha >= '$fecha_desde'";
    }
    if(!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $queryRetirosContables .= " AND r.fecha <= '$fecha_hasta'";
    }
    if(!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $queryRetirosContables .= " AND tp.denominacion = '$metodo_pago'";
    }

    $resultRetirosContables = $conexion->query($queryRetirosContables);
    $totalRetirosContables = ($resultRetirosContables && $row = $resultRetirosContables->fetch_assoc()) ? floatval($row['total']) : 0;

    return $totalEntrada - $totalRetirosContables;
}

function Obtener_Total_Banco($conexion, $filtros = []) {
    // Entradas no efectivo
    $queryEntrada = "SELECT SUM(dc.monto) as total
                     FROM detalle_caja dc
                     JOIN caja c ON dc.idCaja = c.idCaja
                     JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                     JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                     WHERE tm.es_entrada = 1 AND tp.denominacion NOT LIKE '%Efectivo%'";

    // Aplicar filtros
    if(!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $queryEntrada .= " AND c.Fecha >= '$fecha_desde'";
    }
    if(!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $queryEntrada .= " AND c.Fecha <= '$fecha_hasta'";
    }
    if(!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $queryEntrada .= " AND tp.denominacion = '$metodo_pago'";
    }
    if(!empty($filtros['tipo_movimiento']) && $filtros['tipo_movimiento'] == 'Salida') {
        $queryEntrada .= " AND 0"; // no queremos entradas si filtramos salida
    }

    $resultEntrada = $conexion->query($queryEntrada);
    $totalEntrada = ($resultEntrada && $row = $resultEntrada->fetch_assoc()) ? floatval($row['total']) : 0;

    // Salidas no efectivo
    $querySalida = "SELECT SUM(dc.monto) as total
                    FROM detalle_caja dc
                    JOIN caja c ON dc.idCaja = c.idCaja
                    JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
                    JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
                    LEFT JOIN retiros r ON dc.idRetiro = r.idRetiro
                    WHERE tm.es_salida = 1 AND tp.denominacion NOT LIKE '%Efectivo%'";

    // Aplicar filtros
    if(!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $querySalida .= " AND c.Fecha >= '$fecha_desde'";
    }
    if(!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $querySalida .= " AND c.Fecha <= '$fecha_hasta'";
    }
    if(!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $querySalida .= " AND tp.denominacion = '$metodo_pago'";
    }
    if(!empty($filtros['tipo_movimiento']) && $filtros['tipo_movimiento'] == 'Entrada') {
        $querySalida .= " AND 0"; // no queremos salidas si filtramos entrada
    }

    $resultSalida = $conexion->query($querySalida);
    $totalSalida = ($resultSalida && $row = $resultSalida->fetch_assoc()) ? floatval($row['total']) : 0;

    // Retiros contables no efectivo (movimientos donde es_entrada = 0 y es_salida = 0)
    $queryRetirosContables = "SELECT SUM(r.monto) as total
                              FROM retiros r
                              JOIN tipo_pago tp ON r.idTipoPago = tp.idTipoPago
                              JOIN tipo_movimiento tm ON r.idTipoMovimiento = tm.idTipoMovimiento
                              WHERE tm.es_entrada = 0 AND tm.es_salida = 0 
                              AND tp.denominacion NOT LIKE '%Efectivo%'";

    // Aplicar filtros
    if(!empty($filtros['fecha_desde'])) {
        $fecha_desde = $conexion->real_escape_string($filtros['fecha_desde']);
        $queryRetirosContables .= " AND r.fecha >= '$fecha_desde'";
    }
    if(!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = $conexion->real_escape_string($filtros['fecha_hasta']);
        $queryRetirosContables .= " AND r.fecha <= '$fecha_hasta'";
    }
    if(!empty($filtros['metodo_pago'])) {
        $metodo_pago = $conexion->real_escape_string($filtros['metodo_pago']);
        $queryRetirosContables .= " AND tp.denominacion = '$metodo_pago'";
    }

    $resultRetirosContables = $conexion->query($queryRetirosContables);
    $totalRetirosContables = ($resultRetirosContables && $row = $resultRetirosContables->fetch_assoc()) ? floatval($row['total']) : 0;

    return $totalEntrada - $totalSalida - $totalRetirosContables;
}

function Listar_Pedidos_Trabajos_Detallado($vConexion) {
    $Listado = array();

    // Primero obtenemos los pedidos con sus sumatorias y estado de facturación
    $SQL = "SELECT 
                PT.idPedidoTrabajos,
                PT.fecha,
                PT.senia,
                C.nombre AS nombre_cliente,
                C.apellido AS apellido_cliente,
                C.telefono,
                ET.idEstado,
                US.nombre AS usuario,
                ET.denominacion AS estado_nombre,
                COALESCE(SUM(DT.precio), 0) AS precio_total,
                -- Información de facturación
                COUNT(DT.idDetalleTrabajo) as total_detalles,
                SUM(CASE WHEN DT.facturado = 1 THEN 1 ELSE 0 END) as detalles_facturados
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_trabajo ET ON PT.idEstado = ET.idEstado
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            LEFT JOIN detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos AND DT.idActivo = 1
            WHERE PT.idActivo = 1
            GROUP BY PT.idPedidoTrabajos
            ORDER BY PT.idPedidoTrabajos DESC";

    $rs = mysqli_query($vConexion, $SQL);

    if (!$rs) {
        error_log("Error en Listar_Pedidos_Trabajos_Detallado: " . mysqli_error($vConexion));
        return $Listado;
    }

    // Obtenemos todos los pedidos con sus totales
    $pedidos = array();
    while ($data = mysqli_fetch_assoc($rs)) {
        $pedidos[$data['idPedidoTrabajos']] = array(
            'ID' => $data['idPedidoTrabajos'],
            'FECHA' => $data['fecha'],
            'SEÑA' => $data['senia'],
            'TELEFONO' => $data['telefono'],
            'CLIENTE_N' => $data['nombre_cliente'],
            'CLIENTE_A' => $data['apellido_cliente'],
            'ESTADO' => $data['idEstado'],
            'USUARIO' => $data['usuario'],
            'ESTADO_NOMBRE' => $data['estado_nombre'],
            'PRECIO' => $data['precio_total'],
            'TOTAL_DETALLES' => $data['total_detalles'],
            'DETALLES_FACTURADOS' => $data['detalles_facturados'],
            'TRABAJOS' => array()
        );
    }

    // Si hay pedidos, obtenemos sus detalles
    if (!empty($pedidos)) {
        $SQL = "SELECT 
                    DT.id_pedido_trabajos,
                    DT.idDetalleTrabajo,
                    DT.idTrabajo,
                    DT.descripcion,
                    TT.denominacion AS nombre_trabajo
                FROM detalle_trabajos DT
                INNER JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
                WHERE DT.id_pedido_trabajos IN (" . implode(',', array_keys($pedidos)) . ")
                AND DT.idActivo = 1
                ORDER BY DT.id_pedido_trabajos DESC, DT.idDetalleTrabajo ASC";

        $rs = mysqli_query($vConexion, $SQL);

        if ($rs) {
            while ($data = mysqli_fetch_assoc($rs)) {
                $idPedido = $data['id_pedido_trabajos'];
                if (isset($pedidos[$idPedido])) {
                    $pedidos[$idPedido]['TRABAJOS'][] = array(
                        'ID_TRABAJO' => $data['idTrabajo'],
                        'DENOMINACION' => $data['nombre_trabajo'],
                        'DESCRIPCION' => $data['descripcion']
                    );
                }
            }
        } else {
            error_log("Error al obtener detalles de trabajos: " . mysqli_error($vConexion));
        }
    }

    // Convertir a lista indexada
    $Listado = array_values($pedidos);
    return $Listado;
}

function Listar_Pedidos_Trabajo_Parametro_Detallado($vConexion, $criterio, $parametro) {
    $Listado = array();
    $whereClause = "WHERE PT.idActivo = 1"; // Filtro base

    // Construcción de la cláusula WHERE según el criterio
    switch ($criterio) {
        case 'Fecha':
            $whereClause .= " AND PT.fecha LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
        case 'Id':
            $whereClause = "WHERE PT.idPedidoTrabajos LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
        case 'Cliente':
            $parametro = strtolower($parametro);
            $nombreApellido = explode(' ', $parametro);
            if (count($nombreApellido) >= 2) {
                $whereClause .= " AND (
                    (LOWER(C.nombre) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[0])."%' 
                    AND LOWER(C.apellido) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[1])."%') 
                    OR 
                    (LOWER(C.nombre) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[1])."%' 
                    AND LOWER(C.apellido) LIKE '%".mysqli_real_escape_string($vConexion, $nombreApellido[0])."%')
                )";
            } else {
                $whereClause .= " AND (
                    LOWER(C.nombre) LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%' 
                    OR LOWER(C.apellido) LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'
                )";
            }
            break;
        case 'Telefono':
            $whereClause .= " AND C.telefono LIKE '%".mysqli_real_escape_string($vConexion, $parametro)."%'";
            break;
    }

    // Primero obtenemos los pedidos que cumplen con el criterio de búsqueda
    $SQL = "SELECT 
                PT.idPedidoTrabajos,
                PT.fecha,
                PT.senia,
                C.nombre AS nombre_cliente,
                C.apellido AS apellido_cliente,
                C.telefono,
                ET.idEstadoPedidoTrabajo AS idEstado,
                US.nombre AS usuario,
                ET.denominacion AS estado_nombre,
                COALESCE(SUM(DT.precio), 0) AS precio_total,
                -- Información de facturación
                COUNT(DT.idDetalleTrabajo) as total_detalles,
                SUM(CASE WHEN DT.facturado = 1 THEN 1 ELSE 0 END) as detalles_facturados
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_pedido_trabajo ET ON PT.idEstado = ET.idEstadoPedidoTrabajo
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            LEFT JOIN detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos AND DT.idActivo = 1
            $whereClause
            GROUP BY PT.idPedidoTrabajos
            ORDER BY PT.idPedidoTrabajos DESC";

    $rs = mysqli_query($vConexion, $SQL);

    if (!$rs) {
        error_log("Error en Listar_Pedidos_Trabajo_Parametro_Detallado (consulta principal): " . mysqli_error($vConexion));
        return $Listado;
    }

    // Obtenemos todos los pedidos que cumplen con el criterio
    $pedidos = array();
    while ($data = mysqli_fetch_assoc($rs)) {
        $pedidos[$data['idPedidoTrabajos']] = array(
            'ID' => $data['idPedidoTrabajos'],
            'FECHA' => $data['fecha'],
            'SEÑA' => $data['senia'],
            'TELEFONO' => $data['telefono'],
            'CLIENTE_N' => $data['nombre_cliente'],
            'CLIENTE_A' => $data['apellido_cliente'],
            'ESTADO' => $data['idEstado'],
            'USUARIO' => $data['usuario'],
            'ESTADO_NOMBRE' => $data['estado_nombre'],
            'PRECIO' => $data['precio_total'],
            'TOTAL_DETALLES' => $data['total_detalles'],
            'DETALLES_FACTURADOS' => $data['detalles_facturados'],
            'TRABAJOS' => array()
        );
    }

    // Si hay pedidos, obtenemos sus detalles
    if (!empty($pedidos)) {
        $SQL = "SELECT 
                    DT.id_pedido_trabajos,
                    DT.idDetalleTrabajo,
                    DT.idTrabajo,
                    DT.descripcion,
                    DT.precio,
                    TT.denominacion AS nombre_trabajo
                FROM detalle_trabajos DT
                INNER JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
                WHERE DT.id_pedido_trabajos IN (" . implode(',', array_keys($pedidos)) . ")
                AND DT.idActivo = 1
                ORDER BY DT.id_pedido_trabajos DESC, DT.idDetalleTrabajo ASC";

        $rs = mysqli_query($vConexion, $SQL);

        if ($rs) {
            while ($data = mysqli_fetch_assoc($rs)) {
                $idPedido = $data['id_pedido_trabajos'];
                if (isset($pedidos[$idPedido])) {
                    $pedidos[$idPedido]['TRABAJOS'][] = array(
                        'ID_TRABAJO' => $data['idTrabajo'],
                        'DENOMINACION' => $data['nombre_trabajo'],
                        'DESCRIPCION' => $data['descripcion'],
                        'PRECIO' => $data['precio']
                    );
                }
            }
        } else {
            error_log("Error al obtener detalles de trabajos: " . mysqli_error($vConexion));
        }
    }

    // Convertir a lista indexada
    $Listado = array_values($pedidos);
    return $Listado;
}

function Listar_Pedidos_Trabajo_Por_Estado($vConexion, $idEstado) {
    $Listado = array();
    
    $SQL = "SELECT 
                PT.idPedidoTrabajos,
                PT.fecha,
                PT.senia,
                C.nombre AS nombre_cliente,
                C.apellido AS apellido_cliente,
                C.telefono,
                ET.idEstadoPedidoTrabajo AS idEstado,
                US.nombre AS usuario,
                ET.denominacion AS estado_nombre,
                DT.idDetalleTrabajo,
                DT.idTrabajo,
                DT.descripcion,
                TT.denominacion AS nombre_trabajo,
                DT.precio,
                -- Información de facturación
                COUNT(DT.idDetalleTrabajo) OVER (PARTITION BY PT.idPedidoTrabajos) as total_detalles,
                SUM(CASE WHEN DT.facturado = 1 THEN 1 ELSE 0 END) OVER (PARTITION BY PT.idPedidoTrabajos) as detalles_facturados
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_pedido_trabajo ET ON PT.idEstado = ET.idEstadoPedidoTrabajo
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            LEFT JOIN detalle_trabajos DT ON PT.idPedidoTrabajos = DT.id_pedido_trabajos AND DT.idActivo = 1
            LEFT JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
            WHERE PT.idActivo = 1 AND PT.idEstado = ?
            ORDER BY PT.idPedidoTrabajos DESC, DT.idDetalleTrabajo ASC";

    $stmt = mysqli_prepare($vConexion, $SQL);
    mysqli_stmt_bind_param($stmt, "i", $idEstado);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);

    if (!$rs) {
        error_log("Error en Listar_Pedidos_Trabajo_Por_Estado: " . mysqli_error($vConexion));
        return $Listado;
    }

    $pedidos = array();
    while ($data = mysqli_fetch_assoc($rs)) {
        $idPedido = $data['idPedidoTrabajos'];
        if (!isset($pedidos[$idPedido])) {
            $pedidos[$idPedido] = array(
                'ID' => $data['idPedidoTrabajos'],
                'FECHA' => $data['fecha'],
                'SEÑA' => $data['senia'],
                'TELEFONO' => $data['telefono'],
                'CLIENTE_N' => $data['nombre_cliente'],
                'CLIENTE_A' => $data['apellido_cliente'],
                'ESTADO' => $data['idEstado'],
                'USUARIO' => $data['usuario'],
                'ESTADO_NOMBRE' => $data['estado_nombre'],
                'TRABAJOS' => array(),
                'PRECIO' => 0,
                'TOTAL_DETALLES' => $data['total_detalles'],
                'DETALLES_FACTURADOS' => $data['detalles_facturados']
            );
        }
        
        if (!empty($data['idDetalleTrabajo'])) {
            $pedidos[$idPedido]['TRABAJOS'][] = array(
                'ID_TRABAJO' => $data['idTrabajo'],
                'DENOMINACION' => $data['nombre_trabajo'],
                'DESCRIPCION' => $data['descripcion'],
                'PRECIO' => $data['precio']
            );
            $pedidos[$idPedido]['PRECIO'] += floatval($data['precio']);
        }
    }

    $Listado = array_values($pedidos);
    return $Listado;
}

function determinarEstadoFacturacion($detallesFacturados, $totalDetalles) {
    if ($totalDetalles == 0) {
        return ['estado' => 'sin_detalles', 'tooltip' => 'Sin detalles para facturar'];
    } elseif ($detallesFacturados == $totalDetalles) {
        return ['estado' => 'totalmente_facturado', 'tooltip' => 'Totalmente facturado'];
    } elseif ($detallesFacturados > 0) {
        return [
            'estado' => 'parcialmente_facturado', 
            'tooltip' => 'Parcialmente facturado (' . $detallesFacturados . ' de ' . $totalDetalles . ' items)'
        ];
    } else {
        return ['estado' => 'no_facturado', 'tooltip' => 'No facturado'];
    }
}

function Datos_Estados_Pedido_Trabajo($conexion) {
    $query = "SELECT idEstadoPedidoTrabajo AS idEstado, denominacion 
              FROM estado_pedido_trabajo 
              ORDER BY denominacion";
    
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        die("Error al obtener estados de pedidos: " . mysqli_error($conexion));
    }
    
    $estados = array();
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $estados[] = $fila;
    }
    
    return $estados;
}
    // Cuenta Corriente

function Listar_Clientes_Cuenta_Corriente($vConexion) {
    $Listado = array();

    $SQL = "SELECT 
                c.idCliente AS ID_CLIENTE,
                c.nombre AS NOMBRE,
                c.apellido AS APELLIDO,
                c.telefono AS TELEFONO,
                IFNULL(SUM(dt.precio), 0) AS TOTAL_DEUDA,
                COUNT(dt.idDetalleTrabajo) AS CANTIDAD_TRABAJOS
            FROM clientes c
            LEFT JOIN pedido_trabajos pt ON pt.idCliente = c.idCliente AND pt.idActivo = 1
            LEFT JOIN detalle_trabajos dt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos 
                AND dt.idEstadoTrabajo = 8 
                AND dt.idActivo = 1
            WHERE c.idActivo = 1
            GROUP BY c.idCliente, c.nombre, c.apellido, c.telefono
            HAVING CANTIDAD_TRABAJOS > 0
            ORDER BY c.apellido, c.nombre";

    $rs = mysqli_query($vConexion, $SQL);
    
    if (!$rs) {
        error_log("Error en Listar_Clientes_Cuenta_Corriente: " . mysqli_error($vConexion));
        return $Listado;
    }
    
    $i = 0;
    while ($data = mysqli_fetch_array($rs)) {
        $Listado[$i]['ID_CLIENTE'] = $data['ID_CLIENTE'];
        $Listado[$i]['NOMBRE'] = $data['NOMBRE'];
        $Listado[$i]['APELLIDO'] = $data['APELLIDO'];
        $Listado[$i]['TELEFONO'] = $data['TELEFONO'];
        $Listado[$i]['TOTAL_DEUDA'] = $data['TOTAL_DEUDA'];
        $Listado[$i]['CANTIDAD_TRABAJOS'] = $data['CANTIDAD_TRABAJOS'];
        $i++;
    }

    return $Listado;
}

function Listar_Clientes_Cuenta_Corriente_Parametro($vConexion, $criterio, $parametro) {
    $Listado = array();
    $parametro = trim($parametro);
    $parametro = mysqli_real_escape_string($vConexion, $parametro);
    
    // Consulta base con protecciones contra SQL injection
    $SQLBase = "SELECT 
                    c.idCliente AS ID_CLIENTE,
                    c.nombre AS NOMBRE,
                    c.apellido AS APELLIDO,
                    c.telefono AS TELEFONO,
                    IFNULL(SUM(dt.precio), 0) AS TOTAL_DEUDA,
                    COUNT(dt.idDetalleTrabajo) AS CANTIDAD_TRABAJOS
                FROM clientes c
                LEFT JOIN pedido_trabajos pt ON pt.idCliente = c.idCliente AND pt.idActivo = 1
                LEFT JOIN detalle_trabajos dt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos 
                    AND dt.idEstadoTrabajo = 8 
                    AND dt.idActivo = 1
                WHERE c.idActivo = 1 ";
    
    // Añadir condiciones según el criterio
    switch ($criterio) {
        case 'Cliente':
            // Divide el parámetro en partes (nombre y apellido)
            $partes = explode(' ', $parametro);
            $nombre = isset($partes[0]) ? $partes[0] : '';
            $apellido = isset($partes[1]) ? $partes[1] : '';
            
            if ($nombre && $apellido) {
                $SQL = $SQLBase . " AND (c.nombre LIKE '%" . mysqli_real_escape_string($vConexion, $nombre) . "%' 
                                  AND c.apellido LIKE '%" . mysqli_real_escape_string($vConexion, $apellido) . "%')";
            } else {
                $SQL = $SQLBase . " AND (c.nombre LIKE '%" . mysqli_real_escape_string($vConexion, $parametro) . "%' 
                                  OR c.apellido LIKE '%" . mysqli_real_escape_string($vConexion, $parametro) . "%')";
            }
            break;
            
        case 'idCliente':
            $SQL = $SQLBase . " AND c.idCliente = " . intval($parametro);
            break;
            
        case 'Telefono':
            $SQL = $SQLBase . " AND c.telefono LIKE '%" . mysqli_real_escape_string($vConexion, $parametro) . "%'";
            break;
            
        default:
            $SQL = $SQLBase . " AND (c.nombre LIKE '%" . mysqli_real_escape_string($vConexion, $parametro) . "%' 
                              OR c.apellido LIKE '%" . mysqli_real_escape_string($vConexion, $parametro) . "%')";
    }
    
    $SQL .= " GROUP BY c.idCliente, c.nombre, c.apellido, c.telefono
              HAVING CANTIDAD_TRABAJOS > 0
              ORDER BY c.apellido, c.nombre";
    
    $rs = mysqli_query($vConexion, $SQL);
    
    if (!$rs) {
        error_log("Error en Listar_Clientes_Cuenta_Corriente_Parametro: " . mysqli_error($vConexion));
        return $Listado;
    }
    
    $i = 0;
    while ($data = mysqli_fetch_array($rs)) {
        $Listado[$i]['ID_CLIENTE'] = $data['ID_CLIENTE'];
        $Listado[$i]['NOMBRE'] = $data['NOMBRE'];
        $Listado[$i]['APELLIDO'] = $data['APELLIDO'];
        $Listado[$i]['TELEFONO'] = $data['TELEFONO'];
        $Listado[$i]['TOTAL_DEUDA'] = $data['TOTAL_DEUDA'];
        $Listado[$i]['CANTIDAD_TRABAJOS'] = $data['CANTIDAD_TRABAJOS'];
        $i++;
    }

    return $Listado;
}

function Obtener_Cliente_Por_ID($conexion, $idCliente) {
    $sql = "SELECT idCliente, nombre AS NOMBRE, apellido AS APELLIDO, telefono AS TELEFONO
            FROM clientes
            WHERE idCliente = ? AND idActivo = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $cliente = $resultado->fetch_assoc();
    $stmt->close();
    
    return $cliente;
}

function Obtener_Trabajos_Pendientes($conexion, $idCliente) {
    $trabajos = array();
    
    $sql = "SELECT 
                dt.idDetalleTrabajo AS ID_DETALLE,
                pt.fecha AS FECHA_PEDIDO,
                tt.denominacion AS TIPO_TRABAJO,
                dt.descripcion AS DESCRIPCION,
                dt.precio AS PRECIO,
                dt.fechaEntrega AS FECHA_ENTREGA,
                et.denominacion AS ESTADO
            FROM detalle_trabajos dt
            INNER JOIN pedido_trabajos pt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos
            INNER JOIN tipo_trabajo tt ON tt.idTipoTrabajo = dt.idTrabajo
            INNER JOIN estado_trabajo et ON et.idEstado = dt.idEstadoTrabajo
            WHERE pt.idCliente = ? 
            AND dt.idEstadoTrabajo = 8 -- Estado cuenta corriente
            AND dt.idActivo = 1
            ORDER BY pt.fecha ASC, dt.idDetalleTrabajo ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($fila = $result->fetch_assoc()) {
        $trabajos[] = $fila;
    }
    
    return $trabajos;
}

function ActualizarSaldoCliente($conexion, $idCliente, $monto, $tipo, $idUsuario, $idReferencia = null, $tipoReferencia = null, $observaciones = '') {
    // Primero insertar el movimiento
    $sqlMov = "INSERT INTO movimientos_ctacte (idCliente, tipo, monto, idUsuario, idReferencia, tipoReferencia, observaciones) 
               VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sqlMov);
    $stmt->bind_param("isdiiss", $idCliente, $tipo, $monto, $idUsuario, $idReferencia, $tipoReferencia, $observaciones);
    $stmt->execute();
    
    // Luego actualizar el saldo en la tabla saldos_clientes
    $sqlUpdate = "INSERT INTO saldos_clientes (idCliente, saldo, idUsuario) 
                  VALUES (?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                  saldo = saldo + VALUES(saldo), 
                  fechaActualizacion = NOW(), 
                  idUsuario = VALUES(idUsuario)";
    $stmt = $conexion->prepare($sqlUpdate);
    $stmt->bind_param("idi", $idCliente, $monto, $idUsuario);
    $result = $stmt->execute();
    
    return $result;
}

function ObtenerSaldoCliente($conexion, $idCliente) {
    $sql = "SELECT saldo FROM saldos_clientes WHERE idCliente = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        error_log("Error al preparar consulta: " . $conexion->error);
        return 0.00;
    }
    
    $stmt->bind_param("i", $idCliente);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta: " . $stmt->error);
        return 0.00;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (float)$row['saldo'];
    }
    
    return 0.00;
}

function ObtenerMovimientosCliente($conexion, $idCliente, $limit = 10) {
    $movimientos = array();
    
    $sql = "SELECT 
                mc.*,
                CONCAT(u.nombre, ' ', u.apellido) AS usuarioNombre
            FROM movimientos_ctacte mc
            LEFT JOIN usuarios u ON u.idUsuario = mc.idUsuario
            WHERE mc.idCliente = ?
            ORDER BY mc.fecha DESC
            LIMIT ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $idCliente, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $movimientos[] = $row;
    }
    
    $stmt->close();
    return $movimientos;
}

function Obtener_Trabajos_Pendientes_Por_Antiguedad($conexion, $idCliente) {
    $trabajos = array();
    
    $sql = "SELECT 
                dt.idDetalleTrabajo AS ID_DETALLE,
                dt.id_pedido_trabajos AS ID_PEDIDO,
                dt.precio AS PRECIO,
                dt.descripcion AS DESCRIPCION,
                pt.fecha AS FECHA_PEDIDO,
                tt.denominacion AS TIPO_TRABAJO
            FROM detalle_trabajos dt
            INNER JOIN pedido_trabajos pt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos
            INNER JOIN tipo_trabajo tt ON tt.idTipoTrabajo = dt.idTrabajo
            WHERE pt.idCliente = ? 
            AND dt.idEstadoTrabajo = 8 -- Estado cuenta corriente
            AND dt.idActivo = 1
            ORDER BY pt.fecha ASC, dt.idDetalleTrabajo ASC"; // Más antiguos primero
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($fila = $result->fetch_assoc()) {
        $trabajos[] = $fila;
    }
    
    return $trabajos;
}

function Marcar_Trabajo_Pagado($conexion, $idDetalleTrabajo) {
    $sql = "UPDATE detalle_trabajos 
           SET idEstadoTrabajo = 7 
           WHERE idDetalleTrabajo = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idDetalleTrabajo);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function ObtenerNombreTipoPago($conexion, $idTipoPago) {
    $sql = "SELECT denominacion FROM tipo_pago WHERE idTipoPago = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idTipoPago);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = mysqli_fetch_assoc($resultado);
    return $fila ? $fila['denominacion'] : 'Desconocido';
}

    // Usuarios

function Listar_Usuarios($Conexion, $mostrarInactivos = false) {
    $whereClause = $mostrarInactivos ? "" : "WHERE u.idActivo = 1";
    
    $query = "SELECT u.idUsuario AS ID_USUARIO, u.usuario AS USUARIO, 
                     CONCAT(u.nombre, ' ', u.apellido) AS NOMBRE_COMPLETO,
                     tu.denominacion AS TIPO_USUARIO,
                     u.idActivo AS ID_ACTIVO
              FROM usuarios u
              JOIN tipo_usuario tu ON u.idTipoUsuario = tu.idTipoUsuario
              $whereClause
              ORDER BY u.nombre, u.apellido";
    
    $resultado = mysqli_query($Conexion, $query);
    
    $usuarios = array();
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $usuarios[] = $fila;
    }
    
    return $usuarios;
}

function Listar_Usuarios_Parametro($Conexion, $criterio, $parametro, $mostrarInactivos = false) {
    $criteriosPermitidos = ['nombre_completo', 'usuario', 'idUsuario'];
    if (!in_array($criterio, $criteriosPermitidos)) {
        $criterio = 'nombre_completo';
    }
    
    $parametro = mysqli_real_escape_string($Conexion, $parametro);
    
    $whereClause = "";
    if ($criterio == 'nombre_completo') {
        $whereClause = "(u.nombre LIKE '%$parametro%' OR u.apellido LIKE '%$parametro%' OR CONCAT(u.nombre, ' ', u.apellido) LIKE '%$parametro%')";
    } else {
        $whereClause = "u.$criterio LIKE '%$parametro%'";
    }
    
    if (!$mostrarInactivos) {
        $whereClause = ($whereClause ? $whereClause . " AND " : "") . "u.idActivo = 1";
    }
    
    $query = "SELECT u.idUsuario AS ID_USUARIO, u.usuario AS USUARIO, 
                     CONCAT(u.nombre, ' ', u.apellido) AS NOMBRE_COMPLETO,
                     tu.denominacion AS TIPO_USUARIO,
                     u.idActivo AS ID_ACTIVO
              FROM usuarios u
              JOIN tipo_usuario tu ON u.idTipoUsuario = tu.idTipoUsuario
              " . ($whereClause ? "WHERE $whereClause" : "") . "
              ORDER BY u.nombre, u.apellido";
    
    $resultado = mysqli_query($Conexion, $query);
    
    $usuarios = array();
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $usuarios[] = $fila;
    }
    
    return $usuarios;
}

function Listar_Tipos_Usuario($Conexion) {
    $query = "SELECT idTipoUsuario AS ID_TIPO_USUARIO, denominacion AS DENOMINACION 
              FROM tipo_usuario 
              ORDER BY denominacion";
    
    $resultado = mysqli_query($Conexion, $query);
    
    $tipos = array();
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $tipos[] = $fila;
    }
    
    return $tipos;
}

function Validar_Usuario() {
    $mensaje = '';
    
    if (empty($_POST['Nombre'])) {
        $mensaje .= 'Debe ingresar el nombre.<br>';
    }
    
    if (empty($_POST['Apellido'])) {
        $mensaje .= 'Debe ingresar el apellido.<br>';
    }
    
    if (empty($_POST['Usuario'])) {
        $mensaje .= 'Debe ingresar un nombre de usuario.<br>';
    }
    
    if (empty($_POST['Clave'])) {
        $mensaje .= 'Debe ingresar una contraseña.<br>';
    } elseif ($_POST['Clave'] != $_POST['ConfirmarClave']) {
        $mensaje .= 'Las contraseñas no coinciden.<br>';
    }
    
    if (empty($_POST['TipoUsuario'])) {
        $mensaje .= 'Debe seleccionar un tipo de usuario.<br>';
    }
    
    return $mensaje;
}

function InsertarUsuario($Conexion) {
    // Validar campos obligatorios
    if (empty($_POST['Clave']) || strlen($_POST['Clave']) < 4) {
        return 'La contraseña debe tener al menos 4 caracteres';
    }

    // Sanitizar inputs
    $nombre = mysqli_real_escape_string($Conexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($Conexion, $_POST['Apellido']);
    $usuario = mysqli_real_escape_string($Conexion, $_POST['Usuario']);
    $tipoUsuario = intval($_POST['TipoUsuario']);
    
    // Generar hash MD5 de la contraseña (32 caracteres hexadecimal)
    $clave = md5($_POST['Clave']);
    
    // Verificar si el usuario ya existe
    $query = "SELECT idUsuario FROM usuarios WHERE usuario = ?";
    $stmt = $Conexion->prepare($query);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return 'El nombre de usuario ya está en uso.';
    }
    
    // Insertar nuevo usuario
    $query = "INSERT INTO usuarios (idTipoUsuario, usuario, clave, nombre, apellido, idActivo)
              VALUES (?, ?, ?, ?, ?, 1)";
    
    $stmt = $Conexion->prepare($query);
    $stmt->bind_param("issss", $tipoUsuario, $usuario, $clave, $nombre, $apellido);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Error SQL: " . $stmt->error);
        return 'Error al registrar el usuario. Verifica los datos.';
    }
}

function Datos_Usuario($Conexion, $idUsuario) {
    $idUsuario = mysqli_real_escape_string($Conexion, $idUsuario);
    
    $query = "SELECT u.idUsuario AS ID_USUARIO, u.usuario AS USUARIO, 
                     u.nombre AS NOMBRE, u.apellido AS APELLIDO, 
                     u.idTipoUsuario AS ID_TIPO_USUARIO
              FROM usuarios u
              WHERE u.idUsuario = '$idUsuario'";
    
    $resultado = mysqli_query($Conexion, $query);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        return mysqli_fetch_assoc($resultado);
    }
    
    return array();
}

function Validar_Usuario_Modificacion() {
    $_SESSION['Mensaje'] = '';
    
    if (empty($_POST['Nombre'])) {
        $_SESSION['Mensaje'] .= 'Debe ingresar el nombre.<br>';
    }
    
    if (empty($_POST['Apellido'])) {
        $_SESSION['Mensaje'] .= 'Debe ingresar el apellido.<br>';
    }
    
    if (empty($_POST['Usuario'])) {
        $_SESSION['Mensaje'] .= 'Debe ingresar un nombre de usuario.<br>';
    }
    
    if (empty($_POST['TipoUsuario'])) {
        $_SESSION['Mensaje'] .= 'Debe seleccionar un tipo de usuario.<br>';
    }
}

function Modificar_Usuario($Conexion) {
    // Sanitizar inputs
    $idUsuario = mysqli_real_escape_string($Conexion, $_POST['IdUsuario']);
    $nombre = mysqli_real_escape_string($Conexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($Conexion, $_POST['Apellido']);
    $usuario = mysqli_real_escape_string($Conexion, $_POST['Usuario']);
    $tipoUsuario = intval($_POST['TipoUsuario']);
    
    // Verificar si el usuario ya existe (excluyendo el usuario actual)
    $query = "SELECT idUsuario FROM usuarios 
              WHERE usuario = '$usuario' AND idUsuario != '$idUsuario'";
    $resultado = mysqli_query($Conexion, $query);
    
    if (mysqli_num_rows($resultado) > 0) {
        $_SESSION['Mensaje'] = 'El nombre de usuario ya está en uso.';
        return false;
    }
    
    // Construir la consulta base
    $query = "UPDATE usuarios SET 
              nombre = '$nombre',
              apellido = '$apellido',
              usuario = '$usuario',
              idTipoUsuario = $tipoUsuario";
    
    // Si se marcó el checkbox para resetear contraseña
    if (!empty($_POST['ResetearClave'])) {
        $claveMd5 = md5('12345');
        $query .= ", clave = '$claveMd5'";
    }
    
    $query .= " WHERE idUsuario = '$idUsuario'";
    
    if (mysqli_query($Conexion, $query)) {
        return true;
    } else {
        $_SESSION['Mensaje'] = 'Error al modificar el usuario: ' . mysqli_error($Conexion);
        return false;
    }
}

function Desactivar_Usuario($Conexion, $idUsuario) {
    // Sanitizar el input
    $idUsuario = mysqli_real_escape_string($Conexion, $idUsuario);
    
    // Actualizar el estado a inactivo (idActivo = 2)
    $query = "UPDATE usuarios SET idActivo = 2 WHERE idUsuario = '$idUsuario'";
    
    return mysqli_query($Conexion, $query);
}

function Usuario_Esta_Activo($Conexion, $idUsuario) {
    $query = "SELECT idActivo FROM usuarios WHERE idUsuario = '$idUsuario'";
    $resultado = mysqli_query($Conexion, $query);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_assoc($resultado);
        return ($fila['idActivo'] == 1); // Asumiendo que 1 es activo
    }
    
    return false;
}

function Reactivar_Usuario($Conexion, $idUsuario) {
    $idUsuario = mysqli_real_escape_string($Conexion, $idUsuario);
    $query = "UPDATE usuarios SET idActivo = 1 WHERE idUsuario = '$idUsuario'";
    return mysqli_query($Conexion, $query);
}

function VerificarCredencialesActuales($conexion, $idUsuario, $clave) {
    $claveMd5 = md5($clave);
    
    $sql = "SELECT idUsuario FROM usuarios 
            WHERE idUsuario = ? 
            AND clave = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $idUsuario, $claveMd5);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

function ActualizarCredenciales($conexion, $idUsuario, $nuevoUsuario, $nuevaClave = null) {
    $nuevoUsuario = mysqli_real_escape_string($conexion, $nuevoUsuario);
    
    $sql = "UPDATE usuarios SET usuario = ?";
    $params = [$nuevoUsuario];
    $types = "s";
    
    if (!empty($nuevaClave)) {
        $claveMd5 = md5($nuevaClave);
        $sql .= ", clave = ?";
        $params[] = $claveMd5;
        $types .= "s";
    }
    
    $sql .= " WHERE idUsuario = ?";
    $params[] = $idUsuario;
    $types .= "i";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        return $stmt->affected_rows > 0;
    }
    return false;
}

function ObtenerDatosUsuario($conexion, $idUsuario) {
    $sql = "SELECT idUsuario, usuario, nombre, apellido FROM usuarios WHERE idUsuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

function VerificarDisponibilidadUsuario($conexion, $idUsuario, $nuevoUsuario) {
    $nuevoUsuario = mysqli_real_escape_string($conexion, $nuevoUsuario);
    
    $sql = "SELECT idUsuario FROM usuarios 
            WHERE usuario = ? 
            AND idUsuario != ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nuevoUsuario, $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 0;
}

    // Listas trabajos
function Listar_Pedidos_Trabajo_Pendientes($conexion) {
    $sql = "SELECT 
                pt.idPedidoTrabajos,
                pt.fecha,
                pt.senia,
                c.nombre AS CLIENTE_N,
                c.apellido AS CLIENTE_A,
                c.telefono AS TELEFONO,
                ept.denominacion AS estado_pedido,
                u.usuario,
                dt.idDetalleTrabajo,
                tt.denominacion AS trabajo_denom,
                dt.descripcion,
                IFNULL(p.nombre, 'No asignado') AS nombre_proveedor,
                IFNULL(et.denominacion, 'No especificado') AS nombre_estado,
                DATE_FORMAT(dt.fechaEntrega, '%d/%m/%Y') AS fecha_entrega_formateada,
                dt.fechaEntrega AS fecha_entrega_original,
                dt.horaEntrega,
                dt.precio
            FROM pedido_trabajos pt
            INNER JOIN clientes c ON pt.idCliente = c.idCliente
            INNER JOIN estado_pedido_trabajo ept ON pt.idEstado = ept.idEstadoPedidoTrabajo
            INNER JOIN usuarios u ON pt.idUsuario = u.idUsuario
            LEFT JOIN detalle_trabajos dt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos AND dt.idActivo = 1
            LEFT JOIN tipo_trabajo tt ON dt.idTrabajo = tt.idTipoTrabajo
            LEFT JOIN proveedores p ON dt.idProveedor = p.idProveedor
            LEFT JOIN estado_trabajo et ON dt.idEstadoTrabajo = et.idEstado
            WHERE pt.idActivo = 1 
              AND pt.idEstado = 1
            ORDER BY dt.fechaEntrega ASC, dt.horaEntrega ASC, pt.idPedidoTrabajos DESC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }

    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }

    $resultado = $stmt->get_result();
    $pedidos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $pedidoId = $fila['idPedidoTrabajos'];

        if (!isset($pedidos[$pedidoId])) {
            $pedidos[$pedidoId] = [
                'ID' => $fila['idPedidoTrabajos'],
                'FECHA' => $fila['fecha'],
                'SEÑA' => $fila['senia'],
                'TELEFONO' => $fila['TELEFONO'],
                'CLIENTE_N' => $fila['CLIENTE_N'],
                'CLIENTE_A' => $fila['CLIENTE_A'],
                'ESTADO' => $fila['estado_pedido'],
                'USUARIO' => $fila['usuario'],
                'TRABAJOS' => [],
                'PRECIO' => 0,
                'FECHA_ENTREGA_ORIGINAL' => $fila['fecha_entrega_original'] // Para ordenación
            ];
        }

        if (!empty($fila['idDetalleTrabajo'])) {
            $pedidos[$pedidoId]['TRABAJOS'][] = [
                'DENOMINACION' => $fila['trabajo_denom'],
                'DESCRIPCION' => $fila['descripcion'],
                'PROVEEDOR' => $fila['nombre_proveedor'],
                'ESTADO' => $fila['nombre_estado'],
                'FECHA_ENTREGA' => $fila['fecha_entrega_formateada'],
                'HORA_ENTREGA' => $fila['horaEntrega'],
                'PRECIO' => $fila['precio'],
                'FECHA_ENTREGA_ORIGINAL' => $fila['fecha_entrega_original'] // Para ordenación
            ];
            $pedidos[$pedidoId]['PRECIO'] += floatval($fila['precio']);
        }
    }

    // Convertir a array indexado y ordenar por fecha de entrega
    $pedidos = array_values($pedidos);
    
    // Ordenar los pedidos por fecha de entrega más temprana de sus trabajos
    usort($pedidos, function($a, $b) {
        // Obtener la fecha más temprana de cada pedido
        $fechaA = !empty($a['TRABAJOS']) ? 
            min(array_column($a['TRABAJOS'], 'FECHA_ENTREGA_ORIGINAL')) : 
            '9999-12-31';
            
        $fechaB = !empty($b['TRABAJOS']) ? 
            min(array_column($b['TRABAJOS'], 'FECHA_ENTREGA_ORIGINAL')) : 
            '9999-12-31';
            
        return strtotime($fechaA) - strtotime($fechaB);
    });

    // Ordenar los trabajos dentro de cada pedido por fecha de entrega
    foreach ($pedidos as &$pedido) {
        if (!empty($pedido['TRABAJOS'])) {
            usort($pedido['TRABAJOS'], function($a, $b) {
                return strtotime($a['FECHA_ENTREGA_ORIGINAL']) - strtotime($b['FECHA_ENTREGA_ORIGINAL']);
            });
        }
    }

    return $pedidos;
}

function Listar_Pedidos_Listos_Entregar($conexion, $idEstado) {
    $sql = "SELECT 
                pt.*,
                c.nombre AS CLIENTE_N, 
                c.apellido AS CLIENTE_A, 
                c.telefono AS TELEFONO,
                dt.idDetalleTrabajo,
                dt.idTrabajo,
                dt.idProveedor,
                IFNULL(p.nombre, 'No asignado') AS nombre_proveedor,
                DATE_FORMAT(dt.fechaEntrega, '%d/%m/%Y') AS fecha_entrega_formateada,
                dt.horaEntrega,
                dt.precio,
                dt.descripcion,
                dt.idEstadoTrabajo,
                IFNULL(et.denominacion, 'No especificado') AS nombre_estado,
                tt.denominacion AS trabajo_denom,
                ept.denominacion AS estado_pedido
            FROM pedido_trabajos pt
            JOIN clientes c ON pt.idCliente = c.idCliente
            JOIN detalle_trabajos dt ON pt.idPedidoTrabajos = dt.id_pedido_trabajos
            LEFT JOIN proveedores p ON dt.idProveedor = p.idProveedor
            LEFT JOIN estado_trabajo et ON dt.idEstadoTrabajo = et.idEstado
            JOIN tipo_trabajo tt ON dt.idTrabajo = tt.idTipoTrabajo
            JOIN estado_pedido_trabajo ept ON pt.idEstado = ept.idEstadoPedidoTrabajo
            WHERE dt.idEstadoTrabajo = ?
            ORDER BY dt.fechaEntrega ASC, pt.fecha ASC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $idEstado);
    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    $pedidos = [];
    
    while ($fila = $resultado->fetch_assoc()) {
        $pedidoId = $fila['idPedidoTrabajos'];
        
        if (!isset($pedidos[$pedidoId])) {
            $pedidos[$pedidoId] = [
                'ID' => $fila['idPedidoTrabajos'],
                'FECHA' => $fila['fecha'],
                'CLIENTE_N' => $fila['CLIENTE_N'],
                'CLIENTE_A' => $fila['CLIENTE_A'],
                'TELEFONO' => $fila['TELEFONO'],
                'PRECIO' => 0,
                'SEÑA' => $fila['senia'],
                'USUARIO' => $fila['idUsuario'],
                'ESTADO' => $fila['estado_pedido'],
                'TRABAJOS' => []
            ];
        }
        
        $pedidos[$pedidoId]['TRABAJOS'][] = [
            'DENOMINACION' => $fila['trabajo_denom'],
            'DESCRIPCION' => $fila['descripcion'],
            'PROVEEDOR' => $fila['nombre_proveedor'],
            'ESTADO' => $fila['nombre_estado'],
            'FECHA_ENTREGA' => $fila['fecha_entrega_formateada'],
            'HORA_ENTREGA' => $fila['horaEntrega'],
            'PRECIO' => $fila['precio']
        ];
        
        $pedidos[$pedidoId]['PRECIO'] += $fila['precio'];
    }
    
    return array_values($pedidos);
}

function Listar_Trabajos_En_Taller($conexion) {
    $sql = "SELECT 
                pt.idPedidoTrabajos AS ID,
                CONCAT(c.nombre, ' ', c.apellido) AS CLIENTE,
                tt.denominacion AS TRABAJO,
                dt.descripcion AS DESCRIPCION,
                CONCAT(DATE_FORMAT(dt.fechaEntrega, '%d/%m/%Y'), ' ', dt.horaEntrega) AS ENTREGA_PROMETIDA
            FROM detalle_trabajos dt
            INNER JOIN pedido_trabajos pt ON dt.id_pedido_trabajos = pt.idPedidoTrabajos
            INNER JOIN clientes c ON pt.idCliente = c.idCliente
            INNER JOIN tipo_trabajo tt ON dt.idTrabajo = tt.idTipoTrabajo
            WHERE dt.idEstadoTrabajo = 4
              AND dt.idActivo = 1
              AND pt.idActivo = 1
            ORDER BY dt.fechaEntrega ASC, dt.horaEntrega ASC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }

    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }

    $resultado = $stmt->get_result();
    $trabajos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $trabajos[] = [
            'ID' => $fila['ID'],
            'CLIENTE' => $fila['CLIENTE'],
            'TRABAJO' => $fila['TRABAJO'],
            'DESCRIPCION' => $fila['DESCRIPCION'],
            'ENTREGA_PROMETIDA' => $fila['ENTREGA_PROMETIDA']
        ];
    }

    return $trabajos;
}

    // Listas Facturas
function Listar_Tipos_Factura($vConexion) {
    $SQL = "SELECT idTipoFactura, denominacion FROM tipo_factura WHERE idActivo = 1";
    $resultado = mysqli_query($vConexion, $SQL);
    
    $tipos = array();
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $tipos[] = $fila;
        }
    }
    
    return $tipos;
}

// FUNCIONES MOVIMIENTOS

function Listar_Movimientos($conexion, $filtros = []) {
    $SQL = "SELECT 
                dc.idDetalleCaja,
                dc.monto,
                dc.observaciones,
                dc.facturado,
                dc.numeroFactura,
                dc.idCaja,
                dc.idTipoPago,
                dc.idTipoMovimiento,
                dc.idUsuario,
                dc.idTipoFactura,
                c.Fecha as fechaCaja,
                tp.denominacion as metodoPago,
                tm.denominacion as tipoMovimiento,
                u.nombre as usuarioNombre,
                u.apellido as usuarioApellido
            FROM detalle_caja dc
            INNER JOIN caja c ON dc.idCaja = c.idCaja
            INNER JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
            INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento
            INNER JOIN usuarios u ON dc.idUsuario = u.idUsuario
            WHERE 1=1";

    // aplicar filtros si se pasan
    if (!empty($filtros['desde'])) {
        $SQL .= " AND c.Fecha >= '" . mysqli_real_escape_string($conexion, $filtros['desde']) . "'";
    }
    if (!empty($filtros['hasta'])) {
        $SQL .= " AND c.Fecha <= '" . mysqli_real_escape_string($conexion, $filtros['hasta']) . "'";
    }
    if (!empty($filtros['tipo'])) {
        $SQL .= " AND tm.denominacion LIKE '%" . mysqli_real_escape_string($conexion, $filtros['tipo']) . "%'";
    }

    $SQL .= " ORDER BY c.Fecha DESC";

    $resultado = mysqli_query($conexion, $SQL);
    $movimientos = [];
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $movimientos[] = $fila;
        }
    }
    return $movimientos;
}

function Obtener_Resumen_Movimientos($conexion) {
    $resumen = [
        "entradas" => 0,
        "salidas" => 0,
        "balance" => 0,
        "caja" => 0
    ];

    $SQL = "SELECT tm.es_entrada, tm.es_salida, dc.monto
            FROM detalle_caja dc
            INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento";
    $resultado = mysqli_query($conexion, $SQL);

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            if ($fila['es_entrada']) {
                $resumen['entradas'] += $fila['monto'];
            }
            if ($fila['es_salida']) {
                $resumen['salidas'] += $fila['monto'];
            }
        }
    }

    $resumen['balance'] = $resumen['entradas'] - $resumen['salidas'];

    // sacar saldo de caja actual
    $SQLCaja = "SELECT SUM(dc.monto * (CASE WHEN tm.es_entrada=1 THEN 1 ELSE -1 END)) as saldo
                FROM detalle_caja dc
                INNER JOIN tipo_movimiento tm ON dc.idTipoMovimiento = tm.idTipoMovimiento";
    $resultadoCaja = mysqli_query($conexion, $SQLCaja);
    if ($resultadoCaja && $fila = mysqli_fetch_assoc($resultadoCaja)) {
        $resumen['caja'] = $fila['saldo'] ?? 0;
    }

    return $resumen;
}

?>