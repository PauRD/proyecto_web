<?php
//viene de eventos
session_start();
require_once("conexiones.php");

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario || ($privilegio != 'Cliente' && $privilegio != 'Admin')) {
    header("Location: ../eventos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_evento'], $_POST['cantidad'])) {
    
    $id_evento_solicitado = $_POST['id_evento'];
    $cantidad_solicitada = $_POST['cantidad'];

    $sql_stock = "
        SELECT 
            es.slots_totales,
            (es.slots_totales - COALESCE(SUM(c.cantidad_entradas), 0)) AS slots_disponibles
        FROM 
            eventos_solicitados es
        LEFT JOIN 
            compras c ON es.id_evento_solicitado = c.id_evento_solicitado 
        WHERE 
            es.id_evento_solicitado = ? 
        GROUP BY 
            es.id_evento_solicitado";

    if ($stmt_stock = $conexion->prepare($sql_stock)) {
        $stmt_stock->bind_param("i", $id_evento_solicitado);
        $stmt_stock->execute();
        $resultado_stock = $stmt_stock->get_result();
        
        if ($fila_stock = $resultado_stock->fetch_assoc()) {
            $slots_disponibles_reales = $fila_stock['slots_disponibles'];
            
            $sql_cesta = "SELECT cantidad_entradas FROM cesta WHERE id_usuario = ? AND id_evento_solicitado = ?";
            $cantidad_actual = 0;
            if ($stmt_cesta = $conexion->prepare($sql_cesta)) {
                $stmt_cesta->bind_param("ii", $id_usuario, $id_evento_solicitado);
                $stmt_cesta->execute();
                $resultado_cesta = $stmt_cesta->get_result();
                if ($fila_cesta = $resultado_cesta->fetch_assoc()) {
                    $cantidad_actual = $fila_cesta['cantidad_entradas'];
                }
                $stmt_cesta->close();
            }

            $nueva_cantidad_total = $cantidad_actual + $cantidad_solicitada;

            // Usamos ON DUPLICATE KEY UPDATE para que si el evento ya está, se SUME la cantidad.
            $sql_insert_update = "
                INSERT INTO cesta (id_usuario, id_evento_solicitado, cantidad_entradas) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    cantidad_entradas = cantidad_entradas + VALUES(cantidad_entradas)";
            
            if ($stmt_iu = $conexion->prepare($sql_insert_update)) {
                $stmt_iu->bind_param("iii", $id_usuario, $id_evento_solicitado, $cantidad_solicitada);
                
                if ($stmt_iu->execute()) {
                    if ($stmt_iu->affected_rows > 1) {
                        $_SESSION['mensaje'] = "Se añadieron " . $cantidad_solicitada . " entradas adicionales a tu cesta.";
                    } else {
                        $_SESSION['mensaje'] = "Se añadieron " . $cantidad_solicitada . " entradas a tu cesta.";
                    }
                }
                $stmt_iu->close();
            }

        }
        $stmt_stock->close();
    }
}

$conexion->close();
header("Location: ../cesta.php");
exit;
?>