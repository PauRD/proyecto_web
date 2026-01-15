<?php
session_start();

$id_usuario = $_SESSION['id_usuario'];
$privilegio = $_SESSION['privilegio_usuario'];

if (!$id_usuario || ($privilegio != 'Cliente' && $privilegio != 'Admin')) {
    header("Location: login.php");
    exit;
}

require_once("scripts/conexiones.php"); 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['monto_recarga'])) {
    $monto = filter_var($_POST['monto_recarga'], FILTER_VALIDATE_INT);
    
    if ($monto === false || $monto <= 0) {
        $_SESSION['mensaje_saldo'] = "La recarga de saldo debe ser un número entero positivo.";
        $_SESSION['mensaje_tipo'] = "error"; 
    } else {
        $sql = "UPDATE usuarios SET saldo = saldo + ? WHERE id_usuario = ?";
        
        if ($stmt = $conexion->prepare($sql)) {
            $stmt->bind_param("ii", $monto, $id_usuario);
            
            if ($stmt->execute()) {
                $_SESSION['saldo'] += $monto;
                $_SESSION['mensaje_saldo'] = "Has recargado {$monto} €. Saldo actual: {$_SESSION['saldo']} €.";
                $_SESSION['mensaje_tipo'] = "exito"; 
            }
            $stmt->close();
        }
    }
    header("Location: mi_saldo.php"); 
    exit;
}


$mensaje_recarga = $_SESSION['mensaje_saldo'] ?? null;
$mensaje_tipo = $_SESSION['mensaje_tipo'] ?? 'exito';
unset($_SESSION['mensaje_saldo'], $_SESSION['mensaje_tipo']);

$saldo_actual = $_SESSION['saldo'];
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Saldo</title>
</head>
<body class="bg-gray-100">
    
    <?php require_once('cabeza.php'); ?>

    <main class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-10">
            Mi Saldo y Recargas
        </h1>

        <?php
        if ($mensaje_recarga) {
            $clase_alerta = ($mensaje_tipo == 'error') 
                ? 'bg-red-100 border-red-500 text-red-700' 
                : 'bg-green-100 border-green-500 text-green-700';
            echo "<div class='p-4 mb-6 border-l-4 rounded-lg shadow-md {$clase_alerta}' role='alert'>";
            echo "<p class='font-bold'>" . htmlspecialchars($mensaje_recarga) . "</p>";
            echo "</div>";
        }
        ?>

        <section class="bg-white p-8 rounded-lg shadow-xl text-center border border-gray-100">
            <h2 class="text-lg font-medium text-gray-500 uppercase tracking-wider">
                Saldo Disponible
            </h2>
            <div class="text-6xl font-extrabold text-sky-600 my-3">
                <?php echo number_format($saldo_actual, 0, ',', '.'); ?> €
            </div>
            <p class="text-gray-600">
                Este es el saldo que puedes utilizar para comprar entradas.
            </p>
        </section>

        <section class="bg-white p-8 rounded-lg shadow-xl mt-8 border border-gray-100">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">
                Recargar Saldo
            </h2>
            
            <form action="mi_saldo.php" method="POST" class="space-y-4">
                
                <div>
                    <label for="monto_recarga" class="block text-sm font-medium text-gray-700">
                        Monto a Recargar (solo números enteros):
                    </label>
                    <div class="relative mt-1">
                        <input type="number" 
                               id="monto_recarga" 
                               name="monto_recarga" 
                               step="1" 
                               placeholder="Ej: 50"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 transition duration-150 pl-10"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            €
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full py-2.5 bg-sky-600 text-white font-semibold rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition duration-150 cursor-pointer">
                    Confirmar Recarga
                </button>
            </form>
        </section>
    </main>
</body>
</html>