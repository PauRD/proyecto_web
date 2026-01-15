<?php
session_start();

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario || $privilegio != 'Proveedor') {
    header("Location: jaui.php");
    exit;
}

require_once("scripts/conexiones.php"); 


$sql = "
    SELECT 
        es.id_evento_solicitado, 
        es.nombre_evento,
        es.categoria,
        es.precio_base,
        es.slots_totales AS stock_inicial, 
        es.fecha_hora_inicio,
        es.ubicacion_detalle,
        es.estado,
        a.nombre_artista
    FROM 
        eventos_solicitados es
    JOIN 
        artistas a ON es.id_artista = a.id_artista
    WHERE 
        es.id_organizador = ?
    ORDER BY 
        CASE WHEN es.estado = 'Pendiente' THEN 0 ELSE 1 END, 
        es.fecha_solicitud DESC";

$solicitudes = [];

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $solicitudes[] = $fila;
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
    <title>Gestión de Mis Solicitudes</title>
</head>
<body class="bg-gray-100">
    
    <?php require_once('cabeza.php');  ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-4xl font-extrabold text-gray-900">
                Mis Solicitudes de Eventos
            </h1>
            <a href="nuevo_evento.php" 
                class="px-5 py-2 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 transition duration-150 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <div>Solicitar Nuevo Evento</div>
            </a>
        </div>

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

        <?php if (empty($solicitudes)) { ?>
            <div class="p-8 text-center bg-white border border-gray-200 rounded-lg shadow-lg">
                <h2 class="text-2xl font-semibold text-gray-700">No tienes solicitudes.</h2>
                <p class="mt-2 text-gray-500">Aún no has enviado ninguna solicitud de evento. ¡Empieza creando una!</p>
                <a href="nuevo_evento.php" 
                    class="inline-block mt-6 px-6 py-2 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 transition duration-150">
                    Solicitar mi primer evento
                </a>
            </div>
        <?php } else { ?>
            
            <div class="bg-white shadow-xl rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Precio / Stock</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha y Hora</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($solicitudes as $solicitud) { 
                            $es_pendiente = ($solicitud['estado'] == 'Pendiente');
                            
                            $clase_estado = match ($solicitud['estado']) {
                                'Aprobado' => 'bg-green-100 text-green-800',
                                'Denegado' => 'bg-red-100 text-red-800',
                                default => 'bg-yellow-100 text-yellow-800',
                            };
                        ?>
                        <tr>                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="font-semibold text-sky-800">
                                    <?php echo htmlspecialchars($solicitud['nombre_artista']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($solicitud['nombre_evento']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo htmlspecialchars($solicitud['categoria']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-center">
                                <div class="inline-block font-bold">
                                    <?php echo number_format($solicitud['precio_base'], 2, ',', '.'); ?> €
                                </div>
                                / 
                                <div class="inline-block text-xs text-gray-700">
                                    <?php echo htmlspecialchars($solicitud['stock_inicial']); ?> uds
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo date("d/m/Y - H:i", strtotime($solicitud['fecha_hora_inicio'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate text-center" ><?php echo htmlspecialchars($solicitud['ubicacion_detalle']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $clase_estado; ?>">
                                    <?php echo htmlspecialchars($solicitud['estado']); ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 text-center">
                                <?php if ($es_pendiente) { ?>
                                    <a href="editar_solicitud.php?id=<?php echo $solicitud['id_evento_solicitado']; ?>" 
                                        class="text-sky-600 hover:text-sky-800 transition duration-150">
                                        Editar
                                    </a>
                                    
                                    <a href="scripts/eliminar_solicitud.php?id=<?php echo $solicitud['id_evento_solicitado']; ?>" 
                                        class="text-red-600 hover:text-red-800 transition duration-150">
                                        Eliminar
                                    </a>
                                <?php } else { ?>
                                    <div class="text-gray-400 text-xs italic">
                                        <?php echo ($solicitud['estado'] == 'Aprobado') ? 'Aprobado (No editable)' : 'Rechazado (No editable)'; ?>
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