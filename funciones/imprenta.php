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

function Listar_Tipos_Pagos($conexion) {
    $sql = "SELECT idTipoPago, denominacion FROM tipo_pago WHERE idActivo = 1";
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
    
    $SQL_Insert="INSERT INTO tipo_pago (denominacion)
    VALUES ('".$_POST['Denominacion']."')";


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

    $data = mysqli_fetch_array($rs);
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
    $sql = "SELECT caja.*, turnos.denominacion 
            FROM caja 
            LEFT JOIN turnos ON caja.idTurno = turnos.idTurno 
            ORDER BY caja.Fecha DESC";
            
    $resultado = $Conexion->query($sql);
    $Listado = [];
    while ($fila = $resultado->fetch_assoc()) {
        $Listado[] = $fila;
    }
    return $Listado;
}

function Listar_Cajas_Parametro($Conexion, $Criterio, $Parametro) {
    $sql = "SELECT caja.*, turnos.denominacion 
            FROM caja 
            LEFT JOIN turnos ON caja.idTurno = turnos.idTurno 
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
    $sql = "SELECT c.Fecha, t.denominacion AS Turno 
            FROM caja c
            INNER JOIN turnos t ON c.idTurno = t.idTurno
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

function InsertarCaja($vConexion, $Fecha, $idTurno, $cajaInicial) {

    // Verificar si ya existe una caja para la misma fecha y turno
    $SQL_Verificar = "SELECT COUNT(*) AS total FROM caja WHERE DATE(Fecha) = '$Fecha' AND idTurno = $idTurno";
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

    // Ejecutar la inserción
    $SQL_Insert = "INSERT INTO caja (Fecha, idTurno, cajaInicial) VALUES ('$Fecha', $idTurno, $cajaInicial)";
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
    if (strlen($_POST['idTurno']) < 1) {
        $_SESSION['Mensaje'].='Debes seleccionar un turno. <br />';
    }
    if (strlen($_POST['cajaIncial']) < 0) {
        $_SESSION['Mensaje'].='Debes pober una caja inicial. <br />';
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
        $DatosCaja['IDTURNO'] = $data['idTurno'];
        $DatosCaja['CAJA_INICIAL'] = $data['cajaInicial'];
    }
    return $DatosCaja;
}

function ObtenerDetallesCaja($vConexion, $idCaja) {
    $query = "SELECT dc.idDetalleCaja, dc.idTipoOperacion, dc.idCaja, tp.denominacion AS metodoPago, 
                     CASE 
                         WHEN dc.idTipoOperacion = 2 AND tr.denominacion IS NOT NULL THEN tr.denominacion
                         WHEN ts.denominacion IS NOT NULL THEN ts.denominacion
                         ELSE 'No especificado'
                     END AS detalle,
                     u.usuario, dc.monto, dc.observaciones
              FROM detalle_caja dc
              JOIN tipo_pago tp ON dc.idTipoPago = tp.idTipoPago
              JOIN usuarios u ON dc.idUsuario = u.idUsuario
              LEFT JOIN tipo_servicio ts ON (dc.idTipoServicio = ts.idTipoServicio AND dc.idTipoOperacion != 2)
              LEFT JOIN tipo_retiro tr ON (dc.idTipoRetiro = tr.idTipoRetiro AND dc.idTipoOperacion = 2)
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
    $idTurno = mysqli_real_escape_string($vConexion, $_POST['idTurno']);
    $cajaInicial = mysqli_real_escape_string($vConexion, $_POST['cajaInicial']);

    // Verificar si ya existe una caja para la misma fecha y turno, excluyendo la caja actual
    $SQL_Verificar = "SELECT COUNT(*) AS total 
                      FROM caja 
                      WHERE DATE(Fecha) = '$Fecha' 
                      AND idTurno = $idTurno 
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
                           idTurno = $idTurno,
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
    $idTipoServicio = mysqli_real_escape_string($vConexion, $_POST['idTipoServicio']);
    $idUsuario = mysqli_real_escape_string($vConexion, $_POST['idUsuario']);
    $monto = mysqli_real_escape_string($vConexion, $_POST['Monto']);

    $SQL_MiConsulta = "UPDATE detalle_caja
                       SET idCaja = '$idCaja',
                           idTipoPago = '$idTipoPago',
                           idTipoServicio = '$idTipoServicio',
                           idUsuario = '$idUsuario',
                           monto = '$monto'
                       WHERE idDetalleCaja = '$idDetalleCaja'";

    if (mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true; // Modificación exitosa
    } else {
        return false; // Error al modificar
    }
}

function Validar_Venta() {
    $_SESSION['Mensaje'] = '';

    if (empty($_POST['idTipoPago'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de pago. <br />';
    }
    if (empty($_POST['idDetalle'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de servicio. <br />';
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
            'idTipoServicio' => $data['idTipoServicio'], // Asegúrate de incluir este campo
            'Monto' => $data['monto']
        );
    }

    return array();
}

function InsertarVenta($vConexion) {
    // Preparar los valores para la inserción
    $idCaja = mysqli_real_escape_string($vConexion, $_POST['idCaja']);
    $idTipoPago = mysqli_real_escape_string($vConexion, $_POST['idTipoPago']);
    $idTipoServicio = null; // Inicializar idTipoServicio como null
    $idTipoRetiro = null; // Inicializar idTipoRetiro como null
    $idDetalle = mysqli_real_escape_string($vConexion, $_POST['idDetalle']);
    $idUsuario = isset($_SESSION['Usuario_Id']) ? mysqli_real_escape_string($vConexion, $_SESSION['Usuario_Id']) : null;
    $monto = mysqli_real_escape_string($vConexion, $_POST['Monto']);
    $idTipoOperacion = mysqli_real_escape_string($vConexion, $_POST['idTipoOperacion']);
    $observaciones = !empty($_POST['Observaciones']) ? mysqli_real_escape_string($vConexion, $_POST['Observaciones']) : null;

    // Si idTipoOperacion es 3, establecer idCaja como 0
    $idCaja = ($idTipoOperacion == 3) ? "0" : "'$idCaja'";

    // Si idTipoOperacion es 2, usar idDetalle como idTipoRetiro
    if ($idTipoOperacion == 2) {
        $idTipoRetiro = $idDetalle;
        $idTipoServicio = "NULL"; // Asegurarse de que idTipoServicio sea NULL
    } else {
        $idTipoServicio = $idDetalle;
        $idTipoRetiro = "NULL"; // Asegurarse de que idTipoRetiro sea NULL si no es operación 2
    }

    // Verificar que el idUsuario no sea nulo
    if ($idUsuario === null) {
        die('<h4>Error: No se encontró un usuario en la sesión.</h4>');
    }

    // Construir la consulta SQL
    $SQL_Insert = "INSERT INTO detalle_caja (idCaja, idTipoPago, idTipoServicio, idTipoRetiro, idUsuario, monto, idTipoOperacion, observaciones)
                   VALUES ($idCaja, '$idTipoPago', $idTipoServicio, $idTipoRetiro, '$idUsuario', '$monto', '$idTipoOperacion', " . 
                   ($observaciones !== null ? "'$observaciones'" : "NULL") . ")";

    // Ejecutar la consulta
    if (!mysqli_query($vConexion, $SQL_Insert)) {
        die('<h4>Error al intentar insertar la venta: ' . mysqli_error($vConexion) . '</h4>');
    }

    return true;
}

function ColorDeFilaCaja($vTipoOperacion) {
    $Title='';
    $Color=''; 

    if ($vTipoOperacion===1){
        //Es una entrada
        $Title='Entrada';
        $Color='table-success'; 
    
    } else if ($vTipoOperacion===2){
        //Es una salida
        $Title='Salida';
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

    // 1) Consulta adaptada a pedido_trabajos y estado_trabajo
    $SQL = "SELECT C.nombre, C.apellido, PT.idPedidoTrabajos, PT.fecha, PT.precioTotal, PT.senia, ET.idEstado, US.usuario, ET.denominacion AS estado_nombre
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_trabajo ET ON PT.idEstado = ET.idEstado
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            WHERE PT.idActivo = 1
            ORDER BY PT.idPedidoTrabajos DESC";

    // 2) Ejecutar la consulta
    $rs = mysqli_query($vConexion, $SQL);

    // 3) Organizar el resultado en un array
    $i = 0;
    while ($data = mysqli_fetch_array($rs)) {
        $Listado[$i]['ID'] = $data['idPedidoTrabajos'];
        $Listado[$i]['CLIENTE_N'] = $data['nombre'];
        $Listado[$i]['CLIENTE_A'] = $data['apellido'];
        $Listado[$i]['FECHA'] = $data['fecha'];
        $Listado[$i]['PRECIO'] = $data['precioTotal'];
        $Listado[$i]['SEÑA'] = $data['senia'];
        $Listado[$i]['ESTADO'] = $data['idEstado'];
        $Listado[$i]['USUARIO'] = $data['usuario'];
        $Listado[$i]['ESTADO_NOMBRE'] = $data['estado_nombre'];
        $i++;
    }

    // Devolver el listado generado
    return $Listado;
}

function Listar_Pedidos_Trabajo_Parametro($vConexion, $criterio, $parametro) {
    $Listado = array();

    // 1) Generar el WHERE según el criterio
    switch ($criterio) {
        case 'Fecha':
            $whereClause = "WHERE PT.fecha LIKE '%$parametro%'";
            break;
        case 'Id':
            $whereClause = "WHERE PT.idPedidoTrabajos LIKE '%$parametro%'";
            break;
        case 'Estado':
            $whereClause = "WHERE ET.denominacion LIKE '%$parametro%'";
            break;
        case 'Cliente':
            $parametro = strtolower($parametro);
            $nombreApellido = explode(' ', $parametro);
            if (count($nombreApellido) == 2) {
                $whereClause = "WHERE 
                    (LOWER(C.nombre) LIKE '%" . $nombreApellido[0] . "%' 
                    AND LOWER(C.apellido) LIKE '%" . $nombreApellido[1] . "%') 
                    OR 
                    (LOWER(C.nombre) LIKE '%" . $nombreApellido[1] . "%' 
                    AND LOWER(C.apellido) LIKE '%" . $nombreApellido[0] . "%')";
            } else {
                $whereClause = "WHERE 
                    LOWER(C.nombre) LIKE '%$parametro%' 
                    OR 
                    LOWER(C.apellido) LIKE '%$parametro%'";
            }
            break;
        case 'Telefono': // Nuevo caso para búsqueda por teléfono
            $whereClause = "WHERE C.telefono LIKE '%$parametro%'";
            break;
        default:
            $whereClause = "WHERE PT.idActivo = 1";
            break;
    }

    // 2) Construir la consulta SQL con el filtro dinámico
    $SQL = "SELECT 
                C.nombre, 
                C.apellido, 
                C.telefono,
                PT.idPedidoTrabajos, 
                PT.fecha, 
                PT.precioTotal, 
                PT.senia, 
                ET.idEstado, 
                US.usuario, 
                ET.denominacion AS estado_nombre
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            INNER JOIN estado_trabajo ET ON PT.idEstado = ET.idEstado
            INNER JOIN usuarios US ON PT.idUsuario = US.idUsuario
            $whereClause
            ORDER BY PT.fecha DESC, C.nombre";

    // 3) Ejecutar la consulta
    $rs = mysqli_query($vConexion, $SQL);

    // 4) Verificar si la consulta tuvo resultados
    if (!$rs) {
        die("Error en la consulta: " . mysqli_error($vConexion));
    }

    // 5) Recorro los resultados y los organizo en un array
    $i = 0;
    while ($data = mysqli_fetch_array($rs)) {
        $Listado[$i]['ID'] = $data['idPedidoTrabajos'];
        $Listado[$i]['CLIENTE_N'] = $data['nombre'];
        $Listado[$i]['CLIENTE_A'] = $data['apellido'];
        $Listado[$i]['FECHA'] = $data['fecha'];
        $Listado[$i]['PRECIO'] = $data['precioTotal'];
        $Listado[$i]['SEÑA'] = $data['senia'];
        $Listado[$i]['ESTADO'] = $data['idEstado'];
        $Listado[$i]['USUARIO'] = $data['usuario'];
        $Listado[$i]['ESTADO_NOMBRE'] = $data['estado_nombre'];
        $i++;
    }

    // 6) Devuelvo el listado generado
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

function Datos_Pedido_Trabajo($conexion, $idPedido) {
    $sql = "SELECT 
                PT.idPedidoTrabajos,
                PT.fecha,
                PT.precioTotal,
                PT.senia,
                C.nombre AS CLIENTE,
                C.apellido AS CLIENTE_A,
                C.telefono AS TELEFONO
            FROM pedido_trabajos PT
            INNER JOIN clientes C ON PT.idCliente = C.idCliente
            WHERE PT.idPedidoTrabajos = " . intval($idPedido) . " LIMIT 1";
    $rs = mysqli_query($conexion, $sql);
    $data = mysqli_fetch_assoc($rs);

    // Adaptar nombres para el PDF
    return array(
        'ID' => $data['idPedidoTrabajos'],
        'FECHA' => $data['fecha'],
        'PRECIO_TOTAL' => $data['precioTotal'],
        'SENIA' => $data['senia'],
        'CLIENTE' => $data['CLIENTE'],
        'CLIENTE_A' => $data['CLIENTE_A'],
        'TELEFONO' => $data['TELEFONO']
    );
}

function Detalles_Pedido_Trabajo($conexion, $idPedido) {
    $sql = "SELECT 
                DT.idDetalleTrabajo,
                TT.denominacion AS TRABAJO,
                DT.descripcion AS DESCRIPCION,
                DT.fechaEntrega AS FECHA_ENTREGA,
                DT.horaEntrega AS HORA_ENTREGA,
                DT.precio AS PRECIO,
                ET.denominacion AS ESTADO
            FROM detalle_trabajos DT
            INNER JOIN tipo_trabajo TT ON DT.idTrabajo = TT.idTipoTrabajo
            INNER JOIN estado_trabajo ET ON DT.idEstadoTrabajo = ET.idEstado
            WHERE DT.id_pedido_trabajos = " . intval($idPedido) . " AND DT.idActivo = 1";
    $rs = mysqli_query($conexion, $sql);

    $detalles = array();
    while ($data = mysqli_fetch_assoc($rs)) {
        $detalles[] = array(
            'TRABAJO' => $data['TRABAJO'],
            'DESCRIPCION' => $data['DESCRIPCION'],
            'FECHA_ENTREGA' => $data['FECHA_ENTREGA'],
            'HORA_ENTREGA' => $data['HORA_ENTREGA'],
            'PRECIO' => $data['PRECIO'],
            'ESTADO' => $data['ESTADO']
        );
    }
    return $detalles;
}

?>