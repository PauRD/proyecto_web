<?php
//viene de usuarios
session_start();
require_once("conexiones.php");

$id_admin_logueado = $_SESSION['id_usuario'];
$privilegio_admin = $_SESSION['privilegio_usuario'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario']) && isset($_POST['accion'])) {
    
    $id_usuario_a_modificar = $_POST['id_usuario'];
    $accion = $_POST['accion'];
    
    $mensaje_exito = '';
    
    if ($accion === 'actualizar_privilegio' && isset($_POST['privilegio'])) {
        $nuevo_privilegio = htmlspecialchars($_POST['privilegio']);
        if (in_array($nuevo_privilegio, ['Cliente', 'Proveedor', 'Admin'])) {
            $sql = "UPDATE usuarios SET privilegio_usuario = ? WHERE id_usuario = ?";
            if ($stmt = $conexion->prepare($sql)) {
                $stmt->bind_param("si", $nuevo_privilegio, $id_usuario_a_modificar);
                if ($stmt->execute()) {
                    $mensaje_exito = "Privilegio del usuario #{$id_usuario_a_modificar} cambiado a **{$nuevo_privilegio}**.";
                }
                $stmt->close();
            }
        }
        
    } elseif ($accion === 'actualizar_saldo' && isset($_POST['saldo'])) {
        $nuevo_saldo = filter_var($_POST['saldo'], FILTER_VALIDATE_FLOAT);
        if ($nuevo_saldo >= 0) {
            $sql = "UPDATE usuarios SET saldo = ? WHERE id_usuario = ?";
            if ($stmt = $conexion->prepare($sql)) {
                $stmt->bind_param("ii", $nuevo_saldo, $id_usuario_a_modificar);
                if ($stmt->execute()) {
                    $mensaje_exito = "Saldo del usuario #{$id_usuario_a_modificar} actualizado a **{$nuevo_saldo} €**.";                   
                }
                $stmt->close();
            }
        } else {
            $_SESSION['mensaje_tipo'] = 'error'; 
            $_SESSION['mensaje'] = "Saldo no válido. Debe ser un número no negativo.";
            header("Location: ../usuarios.php");
            exit;
        }
    }
    if (!isset($_SESSION['mensaje'])) {
        $_SESSION['mensaje'] = $mensaje_exito;
        $_SESSION['mensaje_tipo'] = 'exito';
    }
} 
$conexion->close();
header("Location: ../usuarios.php");
exit;

?>