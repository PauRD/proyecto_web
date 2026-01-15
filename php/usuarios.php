<?php
session_start();
require_once('scripts/conexiones.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios</title>
    </head>
<body class="bg-gray-100">
    <?php
    require_once('cabeza.php');
    
    $id_admin_logueado = $_SESSION['id_usuario'];

    if ($_SESSION['privilegio_usuario'] !== 'Admin') {
        header("Location: jaui.php");
        exit;
    }

    $sql = "SELECT id_usuario, nombre_usuario, email_usuario, sexo_usuario, privilegio_usuario, saldo 
            FROM usuarios 
            ORDER BY FIELD(privilegio_usuario, 'Admin', 'Proveedor', 'Cliente'), id_usuario ASC";
    
    $consulta = $conexion->prepare($sql);
    $consulta->execute();
    $resultado = $consulta->get_result();
    $conexion->close();
    ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Gestión de Usuarios
        </h1>
        <div class="flex flex-wrap justify-center gap-4 mb-8">
            <a href="nuevo_cliente.php" class="px-5 py-2.5 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-150">
                + Nuevo Cliente
            </a>
            <a href="nuevo_proveedor.php" class="px-5 py-2.5 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 transition duration-150">
                + Nuevo Proveedor
            </a>
            <a href="nuevo_admin.php" class="px-5 py-2.5 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150">
                + Nuevo Administrador
            </a>
        </div>
        
        <?php
        if (isset($_SESSION['mensaje'])) {
            $tipo_mensaje = $_SESSION['mensaje_tipo'] ?? 'exito';
            $clase_alerta = ($tipo_mensaje == 'error') 
                ? 'bg-red-100 border-red-500 text-red-700' 
                : 'bg-green-100 border-green-500 text-green-700';
            echo "<div class='p-4 mb-6 border-l-4 rounded-lg shadow-md {$clase_alerta}' role='alert'>";
            echo "<p class='font-bold'>" . str_replace(['**'], ['<strong>'], htmlspecialchars($_SESSION['mensaje'])) . "</p>";
            echo "</div>";
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
        }
        ?>

        <?php if ($resultado->num_rows > 0) { ?>
            <div class="bg-white shadow-xl rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sexo</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo (€)</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Privilegio</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($fila = $resultado->fetch_assoc()) { 
                            $admin_actual = ($fila['id_usuario'] == $id_admin_logueado);
                            
                            $clase_privilegio = 'bg-white hover:bg-gray-200';
                            switch ($fila['privilegio_usuario']) {
                                case 'Admin':
                                    $clase_privilegio = 'bg-red-100 hover:bg-red-200'; 
                                    break;
                                case 'Proveedor':
                                    $clase_privilegio = 'bg-purple-100 hover:bg-purple-200'; 
                                    break;
                                case 'Cliente':
                                    $clase_privilegio = 'bg-green-100 hover:bg-green-200'; 
                                    break;
                            }

                            if ($admin_actual) {
                                $clase_privilegio = 'bg-sky-50 hover:bg-sky-100';
                            }
                        ?>
                        <tr class="<?php echo $clase_privilegio; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center">#<?php echo htmlspecialchars($fila['id_usuario']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center"><?php echo htmlspecialchars($fila['nombre_usuario']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo htmlspecialchars($fila['email_usuario']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo htmlspecialchars($fila['sexo_usuario']); ?></td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <form action="scripts/actualizar_privilegio_saldo.php" method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="id_usuario" value="<?php echo $fila['id_usuario']; ?>">
                                    <input type="number" 
                                            name="saldo" 
                                            value="<?php echo htmlspecialchars($fila['saldo']); ?>" 
                                            step="1" 
                                            class="w-20 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-sky-500 focus:border-sky-500">
                                    <button type="submit" name="accion" value="actualizar_saldo" 
                                            class="px-2 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-xs font-medium transition duration-150">
                                        Guardar
                                    </button>
                                </form>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <form action="scripts/actualizar_privilegio_saldo.php" method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="id_usuario" value="<?php echo $fila['id_usuario']; ?>">
                                    <select name="privilegio" 
                                            class="w-32 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-sky-500 focus:border-sky-500"
                                            <?php echo $admin_actual ? 'disabled' : ''; ?>>
                                        <option value="Cliente" <?php echo ($fila['privilegio_usuario'] == 'Cliente') ? 'selected' : ''; ?>>Cliente</option>
                                        <option value="Proveedor" <?php echo ($fila['privilegio_usuario'] == 'Proveedor') ? 'selected' : ''; ?>>Proveedor</option>
                                        <option value="Admin" <?php echo ($fila['privilegio_usuario'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="accion" value="actualizar_privilegio" 
                                            class="px-2 py-1 bg-sky-500 text-white rounded-md hover:bg-sky-600 text-xs font-medium transition duration-150
                                            <?php echo $admin_actual ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                            <?php echo $admin_actual ? 'disabled' : ''; ?>>
                                        Cambiar
                                    </button>
                                </form>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 text-center">
                                <?php if (!$admin_actual) { ?>
                                    <a href='editar_usuario.php?id=<?php echo $fila['id_usuario']; ?>'
                                    class="text-sky-600 hover:text-sky-800 transition duration-150">
                                        Editar
                                    </a>
                                    
                                    <a href='scripts/eliminar_usuario.php?id=<?php echo $fila['id_usuario']; ?>'
                                    class="text-red-600 hover:text-red-800 transition duration-150
                                    <?php echo $admin_actual ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                        Eliminar
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="p-6 text-center bg-gray-50 border border-gray-200 rounded-lg shadow-md">
                <p class="font-bold text-lg text-gray-700">No hay usuarios registrados.</p>
                <p class="text-gray-500">La base de datos de usuarios está vacía.</p>
            </div>
        <?php } ?>
    </main>
</body>
</html>