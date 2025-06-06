<?php

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

?>