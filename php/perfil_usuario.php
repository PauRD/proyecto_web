<?php
session_start();
$id_usuario = $_SESSION['id_usuario'];

if (!$id_usuario) {
    header("Location: login.php");
    exit;
}

require_once("scripts/conexiones.php"); 

$sql_usuario = "SELECT nombre_usuario, email_usuario, sexo_usuario, privilegio_usuario, saldo FROM usuarios WHERE id_usuario = ?";

if ($stmt = $conexion->prepare($sql_usuario)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datos_usuario = $resultado->fetch_assoc();
    $stmt->close();
}
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil de Usuario</title>
</head>
<body class="bg-gray-100">
    
    <?php require_once('cabeza.php'); ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Mi Perfil y Configuración
        </h1>

        <?php
        if (isset($_SESSION['mensaje'])) {
            $tipo_mensaje = $_SESSION['mensaje_tipo'] ?? 'exito';
            $clase_alerta = ($tipo_mensaje == 'error') 
                ? 'bg-red-100 border-red-500 text-red-700' 
                : 'bg-green-100 border-green-500 text-green-700';
            echo "<div class='p-4 mb-6 border-l-4 rounded-lg shadow-md {$clase_alerta}' role='alert'>";
            echo "<p class='font-bold'>" . htmlspecialchars($_SESSION['mensaje']) . "</p>";
            echo "</div>";
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
        }
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-xl border border-gray-200 h-fit sticky top-24">
                
                <div class="text-center pb-4 border-b">
                    <div class="mx-auto w-20 h-20 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center text-3xl font-bold mb-3">
                        <?php echo strtoupper(substr($datos_usuario['nombre_usuario'], 0, 1)); ?>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($datos_usuario['nombre_usuario']); ?>
                    </h2>
                    <?php 
                    if ($datos_usuario['privilegio_usuario'] !== 'Cliente') { 
                    ?>
                        <p class="text-sm font-semibold mt-1 px-3 py-1 inline-block rounded-full 
                            <?php
                                $priv_class = match ($datos_usuario['privilegio_usuario']) {
                                    'Admin' => 'bg-red-100 text-red-700',
                                    'Proveedor' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-green-100 text-green-700',
                                };
                                echo $priv_class;
                            ?>">
                            <?php echo htmlspecialchars($datos_usuario['privilegio_usuario']); ?>
                        </p>
                    <?php 
                    } 
                    ?>
                </div>
                
                <div class="mt-4 space-y-3">
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Datos Clave</h3>
                    <div class="flex justify-between text-sm text-gray-600">
                        <div>Email:</div>
                        <div class="font-medium truncate"><?php echo htmlspecialchars($datos_usuario['email_usuario']); ?></div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <div>Sexo:</div>
                        <div class="font-medium"><?php echo htmlspecialchars($datos_usuario['sexo_usuario']); ?></div>
                    </div>
                    <?php if ($datos_usuario['privilegio_usuario'] === 'Cliente') { ?>
                    <div class="flex justify-between text-sm text-gray-600 font-bold pt-2 border-t">
                        <div>SALDO DISPONIBLE:</div>
                        <div class="text-sky-600"><?php echo number_format($datos_usuario['saldo'], 0, ',', '.'); ?> €</div>
                    </div>
                    <a href="mi_saldo.php" class="block w-full text-center mt-3 py-2 bg-sky-600 text-white text-sm font-semibold rounded-lg hover:bg-sky-700">
                        Recargar Saldo
                    </a>
                    <?php } ?>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Datos Personales</h2>
                    
                    <form action="scripts/actualizar_perfil.php" method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nombre_usuario" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                                <input type="text" id="nombre_usuario" name="nombre_usuario"
                                       value="<?php echo htmlspecialchars($datos_usuario['nombre_usuario']); ?>"
                                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500">
                            </div>
                            <div>
                                <label for="email_usuario" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email_usuario" name="email_usuario" 
                                       value="<?php echo htmlspecialchars($datos_usuario['email_usuario']); ?>"
                                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="sexo_usuario" class="block text-sm font-medium text-gray-700">Sexo</label>
                            <select id="sexo_usuario" name="sexo_usuario" 
                                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500">
                                <option value="Hombre" <?php echo ($datos_usuario['sexo_usuario'] == 'Hombre') ? 'selected' : ''; ?>>Hombre</option>
                                <option value="Mujer" <?php echo ($datos_usuario['sexo_usuario'] == 'Mujer') ? 'selected' : ''; ?>>Mujer</option>
                                <option value="Indefinido" <?php echo ($datos_usuario['sexo_usuario'] == 'Indefinido') ? 'selected' : ''; ?>>Indefinido</option>
                            </select>
                        </div>

                        <button type="submit" name="accion" value="datos_personales"
                                class="w-full py-2.5 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 transition duration-150">
                            Guardar Datos Personales
                        </button>
                    </form>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Cambiar Contraseña</h2>
                    
                    <form action="scripts/actualizar_perfil.php" method="POST" class="space-y-4">
                        
                        <div>
                            <label for="contra_actual" class="block text-sm font-medium text-gray-700">Contraseña Actual</label>
                            <div class="relative mt-1">
                                <input type="password" id="contra_actual" name="contra_actual" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 pr-10">
                                <div class="absolute inset-y-0 right-0 top-0 flex items-center pr-3 cursor-pointer" id="caja_ojito_1">
                                    <img class="w-6 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="contra_nueva" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                                <div class="relative mt-1">
                                    <input type="password" id="contra_nueva" name="contra_nueva" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 pr-10">
                                    <div class="absolute inset-y-0 right-0 top-0 flex items-center pr-3 cursor-pointer" id="caja_ojito_2">
                                        <img class="w-6 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_2">
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="contra_confirmar" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña</label>
                                <div class="relative mt-1">
                                    <input type="password" id="contra_confirmar" name="contra_confirmar" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 pr-10">
                                    <div class="absolute inset-y-0 right-0 top-0 flex items-center pr-3 cursor-pointer" id="caja_ojito_3">
                                        <img class="w-6 h-5 text-gray-400" src="..\img\cerrado.png" alt="cerrado" id="ojito_3">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="accion" value="cambiar_contraseña"
                                class="w-full py-2.5 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150">
                            Actualizar Contraseña
                        </button>
                    </form>
                </div>
            </div>
            
        </div>
    </main>
<script src="../js/perfil_usuario.js"></script>
</body>
</html>