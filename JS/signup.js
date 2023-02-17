
// Elementi da controllare
const nomeEl = document.querySelector('#nome');
const cognomeEl = document.querySelector('#cognome');
const usernameEl = document.querySelector('#username');
const emailEl = document.querySelector('#email');
const passwordEl = document.querySelector('#pswd');
const passwordConfirmEl = document.querySelector('#pswdConf');
const radioBase = document.getElementById('usrTypeBase');
const radioEditor = document.getElementById('usrTypeEditor');
// Form
const form = document.querySelector('#signup');

// FUNZIONI DI UTILITÀ

// La funzione emptyField restituisce true se il campo è vuoto
function emptyField(value){
    return value=='';
}

// La funzione isBetween restituisce true se la lunghezza della 
// stringa rientra nel range consentito
function isBetween(length, min, max){
    return length >= min && length <= max;
}

// La funzione isEmailValid controlla tramite una regular expression se l'email 
// è valida ed in caso affermativo restituisce true
function isEmailValid(address){
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(address);
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

// La funzione checkNome effettua il controllo sul campo nome
function checkNome(){
    let valid = false;
    const nome = nomeEl.value.trim(); // rimuove gli spazi bianchi da entrambi i lati di una stringa
    if(emptyField(nome)){
        showError(nomeEl, "Il campo nome non può essere vuoto.");
    }else{
        showSuccess(nomeEl);
        valid = true; 
    }
    return valid; 
}

// La funzione chechCognome effettua il controllo sul campo cognome
function checkCognome(){
    let valid = false;
    const cognome = cognomeEl.value.trim();
    if(emptyField(cognome)){
        showError(cognomeEl, "Il campo cognome non può essere vuoto.");
    }else{
        showSuccess(cognomeEl);
        valid=true; 
    }
    return valid; 
}

// La funzione checkUsername effettua il controllo sul campo username
function checkUsername(){
    let valid = false;
    const min = 3, max = 25;
    const username = usernameEl.value.trim(); 
    if (emptyField(username)) {
        showError(usernameEl, "Il campo username non può essere vuoto.");
    } else if (!isBetween(username.length, min, max)) {
        showError(usernameEl, `L'username deve essere compreso tra ${min} e ${max} caratteri.`);
    } else {
        showSuccess(usernameEl);
        valid = true;
    }
    return valid;
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

// La funzione checkRadios controlla se uno tra i radio buttons è stato selezionato
function checkRadios(){
    let valid = false;
    if(!radioBase.checked && !radioEditor.checked){
        showError(radios, "Devi selezionare una alternativa");
    }else{
        valid = true; 
    }
    return valid; 
}


// GESTIONE UPDATE INPUT FIELD

form.addEventListener('input', function (e) {
    // inserendo i dati in un form field si verificherà un input event che farà scattare l'observer
    switch (e.target.id) {
        case 'username':
            checkUsername();
            break;
        case 'nome':
            checkNome();
            break;
        case 'cognome':
            checkCognome();
            break; 
        case 'email':
            checkEmail();
            break; 
        case 'pswd':
            checkPassword();
            break; 
        case 'pswdConf':
            checkPasswordConfirm();
            break; 
        case 'usrType':
            checkRadios();
            break; 
    }
});



// GESTIONE RICHIESTA DI SUBMIT

form.addEventListener('submit', function (e) {
    // non sottomettere ancora il form
    e.preventDefault();

    // validazione singola dei campi
    let isNomeValid = checkNome();
    let isCognomeValid = checkCognome();
    let isUsernameValid = checkUsername();
    let validEmail = checkEmail();
    let isPasswordValid = checkPassword();
    let isPasswordCorrect = checkPasswordConfirm();
    let isChecked = checkRadios();
    // validazione complessiva dei campi
    let isFormValid = isNomeValid && isCognomeValid && isUsernameValid && validEmail && isPasswordValid && isPasswordCorrect && isChecked;
    if(isFormValid){
        form.submit();
    }else{
        alert("Il form non è valido! Riprova.")
    }
});