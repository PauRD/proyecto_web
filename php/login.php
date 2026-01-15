<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    </head>
<body class="bg-gray-50 min-h-screen"> 
    <?php
    require_once('cabeza.php');
    require_once("scripts/conexiones.php");

    $mensaje_error = '';

    if (isset($_SESSION['id_usuario'])) {
        echo "<script>window.location.href='jaui.php'</script>";
        exit;
    }

    $ultimo_valor = isset($_COOKIE["ultimo_valor"]) ? htmlspecialchars($_COOKIE["ultimo_valor"]) : "";
    $ultimo_password = isset($_COOKIE["ultimo_password"]) ? htmlspecialchars($_COOKIE["ultimo_password"]) : "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre'])) {
        $nombre = trim($_POST['nombre']);
        $contra = trim($_POST['contra']);
        
        $sql = "SELECT id_usuario, nombre_usuario, contra_usuario, privilegio_usuario, saldo FROM usuarios WHERE nombre_usuario = ?";

        if ($consulta = $conexion->prepare($sql)) {
            $consulta->bind_param("s", $nombre);
            $consulta->execute();
            $resultado = $consulta->get_result();

            if ($resultado->num_rows > 0) {
                $fila = $resultado->fetch_assoc();

                if (password_verify($contra, $fila['contra_usuario'])) {
      
                    setcookie("ultimo_valor", $fila['nombre_usuario'], time() + (86400 *30), "/");
                    setcookie("ultimo_password", $contra, time() + (86400 *30), "/");
                    
                    $_SESSION['nombre_usuario'] = $fila['nombre_usuario'];
                    $_SESSION['id_usuario'] = $fila['id_usuario'];
                    $_SESSION['privilegio_usuario'] = $fila['privilegio_usuario'];
                    $_SESSION['saldo'] = $fila['saldo'];
                    
                    if ($_SESSION['privilegio_usuario'] == 'Admin') {
                        header("Location: usuarios.php");
                    } else {
                        header("Location: jaui.php");
                    }
                    exit;
                } else {
                    $mensaje_error = 'El usuario y la contraseña no coinciden';
                }
            } else {
                $mensaje_error = 'El usuario no existe';
            }

            $consulta->close();
        } else {
            $mensaje_error = 'Error en la consulta, por favor intente nuevamente.';
        }

        $conexion->close();
    }
    ?>

    <div class="flex items-center justify-center min-h-[calc(100vh-64px)] p-4">
        
        <form class="w-full max-w-md bg-white p-8 rounded-lg shadow-2xl space-y-6" 
              action="login.php" method="post">
            
            <h1 class="text-3xl font-extrabold text-gray-900 text-center">
                Inicia sesión
            </h1>
            
            <?php 
            if ($mensaje_error) {
                echo '<div class="p-3 text-sm text-red-700 bg-red-100 rounded-lg text-center font-medium">';
                echo htmlspecialchars($mensaje_error);
                echo '</div>';
            }
            ?>
            
            <fieldset class="space-y-4">
                
                <div class="space-y-1">
                    <label for="usuario" class="block text-sm font-medium text-gray-700">Usuario:</label>
                    <input 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150" 
                        type="text" 
                        id="usuario" 
                        name="nombre" 
                        value="<?php echo $ultimo_valor ?>" 
                        placeholder="Nombre de usuario" 
                        spellcheck="false" 
                        autofocus>
                </div>

                <div class="relative space-y-1">
                    <label for="contra" class="block text-sm font-medium text-gray-700">Contraseña:</label>
                    <input 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150 pr-10" 
                        type="password" 
                        id="contra" 
                        name="contra" 
                        value="<?php echo $ultimo_password ?>" 
                        placeholder="Contraseña" 
                        spellcheck="false">
                    
                    <div class="absolute inset-y-0 right-0 top-5 flex items-center pr-3 cursor-pointer" id="caja_ojito_login">
                        <img class="w-6 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_login">
                    </div>
                </div>

                <div>
                    <input 
                        class="w-full py-2.5 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition duration-150 cursor-pointer" 
                        type="submit" 
                        value="Iniciar Sesión">
                </div>
            </fieldset>
            
            <p class="text-sm text-center text-gray-600">
                ¿Aún no tienes cuenta? 
                <a href="registro.php" class="font-medium text-sky-600 hover:text-sky-500 transition duration-150">
                    Regístrate aquí
                </a>
            </p>
        </form>
    </div>
    <script src="../js/login.js"></script>
</body>
</html>