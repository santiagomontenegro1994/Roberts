<?php
session_start();

if (empty($_SESSION['Usuario_Nombre']) ) { // si el usuario no esta logueado no lo deja entrar
  header('Location: ../core/cerrarsesion.php');
  exit;
}

require_once '../funciones/conexion.php';
$MiConexion=ConexionBD();

    if(!empty($_POST)){

        //Buscar Cliente
        if($_POST['action'] == 'searchClienteImprenta'){
            if(!empty($_POST['cliente'])){
                $tel = $_POST['cliente'];

                $query = mysqli_query($MiConexion,"SELECT * FROM clientes WHERE telefono LIKE '$tel'");

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

        //registrar Cliente Pedidos Imprenta
        if($_POST['action'] == 'addCliente_imprenta'){
            
            $nombre = $_POST['nom_cliente_imprenta'];
            $apellido = $_POST['ape_cliente_imprenta'];
            $telefono = $_POST['tel_cliente_imprenta'];

            $query_insert = mysqli_query($MiConexion,"INSERT INTO clientes (nombre, apellido, telefono)
                                                        VALUES ('$nombre' , '$apellido' ,'$telefono')");


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

        //Agregar trabajo al detalle temporal de trabajos
        if($_POST['action'] == 'agregarTrabajoDetalle'){
            header('Content-Type: application/json');
            $arrayData = array(
                'detalle' => '',
                'totales' => '',
                'error' => ''
            );

            try {
                // Validar parámetros requeridos
                $required = ['estado', 'trabajo', 'enviado', 'fecha', 'hora'];
                foreach($required as $field) {
                    if(empty($_POST[$field])) {
                        throw new Exception("Campo requerido faltante: $field");
                    }
                }

                // Asignar y sanitizar valores
                $estado = intval($_POST['estado']);
                $trabajo = intval($_POST['trabajo']);
                $enviado = intval($_POST['enviado']);
                $fecha = mysqli_real_escape_string($MiConexion, $_POST['fecha']);
                $hora = mysqli_real_escape_string($MiConexion, $_POST['hora']);
                $precio = floatval($_POST['precio']);
                $descripcion = mysqli_real_escape_string($MiConexion, $_POST['descripcion'] ?? '');
                $usuario = intval($_SESSION['Usuario_Id']);

                // Llamar al procedimiento almacenado
                $query = mysqli_query($MiConexion, 
                    "CALL add_detalle_temp_trabajos(
                        $estado, 
                        $trabajo, 
                        $enviado, 
                        '$fecha', 
                        '$hora', 
                        $precio, 
                        $usuario, 
                        '$descripcion'
                    )");

                if(!$query) {
                    throw new Exception("Error en base de datos: " . mysqli_error($MiConexion));
                }

                // Procesar resultados
                $detalleTabla = '';
                $subtotal = 0;
                $total = 0;
                
                while($data = mysqli_fetch_assoc($query)) {
                    $precioTotal = round($data['precio'], 2);
                    $subtotal += $precioTotal;
                    $total += $precioTotal;

                    $detalleTabla .= '<div class="table-responsive">
                                        <table class="table table-striped">
                                            <tr>
                                                <th>'.$data['estado_trabajo'].'</th>
                                                <td>'.$data['tipo_trabajo'].'</td>
                                                <td>'.$data['proveedor'].'</td>
                                                <th>'.$data['fechaEntrega'].'</th>
                                                <th>'.$data['horaEntrega'].'</th>
                                                <th>'.number_format($data['precio'], 2).'</th>
                                                <td>
                                                    <a href="#" onclick="event.preventDefault();del_trabajo_detalle('.$data['correlativo'].');">
                                                        <i class="bi bi-trash-fill text-danger fs-5"></i>
                                                    </a>
                                                </td>   
                                            </tr>
                                        </table>
                                    </div>';
                }

                $detalleTotales = '<div class="table-responsive">
                                        <table class="table table-striped">
                                            <tr>
                                                <td colspan="5" class="text-end">SUBTOTAL</td>
                                                <td>'.number_format($subtotal, 2).'</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">SEÑA</td>
                                                <td><input type="number" id="seniaPedidoImprenta" value="0" min="0"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">TOTAL</td>
                                                <td>'.number_format($total, 2).'</td>
                                            </tr>
                                        </table>
                                    </div>';

                $arrayData['detalle'] = $detalleTabla;
                $arrayData['totales'] = $detalleTotales;

            } catch (Exception $e) {
                $arrayData['error'] = $e->getMessage();
            }

            echo json_encode($arrayData, JSON_UNESCAPED_UNICODE);
            exit;
        }

        //muestra datos del detalle temp
        if($_POST['action'] == 'searchforDetalleTrabajo'){
            if(empty($_POST['action'])){
                echo 'error';//...
            }else{
                $usuario = $_SESSION['Usuario_Id'];
                
                //genero el Query para que me devuelva los datos de detalle temp
                $query = mysqli_query($MiConexion,"SELECT 
                                                        tmp.correlativo, 
                                                        et.denominacion AS estado_trabajo,
                                                        tt.denominacion AS tipo_trabajo,
                                                        p.nombre AS proveedor,
                                                        tmp.fechaEntrega, 
                                                        tmp.horaEntrega, 
                                                        tmp.precio 
                                                    FROM 
                                                        detalle_temp_trabajos tmp
                                                    LEFT JOIN 
                                                        estado_trabajo et ON tmp.idEstadoTrabajo = et.idEstado
                                                    LEFT JOIN 
                                                        tipo_trabajo tt ON tmp.idTrabajo = tt.idTipoTrabajo
                                                    LEFT JOIN 
                                                        proveedores p ON tmp.idProveedor = p.idProveedor
                                                    WHERE 
                                                        tmp.idUsuario = $usuario
                                                    ORDER BY tmp.correlativo;");

                $result = mysqli_num_rows($query);
                
                //Declaro variables que voy a usar
                $detalleTabla='';
                $subtotal=0;
                $total=0;
                $arrayData=array();

                if($result > 0){//si tiene algo el result
                    //recorro todos los detalle_temp
                    while($data = mysqli_fetch_assoc($query)){
                        $precioTotal = round($data['precio'], 2);//calculo el precio total con 2 decimales
                        $subtotal = round($subtotal + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales
                        $total = round($total + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales

                        //concateno cada una de las tablas del detalle con los datos correspondientes
                        $detalleTabla .= '<div class="table-responsive">
                                            <table class="table table-striped">
                                                <tr data-bs-toggle="tooltip" data-bs-placement="left">
                                                    <th>'.$data['estado_trabajo'].'</th>
                                                    <td>'.$data['tipo_trabajo'].'</td>
                                                    <td>'.$data['proveedor'].'</td>
                                                    <th>'.$data['fechaEntrega'].'</th>
                                                    <th>'.$data['horaEntrega'].'</th>
                                                    <th>'.$data['precio'].'</th>
                                                    <td>
                                                        <a href="#" onclick="event.preventDefault();del_trabajo_detalle('.$data['correlativo'].');">
                                                            <i class="bi bi-trash-fill text-danger fs-5"></i>
                                                        </a>
                                                    </td>   
                                                </tr>
                                            </table>
                                        </div>';
                    }

                    //genero la tabla con totales
                    $detalleTotales = '<div class="table-responsive">
                                        <table class="table table-striped">
                                            <tr>
                                                <td colspan="5" class="text-end">SUBTOTAL</td>
                                                <td colspan="5" class="text-end">'.number_format($subtotal, 2, '.', '').'</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">SEÑA</td>
                                                <td colspan="5" class="text-end"><input type="number" id="seniaPedidoImprenta" value="0" min="1"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">TOTAL</td>
                                                <td colspan="5" class="text-end" id="total_pedido">'.number_format($total, 2, '.', '').'</td>
                                                <td colspan="5" class="text-end" id="total_pedido_original" style="display: none;">'.$total.'</td>
                                            </tr>
                                        </table>
                                    </div>';
                    
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

        //elimina datos del detalle temp Trabajo
        if($_POST['action'] == 'delProductoDetalleTrabajo'){
            if(empty($_POST['id_detalle'])){
                echo 'error';//si viene vacio retorna error
            }else{
                $id_detalle = $_POST['id_detalle'];
                $usuario = $_SESSION['Usuario_Id'];

                //llamo al procedimiento almacenado para eliminar un detalle temp
                $query_detalle_temp = mysqli_query($MiConexion,"CALL del_detalle_temp_trabajos($id_detalle,$usuario)");
                $result = mysqli_num_rows($query_detalle_temp);
                
                
                //Declaro variables que voy a usar
                $detalleTabla='';
                $subtotal=0;
                $total=0;
                $arrayData=array();

                if($result > 0){//si tiene algo el result
                    //recorro todos los detalle_temp
                    while($data = mysqli_fetch_assoc($query_detalle_temp)){
                        $precioTotal = round($data['precio'], 2);//calculo el precio total con 2 decimales
                        $subtotal = round($subtotal + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales
                        $total = round($total + $precioTotal, 2); //voy haciendo una sumatoria de totales con 2 decimales

                        //concateno cada una de las tablas del detalle con los datos correspondientes
                        $detalleTabla .= '<div class="table-responsive">
                                            <table class="table table-striped">
                                                <tr data-bs-toggle="tooltip" data-bs-placement="left">
                                                    <th>'.$data['estado_trabajo'].'</th>
                                                    <td>'.$data['tipo_trabajo'].'</td>
                                                    <td>'.$data['proveedor'].'</td>
                                                    <th>'.$data['fechaEntrega'].'</th>
                                                    <th>'.$data['horaEntrega'].'</th>
                                                    <th>'.$data['precio'].'</th>
                                                    <td>
                                                        <a href="#" onclick="event.preventDefault();del_trabajo_detalle('.$data['correlativo'].');">
                                                            <i class="bi bi-trash-fill text-danger fs-5"></i>
                                                        </a>
                                                    </td>   
                                                </tr>
                                            </table>
                                        </div>';
                    }

                    //genero la tabla con totales
                    $detalleTotales = '<div class="table-responsive">
                                        <table class="table table-striped">
                                            <tr>
                                                <td colspan="5" class="text-end">SUBTOTAL</td>
                                                <td colspan="5" class="text-end">'.number_format($subtotal, 2, '.', '').'</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">SEÑA</td>
                                                <td colspan="5" class="text-end"><input type="number" id="seniaPedidoImprenta" value="0" min="1"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">TOTAL</td>
                                                <td colspan="5" class="text-end" id="total_pedido">'.number_format($total, 2, '.', '').'</td>
                                                <td colspan="5" class="text-end" id="total_pedido_original" style="display: none;">'.$total.'</td>
                                            </tr>
                                        </table>
                                    </div>';
                    
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
        
        //anular pedido trabajo
        if($_POST['action'] == 'anularPedidoTrabajo'){

            $usuario = $_SESSION['Usuario_Id'];

            $query_del = mysqli_query($MiConexion,"DELETE FROM detalle_temp_trabajos WHERE idUsuario = $usuario");
            mysqli_close($MiConexion);
            if($query_del){
                echo 'ok';
            }else{
                echo 'error';
            }
            exit;

        }

        //confirmar pedido -------------------
        if($_POST['action'] == 'procesarPedidoTrabajo') {
            $codCliente = intval($_POST['codCliente']);
            $senia = floatval($_POST['senia']);
            $usuario = intval($_SESSION['Usuario_Id']);

            // Verificar si hay trabajos en el pedido
            $query = mysqli_query($MiConexion, "SELECT * FROM detalle_temp_trabajos WHERE idUsuario = $usuario");
            if(mysqli_num_rows($query) == 0) {
                echo json_encode(['status' => 'error', 'message' => 'No hay trabajos en el pedido']);
                exit;
            }

            // Procesar el pedido
            $query_procesar = mysqli_query($MiConexion, "CALL procesar_pedido_trabajo($codCliente, $senia, $usuario)");
            if(!$query_procesar) {
                echo json_encode(['status' => 'error', 'message' => 'Error al procesar pedido: '.mysqli_error($MiConexion)]);
                exit;
            }

            // Limpiar resultados del procedimiento almacenado
            while(mysqli_more_results($MiConexion)) {
                mysqli_next_result($MiConexion);
            }

            // Obtener el ID del pedido
            $query_last_id = mysqli_query($MiConexion, "SELECT LAST_INSERT_ID() as idPedido");
            $result = mysqli_fetch_assoc($query_last_id);
            $idPedido = $result['idPedido'] ?? 0;

            if($idPedido == 0) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener ID del pedido']);
                exit;
            }

            echo json_encode(['status' => 'success', 'idPedido' => $idPedido]);
            exit;
        }

        if($_POST['action'] == 'procesarPedidoTrabajoConPago') {
            $codCliente = intval($_POST['codCliente']);
            $senia = floatval($_POST['senia']);
            $idTipoPago = intval($_POST['idTipoPago']);
            $usuario = intval($_SESSION['Usuario_Id']);
            $idCaja = intval($_SESSION['Id_Caja']);

            // 1. Procesar el pedido
            $query_pedido = mysqli_query($MiConexion, "CALL procesar_pedido_trabajo($codCliente, $senia, $usuario)");
            if(!$query_pedido) {
                echo json_encode(['status' => 'error', 'message' => 'Error al procesar pedido: '.mysqli_error($MiConexion)]);
                exit;
            }

            // Limpiar resultados del procedimiento almacenado
            while(mysqli_more_results($MiConexion)) {
                mysqli_next_result($MiConexion);
            }

            // Obtener el ID del pedido
            //$query_last_id = mysqli_query($MiConexion, "SELECT LAST_INSERT_ID() as idPedido");
            //$result = mysqli_fetch_assoc($query_last_id);
            //$idPedido = $result['idPedido'] ?? 0;

            $result_pedido = mysqli_fetch_assoc($query_pedido);
            $idPedido = $result_pedido['idPedidoTrabajos'] ?? 0;

            //if($idPedido == 0) {
            //    echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener ID del pedido']);
            //    exit;
            //}

            if($idPedido == 0) {
                $errorMessage = $result_pedido['mensaje'] ?? 'No se pudo obtener ID del pedido';
                echo json_encode(['status' => 'error', 'message' => $errorMessage]);
                exit;
            }

            // Definir observaciones con el formato deseado
            $observaciones = "Seña de trabajo: " . $idPedido;

            // 2. Registrar el movimiento en caja
            $SQL_Insert = "INSERT INTO detalle_caja (
                idCaja, 
                idTipoPago, 
                idTipoMovimiento, 
                idUsuario, 
                monto, 
                observaciones
            ) VALUES (
                $idCaja,
                $idTipoPago,
                3,  -- Tipo movimiento fijo para trabajos
                $usuario,
                $senia,
                '" . mysqli_real_escape_string($MiConexion, $observaciones) . "'
            )";

            $query_pago = mysqli_query($MiConexion, $SQL_Insert);
            if(!$query_pago) {
                // Eliminar el pedido si falla el registro en caja
                mysqli_query($MiConexion, "DELETE FROM pedido_trabajos WHERE idPedidoTrabajos = $idPedido");
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Error al registrar pago: '.mysqli_error($MiConexion),
                    'sql_error' => $SQL_Insert
                ]);
                exit;
            }

            echo json_encode([
                'status' => 'success', 
                'idPedido' => $idPedido,
                'idMovimiento' => mysqli_insert_id($MiConexion)
            ]);
            exit;
        }

    }
    exit;

?>