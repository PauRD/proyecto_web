<?php
session_start();
require_once("scripts/conexiones.php");

$mensaje_error = '';
$nombre = '';
$email = '';
$sexo = '';

if (isset($_SESSION['id_usuario'])) {
    header("Location: jaui.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $contra = $_POST['contra'] ?? '';
    $contra_confirm = $_POST['contra_confirm'] ?? '';
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $sexo = htmlspecialchars($_POST['sexo'] ?? '');
    
    $errores = [];

    if (empty($nombre) || empty($contra) || empty($contra_confirm) || empty($email) || empty($sexo)) {
        $errores[] = "Todos los campos deben ser rellenados.";
    }
    if ($contra !== $contra_confirm) {
        $errores[] = "La contraseña y la confirmación no coinciden.";
    }
    if (strlen($contra) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email no es válido.";
    }
    
    if (empty($errores)) {
        $sql = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR email_usuario = ?";
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ss", $nombre, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errores[] = "El nombre de usuario o el email ya están registrados.";
            }
            $stmt->close();
        }
    }

    if (empty($errores)) {
        $contra_hash = password_hash($contra, PASSWORD_DEFAULT);
        $privilegio_default = 'Cliente';
        $saldo_default = 0;

        $sql = "
            INSERT INTO usuarios 
            (nombre_usuario, contra_usuario, email_usuario, sexo_usuario, privilegio_usuario, saldo) 
            VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("sssssi", 
                $nombre, $contra_hash, $email, $sexo, $privilegio_default, $saldo_default);
            
            if ($stmt->execute()) {
               $id_nuevo = $stmt->insert_id;

                $_SESSION['id_usuario'] = $id_nuevo;
                $_SESSION['nombre_usuario'] = $nombre;
                $_SESSION['privilegio_usuario'] = $privilegio_default;
                $_SESSION['saldo'] = $saldo_default;

                header("Location: jaui.php");
                exit;
            }
            $stmt->close();
        }
    } 
    
    if (!empty($errores)) {
    $mensaje_error = '<ul>';
    foreach ($errores as $error) {
        $mensaje_error .= '<li>' . htmlspecialchars($error) . '</li>'; 
    }
    $mensaje_error .= '</ul>';
    }
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <?php require_once('cabeza.php'); ?>

    <main class="flex items-center justify-center py-10 px-4 min-h-[calc(100vh-64px)]">
        
        <form class="w-full max-w-lg bg-white p-8 rounded-lg shadow-2xl space-y-6" 
            action="registro.php" method="post">
            <h1 class="text-3xl font-extrabold text-gray-900 text-center">
                Regístrate
            </h1>

            <?php 
            if ($mensaje_error) {
                echo '<div class="p-3 text-sm text-red-700 bg-red-100 rounded-lg font-medium">';
                echo $mensaje_error;
                echo '</div>';
            }
            ?>
            
            <fieldset class="border-t border-gray-200 pt-4 space-y-4">
                <legend class="px-2 text-lg font-semibold text-gray-700">Datos de Acceso</legend>

                <div class="space-y-1">
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Usuario:</label>
                    <input 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150" 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        value="<?php echo htmlspecialchars($nombre); ?>" 
                        placeholder="Elige un nombre de usuario" >
                </div>

                <div class="relative space-y-1"> 
                    <label for="contra" class="block text-sm font-medium text-gray-700">Contraseña:</label>
                    <input 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150 pr-10" 
                        type="password" 
                        id="contra" 
                        name="contra" 
                        placeholder="Mínimo 6 caracteres">
                    
                    <div class="absolute inset-y-0 right-0 top-5 flex items-center pr-3 cursor-pointer" id="caja_ojito_1">
                        <img class="w-6 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_1">
                    </div>
                </div>

                <div class="relative space-y-1">
                    <label for="contra_confirm" class="block text-sm font-medium text-gray-700">Confirmar Contraseña:</label>
                    <input 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150 pr-10" 
                        type="password" 
                        id="contra_confirm" 
                        name="contra_confirm" 
                        placeholder="Repite la contraseña">

                    <div class="absolute inset-y-0 right-0 top-5 flex items-center pr-3 cursor-pointer" id="caja_ojito_2">
                        <img class="w-6 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_2">
                    </div>
                </div>
            </fieldset>

            <fieldset class="border-t border-gray-200 pt-4 space-y-4">
                <legend class="px-2 text-lg font-semibold text-gray-700">Datos Personales</legend>

                <div class="space-y-1">
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail:</label>
                    <input 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150" 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email); ?>" 
                        placeholder="correo@ejemplo.com">
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Sexo:</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-sky-600" name="sexo" value="Mujer" <?php echo ($sexo == 'Mujer') ? 'checked' : ''; ?> >
                            <div class="ml-2 text-gray-700">Femenino</div>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-sky-600" name="sexo" value="Hombre" <?php echo ($sexo == 'Hombre') ? 'checked' : ''; ?>>
                            <div class="ml-2 text-gray-700">Masculino</div>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio text-sky-600" name="sexo" value="Indefinido" <?php echo ($sexo == 'Indefinido') ? 'checked' : ''; ?>>
                            <div class="ml-2 text-gray-700">Prefiero no contestar</div>
                        </label>
                    </div>
                </div>
                
            </fieldset>

            <div>
                <input 
                    class="w-full py-2.5 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition duration-150 cursor-pointer" 
                    type="submit" 
                    value="Completar Registro">
            </div>
            
            <p class="text-sm text-center text-gray-600">
                ¿Ya tienes cuenta? 
                <a href="login.php" class="font-medium text-sky-600 hover:text-sky-500 transition duration-150">
                    Inicia sesión aquí
                </a>
            </p>
        </form>
    </main>
    <script src="../js/registro.js"></script>
</body>
</html>