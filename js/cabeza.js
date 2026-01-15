document.addEventListener('DOMContentLoaded', function() {
    const botonUsuario = document.getElementById('cabeza_boton_usuario');
    const contenidoMenu = document.getElementById('cabeza_hamburguesa_contenido');

    if (botonUsuario && contenidoMenu) {
        botonUsuario.addEventListener("click", alternarMenu);
    }

    function alternarMenu() {
        if (contenidoMenu.classList.contains('opacity-0')) {
            mostrarMenu();
        } else {
            esconderMenu();
        }
    }

    function mostrarMenu() {
        contenidoMenu.classList.remove('opacity-0', 'invisible', 'scale-95');
        contenidoMenu.classList.add('opacity-100', 'scale-100');
    }

    function esconderMenu() {
        contenidoMenu.classList.add('opacity-0', 'invisible', 'scale-95');
        contenidoMenu.classList.remove('opacity-100', 'scale-100');
    }
});