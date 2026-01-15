<?php
// viene de cesta
session_start();
require_once("conexiones.php");

$id_usuario = $_SESSION['id_usuario'];

if (!$id_usuario) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_cesta'])) {
    
    $id_cesta = $_POST['id_cesta'];
    $sql = "DELETE FROM cesta WHERE id_cesta = ? AND id_usuario = ?";
    
    if ($stmt = $conexion->prepare($sql)) {
        
        $stmt->bind_param("ii", $id_cesta, $id_usuario);
        
        if ($stmt->execute()) {
            
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Evento eliminado de la cesta con éxito.";
            }
        }
        $stmt->close();
    }
}
$conexion->close();
header("Location: ../cesta.php");
exit;

?>