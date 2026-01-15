<?php
session_start();

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'] ;
$saldo_usuario = $_SESSION['saldo'];


if (!$id_usuario || $privilegio == 'Proveedor') {
    header("Location: login.php");
    exit;
}

require_once("scripts/conexiones.php"); 

$sql_cesta = "
    SELECT 
        c.id_cesta,
        c.cantidad_entradas,
        es.id_evento_solicitado, 
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento, 
        es.precio_base,
        a.imagen_artista,
        es.fecha_hora_inicio,
        es.ubicacion_detalle
    FROM 
        cesta c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    JOIN
        artistas a ON es.id_artista = a.id_artista
    WHERE 
        c.id_usuario = ?
    ORDER BY 
        es.fecha_hora_inicio ASC";

$total_compra = 0;
$total_entradas = 0;
$eventos_cesta = [];

if ($stmt = $conexion->prepare($sql_cesta)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($evento = $resultado->fetch_assoc()) {
        $evento['subtotal'] = $evento['precio_base'] * $evento['cantidad_entradas']; 

        $eventos_cesta[] = $evento;
        $total_compra += $evento['subtotal'];
        $total_entradas += $evento['cantidad_entradas']; 
    }
    $stmt->close();
}

$conexion->close();
$saldo_suficiente = ($privilegio == 'Admin' || $saldo_usuario >= $total_compra);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cesta de Compras</title>
</head>
<body class="bg-gray-100">
    
    <?php require_once('cabeza.php'); ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Mi Cesta
        </h1>

        <?php
        if (isset($_SESSION['mensaje'])) {
            echo '<div class="p-4 mb-6 text-sm text-green-700 bg-green-100 rounded-lg shadow-md text-center font-medium">';
            echo htmlspecialchars($_SESSION['mensaje']);
            echo '</div>';
            unset($_SESSION['mensaje']); 
        }

        if (empty($eventos_cesta)) {
        ?>
            <div class="p-8 text-center bg-white border border-gray-200 rounded-lg shadow-lg">
                <h2 class="text-2xl font-semibold text-gray-700">Tu cesta está vacía.</h2>
                <p class="mt-2 text-gray-500">Parece que aún no has añadido ningún evento.</p>
                <a href="eventos.php" 
                    class="inline-block mt-6 px-6 py-2 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 transition duration-150">
                    Ver Eventos Disponibles
                </a>
            </div>

        <?php } else { ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">               
                <section class="md:col-span-2 space-y-4">                   
                    <?php foreach ($eventos_cesta as $evento) { ?>
                    <div class="flex items-center bg-white p-4 rounded-lg shadow-md border border-gray-100">
                        <img 
                            src="<?php echo htmlspecialchars($evento['imagen_artista']); ?>" 
                            alt="<?php echo htmlspecialchars($evento['nombre_evento']); ?>"
                            class="w-16 h-16 rounded-lg object-cover mr-4 hidden sm:block">
                        
                        <div class="flex-grow">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($evento['nombre_evento']); ?></h3>
                            <p class="text-sm text-gray-500">
                                <?php echo date("d/m/Y H:i", strtotime($evento['fecha_hora_inicio'])); ?> 
                                | 
                                <?php echo htmlspecialchars($evento['cantidad_entradas']); ?> entradas
                            </p>
                        </div>
                        
                        <div class="text-right ml-4 flex-shrink-0">
                            <div class="text-lg font-bold text-gray-900"><?php echo number_format($evento['subtotal'], 0, ',', '.'); ?> €</div>
                            <p class="text-xs text-gray-500">
                                (<?php echo number_format($evento['precio_base'], 0, ',', '.'); ?> € c/u)
                            </p>
                            
                            <form action="scripts/eliminar_cesta.php" method="POST" class="mt-1">
                                <input type="hidden" name="id_cesta" value="<?php echo htmlspecialchars($evento['id_cesta']); ?>">
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium transition duration-150">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php } ?>
                </section>
                
                <div class="md:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-100 space-y-4 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 border-b pb-2">Resumen</h2>
                        
                        <div class="flex justify-between text-gray-700">
                            <div>Subtotal (<?php echo $total_entradas; ?> <?php echo ($total_entradas > 1 ? 'entradas' : 'entrada'); ?>):</div>
                            <div class="font-medium"><?php echo number_format($total_compra, 0, ',', '.'); ?> €</div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex justify-between text-2xl font-extrabold text-gray-900">
                                <div>Total:</div>
                                <div><?php echo number_format($total_compra, 0, ',', '.'); ?> €</div>
                            </div>
                        </div>

                        <?php if ($privilegio == 'Cliente') { ?>
                            <p class="text-sm text-center text-gray-600 pt-2">
                                Tu saldo actual: 
                                <div class="font-bold <?php echo $saldo_suficiente ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($saldo_usuario, 0, ',', '.'); ?> €
                                </div>
                            </p>
                        <?php } ?>
                        
                        <?php if ($saldo_suficiente) { ?>
                            <a href="scripts/confirmar_compra.php" 
                                class="block w-full text-center py-3 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-150">
                                Confirmar y Pagar
                            </a>
                        <?php } else { ?>
                            <a href="mi_saldo.php" 
                                class="block w-full text-center py-3 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150">
                                Saldo Insuficiente (Recargar)
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </main>
</body>
</html>