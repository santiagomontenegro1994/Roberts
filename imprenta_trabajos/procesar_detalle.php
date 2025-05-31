<?php
session_start();
require_once '../funciones/conexion.php';
require_once '../funciones/detalle_trabajo.php';

$MiConexion = ConexionBD();

$accion = $_GET['accion'] ?? '';
$idPedido = $_POST['idPedido'] ?? ($_GET['idPedido'] ?? 0);

switch ($accion) {
    case 'agregar':
        $datos = [
            'idTrabajo' => $_POST['idTrabajo'],
            'idProveedor' => $_POST['idProveedor'],
            'fechaEntrega' => $_POST['fechaEntrega'],
            'horaEntrega' => $_POST['horaEntrega'],
            'precio' => $_POST['precio'],
            'descripcion' => $_POST['descripcion'],
            'idEstadoTrabajo' => $_POST['idEstadoTrabajo']
        ];
        
        if (Agregar_Detalle_Trabajo($MiConexion, $idPedido, $datos)) {
            $_SESSION['Mensaje'] = "Detalle agregado correctamente!";
            $_SESSION['Estilo'] = 'success';
        } else {
            $_SESSION['Mensaje'] = "Error al agregar el detalle.";
            $_SESSION['Estilo'] = 'danger';
        }
        break;
        
    case 'editar':
        $idDetalle = $_POST['idDetalle'] ?? 0;
        $datos = [
            'idTrabajo' => $_POST['idTrabajo'],
            'idProveedor' => $_POST['idProveedor'],
            'fechaEntrega' => $_POST['fechaEntrega'],
            'horaEntrega' => $_POST['horaEntrega'],
            'precio' => $_POST['precio'],
            'descripcion' => $_POST['descripcion'],
            'idEstadoTrabajo' => $_POST['idEstadoTrabajo'],
            'id_pedido_trabajos' => $idPedido
        ];
        
        if (Editar_Detalle_Trabajo($MiConexion, $idDetalle, $datos)) {
            $_SESSION['Mensaje'] = "Detalle actualizado correctamente!";
            $_SESSION['Estilo'] = 'success';
        } else {
            $_SESSION['Mensaje'] = "Error al actualizar el detalle.";
            $_SESSION['Estilo'] = 'danger';
        }
        break;
        
    case 'eliminar':
        $idDetalle = $_GET['id'] ?? 0;
        
        if (Eliminar_Detalle_Trabajo($MiConexion, $idDetalle)) {
            $_SESSION['Mensaje'] = "Detalle eliminado correctamente!";
            $_SESSION['Estilo'] = 'success';
        } else {
            $_SESSION['Mensaje'] = "Error al eliminar el detalle.";
            $_SESSION['Estilo'] = 'danger';
        }
        break;
}

header("Location: modificar_pedido_trabajo.php?ID_PEDIDO=$idPedido");
exit;
?>