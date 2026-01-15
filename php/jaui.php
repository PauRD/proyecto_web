<?php
session_start();
require_once("scripts/conexiones.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaui</title>
</head>
<body class="bg-gray-100 min-h-screen font-sans"> 

    <?php require_once('cabeza.php'); ?>
    
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-10">

        <section class="text-center py-8">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-6">
                Descubre la <span class="text-sky-600">Música en Vivo</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Tu plataforma exclusiva para conciertos, festivales y eventos musicales. 
            </p>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Desde artistas emergentes hasta grandes estrellas.
            </p>
        </section>

        <?php
        $sql_mas_vendido = "
            SELECT 
                CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
                a.imagen_artista,
                SUM(c.cantidad_entradas) AS total_entradas_vendidas,
                es.ubicacion_detalle
            FROM 
                compras c
            JOIN 
                eventos_solicitados es ON c.id_evento_solicitado = es.id_evento_solicitado
            JOIN 
                artistas a ON es.id_artista = a.id_artista
            WHERE 
                es.estado = 'Aprobado'
            GROUP BY 
                es.id_evento_solicitado
            ORDER BY 
                total_entradas_vendidas DESC
            LIMIT 1";
        
        $evento_mas_vendido = null;
        if ($result = $conexion->query($sql_mas_vendido)) {
            $evento_mas_vendido = $result->fetch_assoc();
            $result->close();
        }
        
        if ($evento_mas_vendido){
        ?>
        <article class="bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col md:flex-row-reverse transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl">
            <div class="md:w-1/2 h-64 md:h-auto relative">
                <img src="<?php echo htmlspecialchars($evento_mas_vendido['imagen_artista']); ?>" 
                     alt="<?php echo htmlspecialchars($evento_mas_vendido['nombre_evento']); ?>"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="md:w-1/2 p-8 flex flex-col justify-center">
                <h2 class="text-3xl font-bold text-sky-600 mb-4 leading-tight">
                    El favorito del público: <br>
                    <span class="text-gray-900"><?php echo htmlspecialchars($evento_mas_vendido['nombre_evento']); ?></span>
                </h2>
                <p class="text-gray-600 text-lg mb-6">
                    ¡No te quedes sin tu entrada! Este es el evento más popular del momento, con más de 
                    <strong><?php echo $evento_mas_vendido['total_entradas_vendidas']; ?></strong> fans confirmados.
                    Prepárate para una noche inolvidable en <strong><?php echo htmlspecialchars($evento_mas_vendido['ubicacion_detalle']); ?></strong>.
                </p>
                <div class="mt-auto">
                    <a href="eventos.php" class="inline-block text-sky-600 font-semibold hover:text-sky-800 transition-colors">
                        Ver entradas disponibles &rarr;
                    </a>
                </div>
            </div>
        </article>
        <?php }; ?>


        <?php
        $sql_ultimo_aprobado = "
            SELECT 
                CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
                a.imagen_artista,
                es.categoria,
                es.precio_base
            FROM 
                eventos_solicitados es
            JOIN 
                artistas a ON es.id_artista = a.id_artista
            WHERE 
                es.estado = 'Aprobado'
            ORDER BY 
                es.fecha_solicitud DESC
            LIMIT 1";
        
        $ultimo_aprobado = null;
        if ($result = $conexion->query($sql_ultimo_aprobado)) {
            $ultimo_aprobado = $result->fetch_assoc();
            $result->close();
        }

        if ($ultimo_aprobado){
        ?>
        <article class="bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col md:flex-row transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl">
            <div class="md:w-1/2 h-64 md:h-auto relative">
                <img src="<?php echo htmlspecialchars($ultimo_aprobado['imagen_artista']); ?>" 
                     alt="<?php echo htmlspecialchars($ultimo_aprobado['nombre_evento']); ?>"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="md:w-1/2 p-8 flex flex-col justify-center">
                <h2 class="text-3xl font-bold text-sky-600 mb-4 leading-tight">
                    Recién llegado: <br>
                    <span class="text-gray-900"><?php echo htmlspecialchars($ultimo_aprobado['nombre_evento']); ?></span>
                </h2>
                <p class="text-gray-600 text-lg mb-6">
                    Descubre lo último en nuestra cartelera. Una experiencia de <strong><?php echo htmlspecialchars($ultimo_aprobado['categoria']); ?></strong> 
                    que no querrás perderte. Entradas desde tan solo <strong><?php echo number_format($ultimo_aprobado['precio_base'], 0); ?>€</strong>.
                </p>
                <div class="mt-auto">
                    <a href="eventos.php" class="inline-block text-sky-600 font-semibold hover:text-sky-800 transition-colors">
                        Más información &rarr;
                    </a>
                </div>
            </div>
        </article>
        <?php }; ?>


        <?php
        $sql_proximo_evento = "
            SELECT 
                CONCAT(a.nombre_artista, ' - ', es.nombre_evento) AS nombre_evento,
                a.imagen_artista,
                es.fecha_hora_inicio,
                TIMESTAMPDIFF(DAY, NOW(), es.fecha_hora_inicio) AS dias_restantes
            FROM 
                eventos_solicitados es
            JOIN 
                artistas a ON es.id_artista = a.id_artista
            WHERE 
                es.estado = 'Aprobado' AND es.fecha_hora_inicio > NOW()
            ORDER BY 
                es.fecha_hora_inicio ASC
            LIMIT 1";
        
        $proximo_evento = null;
        if ($result = $conexion->query($sql_proximo_evento)) {
            $proximo_evento = $result->fetch_assoc();
            $result->close();
        }

        if ($proximo_evento){
        ?>
        <article class="bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col md:flex-row-reverse transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl">
            <div class="md:w-1/2 h-64 md:h-auto relative">
                <img src="<?php echo htmlspecialchars($proximo_evento['imagen_artista']); ?>" 
                     alt="<?php echo htmlspecialchars($proximo_evento['nombre_evento']); ?>"
                     class="absolute inset-0 w-full h-full object-cover">
            </div>
            <div class="md:w-1/2 p-8 flex flex-col justify-center">
                <h2 class="text-3xl font-bold text-sky-600 mb-4 leading-tight">
                    Muy pronto en tu ciudad: <br>
                    <span class="text-gray-900"><?php echo htmlspecialchars($proximo_evento['nombre_evento']); ?></span>
                </h2>
                <p class="text-gray-600 text-lg mb-6">
                    La cuenta atrás ha comenzado. Solo faltan <strong><?php echo $proximo_evento['dias_restantes']; ?> días</strong> 
                    para este gran evento el <?php echo date("d/m/Y", strtotime($proximo_evento['fecha_hora_inicio'])); ?>. 
                    ¡Asegura tu lugar antes de que se agoten!
                </p>
                <div class="mt-auto">
                    <a href="eventos.php" class="inline-block text-sky-600 font-semibold hover:text-sky-800 transition-colors">
                        Comprar entradas &rarr;
                    </a>
                </div>
            </div>
        </article>
        <?php }; ?>


        <section class="text-center p-8 bg-gradient-to-r from-sky-100 to-purple-100 rounded-2xl shadow-inner space-y-6">
            <h2 class="text-3xl font-bold text-gray-800">
                ¿Listo para vivir la música?
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Explora nuestro catálogo de conciertos, festivales y eventos musicales. 
                Encuentra tu próxima experiencia musical.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <?php if (!isset($_SESSION['id_usuario'])){ ?>
                <a href="login.php" class="inline-block">
                    <button class="px-8 py-3 bg-sky-600 text-white font-bold text-lg rounded-full shadow-lg hover:bg-sky-700 transition duration-300 transform hover:scale-105 focus:ring-4 focus:ring-sky-300">
                        Iniciar Sesión
                    </button>
                </a>
                <?php }; ?>
                
                <a href="eventos.php" class="inline-block">
                    <button class="px-8 py-3 <?php echo isset($_SESSION['id_usuario']) ? 'bg-sky-600' : 'bg-white border-2 border-sky-600 text-sky-600'; ?> font-bold text-lg rounded-full shadow-lg hover:bg-sky-50 transition duration-300 transform hover:scale-105 focus:ring-4 focus:ring-sky-300">
                        Ver Todos los Eventos
                    </button>
                </a>
            </div>
        </section>

    </main>

    <?php if (isset($conexion)) $conexion->close(); ?>
</body>
</html>