
/* Funzione che si occupa di controllare la dimensione dei campi del form. 
   Se la lunghezza massima del campo viene superata, mostra un alert. 
*/
function checkLength(item, maxLength){
    if (item.value.length > maxLength){
        alert("Dimensione massima del " + item.name + " " + maxLength + " caratteri!");
        return false;
    }
}

/* La funzione showError evidenzia di rosso il bordo del text field 
   e mostra a video un errore di input non valido
*/ 
function showError(input, message){
    const formField = input.parentElement;
    formField.classList.add('error');
    const error = formField.querySelector('small');
    error.textContent = message;
} 


