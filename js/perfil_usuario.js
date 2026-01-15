document.addEventListener('DOMContentLoaded', function() {
    
    const campoContraActual = document.getElementById('contra_actual');
    const cajaOjito1 = document.getElementById('caja_ojito_1');
    const imgOjito1 = document.getElementById('ojito_1');

    const campoContranueva = document.getElementById('contra_nueva');
    const cajaOjito2 = document.getElementById('caja_ojito_2');
    const imgOjito2 = document.getElementById('ojito_2');

    const campoContraConfirm = document.getElementById('contra_confirmar');
    const cajaOjito3 = document.getElementById('caja_ojito_3');
    const imgOjito3 = document.getElementById('ojito_3');



    if (campoContraActual && cajaOjito1 && imgOjito1) {

        cajaOjito1.addEventListener('click', function() {

            if (campoContraActual.type === 'password') {
                campoContraActual.type = 'text';
                
                imgOjito1.src = '../img/abierto.png'; 
                imgOjito1.alt = 'abierto';
                
            } else {
                campoContraActual.type = 'password';
            
                imgOjito1.src = '../img/cerrado.png'; 
                imgOjito1.alt = 'cerrado';
            }
        });
    }

    if (campoContranueva && cajaOjito2 && imgOjito2) {

        cajaOjito2.addEventListener('click', function() {

            if (campoContranueva.type === 'password') {
                campoContranueva.type = 'text';
                
                imgOjito2.src = '../img/abierto.png'; 
                imgOjito2.alt = 'abierto';
                
            } else {
                campoContranueva.type = 'password';
            
                imgOjito2.src = '../img/cerrado.png'; 
                imgOjito2.alt = 'cerrado';
            }
        });
    }

    if (campoContraConfirm && cajaOjito3 && imgOjito3) {
        
        cajaOjito3.addEventListener('click', function() {

            if (campoContraConfirm.type === 'password') {
                campoContraConfirm.type = 'text';
                
                imgOjito3.src = '../img/abierto.png'; 
                imgOjito3.alt = 'abierto';
                
            } else {
                campoContraConfirm.type = 'password';
            
                imgOjito3.src = '../img/cerrado.png'; 
                imgOjito3.alt = 'cerrado';
            }
        });
    }
});