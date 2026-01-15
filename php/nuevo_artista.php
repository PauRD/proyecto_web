<?php
session_start();

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario || $privilegio != 'Proveedor') {
    header("Location: jaui.php");
    exit;
}

require_once("scripts/conexiones.php"); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nombre_artista = trim($_POST['nombre_artista']);
    $id_proveedor = $_SESSION['id_usuario'];

    $descripcion_temp = trim($_POST['descripcion'] ?? '');
    $descripcion = empty($descripcion_temp) ? NULL : $descripcion_temp;
    
    $errores = [];
    if (empty($nombre_artista)) {
        $errores[] = "Tu artista no tiene nombre.";
    }
    if (empty($_FILES['imagen_artista']['name'])) {
        $errores[] = "Debe subir una imagen para el artista.";
    }
    if (empty($errores)) {
        $sql = "SELECT COUNT(*) AS total FROM artistas WHERE nombre_artista = ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("s", $nombre_artista);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $fila = $resultado->fetch_assoc();
            $stmt->close();
            
            if ($fila['total'] > 0) {
                $errores[] = "El artista <strong>{$nombre_artista}</strong> ya existe en el sistema. Por favor, elige un nombre diferente.";
            }
        }
    }
    if (empty($errores)) {
        $directorio_destino = "../img/artistas/"; 
        $archivo_nombre = basename($_FILES["imagen_artista"]["name"]);
        $extension_archivo = strtolower(pathinfo($archivo_nombre, PATHINFO_EXTENSION));
        $nuevo_nombre_archivo = uniqid() . "." . $extension_archivo;
        $ruta_completa_destino = $directorio_destino . $nuevo_nombre_archivo;
        
        if ($_FILES["imagen_artista"]["size"] > 500000) {
            $errores[] = "Lo sentimos, el archivo de imagen es demasiado grande (máx 500KB).";
        }

        if($extension_archivo != "jpg" && $extension_archivo != "png" && $extension_archivo != "jpeg") {
            $errores[] = "Solo se permiten archivos JPG, JPEG y PNG.";
        }

        if (empty($errores)) {
            
            if (move_uploaded_file($_FILES["imagen_artista"]["tmp_name"], $ruta_completa_destino)) {

                $sql_insert = "
                    INSERT INTO artistas 
                        (nombre_artista, id_proveedor, imagen_artista, descripcion, estado) 
                    VALUES (?, ?, ?, ?, 'Pendiente')";
                
                if ($stmt = $conexion->prepare($sql_insert)) {
                    $stmt->bind_param("siss", $nombre_artista, $id_proveedor, $ruta_completa_destino, $descripcion);
                    if ($stmt->execute()) {
                        $_SESSION['exito'] = "Solicitud del artista <strong>{$nombre_artista}</strong> enviada correctamente. Será revisada por un administrador.";
                        header("Location: nuevo_artista.php"); 
                        exit;
                    }
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $errores[] = "El nombre del artista ya esta registrado.";
                    }
                    $stmt->close();
                }
            }
        }
    } else {
    $_SESSION['errores'] = $errores;
    header("Location: nuevo_artista.php");
    exit; 
}
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Nuevo Artista</title>
</head>
<body class="bg-gray-100">

    <?php require_once('cabeza.php'); ?>
    
    <main class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-6">
            Solicitar Nuevo Artista
        </h1>
        <p class="text-center text-gray-500 mb-10">
            Completa la información para que un administrador apruebe la inclusión de tu artista en el catálogo.
        </p>

        <?php 
        if (!empty($_SESSION['errores'])) {
            foreach ($_SESSION['errores'] as $error) {
                echo "<div class='p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg border-l-4 border-red-500'>{$error}</div>";
            }
            unset($_SESSION['errores']);
        }
        if (isset($_SESSION['exito'])) {
             echo "<div class='p-4 mb-6 text-sm text-green-700 bg-green-100 rounded-lg border-l-4 border-green-500' role='alert'>
                       {$_SESSION['exito']}
                   </div>";
             unset($_SESSION['exito']); 
        }
        ?>

        <div class="bg-white p-8 rounded-xl shadow-2xl border border-gray-200">
            
            <form action="nuevo_artista.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div>
                    <label for="nombre_artista" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Completo o Grupo del Artista:
                    </label>
                    <input type="text" id="nombre_artista" name="nombre_artista" maxlength="100"
                            placeholder="Ej: Rosalía, The Weeknd, Extremoduro"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                </div>
                
                <div>
                    <label for="imagen_artista" class="block text-sm font-medium text-gray-700 mb-1">
                        Imagen/Foto de Perfil del Artista (JPG/JPEG/PNG, máx 500KB):
                    </label>
                    <input type="file" id="imagen_artista" name="imagen_artista" accept="image/jpeg,image/png"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">
                        Breve Descripción (Opcional):
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="4" 
                            placeholder="Una breve descripción sobre el estilo, trayectoria o logros del artista."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150"></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-green-600 text-white font-semibold rounded-lg shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150">
                    Enviar Solicitud de Artista
                </button>
            </form>
            
        </div>
        
    </main>
</body>
</html>