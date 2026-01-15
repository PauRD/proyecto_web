<?php
// viene de cesta 
session_start();

require_once("conexiones.php");

$id_usuario = $_SESSION['id_usuario'];
$saldo_actual = $_SESSION['saldo'];
$mensaje_error = '';
$es_admin = $_SESSION['privilegio_usuario'] == 'Admin';

$sql = "
    SELECT 
        c.id_evento_solicitado,
        c.cantidad_entradas,
        es.precio_base,
        es.id_organizador AS id_proveedor
    FROM 
        cesta c
    JOIN 
        eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
    WHERE 
        c.id_usuario = ?";

$total_compra = 0;
$items_compra = [];

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($item = $resultado->fetch_assoc()) {
        $item['precio_total'] = $item['precio_base'] * $item['cantidad_entradas'];
        
        $items_compra[] = $item;
        $total_compra += $item['precio_total'];
    }
    $stmt->close();
} else {
    $conexion->close();
    header("Location: ../cesta.php");
    exit;
}

if (empty($items_compra)) {
    $_SESSION['mensaje'] = "Tu cesta está vacía. No hay nada que comprar.";
    header("Location: ../cesta.php");
    exit;
}

if (!$es_admin && $saldo_actual < $total_compra) {
    header("Location: ../mi_saldo.php");
    exit;
}

$conexion->begin_transaction();

try {
    if (!$es_admin) {
        $nuevo_saldo_cliente = $saldo_actual - $total_compra;

        $sql = "UPDATE usuarios SET saldo = ? WHERE id_usuario = ?";
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ii", $nuevo_saldo_cliente, $id_usuario);
            if (!$stmt->execute()) {
                throw new Exception("Error al procesar el saldo del cliente.");
            }
            $stmt->close();
        } else {
            throw new Exception("Error de preparación (Saldo).");
        }
    } else {
        $nuevo_saldo_cliente = $saldo_actual;
    }

    foreach ($items_compra as $item) {
        $id_evento = $item['id_evento_solicitado'];
        $precio_total = $item['precio_total'];
        $cantidad = $item['cantidad_entradas'];
        $id_proveedor = $item['id_proveedor'];

        $sql = "
            SELECT 
                es.slots_totales,
                COALESCE(SUM(c.cantidad_entradas), 0) AS total_vendido
            FROM 
                eventos_solicitados es
            LEFT JOIN 
                compras c ON es.id_evento_solicitado = c.id_evento_solicitado
            WHERE 
                es.id_evento_solicitado = ?
            GROUP BY 
                es.id_evento_solicitado";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("i", $id_evento);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $slots_totales = $resultado['slots_totales'];
            $total_vendido = $resultado['total_vendido'];
            $slots_disponibles = $slots_totales - $total_vendido;

        } else {
            throw new Exception("Error de preparación (Stock).");
        }
        
        $sql = "
            INSERT INTO compras 
                (id_usuario, id_evento_solicitado, precio_pagado, monto_proveedor, monto_admins, id_proveedor,  cantidad_entradas)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $monto_proveedor = floor($precio_total * 0.7);
        $monto_admins = $precio_total - $monto_proveedor;
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("iiiiiii", 
                $id_usuario, 
                $id_evento, 
                $precio_total,
                $monto_proveedor,
                $monto_admins,
                $id_proveedor, 
                $cantidad
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar la compra.");
            }
            $stmt->close();
        } else {
            throw new Exception("Error de preparación (Compra).");
        }
        
        $sql = "UPDATE usuarios SET saldo = saldo + ? WHERE id_usuario = ?";
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ii", $monto_proveedor, $id_proveedor);
            if (!$stmt->execute()) {
                throw new Exception("Error al acreditar al proveedor del evento.");
            }
            $stmt->close();
        } else {
            throw new Exception("Error de preparación (Crédito).");
        }
    }

    $sql = "DELETE FROM cesta WHERE id_usuario = ?";
    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("i", $id_usuario);
        if (!$stmt->execute()) {
            throw new Exception("Error al vaciar la cesta.");
        }
        $stmt->close();
    } else {
        throw new Exception("Error de preparación (Vaciar Cesta).");
    }

    $conexion->commit();
    $_SESSION['saldo'] = $nuevo_saldo_cliente;
    $_SESSION['mensaje'] = "¡Compra exitosa! ¡Disfruta de tus eventos!";
    $_SESSION['mensaje_tipo'] = 'exito';

} catch (Exception $e) {
    $conexion->rollback();
    $mensaje_error = $e->getMessage();
    $_SESSION['mensaje'] = "ERROR de Transacción: La compra ha fallado.";
    $_SESSION['mensaje_tipo'] = 'error';
}

$conexion->close();

header("Location: ../proximos_eventos.php");
exit;
?>