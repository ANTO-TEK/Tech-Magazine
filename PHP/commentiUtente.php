<?php

    session_start(); /* Crea una sessione o riprende quella corrente */

    /* Entro solo se l'utente è loggato */
    if(!empty($_SESSION['success']) && $_SESSION['success'] == "logged"){

        require_once "../connection.php";
        require "../utilityFunctions.php";

        /* Prelevo le variabili di sessione di interesse:
            • $emailcom -> email dell'utente loggato
            • $usrType -> tipo di utente loggato (base o editor)
        */
        $emailcom = $_SESSION['email'];
        $usrType = $_SESSION['usrType'];

        /* Distrugge le variabili specificate */
        unset($_SESSION['popupUpdate']);
        unset($_SESSION['popupVisualizza']);

        if(!empty($_POST)){

            /* Specifico un comportamento in base all'azione eseguita dall'utente */
            switch($_POST['action']){

                /* Elimino lo specifico commento dalla tabella; all'atto dell'eliminazione, un trigger si occuperà di eliminare tale commento anche dalla tabella
                   "commentoapprovato" se presente e di decrementare il campo "numcommenti" per quella notizia.  */
                case "delete":
                    $id = $_POST['id'];
                    $query = "DELETE FROM commento WHERE codice=$1";
                    $prep = pg_prepare($db, "deleteComment", $query);
                    $result = pg_execute($db, "deleteComment", array($id));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }
                    break;

                /* Imposto la variabile di sessione "popupUpdate" a "true" per la successiva visualizzazione del popup di aggiornamento del commento */
                case "update":
                    $_SESSION['popupUpdate'] = true;
                    break;
                
                /* Imposto la variabile di sessione "popupVisualizza" a "true" per la successiva comparsa del popup di visualizzazione del commento */
                case "visualizza":
                    $_SESSION['popupVisualizza'] = true;
                    break;

                /* Effettuo l'aggiornamento effettivo del commento:
                    • elimino il commento dalla tabella "commentoapprovato" se presente, in quanto (come da progettazione del sito) necessita di riapprovazione
                    • se è l'amministratore a modificare un suo commento, setto la variabile "stato" a "true" (e quindi tale commento non necessiterà di essere approvato), 
                      altrimenti la setto a NULL (indicando che tale commento è in attesa di approvazione)
                    • aggiorno la tabella "commento" con le nuove informazioni 
                */
                case "updateComment":
                    $id = $_POST['id'];
                    $datainserimento = date('Y-m-d H:i:s', strtotime($_POST['datainserimento']));
                    $contenuto = $_POST['contenuto'];
                    $titolo = $_POST['titolo'];

                    $query = "DELETE FROM commentoapprovato WHERE datainserimento=$1 AND emailcom=$2 AND titolo=$3";
                    $prep = pg_prepare($db, "deleteApprovedComment", $query);
                    $result = pg_execute($db, "deleteApprovedComment", array($datainserimento, $emailcom, $titolo));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }

                    $query = "SELECT count(*) FROM amministratore WHERE email=$1";
                    $prep = pg_prepare($db, "UpdateAdminCom", $query);
                    $result = pg_execute($db, "UpdateAdminCom", array($emailcom));
                    (pg_fetch_row($result)[0] == 1) ? $stato = 'true' : $stato = NULL;

                    $query = "UPDATE commento SET descrizione=$1, datainserimento=$2, emailcom=$3, titolo=$4, stato=$5 WHERE codice=$6;";
                    $prep = pg_prepare($db, "updateComment", $query);
                    $result = pg_execute($db, "updateComment", array($contenuto, date('Y-m-d H:i:s'), $emailcom, $titolo, $stato, $id));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }
                    break;
            }
        }

        /* Prelevo dal database i commenti per lo specifico utente loggato */
        $query = "SELECT titolo, descrizione, datainserimento, stato, codice FROM commento WHERE emailcom=$1";
        $prep = pg_prepare($db, "takeComments", $query);
        $result = pg_execute($db, "takeComments", array($emailcom));

        if(!$result){
            echo pg_last_error($db);
            exit;
        }

    ?>

    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" type="text/css" href="../CSS/style.css" /> <!-- collegamento del foglio di stile esterno -->
            <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"> <!-- collegamento del foglio di stile esterno -> Font e Icone -->
            <link rel="icon" href="logo1.png" type="image/png"/>
            <title>Tech Magazine | I miei commenti</title>
            <script>
                function reload(){
                    window.location="commentiUtente.php";
                }
            </script>
        </head>
    <body>

        <?php include "../PHP/header.php" ?>

        <div class="notizieUtenteBody">

            <h1>  
                <?php echo "I miei commenti"; ?>  
            </h1>

            <div class="div-commentiUtente">
                <table class="tabellaUtente">
                    <thead>
                        <tr>
                            <th>Notizia</th>
                            <th>Commento</th>
                            <th>Data inserimento</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Popolamento della tabella a seguito della query precedente (vedi riga 89) -->
                        <?php
                            while($row = pg_fetch_assoc($result)){
                                $notizia = $row['titolo'];
                                $commento = $row['descrizione'];
                                $datainserimento = substr($row['datainserimento'], 0, 19);
                                $id = $row['codice'];
                                $stato = [];
                                switch($row['stato']){
                                    case "":
                                        $stato[0] = "status-pending"; # nome della classe per lo stile
                                        $stato[1] = "In attesa";
                                        break;
                                    case "t":
                                        $stato[0] = "status-approved"; # nome della classe per lo stile
                                        $stato[1] = "Approvato";
                                        break;
                                    case "f":
                                        $stato[0] = "status-unapproved"; # nome della classe per lo stile
                                        $stato[1] = "Non approvato";
                                        break;
                                }
                        ?>
                        <tr>
                            <!-- Notizia -->
                            <td>
                                <div class="div-notizia">
                                    <a href="<?php $app = str_replace(" ","_",$notizia); echo './news.php?titolo='.$app; ?>"><?php echo $notizia ?></a>
                                </div>
                            </td>

                            <!-- Bottone visualizza -->
                            <td>
                                <div class="div-notizia">
                                    <form id="<?php echo "visualizza".$id; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                        <input type="text" name="contenuto" value="<?php echo $commento?>" hidden>
                                        <input type="text" name="action" value="visualizza" hidden>
                                        <input type="text" name="id" value="<?php echo $id?>" hidden>
                                    </form>
                                    <button id="visualizza" form="<?php echo "visualizza".$id; ?>" type="submit" class="seeCommentButton"><i class="material-icons">&#xe0b9;</i> <p>Visualizza</p></button>
                                </div>
                            </td>

                            <!-- Data inserimento del commento -->
                            <td>
                                <?php echo $datainserimento ?>
                            </td>

                            <!-- Stato -->
                            <td>
                                <p class="status <?php echo " ".$stato[0]; ?>">
                                    <?php echo " ".$stato[1]; ?>
                                </p>
                            </td>
                            
                            <!-- Azioni -->
                            <td class="actions">

                                <!-- Bottone modifica commento -->
                                <form id="<?php echo "updateForm".$id; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                    <input type="text" name="notizia" value="<?php echo $notizia?>" hidden>
                                    <input type="text" name="contenuto" value="<?php echo $commento?>" hidden>
                                    <input type="text" name="datainserimento" value="<?php echo $datainserimento?>" hidden>
                                    <input type="text" name="action" value="update" hidden>
                                    <input type="text" name="id" value="<?php echo $id?>" hidden>
                                </form>
                                <button id="edit" form="<?php echo "updateForm".$id; ?>" type="submit" class="pencilButton"><i class="material-icons">&#xe150;</i></button>

                                <!-- Bottone elimina commento-->
                                <form id="<?php echo $id; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                    <input type="text" name="action" value="delete" hidden>
                                    <input type="text" name="id" value="<?php echo $id?>" hidden>
                                </form>
                                <button form="<?php echo $id; ?>" type="submit" class="deleteButton"><i class="material-icons">&#xe872;</i></button>
                            </td>
                        </tr>

                        <?php
                        
                            }

                        ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
                
                /* Controllo se una delle due variabili di sessione è stata settata per la visualizzazione della finestra di popup */
                if((!empty($_SESSION['popupUpdate']) || !empty($_SESSION['popupVisualizza']))){
            ?>

            <div class="popup">
                <div class="popup-content">

                    <!-- Tag riempito dinamicamente con "Visualizza commento" o "Modifica commento" a seconda dell'azione dell'utente -->
                    <h2 id="h2comm">
                        
                    </h2>
                    
                    <!--  Immagine di profilo dell'utente -->
                    <img class="profilePic" src="
                    <?php

                        if($usrType != "admin"){

                            switch($usrType){
                                case "base":
                                    $query = "SELECT imgProfile FROM utentebase WHERE email=$1;";
                                    break;

                                case "editor":
                                    $query = "SELECT imgProfile FROM utenteeditor WHERE email=$1;";
                                    break;
                            }

                            $prep = pg_prepare($db, "takeImgProfile", $query);
                            $result = pg_execute($db, "takeImgProfile", array($emailcom));

                            if(!$result){
                                echo pg_last_error($db);
                                exit;
                            }

                            echo convertBinaryToImage2(pg_fetch_row($result)[0], str_replace(" ", "", $emailcom));

                        } else {

                            echo '../boss.png';

                        }
                        
                    ?>
                    ">

                    <!-- Compilazione del form in base al tipo di azione -->
                    <form id="formUpdateComment" action="<?php echo $_SERVER['PHP_SELF'] ?>" class="commentiUtenteform" method="post">
                        
                        <!-- Solo se si tratta di un'azione di aggiornamento mostro, in sola lettura, il titolo della notizia relativo al commento -->
                        <?php 
                            if(!empty($_SESSION['popupUpdate']) && $_SESSION['popupUpdate']) { ?>
                                <div class="form-field-CU">
                                    <label for="titolo">Notizia</label>
                                    <input type="text" name="titolo" maxlength="150" value="<?php if(!empty($_POST['notizia'])){echo $_POST['notizia'];}?>" readonly/>
                                </div>
                        <?php 
                            } 
                        ?>
                        
                        <!-- Contenuto del commento: 
                                • se si tratta di un'azione di visualizzazione, il contenuto del commento sarà visibile in sola lettura
                                • se si tratta di un'azione di aggiornamento, il contenuto del commento sarà modificabile, ma obbligatorio 
                        -->
                        <div class="form-field-CU">
                            <label for="contenuto" id="lbl">Contenuto</label>
                            <textarea name="contenuto" maxlength="500" rows="10" cols="120" <?php if(!empty($_SESSION['popupVisualizza']) && $_SESSION['popupVisualizza']) { ?> readonly <?php } ?> required><?php if(!empty($_POST['contenuto'])){echo $_POST['contenuto'];}?></textarea>
                        </div>
                        
                        <!-- Campi di utilità nascosti -->
                        <input type="text" name="id" value="<?php echo $_POST['id']?>" hidden>
                        <input type="text" name="action" value="updateComment" hidden>
                        <input type="text" name="datainserimento" value="<?php echo $datainserimento?>"  hidden>

                    </form>

                    <div class="form-button-CU">

                        <!-- Bottone annulla -->
                        <button id="annulla" class="btn-popup" onclick="reload();">Annulla</button>
                        
                        <!-- Se si tratta di un'azione di aggiornamento, inserisco nel tag h2 (riga 229) il testo "Modifica commento", e mostro il bottone Invia,
                            altrimenti inserisco nel tag h2 (riga 229) il testo "Visualizza commento" 
                        -->
                        <?php 
                            if(!empty($_SESSION['popupUpdate']) && $_SESSION['popupUpdate']) { ?>
                                <script>
                                    const text = document.getElementById('h2comm');
                                    text.innerHTML = "Modifica commento";
                                </script>
                                <button class="btn-popup" type="submit" form="formUpdateComment" onclick="reload();">Invia</button>
                            <?php } else { ?>
                                <script>
                                    const text = document.getElementById('h2comm');
                                    text.innerHTML = "Visualizza commento";
                                </script>
                        <?php } ?>
                    </div>
                </div> 
            </div>
                                
            <script type="text/javascript">

                /* Attribuzione di un gestore di eventi al bottone "Annulla" sull'evento "click" */
                document.getElementById('annulla').addEventListener('click', function(){

                    /* Al click modifico la proprietà display a "none" per eliminare il popup dalla visualizzazione */
                    document.querySelector('.popup').style.display = 'none';

                });
            </script>

            <?php
                }
            ?>

        </div>
        
    </body>
    </html>

    <?php include 'footer.php'; ?>

    <?php 

        pg_close($db);

        } else {
            include 'errorPage.php'; /* Pagina di erore */
        }

    ?>