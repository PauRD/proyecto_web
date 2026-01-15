<?php
session_start();

$id_organizador = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];
$id_evento = $_GET['id'];

if (!$id_organizador || ($privilegio != 'Proveedor' && $privilegio != 'Admin') || !$id_evento) {
    header("Location: eventos_solicitados.php");
    exit;
}

require_once("scripts/conexiones.php"); 


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_evento_solicitado'])) {
    
    $id_evento_solicitado = $_POST['id_evento_solicitado'];
    $id_artista = $_POST['id_artista'];
    $nombre_evento = trim($_POST['nombre_evento']);
    $precio_base = $_POST['precio_base'];
    $categoria = trim($_POST['categoria']); 
    $slots_totales = $_POST['slots_totales']; 
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $hora_inicio = trim($_POST['hora_inicio']);
    $ubicacion = trim($_POST['ubicacion_detalle']);
    
    $fecha_hora_inicio = $fecha_inicio . ' ' . $hora_inicio . ':00';

    if ($id_artista <= 0 || empty($nombre_evento) || $precio_base <= 0 || empty($categoria) || empty($fecha_inicio) || empty($hora_inicio) || empty($ubicacion) || $slots_totales <= 0) {
        $_SESSION['mensaje'] = "Todos los campos son obligatorios y la capacidad y el precio han de ser mayor que cero.";
        $_SESSION['mensaje_tipo'] = 'error';
    } else {
        $sql_update = "
            UPDATE eventos_solicitados 
            SET 
                id_artista = ?, 
                nombre_evento = ?,
                precio_base = ?,
                categoria = ?,
                fecha_hora_inicio = ?, 
                ubicacion_detalle = ?, 
                slots_totales = ?
            WHERE id_evento_solicitado = ? AND id_organizador = ? AND estado = 'Pendiente'";
        
        if ($stmt = $conexion->prepare($sql_update)) {
            $stmt->bind_param("isisssiii", 
                $id_artista, 
                $nombre_evento, 
                $precio_base,
                $categoria,
                $fecha_hora_inicio, 
                $ubicacion, 
                $slots_totales, 
                $id_evento_solicitado, 
                $id_organizador
            );
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['mensaje'] = "Solicitud del evento '{$nombre_evento}' actualizada correctamente.";
                    $_SESSION['mensaje_tipo'] = 'exito';
                } else {
                    $_SESSION['mensaje'] = "No se ha hecho ninguna modificacion.";
                    $_SESSION['mensaje_tipo'] = 'exito';
                }
                header("Location: eventos_solicitados.php");
                exit;
            }
            $stmt->close();
        }
    }
}

$sql_evento = "
    SELECT 
        es.id_evento_solicitado, 
        es.id_artista, 
        es.nombre_evento, 
        es.precio_base, 
        es.categoria, 
        es.slots_totales, 
        DATE(es.fecha_hora_inicio) AS fecha_inicio, 
        TIME_FORMAT(es.fecha_hora_inicio, '%H:%i') AS hora_inicio,
        es.ubicacion_detalle, 
        es.estado
    FROM 
        eventos_solicitados es
    WHERE 
        es.id_evento_solicitado = ? AND es.id_organizador = ?";

if ($stmt = $conexion->prepare($sql_evento)) {
    $stmt->bind_param("ii", $id_evento, $id_organizador);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $evento = $resultado->fetch_assoc();
    $stmt->close();
}

if ($evento['estado'] !== 'Pendiente') {
    $conexion->close();
    $_SESSION['mensaje'] = "Error: El evento no se puede editar, no existe o ha sido revisado.";
    $_SESSION['mensaje_tipo'] = 'error';
    header("Location: eventos_solicitados.php");
    exit;
}

$sql_artistas = "
    SELECT 
        id_artista, 
        nombre_artista
    FROM 
        artistas 
    WHERE 
        id_proveedor = ?
        AND estado = 'Aprobado'
    ORDER BY 
        nombre_artista ASC";

$artistas_aprobados = [];
if ($stmt = $conexion->prepare($sql_artistas)) {
    $stmt->bind_param("i", $id_organizador);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $artistas_aprobados[] = $fila;
    }
    $stmt->close();
}

$conexion->close();

$categorias = ['Pop','Rap','Rock','Techno','Under','Salsa','Bachata','Regueton','Jazz','Merengue','Drill','Dembow'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Solicitud</title>
</head>
<body>
    
    <?php require_once('cabeza.php'); ?>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-6">
            Editar Solicitud
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

        <div class="bg-white p-8 rounded-xl shadow-2xl border border-gray-200">
            
            <form action="editar_solicitud.php?id=<?php echo $id_evento; ?>" method="POST" class="space-y-6">
                <input type="hidden" name="id_evento_solicitado" value="<?php echo htmlspecialchars($evento['id_evento_solicitado']); ?>">
                
                <div>
                    <label for="id_artista" class="block text-sm font-medium text-gray-700 mb-1">
                        Artista Principal del Evento:
                    </label>
                    <select id="id_artista" name="id_artista" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                        <option value="">Selecciona un artista aprobado</option>
                        <?php foreach ($artistas_aprobados as $artista) { 
                            $selected = ($artista['id_artista'] == $evento['id_artista']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($artista['id_artista']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($artista['nombre_artista']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                
                <div>
                    <label for="nombre_evento" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Específico del Evento (Título):
                    </label>
                    <input type="text" id="nombre_evento" name="nombre_evento" maxlength="255" 
                            value="<?php echo htmlspecialchars($evento['nombre_evento']); ?>"
                            placeholder="Ej: Concierto Final de Gira en Madrid"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="precio_base" class="block text-sm font-medium text-gray-700 mb-1">
                            Precio Base de la Entrada (€):
                        </label>
                        <input type="number" id="precio_base" name="precio_base" 
                                value="<?php echo htmlspecialchars($evento['precio_base']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">
                            Categoría/Género:
                        </label>
                        <select id="categoria" name="categoria" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                            <option value="">Seleccionar Categoría</option>
                            <?php 
                            foreach ($categorias as $categoria) {
                                $selected = ($categoria == $evento['categoria']) ? 'selected' : '';
                                echo "<option value=\"{$categoria}\" {$selected}>{$categoria}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha:
                        </label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" 
                                value="<?php echo htmlspecialchars($evento['fecha_inicio']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label for="hora_inicio" class="block text-sm font-medium text-gray-700 mb-1">
                            Hora de Inicio:
                        </label>
                        <input type="time" id="hora_inicio" name="hora_inicio" 
                                value="<?php echo htmlspecialchars($evento['hora_inicio']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    </div>
                </div>

                <div>
                    <label for="ubicacion_detalle" class="block text-sm font-medium text-gray-700 mb-1">
                        Ubicación Detallada (Nombre del recinto, Ciudad, etc.):
                    </label>
                    <textarea id="ubicacion_detalle" name="ubicacion_detalle" rows="3" 
                                placeholder="Ej: WiZink Center, Calle Goya 10, Madrid"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150"><?php echo htmlspecialchars($evento['ubicacion_detalle']); ?></textarea>
                </div>
                
                <div>
                    <label for="slots_totales" class="block text-sm font-medium text-gray-700 mb-1">
                        Cantidad de entradas disponibles:
                    </label>
                    <input type="number" id="slots_totales" name="slots_totales"
                            value="<?php echo htmlspecialchars($evento['slots_totales']); ?>"
                            placeholder="Capacidad máxima de entradas para este evento"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-sky-600 text-white font-semibold rounded-lg shadow-lg hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition duration-150">
                    Guardar Cambios
                </button>
            </form>
            
        </div>
        
    </main>
</body>
</html>