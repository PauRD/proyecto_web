<?php
session_start();

$id_organizador = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_organizador || $privilegio != 'Proveedor' ) {
    header("Location: jaui.php");
    exit;
}

require_once("scripts/conexiones.php"); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $id_artista = $_POST['id_artista'];
    $nombre_evento = trim($_POST['nombre_evento']);
    $precio_base = $_POST['precio_base'];
    $categoria = trim($_POST['categoria']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $hora_inicio = trim($_POST['hora_inicio']);
    $ubicacion = trim($_POST['ubicacion_detalle']);
    $slots_totales = $_POST['slots_totales'];
    $fecha_hora_inicio = $fecha_inicio . ' ' . $hora_inicio . ':00';

    $errores = []; 

    if (empty($id_artista)) {
        $errores[] = "Debes seleccionar un Artista para el Evento.";
    }
    if (empty($nombre_evento)) {
        $errores[] = "Debes darle un Nombre al Evento de tu Artista.";
    }
    if (empty($categoria)) {
        $errores[] = "Debes seleccionar un Género.";
    }
    if (empty($fecha_inicio)) {
        $errores[] = "El campo Fecha de Inicio es obligatorio.";
    }
    if (empty($hora_inicio)) {
        $errores[] = "El campo Hora de Inicio es obligatorio.";
    }
    if (empty($ubicacion)) {
        $errores[] = "El campo Ubicacion del Evento es obligatorio.";
    }
    if (!isset($precio_base) || $precio_base <= 0) {
        $errores[] = "El Precio Base debe ser un valor numérico, entero y positivo.";
    }
    if (!isset($slots_totales) || $slots_totales <= 0) {
        $errores[] = "La Capacidad Total debe ser un valor numérico, entero y positivo.";
    }


    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
    } else {
        $sql = "
            INSERT INTO eventos_solicitados 
                (id_artista, nombre_evento, precio_base, categoria, id_organizador, fecha_hora_inicio, ubicacion_detalle, slots_totales) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("isisissi", 
                $id_artista, 
                $nombre_evento, 
                $precio_base, 
                $categoria, 
                $id_organizador, 
                $fecha_hora_inicio, 
                $ubicacion, 
                $slots_totales
            );
            
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Solicitud del evento **{$nombre_evento}** enviada correctamente. Será revisada por un administrador.";
                $_SESSION['mensaje_tipo'] = 'exito';
                header("Location: eventos_solicitados.php");
                exit;
            }
            $stmt->close();
        }
    }
}


$sql = "
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

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("i", $id_organizador);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $artistas_aprobados[] = $fila;
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
    <title>Solicitar Nuevo Evento</title>
</head>
<body class="bg-gray-100">

    <?php require_once('cabeza.php');  ?>
    
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Solicitar Nuevo Evento
        </h1>

        <?php
        if (!empty($_SESSION['errores'])) {
            foreach ($errores as $error) {
                echo "<div class='p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg border-l-4 border-red-500'>{$error}</div>";
            }
            unset($_SESSION['errores']);
        }
        ?>

        <div class="bg-white p-8 rounded-xl shadow-2xl border border-gray-200">
            <form action="nuevo_evento.php" method="POST" class="space-y-6">
                <div>
                    <label for="id_artista" class="block text-sm font-medium text-gray-700 mb-1">
                        Artista del Evento:
                    </label>
                    <select id="id_artista" name="id_artista" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                        <option value="">Selecciona un artista aprobado</option>
                        <?php foreach ($artistas_aprobados as $artista) { ?>
                            <option value="<?php echo htmlspecialchars($artista['id_artista']); ?>">
                                <?php echo htmlspecialchars($artista['nombre_artista']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                
                <div>
                    <label for="nombre_evento" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre del Evento:
                    </label>
                    <input type="text" id="nombre_evento" name="nombre_evento" maxlength="255" 
                            placeholder="Ej: Concierto Final de Gira en Barcelona"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="precio_base" class="block text-sm font-medium text-gray-700 mb-1">
                            Precio Base de la Entrada (€):
                        </label>
                        <input type="number" id="precio_base" name="precio_base" min="1" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">
                            Género:
                        </label>
                        <select id="categoria" name="categoria" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                            <option value="">Seleccionar Categoría</option>
                            <?php 
                            $categorias = ['Pop','Rap','Rock','Techno','Under','Salsa','Bachata','Regueton','Jazz','Merengue','Drill','Dembow'];
                            foreach ($categorias as $cat) {
                                echo "<option value=\"{$cat}\">{$cat}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha de Inicio:
                        </label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" 
                                min="<?php echo date('Y-m-d'); ?>" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label for="hora_inicio" class="block text-sm font-medium text-gray-700 mb-1">
                            Hora de Inicio:
                        </label>
                        <input type="time" id="hora_inicio" name="hora_inicio" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    </div>
                </div>

                <div>
                    <label for="ubicacion_detalle" class="block text-sm font-medium text-gray-700 mb-1">
                        Ubicación del Evento (Nombre del recinto, Ciudad, etc.):
                    </label>
                    <textarea id="ubicacion_detalle" name="ubicacion_detalle" rows="3" 
                                    placeholder="Ej: WiZink Center, Calle Goya 10, Madrid"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150"></textarea>
                </div>
                
                <div>
                    <label for="slots_totales" class="block text-sm font-medium text-gray-700 mb-1">
                        Capacidad (Stock de Entradas):
                    </label>
                    <input type="number" id="slots_totales" name="slots_totales"
                            placeholder="Capacidad máxima de entradas para este evento"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 transition duration-150">
                    <p class="text-xs text-gray-400 mt-1">Este será el stock inicial disponible una vez el evento sea aprobado.</p>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-green-600 text-white font-semibold rounded-lg shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150">
                    Enviar Solicitud
                </button>
            </form>
            
        </div>
        
    </main>
</body>
</html>