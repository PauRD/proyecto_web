<?php
session_start(); 
require_once("scripts/conexiones.php");

$id_admin_logueado = $_SESSION['id_usuario'];
$privilegio_admin = $_SESSION['privilegio_usuario'];

if ($privilegio_admin !== 'Admin') {
    header("Location: jaui.php");
    exit;
}

$identificador = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && $identificador) {
    
    $nombre = htmlspecialchars($_POST['nombre']);
    $contra = $_POST['contra'];
    $email = htmlspecialchars($_POST['email']);
    $sexo = htmlspecialchars($_POST['sexo']);
    $privilegio_nuevo = htmlspecialchars($_POST['privilegio']);
    $saldo_nuevo = $_POST['saldo'];


    $errores = [];
    if (empty($nombre) || empty($email) || empty($sexo)) {
        $errores[] = "Te faltan campos por rellenar.";
    }
    if (strlen($nombre) < 3 || strlen($nombre) > 20) {
        $errores[] = "El nombre de usuario debe tener entre 3 y 20 caracteres.";
    }
    if (!empty($contra) && (strlen($contra) < 6 || strlen($contra) > 15)) {
        $errores[] = "La contraseña, si se cambia, debe tener entre 6 y 15 caracteres.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email es incorrecto.";
    }
    if (!empty($saldo_nuevo) && $saldo_nuevo < 0) {
        $errores[] = "El saldo no puede ser negativo.";
    }
    if (!empty($nombre) && !empty($email)) {
        $sql = "SELECT id_usuario, nombre_usuario, email_usuario FROM usuarios WHERE (nombre_usuario = ? OR email_usuario = ?) AND id_usuario != ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssi", $nombre, $email, $identificador);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        while ($usuario_duplicado = $resultado->fetch_assoc()) {
            if ($usuario_duplicado['nombre_usuario'] == $nombre) {
                $errores[] = "El nombre de usuario '{$nombre}' ya está en uso por otro usuario.";
            }
            if ($usuario_duplicado['email_usuario'] == $email) {
                $errores[] = "El email '{$email}' ya está en uso por otro usuario.";
            }
        }
        $stmt->close();
    }



    if (empty($errores)) {
        if (!empty($contra)) {

            $contra_hash = password_hash($contra, PASSWORD_DEFAULT);
            $sql = "
                UPDATE usuarios 
                SET nombre_usuario = ?, contra_usuario = ?, email_usuario = ?, sexo_usuario = ?, privilegio_usuario = ?, saldo = ? 
                WHERE id_usuario = ?";
            
            if ($stmt = $conexion->prepare($sql)) {
                $stmt->bind_param("sssssii", $nombre, $contra_hash, $email, $sexo, $privilegio_nuevo, $saldo_nuevo, $identificador);
            }
        } else {
            $sql = "
                UPDATE usuarios 
                SET nombre_usuario = ?, email_usuario = ?, sexo_usuario = ?, privilegio_usuario = ?, saldo = ? 
                WHERE id_usuario = ?";
            
            if ($stmt = $conexion->prepare($sql)) {
                $stmt->bind_param("ssssii", $nombre, $email, $sexo, $privilegio_nuevo, $saldo_nuevo, $identificador);
            }
        }

        if (isset($stmt)) {
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Usuario #{$identificador} actualizado.";
                header("Location: usuarios.php");
                exit;
            }
            $stmt->close();
        }
    }
}


if ($identificador) {
    $sql = "SELECT id_usuario, nombre_usuario, email_usuario, sexo_usuario, privilegio_usuario, saldo FROM usuarios WHERE id_usuario = ?";

    if ($consulta = $conexion->prepare($sql)) {
        $consulta->bind_param("i", $identificador);
        $consulta->execute();
        $resultado = $consulta->get_result();

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
        } else {
            $_SESSION['mensaje'] = "Usuario no encontrado.";
            $_SESSION['mensaje_tipo'] = 'error';
            header("Location: usuarios.php");
            exit;
        }
        $consulta->close();
    }
} else {
    $_SESSION['mensaje'] = "Error: No se especificó el ID de usuario a editar.";
    $_SESSION['mensaje_tipo'] = 'error';
    header("Location: usuarios.php");
    exit;
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario (Admin)</title>
    </head>
<body class="bg-gray-100"> 
    <?php
    require_once('cabeza.php');
    ?>
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white p-8 shadow-xl rounded-lg">

            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Editando Usuario #<?php echo htmlspecialchars($identificador); ?>
            </h1>
            <p class="text-lg text-orange-600 font-semibold mb-6 border-b pb-4">
                Editando a: <strong><?php echo htmlspecialchars($fila['nombre_usuario']); ?></strong>
            </p>

            <?php 
            if (!empty($errores)) {
                foreach ($errores as $error) {
                    echo "<div class='p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg border-l-4 border-red-500'>{$error}</div>";
                }
            }
            ?>

            <form action="editar_usuario.php?id=<?php echo htmlspecialchars($identificador); ?>" method="post" class="space-y-6">
                
                <fieldset class="border border-gray-300 p-4 rounded-lg">
                    <legend class="text-base font-semibold text-gray-900 px-2">Datos de Perfil y Acceso</legend>
                    
                    <div class='mt-3 space-y-4'>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID Usuario:</label>
                            <input class='mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm p-2 text-gray-500 cursor-not-allowed' 
                            type='text' 
                            value='<?php echo htmlspecialchars($identificador); ?>' 
                            disabled>
                        </div>

                        <div>
                            <label for='nombre' class="block text-sm font-medium text-gray-700">Usuario: </label>
                            <input class='mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-sky-500 focus:border-sky-500' 
                            type='text' 
                            id='nombre' 
                            name='nombre' 
                            value='<?php echo htmlspecialchars($fila['nombre_usuario']); ?>' >
                        </div>

                        <div>
                            <label for='email' class="block text-sm font-medium text-gray-700">E-mail: </label>
                            <input class='mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-sky-500 focus:border-sky-500' 
                            type='email' 
                            id='email' 
                            name='email' 
                            value='<?php echo htmlspecialchars($fila['email_usuario']); ?>' >
                        </div>
                        
                        <div class="relative">
                            <label for='contra' class="block text-sm font-medium text-gray-700">Nueva Contraseña:</label>
                            <input class='mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-sky-500 focus:border-sky-500' 
                                type='password' 
                                id='contra' 
                                name='contra' 
                                value='' 
                                placeholder="Dejar vacío para no cambiar" 
                                spellcheck='false'>
                            <div class="absolute inset-y-0 right-0 top-6 flex items-center pr-3 cursor-pointer" id="caja_ojito_editar">
                                <img class="w-5 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_editar">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border border-gray-300 p-4 rounded-lg">
                    <legend class="text-base font-semibold text-gray-900 px-2">Datos Administrativos (Privilegios y Saldo)</legend>

                    <div class='mt-3 grid grid-cols-1 md:grid-cols-2 gap-4'>

                        <div>
                            <label for='saldo' class="block text-sm font-medium text-gray-700">Saldo:</label>
                            <input class='mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-sky-500 focus:border-sky-500' 
                            type='number' 
                            id='saldo' 
                            name='saldo' 
                            value='<?php echo htmlspecialchars($fila['saldo']); ?>' 
                            step="1">
                        </div>

                        <div>
                            <label for="privilegio" class="block text-sm font-medium text-gray-700">Privilegio:</label>
                            <select 
                                id="privilegio" 
                                name="privilegio" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="Cliente" <?php echo ($fila['privilegio_usuario'] == 'Cliente') ? 'selected' : ''; ?>>Cliente</option>
                                <option value="Proveedor" <?php echo ($fila['privilegio_usuario'] == 'Proveedor') ? 'selected' : ''; ?>>Proveedor</option>
                                <option value="Admin" <?php echo ($fila['privilegio_usuario'] == 'Admin') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>
                </fieldset>
                
                <fieldset class="border border-gray-300 p-4 rounded-lg">
                    <legend class="text-base font-semibold text-gray-900 px-2">Otras Preferencias</legend>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sexo:</label>
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center">
                                <input id="mujer" 
                                type="radio"
                                name="sexo" 
                                value="Mujer" 
                                class="focus:ring-sky-500 h-4 w-4 text-sky-600 border-gray-300" 
                                <?php echo ($fila['sexo_usuario'] == 'Mujer') ? 'checked' : ''; ?>>
                                <label for="mujer" class="ml-2 block text-sm text-gray-700">Femenino</label>
                            </div>
                            <div class="flex items-center">
                                <input id="hombre" 
                                type="radio" 
                                name="sexo" 
                                value="Hombre" 
                                class="focus:ring-sky-500 h-4 w-4 text-sky-600 border-gray-300" 
                                <?php echo ($fila['sexo_usuario'] == 'Hombre') ? 'checked' : ''; ?>>
                                <label for="hombre" class="ml-2 block text-sm text-gray-700">Masculino</label>
                            </div>
                            <div class="flex items-center">
                                <input id="indefinido" 
                                type="radio" 
                                name="sexo" 
                                value="Indefinido" 
                                class="focus:ring-sky-500 h-4 w-4 text-sky-600 border-gray-300" 
                                <?php echo ($fila['sexo_usuario'] == 'Indefinido') ? 'checked' : ''; ?>>
                                <label for="indefinido" class="ml-2 block text-sm text-gray-700">Prefiero no contestar</label>
                            </div>
                        </div>
                    </div>
                    
                    </fieldset>

                <div class="pt-4 flex justify-between space-x-3 border-t border-gray-200">
                    <button class="px-4 py-2 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 transition duration-150" type="submit">
                        Actualizar Usuario
                    </button>
                    <a class="px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg shadow-sm hover:bg-gray-100 transition duration-150" href="usuarios.php">
                        Volver a la Lista
                    </a>
                </div>
                
            </form>
        </div>
    </main>
    <script src="../js/editar_usuario.js"></script>
</body>
</html>