<?php
//viene de perfil_usuarios
session_start();
require_once("conexiones.php");

$id_usuario = $_SESSION['id_usuario'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!$id_usuario) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['accion']) && $_POST['accion'] == 'datos_personales') {

    $nombre = trim($_POST['nombre_usuario']);
    $email = filter_var(trim($_POST['email_usuario'] ), FILTER_VALIDATE_EMAIL);
    $sexo = trim($_POST['sexo_usuario']);

    if (empty($nombre) || !$email || empty($sexo)) {
        $_SESSION['mensaje'] = "Datos personales incompletos o email no válido.";
        $_SESSION['mensaje_tipo'] = 'error';
    } else {
        $sql = "SELECT id_usuario FROM usuarios WHERE (nombre_usuario = ? OR email_usuario = ?) AND id_usuario != ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ssi", $nombre, $email, $id_usuario);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                
                $_SESSION['mensaje'] = "El nombre de usuario o el correo electrónico ya están registrados por otro usuario.";
                $_SESSION['mensaje_tipo'] = 'error';
                $stmt->close();
                $conexion->close();
                header("Location: ../perfil_usuario.php");
                exit;
            }
            $stmt->close();
        }

        $sql = "UPDATE usuarios SET nombre_usuario = ?, email_usuario = ?, sexo_usuario = ? WHERE id_usuario = ?";
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("sssi", $nombre, $email, $sexo, $id_usuario);
            if ($stmt->execute()) {
                $_SESSION['nombre_usuario'] = $nombre; 
                $_SESSION['mensaje'] = "Datos personales actualizados correctamente.";
                $_SESSION['mensaje_tipo'] = 'exito';
            }
            $stmt->close();
        }
    }

} elseif ($accion == 'cambiar_contraseña') {
    $contra_actual = $_POST['contra_actual'];
    $contra_nueva = $_POST['contra_nueva'];
    $contra_confirmar = $_POST['contra_confirmar'];

    if (empty($contra_actual) || empty($contra_nueva) || empty($contra_confirmar)) {
        $_SESSION['mensaje'] = "Debes rellenar todos los campos de contraseña.";
        $_SESSION['mensaje_tipo'] = 'error';
    } elseif ($contra_nueva !== $contra_confirmar) {
        $_SESSION['mensaje'] = "La nueva contraseña y su confirmación no coinciden.";
        $_SESSION['mensaje_tipo'] = 'error';
    } elseif (strlen($contra_nueva) < 6) {
        $_SESSION['mensaje'] = "La contraseña debe tener al menos 6 caracteres.";
        $_SESSION['mensaje_tipo'] = 'error';
    } else {
        $sql = "SELECT contra_usuario FROM usuarios WHERE id_usuario = ?";
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($fila = $resultado->fetch_assoc()) {
                $pass_hash = $fila['contra_usuario'];
            }
            $stmt->close();
        }


        if ($pass_hash && password_verify($contra_actual, $pass_hash)) {
            $nueva_pass_hash = password_hash($contra_nueva, PASSWORD_DEFAULT);
            $sql_update = "UPDATE usuarios SET contra_usuario = ? WHERE id_usuario = ?";
            
            if ($stmt = $conexion->prepare($sql_update)) {
                $stmt->bind_param("si", $nueva_pass_hash, $id_usuario);
                if ($stmt->execute()) {
                    $_SESSION['mensaje'] = "Contraseña actualizada correctamente.";
                    $_SESSION['mensaje_tipo'] = 'exito';
                }
                $stmt->close();
            }
        } else {
            $_SESSION['mensaje'] = "La contraseña actual introducida es incorrecta.";
            $_SESSION['mensaje_tipo'] = 'error';
        }
    }
} 

$conexion->close();

header("Location: ../perfil_usuario.php");
exit;
?>