<?php
session_start();
require_once("scripts/conexiones.php");

$ids_en_cesta = $_SESSION['cesta'] ?? [];

$sql = "
    SELECT 
        es.id_evento_solicitado, 
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento, 
        a.imagen_artista,
        es.precio_base AS precio_propuesto,
        es.fecha_hora_inicio,
        es.ubicacion_detalle,
        es.slots_totales,
        (es.slots_totales - COALESCE(SUM(c.cantidad_entradas), 0)) AS slots_disponibles, 
        u.nombre_usuario AS proveedor
    FROM 
        eventos_solicitados es
    JOIN
        artistas a ON es.id_artista = a.id_artista 
    JOIN
        usuarios u ON es.id_organizador = u.id_usuario 
    LEFT JOIN 
        compras c ON es.id_evento_solicitado = c.id_evento_solicitado 
    WHERE 
        es.estado = 'Aprobado'
    GROUP BY 
        es.id_evento_solicitado
    ORDER BY 
        es.fecha_hora_inicio ASC";

$eventos = [];
if ($resultado = $conexion->query($sql)) {
    while ($fila = $resultado->fetch_assoc()) {
        $eventos[] = $fila;
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Eventos</title>
</head>
<body class="bg-gray-50">

    <?php require_once('cabeza.php'); ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Catálogo de Eventos Disponibles
        </h1>

        <?php if (empty($eventos)) { ?>
            <div class="p-6 text-center bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded-lg">
                <p class="font-bold">¡Lo sentimos!</p>
                <p>Actualmente no hay eventos disponibles para la venta.</p>
            </div>
        <?php } else { ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                
                <?php 
                foreach ($eventos as $evento) { 
                    $clase_color = ($evento['slots_disponibles'] > 5) ? 'text-green-600' : 'text-red-600';
                ?>
                    <div class="bg-white rounded-xl shadow-xl overflow-hidden transition duration-300 hover:shadow-2xl hover:scale-[1.02]">
                        
                        <div class="relative h-48 w-full">
                            <img 
                                src="<?php echo htmlspecialchars($evento['imagen_artista']); ?>" 
                                alt="<?php echo htmlspecialchars($evento['nombre_evento']); ?>" 
                                class="w-full h-full object-cover">
                        </div>

                        <div class="p-5 space-y-3">
                            <div class="min-h-[100px] flex flex-col">
                                <div class="flex-1 flex items-center">
                                    <h3 class="text-xl font-bold text-gray-900 leading-snug">
                                        <?php echo htmlspecialchars($evento['nombre_evento']); ?>
                                    </h3>
                                </div>
                                <div class="flex justify-between items-baseline border-b pb-2">
                                    <div class="text-2xl font-bold text-gray-900">
                                        <?php echo number_format($evento['precio_propuesto'], 2, ',', '.'); ?> €
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <span class="font-semibold <?php echo $clase_color; ?>">
                                            <?php echo htmlspecialchars($evento['slots_disponibles']); ?>
                                        </span> disponibles
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 text-gray-700">
                                <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <div class="text-sm font-medium">
                                    <?php echo date("d/m/Y - H:i", strtotime($evento['fecha_hora_inicio'])); ?>h
                                </div>
                            </div>

                            <div class="flex items-start space-x-2 text-gray-700">
                                <svg class="w-5 h-5 text-sky-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <div class="text-sm truncate">
                                    <?php echo htmlspecialchars($evento['ubicacion_detalle']); ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 text-xs text-gray-700">
                                <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <div>Organiza: <strong><?php echo htmlspecialchars($evento['proveedor']); ?></strong></div>
                            </div>
                        </div>

                        <div class="p-5 pt-0">
                            <form action="scripts/anadir_cesta.php" method="POST">
                                <input type="hidden" name="id_evento" value="<?php echo htmlspecialchars($evento['id_evento_solicitado']); ?>">
                                
                                <?php if ($evento['slots_disponibles'] > 0) { ?>
                                    <div class="flex items-center space-x-2 mb-3">
                                        <label class="text-gray-700 text-sm font-medium">Cantidad:</label>
                                        <input type="number" 
                                            name="cantidad" 
                                            value="1" 
                                            min="1" 
                                            max="<?php echo $evento['slots_disponibles']; ?>"  
                                            class="w-full border-2 border-gray-300 rounded-md p-2 text-sm focus:ring-sky-500">
                                    </div>

                                    <button type="submit" 
                                            class="w-full py-2 bg-sky-600 text-white font-semibold rounded-lg hover:bg-sky-700 transition">
                                        Añadir a la Cesta
                                    </button>
                                <?php } else { ?>
                                    <button type="button" disabled
                                            class="w-full py-2 bg-gray-400 text-white font-semibold rounded-lg cursor-not-allowed uppercase">
                                        Agotado
                                    </button>
                                <?php } ?>
                            </form>
                        </div>

                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </main>
</body>
</html>