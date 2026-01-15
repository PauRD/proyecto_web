<?php
session_start();

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario || $privilegio != 'Proveedor') {
    header("Location: jaui.php");
    exit;
}

require_once("scripts/conexiones.php"); 

$sql_artistas = "
    SELECT 
        a.id_artista, 
        a.nombre_artista, 
        a.imagen_artista,
        a.descripcion,
        a.estado,
        a.fecha_solicitud,
        u_prov.nombre_usuario AS nombre_proveedor,
        u_admin.nombre_usuario AS nombre_admin_aprobador
    FROM 
        artistas a
    JOIN 
        usuarios u_prov ON a.id_proveedor = u_prov.id_usuario
    LEFT JOIN
        usuarios u_admin ON a.id_admin_aprobador = u_admin.id_usuario
    WHERE a.id_proveedor = ? 
    ORDER BY 
        CASE WHEN a.estado = 'Pendiente' THEN 0 ELSE 1 END, 
        a.fecha_solicitud DESC";

$artistas_solicitados = [];

if ($stmt = $conexion->prepare($sql_artistas)) {
    $stmt->bind_param("i", $id_usuario); 
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($artista = $resultado->fetch_assoc()) {
        $artistas_solicitados[] = $artista;
    }
    $stmt->close();
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Artistas Solicitados</title>
</head>
<body class="bg-gray-100">

    <?php require_once('cabeza.php'); ?> 

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-4xl font-extrabold text-gray-900">
                Mis Artistas Solicitados
            </h1>
            <a href="nuevo_artista.php" 
                class="px-5 py-2 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 transition duration-150 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <div>Solicitar Nuevo Artista</div>
            </a>
        </div>

        <?php if (empty($artistas_solicitados)) { ?>
            <div class="p-8 text-center bg-white border border-gray-200 rounded-lg shadow-lg">
                <h2 class="text-2xl font-semibold text-gray-700">No tienes artistas registrados.</h2>
                <p class="mt-2 text-gray-500">Aún no has solicitado ningún artista. ¡Empieza creando uno!</p>
                <a href="nuevo_artista.php" 
                    class="inline-block mt-6 px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 transition duration-150">
                    Solicitar mi primer artista
                </a>
            </div>
        <?php } else { ?>
            
            <div class="bg-white shadow-xl rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Artista</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitado</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aprobador</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($artistas_solicitados as $artista) { 
                            $es_pendiente = ($artista['estado'] == 'Pendiente');
                            
                            $clase_estado = match ($artista['estado']) {
                                'Aprobado' => 'bg-green-100 text-green-800',
                                'Denegado' => 'bg-red-100 text-red-800',
                                default => 'bg-yellow-100 text-yellow-800',
                            };
                        ?>
                        <tr>
                            <td class="px-6 py-4 flex items-center text-sm text-gray-700">
                                <img class="h-10 w-10 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars($artista['imagen_artista']); ?>" alt="Imagen de <?php echo htmlspecialchars($artista['nombre_artista']); ?>">
                                <div>
                                    <strong class="font-semibold"><?php echo htmlspecialchars($artista['nombre_artista']); ?></strong>
                                    <p class="text-xs text-gray-500 max-w-xs truncate">
                                        <?php echo htmlspecialchars($artista['descripcion'] ?? 'Sin descripción'); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo date("d/m/Y", strtotime($artista['fecha_solicitud'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $clase_estado; ?>">
                                    <?php echo htmlspecialchars($artista['estado']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <?php 
                                    echo htmlspecialchars($artista['nombre_admin_aprobador'] ?? 'N/A');
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 text-center">
                                <?php if ($es_pendiente) { ?>
                                    <div class="text-gray-500 text-xs italic">Pendiente de Revisión</div>
                                <?php } else { ?>
                                    <div class="text-gray-400 text-xs italic">
                                        <?php echo ($artista['estado'] == 'Aprobado') ? 'Listo para preparar eventos' : 'No nos interesa este artista'; ?>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </main>
</body>
</html>