document.addEventListener('DOMContentLoaded', function() {
    const campoContra = document.getElementById('contra');
    const cajaOjito = document.getElementById('caja_ojito_login');
    const imgOjito = document.getElementById('ojito_login');

    if (campoContra && cajaOjito && imgOjito) {
        
        cajaOjito.addEventListener('click', function() {

            if (campoContra.type === 'password') {
                campoContra.type = 'text';
                
                imgOjito.src = '../img/abierto.png'; 
                imgOjito.alt = 'abierto';
                
            } else {
                campoContra.type = 'password';
            
                imgOjito.src = '../img/cerrado.png'; 
                imgOjito.alt = 'cerrado';
            }
        });
    }
});