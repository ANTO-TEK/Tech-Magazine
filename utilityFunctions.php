<?php

    /* Funzione di utilità per convertire un file per il salvataggio */
    function convertFileToSave($item){
        $fh = fopen($item, 'rb'); //rb indica lettura di byte
        $fbytes = fread($fh, filesize($item));
        return base64_encode($fbytes);
    }

     /* Funzione di utilità per convertire un immagine partendo da un suo formato binario. In particolar modo, questa prima
     versione viene utilizzata solo dalla home page dal momento in cui le immagini per essa salvata, essendo lo stesso livello di visibilità della
     cartella images, vengono ad avere il seguente path: ./images/nomeImgNotizia.jpg. In particolar modo, per una correttezza nel modo in cui 
     un immagine viene salvata, eventuali caratteri di '%' vengono ad essere eliminati dal nome assegnato all'immagine convertita. */
    function convertBinaryToImage($item, $name){
        $path = "./images";
        $name = str_replace("%","",$name);
        $img = './images/'.$name.'.jpg';
		$imgdata = substr($item, 2);
		$bin = hex2bin($imgdata);
		file_put_contents($img, base64_decode($bin));
        return $img;
    }

     /* Funzione di utilità per convertire un immagine partendo da un suo formato binario. In particolar modo, questa seconda
     versione viene utilizzata da tutte le restanti pagine diverse dalla home page, dal momento in cui le immagini per esse salvata, essendo che
     ognuna di esse si situa nella cartella PHP ha un livello di visibilità inferiore rispetto alla cartella images, vengono ad avere 
     il seguente path: ../images/nomeImgNotizia.jpg. In particolar modo, per una correttezza nel modo in cui  un immagine viene salvata,anche in questo
     caso eventuali caratteri di '%' vengono ad essere eliminati dal nome assegnato all'immagine convertita. */
    function convertBinaryToImage2($item, $name){
        $path = "../images";
        $name = str_replace("%","",$name);
        $img = '../images/'.$name.'.jpg';
		$imgdata = substr($item, 2);
		$bin = hex2bin($imgdata);
		file_put_contents($img, base64_decode($bin));
        return $img;
    }

    /* Funzione di utilità per convertire un audio partendo da un suo formato binario. In particolar modo, questa prima
     versione viene utilizzata solo dalla home page dal momento in cui gli audio per essa salvata, essendo lo stesso livello di visibilità della
     cartella audio, vengono ad avere il seguente path: ./audio/nomeAudio.mp3. In particolar modo, per una correttezza nel modo in cui 
     un audio viene salvato, eventuali caratteri di '%' vengono ad essere eliminati dal nome assegnato all'audio convertito. */
    function convertBinaryToAudio($item, $name){
        $path = "./audio";
        $name = str_replace("%","",$name);
        $mp3 = './audio/'.$name.'.mp3';
		$mp3data = substr($item, 2);
		$bin = hex2bin($mp3data);
		file_put_contents($mp3, base64_decode($bin));
        return $mp3;
    }


    /* Funzione di utilità per convertire un audio partendo da un suo formato binario. In particolar modo, questa seconda
     versione viene utilizzata da tutte le restanti pagine diverse dalla home page, dal momento in cui gli audio per esse salvati, essendo che
     ognuna di esse si situa nella cartella PHP ha un livello di visibilità inferiore rispetto alla cartella audio, vengono ad avere 
     il seguente path: ../audio/nomeAudio.mp3. In particolar modo, per una correttezza nel modo in cui  un audio viene salvato,anche in questo
     caso eventuali caratteri di '%' vengono ad essere eliminati dal nome assegnato all'audio convertito. */
    function convertBinaryToAudio2($item, $name){
        $path = "../audio";
        $name = str_replace("%","",$name);
        $mp3 = '../audio/'.$name.'.mp3';
		$mp3data = substr($item, 2);
		$bin = hex2bin($mp3data);
		file_put_contents($mp3, base64_decode($bin));
        return $mp3;
    }

    
    
?>