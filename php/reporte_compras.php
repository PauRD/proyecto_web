<?php
session_start();

require_once("scripts/conexiones.php");

$id_admin_logueado = $_SESSION['id_usuario'];
$privilegio_admin = $_SESSION['privilegio_usuario'];

if ($privilegio_admin !== 'Admin' || !$id_admin_logueado) {
    header("Location: jaui.php");
    exit;
}

$sql = "
    SELECT 
        c.id_compra,
        c.fecha_compra,
        c.precio_pagado,
        c.monto_proveedor,
        c.monto_admins,
        c.cantidad_entradas,
        es.id_evento_solicitado,
        CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento_vendido,
        u_cliente.nombre_usuario AS cliente,
        u_proveedor.nombre_usuario AS proveedor
    FROM 
        compras c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    JOIN
        artistas a ON es.id_artista = a.id_artista
    JOIN 
        usuarios u_cliente ON c.id_usuario = u_cliente.id_usuario
    JOIN
        usuarios u_proveedor ON c.id_proveedor = u_proveedor.id_usuario
    ORDER BY 
        c.fecha_compra DESC";

$resultado = $conexion->query($sql);
$compras = [];
$total_ventas = 0;

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $compras[] = $fila;
        $total_ventas += $fila['precio_pagado'];
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras Globales</title>
</head>
<body>

    <?php require_once('cabeza.php'); ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2">
            Reporte Global de Ventas
        </h1>
        <p class="text-xl text-gray-600 mb-6">
            Transacciones de venta de entradas registradas en la plataforma.
        </p>
                
        <section class="mb-10 p-6 bg-white shadow-xl rounded-xl border-b-4 border-green-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Resumen Financiero (Distribución 70/30)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
                
                <div class="p-4 bg-gray-50 rounded-lg border">
                    <p class="text-sm text-gray-500 font-medium">Transacciones Totales</p>
                    <p class="text-3xl font-extrabold text-sky-600 mt-1">
                        <?php echo count($compras); ?>
                    </p>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-lg border">
                    <p class="text-sm text-gray-500 font-medium">Total Vendido</p>
                    <p class="text-3xl font-extrabold text-green-600 mt-1">
                        +<?php echo number_format($total_ventas, 0, ',', '.'); ?> €
                    </p>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border">
                    <p class="text-sm text-gray-500 font-medium">Para Proveedores (70%)</p>
                    <p class="text-3xl font-extrabold text-blue-600 mt-1">
                        +<?php 
                            $total_proveedores = array_sum(array_column($compras, 'monto_proveedor'));
                            echo number_format($total_proveedores, 0, ',', '.');
                        ?> €
                    </p>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border">
                    <p class="text-sm text-gray-500 font-medium">Para Plataforma (30%)</p>
                    <p class="text-3xl font-extrabold text-purple-600 mt-1">
                        +<?php 
                            $total_admins = array_sum(array_column($compras, 'monto_admins'));
                            echo number_format($total_admins, 0, ',', '.');
                        ?> €
                    </p>
                </div>
            </div>
        </section>

        <div class="border-t border-gray-200 mb-8"></div>

        <?php if (empty($compras)) { ?>
            <div class="p-6 text-center bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded-lg">
                <p class="font-bold">¡Plataforma en espera!</p>
                <p>Aún no se ha registrado ninguna compra de entradas en la plataforma.</p>
            </div>
        <?php } else { ?>
            <section class="bg-white shadow-xl rounded-xl overflow-hidden">
                <h2 class="text-2xl font-bold text-gray-800 p-6 border-b">Detalle de Transacciones</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID Compra</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Venta</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total (€)</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor (70%)</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Admins (30%)</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Evento Vendido</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($compras as $compra) { ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center">
                                    #<?php echo htmlspecialchars($compra['id_compra']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <?php echo date("d/m/Y H:i", strtotime($compra['fecha_compra'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 text-center">
                                    +<?php echo number_format($compra['precio_pagado'], 0, ',', '.'); ?> €
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 text-center">
                                    +<?php echo number_format($compra['monto_proveedor'], 0, ',', '.'); ?> €
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-purple-600 text-center">
                                    +<?php echo number_format($compra['monto_admins'], 0, ',', '.'); ?> €
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center text-sky-600 text-center">
                                    <?php echo htmlspecialchars($compra['cantidad_entradas']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium text-center">
                                    <?php echo htmlspecialchars($compra['nombre_evento_vendido']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-sky-600 text-center">
                                    <?php echo htmlspecialchars($compra['cliente']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 text-center">
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