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
        c.id_compra,
        c.cantidad_entradas, 
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
        c.monto_proveedor AS ingreso,
        c.precio_pagado AS total_venta,
        c.monto_admins AS comision_plataforma,
        u_cliente.nombre_usuario AS cliente,
        c.fecha_compra
    FROM 
        compras c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    JOIN
        artistas a ON es.id_artista = a.id_artista
    JOIN 
        usuarios u_cliente ON c.id_usuario = u_cliente.id_usuario
    WHERE 
        c.id_proveedor = ?
    ORDER BY 
        c.fecha_compra DESC";

$historial_ventas = [];
$total_ingresos = 0; 
$total_entradas_vendidas = 0; 

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $historial_ventas[] = $fila;
        $total_ingresos += $fila['ingreso'];
        $total_entradas_vendidas += $fila['cantidad_entradas']; 
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
    <title>Saldo y Ganancias Proveedor</title>
</head>
<body>

    <?php require_once('cabeza.php'); ?>
    
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2">
            Mi Dashboard de Ganancias
        </h1>
        <p class="text-xl text-gray-600 mb-8">
            Aquí puedes ver tu saldo acumulado y tu historial de ventas.
        </p>

        <section class="mb-10 p-6 bg-white shadow-xl rounded-xl border-l-4 border-green-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Resumen de Ventas</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-center">
                
                <div class="p-4 bg-gray-50 rounded-lg border">
                    <p class="text-sm text-gray-500 font-medium">Entradas Vendidas</p>
                    <p class="text-3xl font-extrabold text-green-600 mt-1">
                        <?php echo $total_entradas_vendidas; ?>
                    </p>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border">
                    <p class="text-sm text-gray-500 font-medium">Tus Ingresos</p>
                    <p class="text-3xl font-extrabold text-green-600 mt-1">
                        <?php echo number_format($total_ingresos, 0, ',', '.'); ?> €
                    </p>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-4">
                <strong>Nota:</strong> Por cada venta recibes el <strong>70%</strong> del total. El <strong>30%</strong> restante es la comisión de la plataforma.
            </p>
        </section>

        <section class="bg-white shadow-xl rounded-xl overflow-hidden">
            <h2 class="text-2xl font-bold text-gray-800 p-6 border-b">
                Historial de Transacciones de Venta
            </h2>
            
            <?php if (empty($historial_ventas)) { ?>
                <div class="p-6 text-center bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                    <p class="font-bold">Aún no se han vendido entradas para tus eventos aprobados.</p>
                    <p>¡Publica más eventos para empezar a generar ingresos!</p>
                </div>
            <?php } else { ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Evento Vendido</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">Cant.</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Cliente</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Fecha Venta</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Total Venta</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">Tu Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($historial_ventas as $venta) { ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold"><?php echo htmlspecialchars($venta['nombre_evento']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center text-sky-600">
                                    <?php echo htmlspecialchars($venta['cantidad_entradas']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-sky-600"><?php echo htmlspecialchars($venta['cliente']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?php echo date("d/m/Y H:i", strtotime($venta['fecha_compra'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-center text-gray-700">
                                    <?php echo number_format($venta['total_venta'], 0, ',', '.'); ?>€
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-center text-green-600">
                                    +<?php echo number_format($venta['ingreso'], 0, ',', '.'); ?>€
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </section>
    </main>
</body>
</html>