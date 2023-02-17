<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    session_start(); /* Crea una sessione o riprende quella corrente */

    /* Entro solo se l'utente è loggato ed è un amministratore */
    if(!empty($_SESSION['usrType']) && $_SESSION['usrType'] == "admin"){

        require_once "../connection.php";
        require '../vendor/autoload.php';

        /* Prelevo le variabili di sessione di interesse:
            • $emailcom -> email dell'utente loggato
            • $usrType -> tipo di utente loggato (base o editor)
        */  
        $emailpub = $_SESSION['email'];
        $usrType = $_SESSION['usrType'];

        if(!empty($_POST)){

            /* Specifico un comportamento in base all'azione eseguita dall'utente */
            switch($_POST['action']){

                /* Inserisco la notizia approvata nell'omonima tabella; all'atto dell'inserimento, un trigger si occuperà di aggiornare lo stato della specifica notizia 
                    a "true" nella tabella "notizia". Di conseguenza, l'utente proprietario di quella notizia vedrà, nella sua pagina personale, il messaggio "Approvata" 
                    per quella specifica notizia. Inoltre, attraverso la classe PHPMailer (una classe che permette di inviare messaggi di posta elettronica sia come 
                    semplice testo che in formato HTML) viene inviata all'utente un'email di conferma approvazione della notizia da lui pubblicata, con un link a 
                    quest'ultima.
                */
                case "approva":
                    $notizia = $_POST['notizia'];
                    $userPubMail = $_POST['userPub'];

                    $query = "INSERT INTO notiziaapprovata VALUES($1, $2);";
                    $prep = pg_prepare($db, "approvedNews", $query);
                    $result = pg_execute($db, "approvedNews", array($notizia, date('Y-m-d H:i:s')));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }
                    
                    $mail = new PHPMailer(true);
                    $app = str_replace(" ","_", $notizia);
                    $app = 'http://localhost/tsw/Progetto/PHP/news.php?titolo='.$app;

                    try {
                        $mail->isSMTP();                        // Set mailer to use SMTP
                        $mail->Host       = 'smtp.tim.it;';    // Specify main SMTP server
                        $mail->SMTPAuth   = true;               // Enable SMTP authentication
                        $mail->Username   = 'techmagazine@tim.it';     // SMTP username
                        $mail->Password   = 'Admin.01tsw';         // SMTP password
                        // $mail->Username   = 'techmagazine01tsw@gmail.com';     // SMTP username
                        // $mail->Password   = 'Admin#01';         // SMTP password
                        $mail->SMTPSecure = 'tls';              // Enable TLS encryption, 'ssl' also accepted
                        $mail->Port       = 587;                // TCP port to connect to   

                        $mail->setFrom('techmagazine@tim.it', 'Tech Magazine');           // Set sender of the mail
                        $mail->addAddress("$userPubMail");           // Add a recipient

                        $mail->isHTML(true);                                  
                        $mail->Subject = 'News approval for Tech Magazine';
                        $mail->Body    = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8" />
                            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                            <title>Notizia approvata</title>
                            <style>
                            @import url("https://fonts.googleapis.com/css2?family=Raleway:ital,wght@1,200&display=swap");

                            * {
                                font-family: "Akshar", sans-serif;
                                margin: 0;
                                padding: 0;
                                border: 0;
                            }

                            body {
                                background-color: #d8dada;
                                font-size: 19px;
                                max-width: 800px;
                                margin: 0 auto;
                                padding: 3%;
                            }

                            img {
                                display: block;
                                margin-left: auto;
                                margin-right: auto;
                                max-width: 20%;
                            }

                            header {
                                width: 98%;
                                padding-top: 10px;
                            }
                            
                            header * {
                                text-align: center;
                            }

                            #logo {
                                max-width: 120px;
                                margin: 3% 0 3% 3%;
                                float: left;
                            }

                            #wrapper {
                                background-color: #f0f6fb;
                                border-radius: 10px;
                            }

                            #social {
                                float: right;
                                margin: 3% 2% 4% 3%;
                                list-style-type: none;
                            }

                            #social > li {
                                display: inline;
                            }

                            #social > li > a > img {
                                max-width: 35px;
                            }

                            h1, p {
                                margin: 3%;
                            }

                            .btn {
                                display: block;
                                margin: auto;
                                width: 240px;
                                text-align: center;
                                background-color: #303840;
                                color: #f6faff;
                                text-decoration: none;
                                font-weight: 800;
                                padding: 8px 12px;
                                border-radius: 8px;
                                letter-spacing: 1px;
                            }
                            
                            .btn > a {
                                color: white;
                            }
                            
                            .btn:hover {
                                background-color: violet;
                                cursor: pointer;
                                
                            }

                            hr {
                                height: 1px;
                                background-color: #303840;
                                clear: both;
                                width: 96%;
                                margin: auto;
                            }

                            #contact {
                                text-align: center;
                                padding-bottom: 3%;
                                line-height: 16px;
                                font-size: 12px;
                                color: #303840;
                            }
                            </style>
                        </head>
                        <body>
                            <div id="wrapper">
                            <header>
                            
                                <div>
                                <h1>Tech Magazine</h1>
                                </div>
                            </header>
                            <div id="banner">
                                <img
                                src="https://icon-library.com/images/completed-icon/completed-icon-6.jpg" alt="Notizia approvata"
                                />
                            </div>
                            <div class="one-col">
                                <h1>Pubblicazione completata!</h1>

                                <p>
                                Congratulazioni, ti comunichiamo che la redazione di Tech Magazine ha analizzato il tuo articolo e lo ha giudicato idoneo alla pubblicazione. <br /> Ora la tua notizia è pubblica sul sito web ufficiale al seguente link:
                                </p>

                                <a href="' . $app . '" class="btn">Accedi</a>
                                <p>
                                    ~ La redazione
                                </p>

                                <hr />

                                <footer>
                                <p id="contact">
                                    All Right reserved by &copy; Tech Magazine
                                </p>
                                </footer>
                            </div>
                            </div>
                        </body>
                        </html>
                        ';
                        $mail->send();
                        unset($mail);

                    } catch (Exception $e) {
                        unset($mail);
                    }
        
                    header("location: notizieDaApprovare.php"); /* redirect alla pagina personale dell'admin "Notizie da approvare" */
                    break;
                
                /* Effettuo un aggiornamento dello stato della specifica notizia, impostandolo a "false". Di conseguenza, l'utente proprietario di quella notizia vedrà, nella sua
                pagina personale, il messaggio "Non approvato" per quella specifica notizia. Inoltre, attraverso la classe PHPMailer (una classe che permette di inviare messaggi 
                di posta elettronica sia come semplice testo che in formato HTML) viene inviata all'utente un'email di non approvazione della notizia da lui pubblicata, con 
                un link al LogIn per essere eventualmente modificata.
                */
                case "disapprova":
                    $notizia = $_POST['notizia'];
                    $userPubMail = $_POST['userPub'];

                    $query = "UPDATE notizia SET stato=$1 WHERE titolo=$2;";
                    $prep = pg_prepare($db, "unApprovedNews", $query);
                    $result = pg_execute($db, "unApprovedNews", array('false', $notizia));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }

                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();                        // Set mailer to use SMTP
                        $mail->Host       = 'smtp.tim.it;';    // Specify main SMTP server
                        $mail->SMTPAuth   = true;               // Enable SMTP authentication
                        $mail->Username   = 'techmagazine@tim.it';     // SMTP username
                        $mail->Password   = 'Admin.01tsw';         // SMTP password
                        // $mail->Username   = 'techmagazine01tsw@gmail.com';     // SMTP username
                        // $mail->Password   = 'Admin#01';         // SMTP password
                        $mail->SMTPSecure = 'tls';              // Enable TLS encryption, 'ssl' also accepted
                        $mail->Port       = 587;                // TCP port to connect to   

                        $mail->setFrom('techmagazine@tim.it', 'Tech Magazine');           // Set sender of the mail
                        $mail->addAddress("$userPubMail");           // Add a recipient

                        $mail->isHTML(true);                                  
                        $mail->Subject = 'News disapproval for Tech Magazine';
                        $mail->Body    = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8" />
                            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                            <title>Notizia non approvata</title>
                            <style>
                            @import url("https://fonts.googleapis.com/css2?family=Raleway:ital,wght@1,200&display=swap");

                            * {
                                font-family: "Akshar", sans-serif;
                                margin: 0;
                                padding: 0;
                                border: 0;
                            }

                            body {
                                background-color: #d8dada;
                                font-size: 19px;
                                max-width: 800px;
                                margin: 0 auto;
                                padding: 3%;
                            }

                            img {
                                display: block;
                                margin-left: auto;
                                margin-right: auto;
                                max-width: 20%;
                            }

                            header {
                                width: 98%;
                                padding-top: 10px;
                            }
                            
                            header * {
                                text-align: center;
                            }

                            #logo {
                                max-width: 120px;
                                margin: 3% 0 3% 3%;
                                float: left;
                            }

                            #wrapper {
                                background-color: #f0f6fb;
                                border-radius: 10px;
                            }

                            #social {
                                float: right;
                                margin: 3% 2% 4% 3%;
                                list-style-type: none;
                            }

                            #social > li {
                                display: inline;
                            }

                            #social > li > a > img {
                                max-width: 35px;
                            }

                            h1, p {
                                margin: 3%;
                            }

                            .btn {
                                display: block;
                                margin: auto;
                                width: 240px;
                                text-align: center;
                                background-color: #303840;
                                color: #f6faff;
                                text-decoration: none;
                                font-weight: 800;
                                padding: 8px 12px;
                                border-radius: 8px;
                                letter-spacing: 1px;
                            }
                            
                            .btn > a {
                                color: white;
                            }
                            
                            .btn:hover {
                                background-color: violet;
                                cursor: pointer;
                                
                            }

                            hr {
                                height: 1px;
                                background-color: #303840;
                                clear: both;
                                width: 96%;
                                margin: auto;
                            }

                            #contact {
                                text-align: center;
                                padding-bottom: 3%;
                                line-height: 16px;
                                font-size: 12px;
                                color: #303840;
                            }
                            </style>
                        </head>
                        <body>
                            <div id="wrapper">
                            <header>
                            
                                <div>
                                <h1>Tech Magazine</h1>
                                </div>
                            </header>
                            <div id="banner">
                                <img
                                src="https://v.fastcdn.co/u/b4e8b365/61180120-0-x.png" alt="Notizia non approvata"
                                />
                            </div>
                            <div class="one-col">
                                <h1>Pubblicazione non completata!</h1>

                                <p>
                                Siamo spiacenti, ti comunichiamo che la redazione di Tech Magazine ha analizzato il tuo articolo e non lo ha giudicato idoneo alla pubblicazione. <br /> Puoi modificare la tua notizia accedendo all’area personale al seguente link:
                                </p>

                                <a href="http://localhost/gruppo01/PHP/login.php" class="btn">Accedi</a>
                                <p>
                                    ~ La redazione
                                </p>

                                <hr />

                                <footer>
                                <p id="contact">
                                    All Right reserved by &copy; Tech Magazine
                                </p>
                                </footer>
                            </div>
                            </div>
                        </body>
                        </html>
                        ';
                        $mail->send();
                        unset($mail);

                    } catch (Exception $e) {
                        unset($mail);
                    }

                    header("location: notizieDaApprovare.php"); /* redirect alla pagina personale dell'admin "Notizie da approvare" */
                    break;
            }
        }

        /* Prelevo dal database le notizie di tutti gli utenti che sono in attesa di approvazione ("stato" = NULL) */
        $query = "SELECT titolo, contenuto, datapubblicazione, emailpub, categoria FROM notizia WHERE stato IS NULL";
        $prep = pg_prepare($db, "takeNews", $query);
        $result = pg_execute($db, "takeNews", array());

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
                <title>Tech Magazine | Notizie da approvare</title>
            </head>
        <body>

                <?php include "../PHP/header.php"; ?>

                <div class="notizieUtenteBody">
                        <h1>  
                            <?php echo "Notizie da approvare"; ?>  
                        </h1>

                    <div class="div-notizieUtente">
                        <table class="tabellaUtente">
                            <thead>
                                <tr>
                                    <th>Notizia</th>
                                    <th>Contenuto</th>
                                    <th>Data pubblicazione</th>
                                    <th>Utente</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>

                                <!-- Popolamento della tabella a seguito della query precedente (vedi riga 246) -->
                                <?php
                                    while($row = pg_fetch_assoc($result)){
                                        $categoria = $row['categoria'];
                                        $notizia = $row['titolo'];
                                        $contenuto = $row['contenuto'];
                                        $datapubblicazione = substr($row['datapubblicazione'], 0, 19);
                                        $utente = $row['emailpub'];
                                ?>
                                <tr>
                                    <!-- Notizia -->
                                    <td>
                                        <div class="div-notizia">
                                            <?php echo $notizia ?>
                                        </div>
                                    </td>

                                    <!-- Bottone visualizza -->
                                    <td>
                                        <div class="div-notizia">
                                            <a href="<?php $app = str_replace(" ","_",$notizia); echo './news.php?titolo='.$app; ?>">Visualizza</a>
                                        </div>
                                    </td>

                                    <!-- Data pubblicazione notizia -->
                                    <td>
                                        <?php echo $datapubblicazione ?>
                                    </td>

                                    <!-- Email utente proprietario -->
                                    <td>
                                        <?php echo $utente ?></td>
                                    </td>

                                    <!-- Azioni -->
                                    <td class="actions">

                                        <!-- Bottone approva notizia -->
                                        <form id="<?php echo "approva".$notizia; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                            <input type="text" name="notizia" value="<?php echo $notizia?>" hidden>
                                            <input type="text" name="userPub" value="<?php echo $utente ?>" hidden>
                                            <input type="text" name="action" value="approva" hidden>
                                        </form>
                                        <button form="<?php echo "approva".$notizia; ?>" type="submit" class="seeCommentButton"><i class="material-icons">&#xe834;</i></button>

                                        <!-- Bottone disapprova notizia -->
                                        <form id="<?php echo "disapprova".$notizia; ?>" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                            <input type="text" name="userPub" value="<?php echo $utente ?>" hidden>
                                            <input type="text" name="action" value="disapprova" hidden>
                                            <input type="text" name="notizia" value="<?php echo $notizia?>" hidden>
                                        </form>
                                        <button form="<?php echo "disapprova".$notizia; ?>" type="submit" class="seeCommentButton"><i class="material-icons">&#xe909;</i></button>
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