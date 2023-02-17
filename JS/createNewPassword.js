// Elementi 
const passwordEl = document.getElementById("pswd");
const passwordConfirmEl = document.getElementById("pswdConf");

// Form 
const form = document.getElementById('createNewPwdForm');



// FUNZIONI DI UTILITÀ

// La funzione emptyField restituisce true se il campo è vuoto
function emptyField(value){
    return value=='';
}

// La funzione isPasswordStrong controlla se la password inserita rispetta tutti
// i parametri richiesti per essere considerata forte ed in tal caso restituisce
// true
function isPasswordStrong(pass){
    const re = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})");
    // ^ -> inizio
    // (?=.*[a-z]) -> almeno un carattere minuscolo
    // (?=.*[A-Z]) -> almeno un carattere maiuscolo
    // (?=.*[0-9]) -> almeno una cifra
    // (?=.*[!@#\$%\^&\*]) -> almeno un carattere speciale
    // (?=.{8,}) -> lunghezza di almeno 8 caratteri
    return re.test(pass);
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


// VALIDAZIONE DEI CAMPI INSERITI NEL FORM

// La funzione checkPassowrd effettua il controllo sul campo password
function checkPassword(){
    let valid = false;
    const password = passwordEl.value.trim();
    if(emptyField(password)){
        showError(passwordEl, "Il campo password non può essere vuoto.");
    }else if(!isPasswordStrong(password)){
        showError(passwordEl, "La password deve contenere almeno 8 caratteri tra cui: una lettera minuscola, una lettera maiuscola, una cifra e un carattere speciale tra (!@#$%^&*).");
    }else{
        showSuccess(passwordEl);
        valid=true;
    }
    return valid; 
}

// La funzione checkPasswordConfirm controlla se le password corrispondono
function checkPasswordConfirm(){
    let valid = false; 
    const confirmPassword = passwordConfirmEl.value.trim();
    const password = passwordEl.value.trim();
    if(emptyField(confirmPassword)){
        showError(passwordConfirmEl, "Il campo di conferma password non può essere vuoto");
    }else if(password != confirmPassword){
        showError(passwordConfirmEl, "Le password non corrispondono");
    }else{
        showSuccess(passwordConfirmEl);
        valid = true; 
    }
    return valid; 
}

form.addEventListener('input', function (e) {
    // inserendo i dati in un form field si verificherà un input event che farà scattare l'observer
    switch (e.target.id) {
        case 'pswd':
            checkPassword();
            break; 
        case 'pswdConf':
            checkPasswordConfirm();
            break; 
    }
});


// GESTIONE RICHIESTA DI SUBMIT

form.addEventListener('submit', function (e) {
    // non sottomettere ancora il form
    e.preventDefault();

    // validazione singola dei campi
    let isPasswordValid = checkPassword();
    let isPasswordCorrect = checkPasswordConfirm();
    // validazione complessiva dei campi
    
    if(isPasswordValid && isPasswordCorrect){
        form.submit();
    }else{
        alert("Il form non è valido! Riprova.")
    }
});