// Elemento da controllare 
const emailEl = document.getElementById('email');

// Form
const form = document.getElementById('resetPasswordForm');

const click = false; 

// FUNZIONI DI UTILITÀ

// La funzione emptyField restituisce true se il campo è vuoto
function emptyField(value){
    return value=='';
}

// La funzione isEmailValid controlla tramite una regular expression se l'email 
// è valida ed in caso affermativo restituisce true
function isEmailValid(address){
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(address);
}

// La funzione checkEmail effettua il controllo sul campo email
function checkEmail(){
    let valid = false;
    const email = emailEl.value.trim();
    if(emptyField(email)){
        showError(emailEl, "Il campo email non può essere vuoto.");
    }else if(!isEmailValid(email)){
        showError(emailEl, "L'email inserita non è valida.");
    }else{
        showSuccess(emailEl);
        valid = true;
    }
    return valid; 
}

// La funzione showError evidenzia di rosso il bordo del text field 
// e mostra a video un errore di input non valido
function showError(input, message){
    // accede al <div> che contiene il text field
    const formField = input.parentElement;
    // rimuove success dal nome della classe ed aggiunge error (vedi css)
    formField.classList.remove('success');
    formField.classList.add('error');
    // seleziona l'elemento <small> contenuto nel <div> e setta il 
    // messaggio di errore nella sua proprietà textContent
    const error = formField.querySelector('small');
    error.textContent = message;
}

// La funzione showError evidenzia di verde il bordo del text field
function showSuccess(input){
    // accede al <div> che contiene il text field
    const formField = input.parentElement;
    // rimuove error dal nome della classe ed aggiunge success (vedi css)
    formField.classList.remove('error');
    formField.classList.add('success');
    // seleziona l'elemento <small> contenuto nel <div> e cancella il 
    // messaggio di errore dalla sua proprietà textContent
    const error = formField.querySelector('small');
    error.textContent = '';
}

// GESTIONE UPDATE INPUT FIELD

form.addEventListener('input', function (e) {
    // inserendo i dati in un form field si verificherà un input event che farà scattare l'observer
    checkEmail();
    console.log("ok");
});


// Controllo all'invio del form
form.addEventListener('submit', function (e) {

    e.preventDefault();

    const email = emailEl.value.trim();

    if (emptyField(email)){
        alert("Compila tutti i campi!");
    }else{
        form.submit();
    }
    
});
