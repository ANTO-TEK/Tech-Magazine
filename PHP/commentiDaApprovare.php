<?php

    session_start(); /* Crea una sessione o riprende quella corrente */
    
    /* Entro solo se l'utente è loggato ed è un amministratore */
    if(!empty($_SESSION['usrType']) && $_SESSION['usrType'] == "admin"){

        require_once "../connection.php";
        require "../utilityFunctions.php";

        /* Prelevo le variabili di sessione di interesse:
            • $emailcom -> email dell'utente loggato
            • $usrType -> tipo di utente loggato (base o editor)
        */
        $emailcom = $_SESSION['email'];
        $usrType = $_SESSION['usrType'];
        
        /* Distrugge le variabili specificate */
        unset($_SESSION['popupVisualizza']);

        if(!empty($_POST)){

            /* Specifico un comportamento in base all'azione eseguita dall'utente */
            switch($_POST['action']){

                /* Inserisco il commento approvato nell'omonima tabella; all'atto dell'inserimento, un trigger si occuperà di aggiornare lo stato dello specifico commento 
                   a "true" nella tabella commento e di incrementare il campo "numcommenti" della notizia associata a quel commento. Di conseguenza, l'utente proprietario 
                   di quel commento vedrà, nella sua pagina personale, il messaggio "Approvato" per quello specifico commento
                */
                case "approva":
                    $notizia = $_POST['notizia'];
                    $emailcom = $_POST['emailcom'];
                    $datainserimento = date('Y-m-d H:i:s', strtotime($_POST['datainserimento']));

                    $query = "INSERT INTO commentoapprovato VALUES($1, $2, $3, $4);";
                    $prep = pg_prepare($db, "approvedComment", $query);
                    $result = pg_execute($db, "approvedComment", array(date('Y-m-d H:i:s'), $datainserimento, $emailcom, $notizia));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }

                    header("location: commentiDaApprovare.php"); /* redirect alla pagina personale dell'admin "Commenti da approvare" */
                    break;
                
                /* Prelevo dal database l'immagine di profilo dell'utente proprietario del commento da visualizzare e imposto la variabile di sessione "popupVisualizza" a "true" 
                   per la successiva comparsa del popup di visualizzazione del commento
                */
                case "visualizza":
                    $query = "SELECT email, nome, cognome, imgprofile, username FROM utentebase UNION SELECT email, nome, cognome, imgprofile, username FROM utenteeditor UNION SELECT email, nome, cognome, imgprofile, username FROM amministratore";
                    $prep = pg_prepare($db, "takeUser", $query);
                    $result = pg_execute($db, "takeUser", array());


                    if(!$result) {
                        echo pg_last_error($db);
                        exit;
                    }

                    while($row = pg_fetch_array($result)){
                        if(!empty($_POST['utente']) && $row[0] == $_POST['utente']){
                            $mail = explode("@", $row[0]);
                            $userImage = convertBinaryToImage2($row[3],$mail[0]);
                            break;
                        }
                    }

                    $_SESSION['popupVisualizza'] = true;
                    break;
                
                /* Effettuo un aggiornamento dello stato dello specifico commento, impostandolo a "false". Di conseguenza, l'utente proprietario di quel commento vedrà, nella sua
                   pagina personale, il messaggio "Non approvato" per quello specifico commento */
                case "disapprova":
                    $id = $_POST['id'];

                    $query = "UPDATE commento SET stato = $1 WHERE codice = $2;";
                    $prep = pg_prepare($db, "unApprovedComment", $query);
                    $result = pg_execute($db, "unApprovedComment", array('false', $id));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }

                    header("location: commentiDaApprovare.php"); /* redirect alla pagina personale dell'admin "Commenti da approvare" */
                    break;
            }
        }

        /* Prelevo dal database i commenti di tutti gli utenti che sono in attesa di approvazione ("stato" = NULL) */
        $query = "SELECT titolo, descrizione, datainserimento, emailcom, codice FROM commento WHERE stato IS NULL";
        $prep = pg_prepare($db, "takeComments", $query);
        $result = pg_execute($db, "takeComments", array());

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
            <link rel="stylesheet" type="text/css" href="../CSS/style.css" />  <!-- collegamento del foglio di stile esterno -->
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> <!-- collegamento del foglio di stile esterno -> Font e Icone -->
            <link rel="icon" href="logo1.png" type="image/png"/>
            <title>Tech Magazine | Commenti da approvare</title>
        </head>
    <body>
            <?php include "../PHP/header.php"; ?>

            <div class="notizieUtenteBody">
                <h1>  
                    <?php echo "Commenti da approvare"; ?>  
                </h1>

            <div class="div-commentiUtente">
                <table class="tabellaUtente">   
                    <thead>
                        <tr>
                            <th>Notizia</th>
                            <th>Commento</th>
                            <th>Data inserimento</th>
                            <th>Utente</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>

                        <!-- Popolamento della tabella a seguito della query precedente (vedi riga 91) -->
                        <?php
                            while($row = pg_fetch_assoc($result)){
                                $notizia = $row['titolo'];
                                $commento = $row['descrizione'];
                                $datainserimento = substr($row['datainserimento'], 0, 19);
                                $id = $row['codice'];
                                $utente = $row['emailcom'];
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
                                        <input type="text" name="utente" value="<?php echo $utente?>" hidden>
                                    </form>
                                    <button id="visualizza" form="<?php echo "visualizza".$id; ?>" type="submit" class="seeCommentButton"><i class="material-icons">&#xe0b9;</i> <p>Visualizza</p></button>
                                </div>
                            </td>
                            
                            <!-- Data inserimento del commento -->
                            <td>
                                <?php echo $datainserimento ?>
                            </td>
                            
                            <!-- Email utente proprietario -->
                            <td>
                                <?php echo $utente ?></td>
                            </td>
                            
                            <!-- Azioni -->
                            <td class="actions">

                                <!-- Bottone approva commento -->
                                <form id="<?php echo "approva".$id; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                    <input type="text" name="notizia" value="<?php echo $notizia?>" hidden>
                                    <input type="text" name="emailcom" value="<?php echo $utente?>" hidden>
                                    <input type="text" name="datainserimento" value="<?php echo $datainserimento?>" hidden>
                                    <input type="text" name="action" value="approva" hidden>
                                </form>
                                <button form="<?php echo "approva".$id; ?>" type="submit" class="seeCommentButton"><i class="material-icons">&#xe834;</i></button>
                                
                                <!-- Bottone disapprova commento -->
                                <form id="<?php echo "disapprova".$id; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                    <input type="text" name="action" value="disapprova" hidden>
                                    <input type="text" name="id" value="<?php echo $id?>" hidden>
                                </form>
                                <button form="<?php echo "disapprova".$id; ?>" type="submit" class="seeCommentButton"><i class="material-icons">&#xe909;</i></button>
                            </td>
                        </tr>

                        <?php

                            }

                        ?>
                    </tbody>
                </table>
            </div>

            <?php 

                /* Controllo la variabili di sessione "popupVisualizza" è stata settata per la visualizzazione della finestra di popup */
                if(!empty($_SESSION['popupVisualizza'])){
                    
            ?>

            <div class="popup">
                <div class="popup-content">

                    <h2 id="h2comm">
                        Visualizza commento
                    </h2>

                    <!--  Immagine di profilo dell'utente proprietario dello specifico commento-->
                    <img class="profilePic" src="
                    <?php
                        echo $userImage;
                    ?>
                    ">

                    <!-- Contenuto del commento: il contenuto del commento sarà visibile in sola lettura -->
                    <div class="form-field-CU">
                        <label for="contenuto">Contenuto</label>
                        <textarea name="contenuto" maxlength="500" rows="10" cols="120" id="taCDA" readonly><?php if(!empty($_POST['contenuto'])){echo $_POST['contenuto'];}?></textarea>
                    </div>
                    
                    <!-- Bottone annulla -->
                    <div class="form-button-CU">
                        <button id="annulla" class="btn-popup" form="formUpdateComment">Annulla</button>
                    </div>
                </div> 
            </div>

            <?php 
                }
            ?>

            <script type="text/javascript">

                /* Attribuzione di un gestore di eventi al bottone "Annulla" sull'evento "click" */
                document.getElementById('annulla').addEventListener('click', function(){

                    /* Al click modifico la proprietà display a "none" per eliminare il popup dalla visualizzazione */
                    document.querySelector('.popup').style.display = 'none';
                });

                
            </script>

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