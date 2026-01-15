<?php
$fichero_ruta = basename($_SERVER['PHP_SELF']); 
$privilegio = $_SESSION['privilegio_usuario'] ?? '';

function generar_enlace($ruta_actual, $fichero, $texto, $clase_base) {
    $clase = $clase_base;
    if ($ruta_actual == $fichero) {
        $clase .= ' bg-sky-700 text-white'; 
    }
    $clase .= ' transition duration-150 ease-in-out hover:bg-sky-600';
    echo "<a class='{$clase}' href='{$fichero}'><div>{$texto}</div></a>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pau Ruiz Dicenta</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen"> 
    
    <header class="bg-sky-800 shadow-lg sticky top-0 z-50">
        <nav class="flex items-center justify-between p-4 max-w-7xl mx-auto">
            
            <a href="jaui.php" class="flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <img src="../img/jaui.png" alt="logo de jaui"
                    class="w-10 h-10 rounded-full object-cover">
                </div>
            </a>
            
            <div class='hidden md:flex space-x-2'>
                <?php
                $clase_botones_centro = "px-3 py-2 text-sm font-medium text-white rounded-md";
                generar_enlace($fichero_ruta, "eventos.php", "Catálogo de Eventos", $clase_botones_centro);
                // generar_enlace($fichero_ruta, "calendario_eventos.php", "Calendario", $clase_botones_centro);
                // generar_enlace($fichero_ruta, "localizaciones.php", "Localizaciones", $clase_botones_centro); 
                ?>
            </div>
            
            <?php if (!isset($_SESSION['id_usuario'])) { ?>
                <div class='flex space-x-2 items-center'>
                    <a class='px-3 py-2 text-sm font-medium text-white rounded-md transition duration-150 hover:bg-sky-600' href='registro.php'>
                        <div>Regístrate</div>
                    </a>
                    
                    <a class='px-3 py-2 text-sm font-semibold text-white bg-sky-600 rounded-md transition duration-150 hover:bg-sky-700' href='login.php'>
                        <div>Inicia sesión</div>
                    </a>
                </div>
            <?php } else { ?>
                <div class='relative flex items-center space-x-2'>
                    
                    <div class='flex items-center space-x-2 cursor-pointer' id='cabeza_boton_usuario'>
                        
                        <a href='perfil_usuario.php' class='rounded-full border-2 border-white hover:border-sky-300 transition duration-150 p-0.5'> 
                            <div class="w-7 h-7 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center text-xs font-bold leading-none">
                                <?php echo strtoupper(substr($_SESSION['nombre_usuario'], 0, 1)); ?>
                            </div>
                        </a>
                        <button class='px-3 py-2 hidden md:block text-white rounded-md font-semibold flex items-center space-x-1 duration-150 hover:bg-sky-700'> 
                            <div><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></div>
                        </button>
                    </div>
                    
                    <div id='cabeza_hamburguesa_contenido' 
                         class='absolute right-0 top-12 mt-2 w-56 bg-white rounded-md shadow-xl z-50 
                                transition-all duration-300 opacity-0 invisible origin-top-right transform scale-95'>
                        
                        <div class="py-1">
                            <?php 
                            $clase_lateral = "block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100";

                            if ($privilegio == 'Admin') {
                                generar_enlace($fichero_ruta, "usuarios.php", "Gestión de Usuarios", $clase_lateral);
                                generar_enlace($fichero_ruta, "reporte_compras.php", "Reporte de Ventas", $clase_lateral);
                                generar_enlace($fichero_ruta, "administracion_eventos.php", "Aprobación de Eventos", $clase_lateral);
                                generar_enlace($fichero_ruta, "administracion_artistas.php", "Aprobación de Artistas", $clase_lateral);
                                echo '<hr class="border-gray-200">';
                            }
                            
                            if ($privilegio == 'Proveedor') {
                                generar_enlace($fichero_ruta, "nuevo_artista.php", "Solicitar Artista", $clase_lateral);
                                generar_enlace($fichero_ruta, "nuevo_evento.php", "Solicitar Evento", $clase_lateral);
                                generar_enlace($fichero_ruta, "artistas_solicitados.php", "Mis Artistas Solicitados", $clase_lateral);
                                generar_enlace($fichero_ruta, "eventos_solicitados.php", "Mis Eventos Solicitados", $clase_lateral);
                                generar_enlace($fichero_ruta, "saldo_proveedor.php", "Mi Saldo de Ventas", $clase_lateral);
                                echo '<hr class="border-gray-200">';
                            }

                            if ($privilegio == 'Cliente' || $privilegio == 'Admin') {
                                generar_enlace($fichero_ruta, "historial_compras.php", "Historial de Compras", $clase_lateral);
                                generar_enlace($fichero_ruta, "proximos_eventos.php", "Mis Proximos Eventos", $clase_lateral);
                                generar_enlace($fichero_ruta, "cesta.php", "Cesta de Compra", $clase_lateral);
                                generar_enlace($fichero_ruta, "mi_saldo.php", "Mi Saldo", $clase_lateral);
                            }
                            
                            echo '<hr class="border-gray-200">';
                            generar_enlace($fichero_ruta, "perfil_usuario.php", "Editar Perfil", $clase_lateral); 
                            
                            generar_enlace($fichero_ruta, "scripts/logout.php", "Cerrar Sesión", "block px-4 py-2 text-sm text-red-600 hover:bg-red-50");
                            ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </nav>
    </header> 
<script src="../js/cabeza.js"></script>