// Elementi da controllare
const emailEl = document.querySelector('#email');
const passwordEl = document.querySelector('#pswd');

// Form
const form = document.querySelector('#login');



// FUNZIONI DI UTILITÀ

// La funzione emptyField restituisce true se il campo è vuoto
function emptyField(value){
    return value=='';
}



// Controllo campo email vuoto
form.addEventListener('submit', function (e) {

    e.preventDefault();

    const email = emailEl.value.trim();
    const password = passwordEl.value.trim();

    if (emptyField(email) || emptyField(password)){
        alert("Compila tutti i campi!");
    }else{
        form.submit();
    }
    
});