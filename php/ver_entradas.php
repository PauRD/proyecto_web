<?php
session_start();
require_once("scripts/conexiones.php");

$id_usuario = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre_usuario'];
$id_evento = $_GET['id'];

if (!$id_usuario || !$id_evento) {
    header("Location: proximos_eventos.php");
    exit;
}

$sql = "
    SELECT 
        es.id_evento_solicitado,
        SUM(c.cantidad_entradas) AS cantidad_total_entradas, 
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
        a.imagen_artista,
        es.ubicacion_detalle,
        es.fecha_hora_inicio,
        es.categoria,
        es.precio_base
    FROM 
        compras c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    JOIN
        artistas a ON es.id_artista = a.id_artista
    WHERE 
        c.id_usuario = ? AND es.id_evento_solicitado = ?
    GROUP BY 
        es.id_evento_solicitado";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $id_evento);
$stmt->execute();
$resultado = $stmt->get_result();
$ticket = $resultado->fetch_assoc();

if (!$ticket) {
    header("Location: proximos_eventos.php");
    exit;
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu Entrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-6">

    <div class="mb-8 w-full max-w-md">
        <a href="proximos_eventos.php" class="flex items-center text-gray-600 hover:text-sky-600 transition font-semibold">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver a mis eventos
        </a>
    </div>

    <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        
        <div class="h-48 relative">
            <img src="<?php echo htmlspecialchars($ticket['imagen_artista']); ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-sky-900 to-transparent opacity-80"></div>
            <div class="absolute bottom-6 left-8 right-8 text-white">
                <span class="text-xs uppercase tracking-widest bg-sky-500 px-2 py-1 rounded-md mb-2 inline-block">Confirmado</span>
                <h1 class="text-2xl font-bold leading-tight"><?php echo htmlspecialchars($ticket['nombre_evento']); ?></h1>
            </div>
        </div>

        <div class="p-8 space-y-8 bg-white">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-tighter font-bold mb-1">Nombre del Asistente</p>
                    <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($nombre_usuario); ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400 uppercase tracking-tighter font-bold mb-1">Entradas</p>
                    <p class="text-3xl font-black text-sky-600"><?php echo $ticket['cantidad_total_entradas']; ?></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 border-y border-gray-50 py-6">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold mb-1">Fecha</p>
                    <p class="font-semibold text-gray-700"><?php echo date("d M, Y", strtotime($ticket['fecha_hora_inicio'])); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold mb-1">Hora</p>
                    <p class="font-semibold text-gray-700"><?php echo date("H:i", strtotime($ticket['fecha_hora_inicio'])); ?> h</p>
                </div>
            </div>

            <div>
                <p class="text-xs text-gray-400 uppercase font-bold mb-1">Ubicación</p>
                <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($ticket['ubicacion_detalle']); ?></p>
            </div>

            <div class="flex flex-col items-center pb-2">
                <div class="p-4 bg-gray-50 rounded-2xl">
                    <svg class="w-32 h-32 text-gray-800" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h4v4H3V3zm14 0h4v4h-4V3zM3 17h4v4H3v-4zm14 0h4v4h-4v-4zm-3-7h2v2h-2v-2zm2-2h2v2h-2V8zm-2-2h2v2h-2V6zm-2 2h2v2h-2V8zm0 4h2v2h-2v-2zm2 2h2v2h-2v-2zm-4-4h2v2h-2v-2zm2-2h2v2h-2V8zm-2-2h2v2h-2V6zm2-4h2v2h-2V2zm-4 4h2v2h-2V6zm2 2h2v2h-2V8zm-2 2h2v2h-2v-2zm-2-2h2v2h-2V8zm0 2h2v2h-2v-2zm0 2h2v2h-2v-2zm2 2h2v2h-2v-2zm-4-4h2v2h-2v-2zm2-2h2v2h-2V8z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <p class="mt-8 text-gray-400 text-sm max-w-xs text-center leading-relaxed">
        Presenta esta entrada digital en la puerta del recinto para el control de acceso.
    </p>

</body>
</html>