// Valore assunto di default dal player audio, ovvero non in riproduzione
let isPlaying = false;

// Recupero dell'id associato al plauer audio
const player = document
    .getElementById('player');

// Recupero dell'id associato alla gif-audio
const gif = document.getElementById('gif-audio');
gif.src = "../stop.jpg";

// Bottone di riproduzione del player
const playBtn = document.querySelector('.pause');

//Listener aggiunto sul click del player audio
playBtn.addEventListener('click', function () {

    //Se non è in riproduzione, viene cambiata l'icona del player da play a pausa, viene avviata la riproduzione, viene caricata la gif di riproduzione e isPlaying viene settato a true.
    // Se è in riproduzione, viene cambiata l'icona del player da pausa a play, viene messa in pausa la riproduzione, viene caricata la gif di stop audio e isPlaying viene settato a false.
    if (!isPlaying) {
        playBtn.classList.remove('ri-play-circle-line');
        playBtn.classList.add('ri-pause-circle-line');
        player.play();
        gif.src = "../gifAudio.gif";
        isPlaying = true;
    } else {
        playBtn.classList.remove('ri-pause-circle-line');
        playBtn.classList.add('ri-play-circle-line');
        player.pause();
        gif.src = "../stop.jpg";
        isPlaying = false;
    }

});

//Funzione di utilità associata alla terminazione della riproduzione da parte del player audio la quale, a riproduzione terminata, mette in pausa il player, carica l'icona di pausa al bottone
// e inserisce la gif di riproduzione terminata
function onEnded(){
    playBtn.classList.remove('ri-pause-circle-line');
    playBtn.classList.add('ri-play-circle-line');
    player.pause();
    gif.src = "../stop.jpg";
}