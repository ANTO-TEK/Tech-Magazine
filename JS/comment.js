// Elementi da controllare
const commento = document.querySelector('#commento');

// Form
const form = document.querySelector('#addComment');



// FUNZIONI DI UTILITÀ

// La funzione emptyField restituisce true se il campo è vuoto
function emptyField(value){
    return value=='';
}



// Controllo campi vuoti
form.addEventListener('submit', function (e) {
    e.preventDefault();

    const commentoOk = commento.value.trim();

    if (emptyField(commentoOk)){
        alert("Attenzione, non puoi inserire un commento vuoto.");
    }else{
        form.submit();
    }
    
});