<?php  

    session_start(); /* Crea una sessione o riprende quella corrente */

    if(!empty($_SESSION['email'])){

        require_once "../connection.php";
        require "../utilityFunctions.php";

        $flagCheckTitle = false; /* Flag utilizzato per controllare se il titolo inserito è già presente nel db */
        $emailpub = $_SESSION['email']; 

        /* Verifico se la notizia è caricata dall'admin e, in caso affermativo setto la variabile "stato" a "true" (e quindi tale notizia non necessiterà di essere approvata), 
        altrimenti  la setto a NULL (indicando che tale notizia è in attesa di approvazione) */
        $query = "SELECT count(*) FROM amministratore WHERE email=$1";
        $prep = pg_prepare($db, "checkAdminPub", $query);
        $result = pg_execute($db, "checkAdminPub", array($emailpub));
        (pg_fetch_row($result)[0] == 1) ? $stato = 'true' : $stato = NULL;
        
        /* Entro se tutti i campi sono compilati correttamente */
        if(!empty($_POST) && !empty($_POST['categoria']) && !empty($_POST['titolo']) && !empty($_POST['contenuto']) && !empty($_FILES['imgCopertina']['name'])){
            $categoria = $_POST['categoria'];
            $titolo = $_POST['titolo'];
            $contenuto = str_replace('"', "”", $_POST['contenuto']);
            $img = $_FILES['imgCopertina']['tmp_name'];
            
            $query = "SELECT count(*) FROM notizia WHERE LOWER(titolo)=$1";
            $prep = pg_prepare($db, "checkTitoloPK", $query);
            $result = pg_execute($db, "checkTitoloPK", array(strtolower($titolo)));

            if(!$result){
                echo pg_last_error($db);
                exit;
            }

            $row = pg_fetch_row($result);

            /* Entro se il titolo inserito non è presente nel database e le dimensioni di questo e del contenuto della notizia non superano i limiti fissati */  
            if($row[0] == 0 && strlen($titolo) <= 100 && strlen($contenuto) <= 5000){
                if(!empty($_FILES['audio']['name'])){
                    $audio = $_FILES['audio']['tmp_name'];
                    $query = "INSERT INTO notizia(datapubblicazione, emailpub, titolo, contenuto, numcommenti, categoria, immagine, audio, stato) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
                    $prep = pg_prepare($db, "InsertNotizia", $query);
                    $params = array(date('Y-m-d H:i:s'), $emailpub, $titolo, $contenuto, "0", $categoria, convertFileToSave($img), convertFileToSave($audio), $stato);
                } else {
                    $query = "INSERT INTO notizia(datapubblicazione, emailpub, titolo, contenuto, numcommenti, categoria, immagine, stato) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
                    $prep = pg_prepare($db, "InsertNotizia", $query);
                    $params = array(date('Y-m-d H:i:s'), $emailpub, $titolo, $contenuto, "0", $categoria, convertFileToSave($img), $stato);
                }

                $result = pg_execute($db, "InsertNotizia", $params);

                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }

                header("location: ../PHP/notizieUtente.php"); /* redirect alla pagina personale dell'utente "Le mie notizie" */
            
            /* Entro se il titolo è già presente e deve essere eseguito un'aggiornamento del contenuto, immagine o audio della notizia */
            } else if ($row[0] == 1 && !empty($_POST['updateAction']) && strlen($titolo) <= 100 && strlen($contenuto) <= 5000) {
                if(!empty($_FILES['audio']['name'])){
                    $audio = $_FILES['audio']['tmp_name'];
                    $query = "UPDATE notizia SET datapubblicazione=$1, emailpub=$2, titolo=$3, contenuto=$4, categoria=$5, immagine=$6, audio=$7, stato=$9 WHERE titolo=$8;";
                    $prep = pg_prepare($db, "UpdateNotizia", $query);
                    $params = array(date('Y-m-d H:i:s'), $emailpub, $titolo, $contenuto, $categoria, convertFileToSave($img), convertFileToSave($audio), $titolo, $stato);
                } else {
                    $query = "UPDATE notizia SET datapubblicazione=$1, emailpub=$2, titolo=$3, contenuto=$4, categoria=$5, immagine=$6, stato=$8 WHERE titolo=$7;";
                    $prep = pg_prepare($db, "UpdateNotizia", $query);
                    $params = array(date('Y-m-d H:i:s'), $emailpub, $titolo, $contenuto, $categoria, convertFileToSave($img), $titolo, $stato);
                }

                $result = pg_execute($db, "UpdateNotizia", $params);

                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }
                
                /* Elimino la notiza modificata dalla tabella "notiziaapprovata" in quanto (come da progettazione del sito) necessita di riapprovazione */
                $query = "DELETE FROM notiziaapprovata WHERE titolo = $1";
                $prep = pg_prepare($db, "EraseNotizia", $query);
                $params = array($titolo);
                $result = pg_execute($db, "EraseNotizia", $params);

                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }
                
                header("location: ../PHP/notizieUtente.php"); /* redirect alla pagina personale dell'utente "Le mie notizie" */
            
            /* Entro se il titolo è già presente e non si tratta di una modifica della notizia */
            } else if($row[0] == 1 && strlen($titolo) <= 100){
                $flagCheckTitle = true;
            }
        }

    ?>

    <?php include './header.php'?>

    <!DOCTYPE html>
    <html>
    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="../CSS/style.css" /> <!-- collegamento del foglio di stile esterno -->
        <script type="text/javascript" src="../utilityFunctions.js"></script> <!-- inclusione delle funzioni di supporto per il salvataggio e recupero delle immagini e degli audio dal database -->
        <link rel="icon" href="logo1.png" type="image/png"/>
        <title>Tech Magazine | User Page</title>

    </head>
    <body>
        <div class="bodyAddNews">
            <div class="containerAddNews">
                <!--  
                    definizione del form per l'aggiunta della notizia, in particolar modo:
                        • se si sta aggiungenfo una nuova notizia, tutti i campi saranno vuoti e modificabili
                        • se si sta modificando una notizia esistente, ad eccezione dei campi "immagine" e "audio", tutti gli altri saranno precompilati con le informazioni specifiche della notizia
                        corrente; inoltre, il campo "titolo" non sarà modificabile e vi è l'aggiunta di un nuovo campo nascosto con name="updateAction" e value="updateNews" (utilizzato lato php)
                -->
                <form id="addNewsForm" class="addNewsForm" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
                    <div class="form-field-AN">
                        <label for="categoria">Categoria:</label>
                            <div>
                                <select name="categoria" required>
                                    <?php

                                        $query = "SELECT nome FROM categoria";
                                        $prep = pg_prepare($db, "caricaCategorie", $query);
                                        $result = pg_execute($db, "caricaCategorie", array());

                                        if(!$result){
                                            echo pg_last_error($db);
                                            exit;
                                        }
                                        
                                        while ($row = pg_fetch_array($result)) {
                                            ?>
                                            <option value="<?php echo $row[0]; ?>" <?php if(!empty($_POST['categoria']) && $row[0] == $_POST['categoria']){ ?> selected <?php } ?>><?php echo $row[0]; ?></option>
                                            <?php
                                        }

                                    ?>
                                </select>
                            </div>
                    </div>
                    
                    <div class="form-field-AN title">
                        <label for="titolo">Titolo:</label>
                        <input id="titolo" class="title" type="text" name="titolo" maxlength="150" placeholder="Inserisci il titolo" value="<?php 
                            if(!empty($_POST['titolo'])){echo $_POST['titolo'];}?>" <?php if(!empty($_POST['action']) && $_POST['action'] == "updateNews") {?>readonly<?php } ?> required/>
                        <small></small>
                    </div>
                    
                    <div class="form-field-AN">
                        <label for="contenuto">Contenuto:</label>
                        <textarea name="contenuto" maxlength="5000" placeholder="Inserisci il contenuto" rows="10" cols="120" required><?php 
                        if(!empty($_POST['contenuto'])){echo $_POST['contenuto'];}
                        ?></textarea>
                    </div>
                    
                    <div class="form-field-AN">
                        <label for="imgCopertina">Inserisci immagine di copertina:</label>
                        <input type="file" name="imgCopertina" accept="image/png, image/jpeg" required/>
                    </div>
                    
                    <div class="form-field-AN">
                        <label for="audio">Inserisci audio:</label>
                        <input type="file" name="audio" accept="audio/mp3"/>
                    </div>

                    <input type="text" name="updateAction" value="<?php if(!empty($_POST['action']) && $_POST['action'] == "updateNews"){echo "update";}?>" hidden>

                </form>
                
                    <div class="form-field-AN">
                        <div class="buttonsAN">
                            <button class="btnAN" onClick="window.open('../PHP/notizieUtente.php', '_self');">Annulla</button> 
                            <button class="btnAN" type="submit" form="addNewsForm">Carica notizia</button>
                        </div>
                    </div>

            </div>
            
            <!-- validazione input lato client -->
            <script type="text/javascript">

                /* Attribuzione di un gestore di eventi al form "addNewsForm" sull'evento "submit" */
                document.forms.addNewsForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if(addNewsForm.categoria.value == ""){
                        alert("Devi selezionare una categoria");
                        return false;
                    }
                    if(addNewsForm.titolo.value == ""){
                        alert("Devi inserire un titolo");
                        return false;
                    }
                    checkLength(addNewsForm.titolo, 100);

                    if(addNewsForm.contenuto.value == ""){
                        alert("Devi inserire un contenuto");
                        return false;
                    }
                    checkLength(addNewsForm.contenuto, 5000);
                    
                    if(addNewsForm.imgCopertina.value == ""){
                        alert("Devi inserire un'immagine di copertina!");
                        return false;
                    }
                    addNewsForm.submit();
                });

            </script>

            <?php
                /* Entro se la variabile "flagCheckTitle" è stata assegnata ed è uguale a "true", visualizzando un errore in corrispondenza del campo "Titolo" del form  */
                if(!empty($flagCheckTitle) && $flagCheckTitle == true){
                    $flagCheckTitle == false;
                    ?>
                    <script type="text/javascript">
                        showError(document.querySelector('#titolo'), "Titolo già presente");
                    </script>

                    <?php
                }

                pg_close($db);

            ?>
        </div>

        <?php include './footer.php'?>
        
    </body>
    </html>

    <?php
    } else {
        include 'errorPage.php'; /* Pagina di erore */
    }
    ?>


