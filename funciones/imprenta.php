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

function InsertarProveedores($vConexion){
    
    $SQL_Insert="INSERT INTO proveedores (nombre, CUIT, contacto)
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
                u.usuario, 
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
    $SQL_MiConsulta = "SELECT idCaja FROM caja WHERE idCaja = $vIdCaja";

    $rs = mysqli_query($vConexion, $SQL_MiConsulta);

    $data = mysqli_fetch_array($rs);

    if (!empty($data['idCaja'])) {
        // Si la caja existe, eliminarla
        $SQL_Delete = "DELETE FROM caja WHERE idCaja = $vIdCaja";
        if (mysqli_query($vConexion, $SQL_Delete)) {
            return true; // Eliminación exitosa
        } else {
            return false; // Error al eliminar
        }
    } else {
        return false; // No se encontró la caja
    }
}

function Anular_Venta($vConexion, $vIdConsulta) {
    // Verificar si el registro existe en la tabla detalle_caja
    $SQL_MiConsulta = "SELECT idDetalleCaja FROM detalle_caja WHERE idDetalleCaja = $vIdConsulta";

    $rs = mysqli_query($vConexion, $SQL_MiConsulta);

    $data = mysqli_fetch_array($rs);

    if (!empty($data['idDetalleCaja'])) {
        // Si el registro existe, eliminarlo
        $SQL_Delete = "DELETE FROM detalle_caja WHERE idDetalleCaja = $vIdConsulta";
        if (mysqli_query($vConexion, $SQL_Delete)) {
            return true; // Eliminación exitosa
        } else {
            return false; // Error al eliminar
        }
    } else {
        return false; // No se encontró el registro
    }
}

function Modificar_Venta($vConexion) {
    $idDetalleCaja = mysqli_real_escape_string($vConexion, $_POST['idDetalleCaja']);
    $idCaja = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
    $idTipoPago = mysqli_real_escape_string($vConexion, $_POST['idTipoPago']);
    $idTipoMovimiento = mysqli_real_escape_string($vConexion, $_POST['idTipoMovimiento']);
    $idUsuario = mysqli_real_escape_string($vConexion, $_POST['idUsuario']);
    $monto = mysqli_real_escape_string($vConexion, $_POST['Monto']);
    $observaciones = isset($_POST['Observaciones']) ? mysqli_real_escape_string($vConexion, $_POST['Observaciones']) : null;

    $SQL_MiConsulta = "UPDATE detalle_caja
                       SET idCaja = '$idCaja',
                           idTipoPago = '$idTipoPago',
                           idTipoMovimiento = '$idTipoMovimiento',
                           idUsuario = '$idUsuario',
                           monto = '$monto',
                           observaciones = " . ($observaciones !== null ? "'$observaciones'" : "NULL") . "
                       WHERE idDetalleCaja = '$idDetalleCaja'";

    if (mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    } else {
        return false;
    }
}

function Validar_Venta() {
    $_SESSION['Mensaje'] = '';

    if (empty($_POST['idTipoPago'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de pago. <br />';
    }
    if (empty($_POST['idTipoMovimiento'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de entrada. <br />';
    }
    if (empty($_POST['Monto']) || !is_numeric($_POST['Monto']) || $_POST['Monto'] <= 0) {
        $_SESSION['Mensaje'] .= 'Debes ingresar un monto válido. <br />';
    }

    // Limpiar espacios y caracteres no deseados
    foreach ($_POST as $Id => $Valor) {
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Datos_Venta($vConexion, $vIdDetalleCaja) {
    $SQL = "SELECT * FROM detalle_caja WHERE idDetalleCaja = $vIdDetalleCaja";
    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_assoc($rs);
    if (!empty($data)) {
        return array(
            'idDetalleCaja' => $data['idDetalleCaja'],
            'idCaja' => $data['idCaja'],
            'idTipoPago' => $data['idTipoPago'],
            'idTipoMovimiento' => $data['idTipoMovimiento'],
            'Monto' => $data['monto'],
            'observaciones' => $data['observaciones'] 
        );
    }

    return array();
}

function InsertarMovimiento($vConexion) {
    // Preparar los valores para la inserción
    $idCaja = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
    $idTipoPago = mysqli_real_escape_string($vConexion, $_POST['idTipoPago']);
    $idTipoMovimiento = mysqli_real_escape_string($vConexion, $_POST['idTipoMovimiento']); // Nuevo campo
    $idUsuario = isset($_SESSION['Usuario_Id']) ? mysqli_real_escape_string($vConexion, $_SESSION['Usuario_Id']) : null;
    $monto = mysqli_real_escape_string($vConexion, $_POST['Monto']);
    $observaciones = !empty($_POST['Observaciones']) ? mysqli_real_escape_string($vConexion, $_POST['Observaciones']) : null;

    // Verificar que el idUsuario no sea nulo
    if ($idUsuario === null) {
        die('<h4>Error: No se encontró un usuario en la sesión.</h4>');
    }

    // Construir la consulta SQL
    $SQL_Insert = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoMovimiento, idUsuario, monto, observaciones)
                   VALUES ('$idCaja', '$idTipoPago', '$idTipoMovimiento', '$idUsuario', '$monto', " . 
                   ($observaciones !== null ? "'$observaciones'" : "NULL") . ")";

    // Ejecutar la consulta
    if (!mysqli_query($vConexion, $SQL_Insert)) {
        die('<h4>Error al intentar insertar la venta: ' . mysqli_error($vConexion) . '</h4>');
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

function ColorDeFilaTrabajo($vEstado) {
    $Title='';
    $Color=''; 

    if ($vEstado == '4'){
        //Estado pendiente
        $Title='Entregado';
        $Color='table-primary'; 
    
    } else if ($vEstado == '3'){
        //Estado listo para retirar
        $Title='Recibido';
        $Color='table-success'; 
    } else if ($vEstado == '2'){
        //Estado retirado
        $Title='Pedido';
        $Color='table-warning'; 
    } else if ($vEstado == '1'){
    //Estado retirado
    $Title='Para pedir';
    $Color='table-danger'; 
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

    // 1) Generar el WHERE según el criterio de búsqueda
    $whereClause = "";
    switch ($criterio) {
        case 'Fecha':
            // Se busca en la fecha del pedido principal
            $whereClause = "WHERE PT.fecha LIKE '%$parametro%'";
            break;
        case 'Id':
            // Se busca por el ID del pedido principal
            $whereClause = "WHERE PT.idPedidoTrabajos LIKE '%$parametro%'";
            break;
        case 'Estado':
            // Se busca por la denominación del estado del trabajo
            $whereClause = "WHERE ET.denominacion LIKE '%$parametro%'";
            break;
        case 'Cliente':
            // Búsqueda flexible por nombre y/o apellido del cliente
            $parametro = strtolower($parametro);
            $nombreApellido = explode(' ', $parametro);
            if (count($nombreApellido) >= 2) {
                // Si se ingresan dos palabras, busca combinaciones de nombre y apellido
                $whereClause = "WHERE 
                    (LOWER(C.nombre) LIKE '%" . $nombreApellido[0] . "%' AND LOWER(C.apellido) LIKE '%" . $nombreApellido[1] . "%') OR 
                    (LOWER(C.nombre) LIKE '%" . $nombreApellido[1] . "%' AND LOWER(C.apellido) LIKE '%" . $nombreApellido[0] . "%')";
            } else {
                // Si es una sola palabra, busca en nombre o apellido
                $whereClause = "WHERE 
                    LOWER(C.nombre) LIKE '%$parametro%' OR LOWER(C.apellido) LIKE '%$parametro%'";
            }
            break;
        case 'Telefono':
            // Búsqueda por el teléfono del cliente
            $whereClause = "WHERE C.telefono LIKE '%$parametro%'";
            break;
        default:
            // Por defecto, si no hay criterio, muestra solo los pedidos activos
            $whereClause = "WHERE PT.idActivo = 1";
            break;
    }

    // 2) Construir la consulta SQL principal
    // Se incorpora el LEFT JOIN y las funciones de agregación (SUM y COUNT)
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

    // 3) Ejecutar la consulta
    $rs = mysqli_query($vConexion, $SQL);

    // 4) Manejo de errores en la consulta
    if (!$rs) {
        // Es buena práctica registrar el error en lugar de solo mostrarlo
        error_log("Error en la consulta SQL: " . mysqli_error($vConexion));
        die("Ocurrió un error al obtener los datos. Por favor, intente más tarde.");
    }

    // 5) Recorrer los resultados y almacenarlos en el array
    $i = 0;
    while ($data = mysqli_fetch_assoc($rs)) {
        $Listado[$i]['ID'] = $data['idPedidoTrabajos'];
        $Listado[$i]['CLIENTE_N'] = $data['nombre'];
        $Listado[$i]['CLIENTE_A'] = $data['apellido'];
        $Listado[$i]['TELEFONO'] = $data['telefono']; // Se añade el teléfono al resultado
        $Listado[$i]['FECHA'] = $data['fecha'];
        $Listado[$i]['PRECIO'] = $data['precio_total']; // Se usa el precio calculado
        $Listado[$i]['SEÑA'] = $data['senia'];
        $Listado[$i]['ESTADO'] = $data['idEstado'];
        $Listado[$i]['USUARIO'] = $data['usuario'];
        $Listado[$i]['ESTADO_NOMBRE'] = $data['estado_nombre'];
        $Listado[$i]['CANTIDAD_TRABAJOS'] = $data['cantidad_trabajos']; // Se añade la cantidad de trabajos
        $i++;
    }

    // 6) Devolver el listado final
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
                ET.denominacion AS ESTADO
            FROM detalle_trabajos DT
            INNER JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
            INNER JOIN estado_trabajo ET ON DT.idEstadoTrabajo = ET.idEstado
            INNER JOIN proveedores P ON DT.idProveedor = P.idProveedor
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
            'ESTADO' => $data['ESTADO']
        );
    }
    return $detalles;
}

function Modificar_Senia_Pedido($conexion, $idPedido, $nuevaSenia){
    // Validación adicional de parámetros
    if ($idPedido <= 0) {
        error_log("ID de pedido inválido: $idPedido");
        return false;
    }
    
    if ($nuevaSenia < 0) {
        error_log("Intento de establecer seña negativa: $nuevaSenia");
        return false;
    }

    try {
        // Iniciar transacción para mayor seguridad
        $conexion->begin_transaction();

        $query = "UPDATE pedido_trabajos SET senia = ? WHERE idPedidoTrabajos = ?";
        $stmt = $conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error al preparar la consulta: " . $conexion->error);
            $conexion->rollback();
            return false;
        }
        
        $stmt->bind_param('di', $nuevaSenia, $idPedido);
        $resultado = $stmt->execute();
        $stmt->close();
        
        if (!$resultado) {
            error_log("Error al ejecutar la actualización: " . $conexion->error);
            $conexion->rollback();
            return false;
        }
        
        $conexion->commit();
        return true;
        
    } catch (Exception $e) {
        error_log("Excepción al modificar seña: " . $e->getMessage());
        $conexion->rollback();
        return false;
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
                $query = "UPDATE detalle_trabajos SET 
                         idTrabajo = ?, 
                         precio = ?, 
                         fechaEntrega = ?, 
                         horaEntrega = ?, 
                         descripcion = ?,
                         idProveedor = ?,
                         idEstadoTrabajo = ?
                         WHERE idDetalleTrabajo = ?";
                
                $stmt = $conexion->prepare($query);
                if (!$stmt) {
                    error_log("Error al preparar la consulta: " . $conexion->error);
                    return false;
                }
                
                $stmt->bind_param('idsssiii', 
                    $datos['idTrabajo'], 
                    $datos['precio'], 
                    $datos['fechaEntrega'],
                    $datos['horaEntrega'],
                    $datos['descripcion'],
                    $datos['idProveedor'],
                    $datos['idEstadoTrabajo'],
                    $datos['idDetalle']
                );
                break;
                
            case 'agregar':
                $query = "INSERT INTO detalle_trabajos (
                    id_pedido_trabajos, 
                    idTrabajo, 
                    precio, 
                    fechaEntrega, 
                    horaEntrega, 
                    descripcion,
                    idProveedor,
                    idEstadoTrabajo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conexion->prepare($query);
                if (!$stmt) {
                    error_log("Error al preparar la consulta: " . $conexion->error);
                    return false;
                }
                
                $stmt->bind_param('iidsssii',
                    $datos['id_pedido_trabajos'],
                    $datos['idTrabajo'],
                    $datos['precio'],
                    $datos['fechaEntrega'],
                    $datos['horaEntrega'],
                    $datos['descripcion'],
                    $datos['idProveedor'],
                    $datos['idEstadoTrabajo']
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
        
        return $filasAfectadas > 0;
        
    } catch (Exception $e) {
        error_log("Excepción al procesar detalle: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        return false;
    }
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

?>