<?php
function InsertarClientes($vConexion){
    
    $SQL_Insert="INSERT INTO clientes (nombre, apellido, dni, telefono)
    VALUES ('".$_POST['Nombre']."' , '".$_POST['Apellido']."' , '".$_POST['DNI']."', '".$_POST['Telefono']."')";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
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
            $Listado[$i]['DNI'] = $data['dni'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
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

function Listar_Clientes_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo segun el parametro
        $sql = "SELECT * FROM clientes";
        switch ($criterio) { 
        case 'Nombre': 
        $sql = "SELECT * FROM clientes WHERE nombre LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'Apellido':
        $sql = "SELECT * FROM clientes WHERE apellido LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'Telefono':
        $sql = "SELECT * FROM clientes WHERE telefono LIKE '%$parametro%' AND idActivo = 1";
        break;
        case 'DNI':
        $sql = "SELECT * FROM clientes WHERE dni LIKE '%$parametro%' AND idActivo = 1";
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
            $Listado[$i]['DNI'] = $data['dni'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
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
        $DatosCliente['DNI'] = $data['dni'];
    }
    return $DatosCliente;

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

function Validar_Cliente(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Nombre']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un nombre con al menos 3 caracteres. <br />';
    }
    if (strlen($_POST['Apellido']) < 3) {
        $_SESSION['Mensaje'].='Debes ingresar un apellido con al menos 3 caracteres. <br />';
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

function Modificar_Cliente($vConexion) {
    $nombre = mysqli_real_escape_string($vConexion, $_POST['Nombre']);
    $apellido = mysqli_real_escape_string($vConexion, $_POST['Apellido']);
    $telefono = mysqli_real_escape_string($vConexion, $_POST['Telefono']);
    $dni = mysqli_real_escape_string($vConexion, $_POST['DNI']);
    $idCliente = mysqli_real_escape_string($vConexion, $_POST['IdCliente']);

    $SQL_MiConsulta = "UPDATE clientes 
    SET nombre = '$nombre',
    apellido = '$apellido',
    telefono = '$telefono',
    dni = '$dni'
    WHERE idCliente = '$idCliente'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
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
    $_SESSION['Mensaje']='';
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

function Datos_Libro($vConexion , $vIdLibro) {
    
    // Determinar en qué tabla está el ID
    $tabla = null;
    if (existeEnTabla($vConexion, 'libros', $vIdLibro)) {
        $tabla = 'libros';
    } elseif (existeEnTabla($vConexion, 'librosleas', $vIdLibro)) {
        $tabla = 'librosleas';
    } elseif (existeEnTabla($vConexion, 'librossbs', $vIdLibro)) {
        $tabla = 'librossbs';
    }

    $DatosLibro  =   array();
    //me aseguro que la consulta exista

    $SQL = "SELECT * FROM $tabla 
            WHERE IdLibros = $vIdLibro";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosLibro['ID_LIBRO'] = $data['idLibros'];
        $DatosLibro['ISBN'] = $data['isbn'];
        $DatosLibro['TITULO'] = $data['titulo'];
        $DatosLibro['AUTOR'] = $data['autor'];
        $DatosLibro['EDITORIAL'] = $data['editorial'];
        $DatosLibro['PRECIO'] = $data['precio'];
    }
    return $DatosLibro;

}

function Listar_Libros($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT idLibros, codigo, titulo, editorial, precio, 'Personal' AS mayorista FROM libros 
        UNION ALL 
        SELECT idLibros, codigo, titulo, editorial, precio, 'SBS' AS mayorista FROM librossbs 
        UNION ALL 
        SELECT idLibros, codigo, titulo, editorial, precio, 'LEAS' AS mayorista FROM librosleas
        LIMIT 1000";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_LIBRO'] = $data['idLibros'];
            $Listado[$i]['CODIGO'] = $data['codigo'];
            $Listado[$i]['TITULO'] = $data['titulo'];
            $Listado[$i]['EDITORIAL'] = $data['editorial'];
            $Listado[$i]['PRECIO'] = $data['precio'];
            $Listado[$i]['MAYORISTA'] = $data['mayorista'];
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

function Listar_Libros_Parametro($vConexion,$criterio,$parametro) {
    $Listado=array();

      //1) genero la consulta que deseo segun el parametro
      switch ($criterio) { 
        case 'Titulo': 
            $whereClause = "WHERE titulo LIKE '%$parametro%'"; 
            break; 
        case 'Codigo': 
            $whereClause = "WHERE codigo LIKE '%$parametro%'"; 
            break; 
        case 'Editorial': 
            $whereClause = "WHERE editorial LIKE '%$parametro%'";
            break;
        default: 
            $whereClause = ""; 
            break; 
        }

        $sql = "SELECT idLibros, codigo, titulo, editorial, precio, 'Personal' AS mayorista FROM libros $whereClause 
        UNION ALL 
        SELECT idLibros, codigo, titulo, editorial, precio, 'SBS' AS mayorista FROM librossbs $whereClause 
        UNION ALL 
        SELECT idLibros, codigo, titulo, editorial, precio, 'LEAS' AS mayorista FROM librosleas $whereClause 
        LIMIT 1000";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $sql);
        
        //3) el resultado deberá organizarse en una matriz, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID_LIBRO'] = $data['idLibros'];
            $Listado[$i]['CODIGO'] = $data['codigo'];
            $Listado[$i]['TITULO'] = $data['titulo'];
            $Listado[$i]['EDITORIAL'] = $data['editorial'];
            $Listado[$i]['PRECIO'] = $data['precio'];
            $Listado[$i]['MAYORISTA'] = $data['mayorista'];
            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

// Función para verificar si un ID existe en una tabla
function existeEnTabla($conexion, $tabla, $idLibro) {
    $query = "SELECT COUNT(*) as count FROM $tabla WHERE idLibros = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $idLibro);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

function Modificar_Libros($vConexion) {
    $isbn = mysqli_real_escape_string($vConexion, $_POST['ISBN']);
    $autor = mysqli_real_escape_string($vConexion, $_POST['Autor']);
    $titulo = mysqli_real_escape_string($vConexion, $_POST['Titulo']);
    $editorial = mysqli_real_escape_string($vConexion, $_POST['Editorial']);
    $precio = mysqli_real_escape_string($vConexion, $_POST['Precio']);
    $idLibro = mysqli_real_escape_string($vConexion, $_POST['IdLibro']);

    // Determinar en qué tabla está el ID
    $tabla = null;
    if (existeEnTabla($vConexion, 'libros', $idLibro)) {
        $tabla = 'libros';
    } elseif (existeEnTabla($vConexion, 'librosleas', $idLibro)) {
        $tabla = 'librosleas';
    } elseif (existeEnTabla($vConexion, 'librossbs', $idLibro)) {
        $tabla = 'librossbs';
    }

    $SQL_MiConsulta = "UPDATE $tabla 
    SET isbn = '$isbn',
    titulo = '$titulo',
    autor = '$autor',
    editorial = '$editorial',
    precio = '$precio'
    WHERE idLibros = '$idLibro'";

    if ( mysqli_query($vConexion, $SQL_MiConsulta) != false) {
        return true;
    }else {
        return false;
    }
    
}

function Eliminar_Libro($vConexion , $vIdConsulta) {


    //soy admin 
        $SQL_MiConsulta="SELECT idLibros FROM libros 
                        WHERE idLibros = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idLibros']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "DELETE FROM libros WHERE idLibros = $vIdConsulta");
        return true;

    }else {
        return false;
    }
    
}

function Listar_Pedidos($vConexion) {

    $Listado=array();

      //1) genero la consulta que deseo
        $SQL = "SELECT C.nombre, C.apellido, PL.idPedidoLibros, PL.fecha, PL.precioTotal,PL.descuento, PL.senia, E.idEstado
        FROM pedido_libros PL, clientes C, estado E
        WHERE PL.idCliente=C.idCliente AND PL.idEstado=E.idEstado AND PL.idActivo=1
        ORDER BY PL.fecha DESC, C.nombre";

        //2) a la conexion actual le brindo mi consulta, y el resultado lo entrego a variable $rs
        $rs = mysqli_query($vConexion, $SQL);
        
        //3) el resultado deberá organizarse en un vector, entonces lo recorro
        $i=0;
        while ($data = mysqli_fetch_array($rs)) {
            $Listado[$i]['ID'] = $data['idPedidoLibros'];
            $Listado[$i]['CLIENTE_N'] = $data['nombre'];
            $Listado[$i]['CLIENTE_A'] = $data['apellido'];
            $Listado[$i]['FECHA'] = $data['fecha'];
            $Listado[$i]['TITULO'] = 'en proceso';
            $Listado[$i]['EDITORIAL'] = 'en proceso';
            $Listado[$i]['PRECIO'] = $data['precioTotal'];
            $Listado[$i]['DESCUENTO'] = $data['descuento'];
            $Listado[$i]['SEÑA'] = $data['senia'];
            $Listado[$i]['ESTADO'] = $data['idEstado'];

            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
    return $Listado;
}

function Listar_Pedidos_Parametro($vConexion, $criterio, $parametro) {
    $Listado = array();

    // 1) Genero la consulta que deseo según el parámetro
    switch ($criterio) {
        case 'Fecha':
            $whereClause = "WHERE PL.fecha LIKE '%$parametro%'";
            break;
        case 'Id':
            $whereClause = "WHERE PL.idPedidoLibros LIKE '%$parametro%'";
            break;
        case 'Estado':
            $whereClause = "WHERE E.idEstado LIKE '%$parametro%'";
            break;
        case 'Cliente': // Nueva opción para buscar por nombre o apellido del cliente
            $parametro = strtolower($parametro); // Convertir el parámetro a minúsculas
            // Dividir el parámetro en nombre y apellido
            $nombreApellido = explode(' ', $parametro);
            if (count($nombreApellido) == 2) {
                // Si se encuentran dos partes (nombre y apellido), buscar ambas combinaciones
                $whereClause = "WHERE 
                    (LOWER(C.nombre) LIKE '%" . $nombreApellido[0] . "%' AND LOWER(C.apellido) LIKE '%" . $nombreApellido[1] . "%') 
                    OR 
                    (LOWER(C.nombre) LIKE '%" . $nombreApellido[1] . "%' AND LOWER(C.apellido) LIKE '%" . $nombreApellido[0] . "%')";
            } else {
                // Si no se separan en dos palabras, buscar solo en uno de los campos
                $whereClause = "WHERE 
                    LOWER(C.nombre) LIKE '%$parametro%' 
                    OR 
                    LOWER(C.apellido) LIKE '%$parametro%'";
            }
            break;
        default:
            $whereClause = "WHERE PL.idActivo = 1"; // Filtro por defecto
            break;
    }

    // 2) Construyo la consulta SQL con el filtro dinámico
    $sql = "SELECT C.nombre, C.apellido, PL.idPedidoLibros, PL.fecha, PL.precioTotal, PL.descuento, PL.senia, E.idEstado
            FROM pedido_libros PL
            INNER JOIN clientes C ON PL.idCliente = C.idCliente
            INNER JOIN estado E ON PL.idEstado = E.idEstado
            $whereClause
            ORDER BY PL.fecha DESC, C.nombre";

    // 3) Ejecuto la consulta
    $rs = mysqli_query($vConexion, $sql);

    // 4) Verifico si la consulta tuvo resultados
    if (!$rs) {
        die("Error en la consulta: " . mysqli_error($vConexion));
    }

    // 5) Recorro los resultados y los organizo en un array
    $i = 0;
    while ($data = mysqli_fetch_array($rs)) {
        $Listado[$i]['ID'] = $data['idPedidoLibros'];
        $Listado[$i]['CLIENTE_N'] = $data['nombre'];
        $Listado[$i]['CLIENTE_A'] = $data['apellido'];
        $Listado[$i]['FECHA'] = $data['fecha'];
        $Listado[$i]['TITULO'] = 'en proceso'; // Campo pendiente de implementación
        $Listado[$i]['EDITORIAL'] = 'en proceso'; // Campo pendiente de implementación
        $Listado[$i]['PRECIO'] = $data['precioTotal'];
        $Listado[$i]['DESCUENTO'] = $data['descuento'];
        $Listado[$i]['SEÑA'] = $data['senia'];
        $Listado[$i]['ESTADO'] = $data['idEstado'];

        $i++;
    }

    // 6) Devuelvo el listado generado
    return $Listado;
}

function Contar_Pedidos($vConexion, $id) {
    // Genero la consulta que deseo
    $SQL = "SELECT COUNT(*) AS total 
            FROM detalle_pedido 
            WHERE id_pedido_libros = '$id'";

    $rs = mysqli_query($vConexion, $SQL);

    // Verificar si la consulta se ejecutó correctamente
    if ($rs) {
        // Obtener el resultado
        $row = mysqli_fetch_assoc($rs);
        return $row['total'];
    } else {
        // En caso de error, retornar 0 o manejar el error según tus necesidades
        return 0;
    }
}

function Datos_Pedido($vConexion , $vIdPedido) {
    $DatosPedido  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT PL.idPedidoLibros, PL.fecha, PL.precioTotal, PL.senia, L.autor,
        L.titulo, C.nombre, C.apellido, C.telefono
        FROM pedido_libros PL, clientes C, libros L
        WHERE PL.idCliente=C.idCliente AND PL.idLibro=L.idLibros
        AND idPedidoLibros = $vIdPedido";

    $rs = mysqli_query($vConexion, $SQL);

    $data = mysqli_fetch_array($rs) ;
    if (!empty($data)) {
        $DatosPedido['ID_PEDIDO'] = $data['idPedidoLibros'];
        $DatosPedido['NOMBRE_CLIENTE'] = $data['nombre'];
        $DatosPedido['APELLIDO_CLIENTE'] = $data['apellido'];
        $DatosPedido['TELEFONO_CLIENTE'] = $data['telefono'];
        $DatosPedido['TITULO'] = $data['titulo'];
        $DatosPedido['AUTOR'] = $data['autor'];
        $DatosPedido['FECHA'] = $data['fecha'];
        $DatosPedido['PRECIO'] = $data['precio'];
        $DatosPedido['SEÑA'] = $data['seña'];
    }
    return $DatosPedido;

}

function InsertarPedido($vConexion){
    
    $SQL_Insert="INSERT INTO pedido_libros (idCliente, fecha, tituloLibro, autorLibro, precio, seña, idEstado)
    VALUES ('".$_POST['Cliente']."' , CURDATE(), '".$_POST['Titulo']."', '".$_POST['Autor']."', '".$_POST['Precio']."', '".$_POST['Seña']."', 1)";


    if (!mysqli_query($vConexion, $SQL_Insert)) {
        //si surge un error, finalizo la ejecucion del script con un mensaje
        die('<h4>Error al intentar insertar el registro.</h4>');
    }

    return true;
}

function Validar_Pedidos(){
    $_SESSION['Mensaje']='';
    if (strlen($_POST['Cliente']) == 'Selecciona una opcion') {
        $_SESSION['Mensaje'].='Debes seleccionar un Cliente. <br />';
    }
    if (strlen($_POST['Libro']) == 'Selecciona una opcion') {
        $_SESSION['Mensaje'].='Debes seleccionar un Libro. <br />';
    }
    if (strlen($_POST['Seña']) < 1) {
        $_SESSION['Mensaje'].='Debes ingresar una seña. <br />';
    }
    
    //con esto aseguramos que limpiamos espacios y limpiamos de caracteres de codigo ingresados
    foreach($_POST as $Id=>$Valor){
        $_POST[$Id] = trim($_POST[$Id]);
        $_POST[$Id] = strip_tags($_POST[$Id]);
    }

    return $_SESSION['Mensaje'];
}

function Datos_Pedidos($conexion, $idPedido) {
    $query = "SELECT p.idPedidoLibros AS ID_PEDIDO, c.nombre AS CLIENTE, c.apellido AS CLIENTE_A, c.telefono AS TELEFONO,p.fecha AS FECHA, p.precioTotal AS PRECIO_TOTAL, p.senia AS SENIA, p.descuento AS DESCUENTO, e.denominacion AS ESTADO
              FROM pedido_libros p
              INNER JOIN clientes c ON p.idCliente = c.idCliente
              INNER JOIN estado e ON p.idEstado = e.idEstado
              WHERE p.idPedidoLibros = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function Detalles_Pedido($conexion, $idPedido) {
    $query = "
        SELECT 
            d.idDetallePedido AS ID_DETALLE, 
            COALESCE(l.titulo, leas.titulo, sbs.titulo) AS LIBRO_T,
            COALESCE(l.editorial, leas.editorial, sbs.editorial) AS LIBRO_E,  
            d.precio_pedido AS PRECIO, 
            d.cantidad AS CANTIDAD, 
            d.idEstado AS ESTADO,
            d.idProveedor
        FROM detalle_pedido d
        LEFT JOIN libros l ON d.idLibro = l.idLibros
        LEFT JOIN librosleas leas ON d.idLibro = leas.idLibros
        LEFT JOIN librossbs sbs ON d.idLibro = sbs.idLibros
        WHERE d.id_pedido_libros = ?
    ";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();
    $result = $stmt->get_result();
    $detalles = array();
    while ($row = $result->fetch_assoc()) {
        $detalles[] = $row;
    }
    return $detalles;
}

function Modificar_Detalles_Pedido($conexion, $datos) {

    // Obtener la seña actual de la tabla pedido_libros
    $query = "SELECT senia FROM pedido_libros WHERE idPedidoLibros = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $datos['IdPedido']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $seniaActual = $row['senia']; // Seña actual

    // Verificar si se envió una nueva seña
    if (isset($datos['nueva_senia']) && $datos['nueva_senia'] !== '') {
        // Validar que la seña sea un número válido y no negativo
        if (!is_numeric($datos['nueva_senia']) || $datos['nueva_senia'] < 0) {
            return "Error: La seña ingresada no es válida. Debe ser un número positivo.";
        }

        // Sumar la nueva seña a la seña actual
        $nuevaSeniaTotal = $seniaActual + floatval($datos['nueva_senia']);

        // Actualizar el campo senia en la tabla pedido_libros
        $query = "UPDATE pedido_libros SET senia = ? WHERE idPedidoLibros = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("di", $nuevaSeniaTotal, $datos['IdPedido']); // "di" = double, int
        $stmt->execute();
    } else {
        // Si no se envió seña, se mantiene el valor actual
        $nuevaSeniaTotal = $seniaActual; // Mantener el valor actual
    }

    // Recorrer los detalles del pedido para actualizar el estado y el proveedor
    foreach ($datos['estado_detalle'] as $idDetalle => $estado) {
        // Obtener el proveedor seleccionado para este detalle (si existe)
        $idProveedor = isset($datos['proveedor_detalle'][$idDetalle]) ? $datos['proveedor_detalle'][$idDetalle] : null;

        // Actualizar el estado y el proveedor en la base de datos
        $query = "UPDATE detalle_pedido SET idEstado = ?, idProveedor = ? WHERE idDetallePedido = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("isi", $estado, $idProveedor, $idDetalle); // "isi" = int, string, int
        $stmt->execute();
    }

    // Obtener los estados de todos los detalles del pedido
    $query = "SELECT idEstado FROM detalle_pedido WHERE id_pedido_libros = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $datos['IdPedido']);
    $stmt->execute();
    $result = $stmt->get_result();

    $estados = [];
    while ($row = $result->fetch_assoc()) {
        $estados[] = $row['idEstado'];
    }

    // Determinar el nuevo estado del pedido_libros
    $nuevoEstadoPedido = 4; // Por defecto, asumimos que todos están entregados (estado 4)

    // Verificar si hay al menos un detalle con estado 1 (para pedir)
    if (in_array(1, $estados)) {
        $nuevoEstadoPedido = 1;
    }
    // Si no hay detalles con estado 1, verificar si hay al menos uno con estado 2 (pedido)
    elseif (in_array(2, $estados)) {
        $nuevoEstadoPedido = 2;
    }
    // Si no hay detalles con estado 1 ni 2, verificar si hay al menos uno con estado 3 (recibido)
    elseif (in_array(3, $estados)) {
        $nuevoEstadoPedido = 3;
    }
    // Si todos los detalles tienen estado 4 (entregado), el estado ya es 4 por defecto

    // Actualizar el estado del pedido_libros
    $query = "UPDATE pedido_libros SET idEstado = ? WHERE idPedidoLibros = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $nuevoEstadoPedido, $datos['IdPedido']);
    $stmt->execute();

    return true; // Éxito
}

function Anular_Pedido($vConexion , $vIdConsulta) {
 
        $SQL_MiConsulta="SELECT idPedidoLibros FROM pedido_libros 
                        WHERE idPedidoLibros = $vIdConsulta ";
   
    
    $rs = mysqli_query($vConexion, $SQL_MiConsulta);
        
    $data = mysqli_fetch_array($rs);

    if (!empty($data['idPedidoLibros']) ) {
        //si se cumple todo, entonces elimino:
        mysqli_query($vConexion, "UPDATE pedido_libros SET idActivo = 2 WHERE idPedidoLibros = $vIdConsulta");
        
        return true;

    }else {
        return false;
    }
    
}

function ColorDeFila($vEstado) {
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
    $sql = "SELECT * FROM caja ORDER BY Fecha DESC";
    $resultado = $Conexion->query($sql);
    $Listado = [];
    while ($fila = $resultado->fetch_assoc()) {
        $Listado[] = $fila;
    }
    return $Listado;
}

function Listar_Cajas_Parametro($Conexion, $Criterio, $Parametro) {
    $sql = "SELECT * FROM caja WHERE $Criterio LIKE ? ORDER BY Fecha DESC";
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

function Listar_Turnos($Conexion) {
    $sql = "SELECT * FROM turnos";
    $resultado = $Conexion->query($sql);
    $Listado = [];
    while ($fila = $resultado->fetch_assoc()) {
        $Listado[] = $fila;
    }
    return $Listado;
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

    if (empty($_POST['idCaja'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar una caja. <br />';
    }
    if (empty($_POST['idTipoPago'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de pago. <br />';
    }
    if (empty($_POST['idTipoServicio'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un tipo de servicio. <br />';
    }
    if (empty($_POST['idUsuario'])) {
        $_SESSION['Mensaje'] .= 'Debes seleccionar un usuario. <br />';
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

?>