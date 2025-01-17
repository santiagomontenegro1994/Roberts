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






    }
    exit;

?>