<?php
session_start();
require_once("scripts/conexiones.php");

$id_usuario_logueado = $_SESSION['id_usuario'];

if (!$id_usuario_logueado) {
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT 
        c.id_compra,
        c.fecha_compra,
        c.cantidad_entradas, 
        es.id_evento_solicitado,
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
        u.nombre_usuario AS proveedor
    FROM 
        compras c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    JOIN
        artistas a ON es.id_artista = a.id_artista
    JOIN
        usuarios u ON c.id_proveedor = u.id_usuario
    WHERE
        c.id_usuario = ?
    ORDER BY 
        c.fecha_compra DESC";

$compras = [];

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario_logueado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $compras[] = $fila;
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
    <title>Mi Historial de Compras</title>
    </head>
<body>

    <?php require_once('cabeza.php'); ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <h1 class="text-4xl font-extrabold text-gray-900 mb-6">
            Historial de Compras
        </h1>
    
        <?php if (empty($compras)) { ?>
            <div class="p-6 text-center bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded-lg">
                <p class="font-bold">¡Aún no has comprado ninguna entrada!</p>
                <p>Explora el <a href="eventos.php" class="font-semibold underline hover:text-yellow-800">catálogo de eventos</a>.</p>
            </div>
        <?php } else { ?>
            <section class="bg-white shadow-xl rounded-xl overflow-hidden">
                <h2 class="text-2xl font-bold text-gray-800 p-6 border-b">Detalle de Transacciones</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Venta</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($compras as $compra) { ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                                    <div class="font-semibold"><?php echo htmlspecialchars($compra['nombre_evento']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <?php echo date("d/m/Y H:i", strtotime($compra['fecha_compra'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-sky-600 text-center">
                                    <?php echo htmlspecialchars($compra['cantidad_entradas']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <?php echo htmlspecialchars($compra['proveedor']); ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php } ?>
    </main>
</body>
</html>