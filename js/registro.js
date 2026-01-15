document.addEventListener('DOMContentLoaded', function() {
    
    const campoContra = document.getElementById('contra');
    const cajaOjito1 = document.getElementById('caja_ojito_1');
    const imgOjito1 = document.getElementById('ojito_1');

    const campoContraConfirm = document.getElementById('contra_confirm');
    const cajaOjito2 = document.getElementById('caja_ojito_2');
    const imgOjito2 = document.getElementById('ojito_2');


    if (campoContra && cajaOjito1 && imgOjito1) {

        cajaOjito1.addEventListener('click', function() {

            if (campoContra.type === 'password') {
                campoContra.type = 'text';
                
                imgOjito1.src = '../img/abierto.png'; 
                imgOjito1.alt = 'abierto';
                
            } else {
                campoContra.type = 'password';
            
                imgOjito1.src = '../img/cerrado.png'; 
                imgOjito1.alt = 'cerrado';
            }
        });
    }

    if (campoContraConfirm && cajaOjito2 && imgOjito2) {
        
        cajaOjito2.addEventListener('click', function() {

            if (campoContraConfirm.type === 'password') {
                campoContraConfirm.type = 'text';
                
                imgOjito2.src = '../img/abierto.png'; 
                imgOjito2.alt = 'abierto';
                
            } else {
                campoContraConfirm.type = 'password';
            
                imgOjito2.src = '../img/cerrado.png'; 
                imgOjito2.alt = 'cerrado';
            }
        });
    }
});