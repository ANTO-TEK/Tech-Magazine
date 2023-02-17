<?php

    session_start(); /* Crea una sessione o riprende quella corrente */

    /* Entro solo se l'utente è loggato ed è un amministratore o un editor */
    if(!empty($_SESSION['usrType']) && ($_SESSION['usrType'] == "admin" || $_SESSION['usrType'] == "editor")){

        require_once "../connection.php";
    
        /* Prelevo le variabili di sessione di interesse:
            • $emailcom -> email dell'utente loggato
            • $usrType -> tipo di utente loggato (base o editor)
        */  
        $emailpub = $_SESSION['email'];
        $usrType = $_SESSION['usrType'];
        
        if(!empty($_POST) && $_POST['action'] == "delete"){

            /* Elimino la specifico commento dalla tabella; all'atto dell'eliminazione, un trigger si occuperà di eliminare tale notizia anche dalla tabella
                "notiziaapprovata" se presente e di eliminare dalla tabella "commento" tutti i commenti associati a tale notizia  */

            $titolo = $_POST['titolo'];
            $query = "DELETE FROM notizia WHERE titolo=$1";
            $prep = pg_prepare($db, "deleteNews", $query);
            $result = pg_execute($db, "deleteNews", array($titolo));

            if(!$result){
                echo pg_last_error($db);
                exit;
            }
        }

        /* Prelevo dal database le notizie per lo specifico utente loggato */
        $query = "SELECT titolo, categoria, contenuto, numcommenti, datapubblicazione, stato FROM notizia WHERE emailpub=$1";
        $prep = pg_prepare($db, "takeNews", $query);
        $result = pg_execute($db, "takeNews", array($emailpub));

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
            <title> Tech Magazine | Le mie notizie </title>
        </head>
    <body>

        <?php include "../PHP/header.php"; ?>

        <div class="notizieUtenteBody">
            
            <h1>  
                <?php echo "Le mie notizie"; ?>  
            </h1>

            <div class="div-addNewsBtn">
                <button class="addNewsBtn" onclick="window.open('../PHP/addNews.php', '_self');">Aggiungi una notizia</button>
            </div>

            <div class="div-notizieUtente">
                <table class="tabellaUtente">
                    <thead>
                        <tr>
                            <th>Notizia</th>
                            <th>Categoria</th>
                            <th>Commenti</th>
                            <th>Data inserimento</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>

                        <!-- Popolamento della tabella a seguito della query precedente (vedi riga 33) -->
                        <?php
                            while($row = pg_fetch_assoc($result)){
                                $notizia = $row['titolo'];
                                $categoria = $row['categoria'];
                                $contenuto = $row['contenuto'];
                                $datapubblicazione = substr($row['datapubblicazione'], 0, 19);
                                $numcommenti = $row['numcommenti'];
                                $stato = [];
                                switch($row['stato']){
                                    case "":
                                        $stato[0] = "status-pending";
                                        $stato[1] = "In attesa";
                                        break;
                                    case "t":
                                        $stato[0] = "status-approved";
                                        $stato[1] = "Approvata";
                                        break;
                                    case "f":
                                        $stato[0] = "status-unapproved";
                                        $stato[1] = "Non approvata";
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

                                <!-- Categoria -->
                                <td>
                                    <?php echo $categoria ?>
                                </td>
                                
                                <!-- Numero di commenti -->
                                <td>
                                    <?php echo $numcommenti ?>
                                </td>
                                
                                <!-- Data pubblicazione notizia -->
                                <td>
                                    <?php echo $datapubblicazione ?>
                                </td>

                                <!-- Stato -->
                                <td>
                                    <p class="status <?php echo " ".$stato[0]; ?>">
                                        <?php echo " ".$stato[1]; ?>
                                    </p>
                                </td>

                                <!-- Azioni -->
                                <td class="actions">

                                    <!-- Bottone modifica notizia -->
                                    <form id="<?php echo "updateNews".$notizia; ?>" action="../PHP/addNews.php" method="post">
                                        <input type="text" name="titolo" value="<?php echo $notizia;?>" hidden>
                                        <input type="text" name="categoria" value="<?php echo $categoria;?>" hidden>
                                        <input type="text" name="contenuto" value="<?php echo $contenuto;?>" hidden>
                                        <input type="text" name="action" value="updateNews" hidden>
                                    </form>
                                    <button id="edit" form="<?php echo "updateNews".$notizia; ?>" type="submit" onclick="window.open('../PHP/addNews.php', '_self');" class="pencilButton"><i class="material-icons">&#xe150;</i></button>

                                    <!-- Bottone elimina notizia-->
                                    <form id="<?php echo "deleteNews".$notizia; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                        <input type="text" name="action" value="delete" hidden>
                                        <input type="text" name="titolo" value="<?php echo $notizia?>" hidden>
                                    </form>
                                    <button form="<?php echo "deleteNews".$notizia; ?>" type="submit" class="deleteButton"><i class="material-icons">&#xe872;</i></button>
                                </td>
                            </tr>

                            <?php
                            
                            }
                        
                            pg_close($db);

                            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
    </html>

    <?php include 'footer.php'; ?>

    <?php 

        } else {
            include 'errorPage.php'; /* Pagina di erore */
        }

    ?>