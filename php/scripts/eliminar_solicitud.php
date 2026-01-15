<?php
// viene de eventos_solicitados
session_start();
require_once("conexiones.php");

$id_usuario = $_SESSION['id_usuario'];
$id_solicitud = $_GET['id'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario || $privilegio != 'Proveedor' || !$id_solicitud) {
    $_SESSION['mensaje'] = "Acceso denegado o solicitud no válida.";
    $_SESSION['mensaje_tipo'] = 'error';
    header("Location: ../eventos_solicitados.php");
    exit;
}

$sql = "
    DELETE FROM eventos_solicitados 
    WHERE id_evento_solicitado = ? 
    AND id_organizador = ? 
    AND estado = 'Pendiente'";

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("ii", $id_solicitud, $id_usuario);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['mensaje'] = "La solicitud de evento ha sido eliminada correctamente.";
            $_SESSION['mensaje_tipo'] = 'exito';
        }
    }
    $stmt->close();
}

$conexion->close();
header("Location: ../eventos_solicitados.php");
exit;
?>