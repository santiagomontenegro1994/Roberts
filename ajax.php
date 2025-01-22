<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: cerrarsesion.php');
  exit;
}

require_once 'funciones/conexion.php';
$MiConexion=ConexionBD();

    if(!empty($_POST)){

        //Buscar Cliente
        if($_POST['action'] == 'searchCliente'){
            if(!empty($_POST['cliente'])){
                $dni = $_POST['cliente'];

                $query = mysqli_query($MiConexion,"SELECT * FROM clientes WHERE dni LIKE '$dni'");

                mysqli_close($MiConexion);
                $result = mysqli_num_rows($query);

                $data ='';
                if($result > 0){
                    $data = mysqli_fetch_assoc($query);
                }else{
                    $data = 0;
                }
                echo json_encode($data,JSON_UNESCAPED_UNICODE);


            }
            exit;
        }

        //registrar Cliente Pedidos
        if($_POST['action'] == 'addCliente'){
            
            $dni = $_POST['dni_cliente'];
            $nombre = $_POST['nom_cliente'];
            $apellido = $_POST['ape_cliente'];
            $direccion = $_POST['dir_cliente'];
            $telefono = $_POST['tel_cliente'];

            $query_insert = mysqli_query($MiConexion,"INSERT INTO clientes (nombre, apellido, dni, direccion, telefono)
                                                        VALUES ('$nombre' , '$apellido' , '$dni', '$direccion', '$telefono')");


            if($query_insert){ // si se ejecuto bien la insercion
                $codCliente = mysqli_insert_id($MiConexion); // extraemos el ID por medio de la funcion mysqli_insert_id
                $msg = $codCliente;
            }else{
                $msg = 'error';
            }
            mysqli_close($MiConexion);//cierro la conexion
            echo $msg;
            exit;                                          

        }

        //Buscar libro
        if($_POST['action'] == 'infoLibro'){

                $idLibro = $_POST['libro'];

                $query = mysqli_query($MiConexion,"SELECT titulo, editorial, precio
                 FROM librosleas WHERE idLibros LIKE '$idLibro'");

                mysqli_close($MiConexion);

                $result = mysqli_num_rows($query);
                if($result > 0){
                    $data = mysqli_fetch_assoc($query);
                    echo json_encode($data,JSON_UNESCAPED_UNICODE);
                    exit;
                }
                echo 'error';
                exit;
            
        }

        //Agregar libro al detalle temporal
        if($_POST['action'] == 'agregarLibroDetalle'){
            if(empty($_POST['producto']) || empty($_POST['cantidad'])){
                echo 'error';//si producto o cantidad vienen vacios devuelve error
            }else{
                //si no vienen vacios creo variables y les paso los datos
                $idlibro = $_POST['producto'];
                $cantidad = $_POST['cantidad'];

                //llamo al procedimiento almacenado y le paso los datos
                $query_detalle_temp = mysqli_query($MiConexion,"CALL add_detalle_temp($idlibro,$cantidad)");
                $result = mysqli_num_rows($query_detalle_temp);
                
                //Declaro variables que voy a usar
                $detalleTabla='';
                $subtotal=0;
                $total=0;
                $arrayData=array();

                if($result > 0){//si tiene algo el result
                    //recorro todos los detalle_temp
                    while($data = mysqli_fetch_assoc($query_detalle_temp)){
                        $precioTotal = round($data['cantidad'] * $data['precio_pedido'], 2);//calculo el precio total con 2 decimales
                        $subtotal = round($subtotal + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales
                        $total = round($total + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales

                        //concateno cada una de las tablas del detalle con los datos correspondientes
                        $detalleTabla  .='<tr data-bs-toggle="tooltip" data-bs-placement="left">
                                            <th>'.$data['idLibro'].'</th>
                                            <td>'.$data['titulo'].'</td>
                                            <td>'.$data['editorial'].'</td>
                                            <th>'.$data['cantidad'].'</th>
                                            <td>'.$data['precio_pedido'].'</td>
                                            <td>'.$precioTotal.'</td>
                                            <td>
                                                <a href="#" onclick="event.preventDefault();del_libro_detalle('.$data['correlativo'].');">
                                                    <i class="bi bi-trash text-danger"></i></a>
                                            </td>   
                                        </tr>';
                    }

                    //genero la tabla con totales
                    $detalleTotales='<tr>
                                        <td colspan="5" class="text-end">SUBTOTAL</td>
                                        <td colspan="5" class="text-end">'.$subtotal.'</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">SEÑA</td>
                                        <td colspan="5" class="text-end"><input type="text" id="seniaPedido" value="0" min="1"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">TOTAL</td>
                                        <td colspan="5" class="text-end" id="total_pedido">'.$total.'</td>
                                        <td colspan="5" class="text-end" id="total_pedido_original" style="display: none;">'.$total.'</td>
                                    </tr>';
                    
                    $arrayData['detalle'] = $detalleTabla;
                    $arrayData['totales'] = $detalleTotales;

                    echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);//retorno en formato JSON

                }else{
                    echo 'error';
                }
                mysqli_close($MiConexion);
            }
            exit;

        }

        //muestra datos del detalle temp
        if($_POST['action'] == 'searchforDetalle'){
            if(empty($_POST['action'])){
                echo 'error';//...
            }else{
                
                //genero el Query para que me devuelva los datos de detalle temp
                $query = mysqli_query($MiConexion,"SELECT tmp.correlativo,
                                                          tmp.cantidad,
                                                          tmp.precio_pedido,
                                                          tmp.idLibro,
                                                          l.titulo,
                                                          l.editorial
                                                   FROM detalle_temp tmp
                                                   INNER JOIN librosleas l
                                                   WHERE tmp.idLibro = l.idLibros");

                $result = mysqli_num_rows($query);
                
                //Declaro variables que voy a usar
                $detalleTabla='';
                $subtotal=0;
                $total=0;
                $arrayData=array();

                if($result > 0){//si tiene algo el result
                    //recorro todos los detalle_temp
                    while($data = mysqli_fetch_assoc($query)){
                        $precioTotal = round($data['cantidad'] * $data['precio_pedido'], 2);//calculo el precio total con 2 decimales
                        $subtotal = round($subtotal + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales
                        $total = round($total + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales

                        //concateno cada una de las tablas del detalle con los datos correspondientes
                        $detalleTabla  .='<tr data-bs-toggle="tooltip" data-bs-placement="left">
                                            <th>'.$data['idLibro'].'</th>
                                            <td>'.$data['titulo'].'</td>
                                            <td>'.$data['editorial'].'</td>
                                            <th>'.$data['cantidad'].'</th>
                                            <td>'.$data['precio_pedido'].'</td>
                                            <td>'.$precioTotal.'</td>
                                            <td>
                                                <a href="#" onclick="event.preventDefault();del_libro_detalle('.$data['correlativo'].');">
                                                    <i class="bi bi-trash text-danger"></i></a>
                                            </td>   
                                        </tr>';
                    }

                    //genero la tabla con totales
                    $detalleTotales='<tr>
                                        <td colspan="5" class="text-end">SUBTOTAL</td>
                                        <td colspan="5" class="text-end">'.$subtotal.'</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">SEÑA</td>
                                        <td colspan="5" class="text-end"><input type="text" id="seniaPedido" value="0" min="1"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">TOTAL</td>
                                        <td colspan="5" class="text-end" id="total_pedido">'.$total.'</td>
                                        <td colspan="5" class="text-end" id="total_pedido_original" style="display: none;">'.$total.'</td>
                                    </tr>';
                    
                    $arrayData['detalle'] = $detalleTabla;
                    $arrayData['totales'] = $detalleTotales;

                    echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);//retorno en formato JSON

                }else{
                    echo 'error';
                }
                mysqli_close($MiConexion);
            }
            exit;

        }

        //elimina datos del detalle temp
        if($_POST['action'] == 'delProductoDetalle'){
            if(empty($_POST['id_detalle'])){
                echo 'error';//si viene vacio retorna error
            }else{
                $id_detalle = $_POST['id_detalle'];

                //llamo al procedimiento almacenado para eliminar un detalle temp
                $query_detalle_temp = mysqli_query($MiConexion,"CALL del_detalle_temp($id_detalle)");
                $result = mysqli_num_rows($query_detalle_temp);
                
                
                //Declaro variables que voy a usar
                $detalleTabla='';
                $subtotal=0;
                $total=0;
                $arrayData=array();

                if($result > 0){//si tiene algo el result
                    //recorro todos los detalle_temp
                    while($data = mysqli_fetch_assoc($query_detalle_temp)){
                        $precioTotal = round($data['cantidad'] * $data['precio_pedido'], 2);//calculo el precio total con 2 decimales
                        $subtotal = round($subtotal + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales
                        $total = round($total + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales

                        //concateno cada una de las tablas del detalle con los datos correspondientes
                        $detalleTabla  .='<tr data-bs-toggle="tooltip" data-bs-placement="left">
                                            <th>'.$data['idLibro'].'</th>
                                            <td>'.$data['titulo'].'</td>
                                            <td>'.$data['editorial'].'</td>
                                            <th>'.$data['cantidad'].'</th>
                                            <td>'.$data['precio_pedido'].'</td>
                                            <td>'.$precioTotal.'</td>
                                            <td>
                                                <a href="#" onclick="event.preventDefault();del_libro_detalle('.$data['correlativo'].');">
                                                    <i class="bi bi-trash text-danger"></i></a>
                                            </td>   
                                        </tr>';
                    }

                    //genero la tabla con totales
                    $detalleTotales='<tr>
                                        <td colspan="5" class="text-end">SUBTOTAL</td>
                                        <td colspan="5" class="text-end">'.$subtotal.'</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">SEÑA</td>
                                        <td colspan="5" class="text-end"><input type="text" id="seniaPedido" value="0" min="1"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">TOTAL</td>
                                        <td colspan="5" class="text-end" id="total_pedido">'.$total.'</td>
                                        <td colspan="5" class="text-end" id="total_pedido_original" style="display: none;">'.$total.'</td>
                                    </tr>';
                    
                    $arrayData['detalle'] = $detalleTabla;
                    $arrayData['totales'] = $detalleTotales;

                    echo json_encode($arrayData,JSON_UNESCAPED_UNICODE);//retorno en formato JSON

                }else{
                    echo 'error';
                }
                mysqli_close($MiConexion);
            }
            exit;
        }
        

    }
    exit;

?>