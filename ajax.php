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

                $isbn = $_POST['libro'];

                $query = mysqli_query($MiConexion,"SELECT titulo, editorial, precio
                 FROM librosleas WHERE isbn LIKE '$isbn'");

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




    }
    exit;

?>