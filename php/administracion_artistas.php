<?php
session_start();

require_once("scripts/conexiones.php");

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if ($privilegio !== 'Admin') {
    header("Location: jaui.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_artista']) && (isset($_POST['accion_aprobar']) || isset($_POST['accion_denegar']))) {

    $id_artista = $_POST['id_artista'];
    $nuevo_estado = isset($_POST['accion_aprobar']) ? 'Aprobado' : 'Denegado';

    $sql = "UPDATE artistas SET estado = ?, id_admin_aprobador = ? WHERE id_artista = ? AND estado = 'Pendiente'";

    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("sii", $nuevo_estado, $id_usuario, $id_artista);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Artista #{$id_artista} ha sido marcado como {$nuevo_estado}.";
                $_SESSION['mensaje_tipo'] = 'exito';
            }
        }
        $stmt->close();
    }

    header("Location: administracion_artistas.php");
    exit;
}

$sql = "
    SELECT
        a.id_artista,
        a.nombre_artista,
        a.imagen_artista,
        a.estado,
        u.nombre_usuario AS proveedor,
        a.fecha_solicitud,
        a.descripcion
    FROM
        artistas a
    JOIN
        usuarios u ON a.id_proveedor = u.id_usuario
    ORDER BY
        CASE WHEN a.estado = 'Pendiente' THEN 0 ELSE 1 END,
        a.fecha_solicitud DESC";

$resultado = $conexion->query($sql);
$solicitudes = [];

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $solicitudes[] = $fila;
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Artistas</title>
</head>
<body class="bg-gray-100">

    <?php require_once('cabeza.php'); ?>

    <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Panel de Aprobación de Artistas
        </h1>

        <?php

        if (isset($_SESSION['mensaje'])) {
            $tipo_mensaje = $_SESSION['mensaje_tipo'];
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
                <h2 class="text-2xl font-semibold text-gray-700">¡Bandeja de entrada limpia!</h2>
                <p class="mt-2 text-gray-500">No hay solicitudes de artistas pendientes ni historial de solicitudes.</p>
            </div>

        <?php } else { ?>

            <div class="bg-white shadow-xl rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Artista</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Proveedor</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Fecha Solicitud</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-3/12">Descripcion</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">Estado</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Acciones</th>
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
                        <tr class="<?php echo $es_pendiente ? 'bg-yellow-50' : ''; ?>">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 text-center">#<?php echo htmlspecialchars($solicitud['id_artista']); ?></td>
                            <td class="px-6 py-4 flex items-center text-sm text-gray-700">
                                <img class="h-10 w-10 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars($solicitud['imagen_artista']); ?>" alt="Imagen de <?php echo htmlspecialchars($solicitud['nombre_artista']); ?>">
                                <strong><?php echo htmlspecialchars($solicitud['nombre_artista']); ?></strong>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-500 text-center"><?php echo htmlspecialchars($solicitud['proveedor']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center"><?php echo date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center"><?php echo htmlspecialchars($solicitud['descripcion']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $clase_estado; ?>">
                                    <?php echo htmlspecialchars($solicitud['estado']); ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-center">
                                <?php if ($es_pendiente) { ?>
                                    <form action="administracion_artistas.php" method="POST" class="flex flex-col space-y-1">
                                        <input type="hidden" name="id_artista" value="<?php echo $solicitud['id_artista']; ?>">
                                        <div class="flex space-x-1 w-full justify-center">
                                            <button type="submit" name="accion_aprobar"
                                                class="flex-1 px-2 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-xs font-medium transition duration-150">
                                                Aprobar
                                            </button>

                                            <button type="submit" name="accion_denegar"
                                                class="flex-1 px-2 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 text-xs font-medium transition duration-150">
                                                Denegar
                                            </button>
                                        </div>
                                    </form>
                                <?php } else { ?>
                                    <div class="text-gray-400 text-xs italic">Revisado</div>
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