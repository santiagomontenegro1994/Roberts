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
    $DatosLibro  =   array();
    //me aseguro que la consulta exista
    $SQL = "SELECT * FROM libros 
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

function Modificar_Libros($vConexion) {
    $isbn = mysqli_real_escape_string($vConexion, $_POST['ISBN']);
    $autor = mysqli_real_escape_string($vConexion, $_POST['Autor']);
    $titulo = mysqli_real_escape_string($vConexion, $_POST['Titulo']);
    $editorial = mysqli_real_escape_string($vConexion, $_POST['Editorial']);
    $precio = mysqli_real_escape_string($vConexion, $_POST['Precio']);
    $idLibro = mysqli_real_escape_string($vConexion, $_POST['IdLibro']);

    $SQL_MiConsulta = "UPDATE libros 
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
        $SQL = "SELECT C.nombre, C.apellido, PL.idPedidoLibros, PL.fecha, PL.precioTotal,PL.descuento, PL.senia, E.denominación
        FROM pedido_libros PL, clientes C, estado E
        WHERE PL.idCliente=C.idCliente AND PL.idEstado=E.idEstado
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
            $Listado[$i]['ESTADO'] = $data['denominación'];

            $i++;
        }

    //devuelvo el listado generado en el array $Listado. (Podra salir vacio o con datos)..
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

function ColorDeFila($vEstado) {
    $Title='';
    $Color=''; 

    if ($vEstado=='Pendiente'){
        //Estado pendiente
        $Title='Pendiente de buscar';
        $Color='table-danger'; 
    
    } else if ($vEstado == 'Listo para retirar'){
        //Estado listo para retirar
        $Title='Listo para retirar';
        $Color='table-warning'; 
    } else if ($vEstado=='Retirado'){
        //Estado retirado
        $Title='Retirado';
        $Color='table-success'; 
    }     
    
    return [$Title, $Color];

}

function Datos_Pedidos($conexion, $idPedido) {
    $query = "SELECT p.idPedidoLibros AS ID_PEDIDO, c.nombre AS CLIENTE, c.apellido AS CLIENTE_A, p.fecha AS FECHA, p.precioTotal AS PRECIO_TOTAL, p.senia AS SENIA, p.descuento AS DESCUENTO, e.denominación AS ESTADO
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
            d.idEstado AS ESTADO
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
    error_log("Función Modificar_Detalles_Pedido ejecutándose."); // Depuración
    foreach ($datos['estado_detalle'] as $idDetalle => $estado) {
        $query = "UPDATE detalle_pedido SET idEstado = ? WHERE idDetallePedido = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("ii", $estado, $idDetalle);
        $stmt->execute();
    }
    return true;
}

?>