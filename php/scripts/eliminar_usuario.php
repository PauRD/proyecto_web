<?php
//viene de usuarios
session_start();
require_once("conexiones.php");

$id_admin_logueado = $_SESSION['id_usuario'];
$privilegio_admin = $_SESSION['privilegio_usuario'];

if ($privilegio_admin !== 'Admin') {
    header("Location: ../jaui.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_usuario_a_eliminar = $_GET['id'];
    
    // Anular referencias como admin aprobador (si es admin)
    $sql_admin_eventos = "UPDATE eventos_solicitados SET id_admin_aprobador = NULL WHERE id_admin_aprobador = ?";
    $stmt = $conexion->prepare($sql_admin_eventos);
    $stmt->bind_param("i", $id_usuario_a_eliminar);
    $stmt->execute();
    $stmt->close();
    
    $sql_admin_artistas = "UPDATE artistas SET id_admin_aprobador = NULL WHERE id_admin_aprobador = ?";
    $stmt = $conexion->prepare($sql_admin_artistas);
    $stmt->bind_param("i", $id_usuario_a_eliminar);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar usuario (las FKs harán el resto automáticamente)
    $sql_borrar_usuario = "DELETE FROM usuarios WHERE id_usuario = ?";
    $stmt = $conexion->prepare($sql_borrar_usuario);
    $stmt->bind_param("i", $id_usuario_a_eliminar);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['mensaje_tipo'] = 'exito'; 
        $_SESSION['mensaje'] = "Usuario #{$id_usuario_a_eliminar} ha sido eliminado con éxito.";
    } else {
        $_SESSION['mensaje_tipo'] = 'error'; 
        $_SESSION['mensaje'] = "El usuario #{$id_usuario_a_eliminar} no fue encontrado o no pudo ser eliminado.";
    }
    $stmt->close();
}

$conexion->close();
header("Location: ../usuarios.php");
exit;
?>