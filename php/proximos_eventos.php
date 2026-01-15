<?php
session_start();

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario) {
    header("Location: login.php");
    exit;
}

require_once("scripts/conexiones.php"); 

$sql = "
    SELECT 
        es.id_evento_solicitado,
        SUM(c.cantidad_entradas) AS cantidad_total_entradas, 
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
        a.imagen_artista AS imagen_evento,
        es.ubicacion_detalle,
        es.fecha_hora_inicio
    FROM 
        compras c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    JOIN
        artistas a ON es.id_artista = a.id_artista
    WHERE 
        c.id_usuario = ? 
        AND es.fecha_hora_inicio > NOW()
    GROUP BY 
        es.id_evento_solicitado, nombre_evento, imagen_evento, ubicacion_detalle, fecha_hora_inicio
    ORDER BY 
        es.fecha_hora_inicio ASC";

$eventos_comprados = [];

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($evento = $resultado->fetch_assoc()) {
        $eventos_comprados[] = $evento;
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
    <title>Mis Próximos Eventos</title>
    </head>
<body>
    
    <?php require_once('cabeza.php');  ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <h1 class="text-4xl font-extrabold text-gray-900 mb-8">
            Mis Próximos Eventos
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

        if (empty($eventos_comprados)) {
            echo '<div class="p-6 text-center bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded-lg">
                    <p class="font-bold">Aún no has comprado entradas para ningún evento</p>
                    <p>¡Explora el <a href="eventos.php" class="font-semibold underline hover:text-yellow-800">catálogo</a>!</p>
                </div>';
        } else {
        ?>
            <section class="space-y-6">
                <?php 
                foreach ($eventos_comprados as $evento) { 
                ?>
                    <article class="bg-white rounded-xl shadow-lg overflow-hidden md:flex transition duration-300 hover:shadow-xl hover:translate-y-[-2px]">
                        
                        <div class="md:w-1/3 h-48 md:h-auto flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($evento['imagen_evento']); ?>" 
                                alt="Imagen de <?php echo htmlspecialchars($evento['nombre_evento']); ?>" 
                                class="w-full h-full object-cover"/>
                        </div>
                        
                        <div class="md:w-2/3 p-5 md:p-6 flex flex-col justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                    <?php echo htmlspecialchars($evento['nombre_evento']); ?>
                                </h3>
                                
                                <p class="text-sm text-gray-500 space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <strong>Ubicación:</strong> <?php echo htmlspecialchars($evento['ubicacion_detalle']); ?>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-5 h-5 text-sky-500 " fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <strong>Fecha y Hora:</strong> <?php echo date("d/m/Y H:i", strtotime($evento['fecha_hora_inicio'])); ?>h
                                    </div>
                                    
                                    <div class="flex items-center space-x-2 text-base font-semibold text-gray-700">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 010 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 010-4V7a2 2 0 00-2-2H5z"></path></svg>
                                        <?php echo htmlspecialchars($evento['cantidad_total_entradas']); ?> Entradas
                                    </div>
                                </p>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-dashed border-gray-200 flex justify-end items-center">   
                                <a href="ver_entradas.php?id=<?php echo $evento['id_evento_solicitado']; ?>" 
                                class="px-4 py-2 bg-sky-600 text-white font-semibold rounded-lg text-sm shadow-md transition duration-150 hover:bg-sky-700 inline-block">
                                    Ver Entradas
                                </a>
                            </div>
                        </div>
                    </article>
                <?php } ?>
            </section>
        <?php } ?>
    </main>
</body>
</html>