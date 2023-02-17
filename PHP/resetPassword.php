<?php

    session_start(); /* Crea una sessione o riprende quella corrente */

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    require_once "../connection.php";

    /* Includiamo all'interno di questo file la classe PHPMailer attraverso un "composer", ovvero un gestore delle dipendenze dei progetti PHP */
    require '../vendor/autoload.php'; 

    $_SESSION["sent"] = "false";
    
    /* Verifichiamo se l'utente ha premuto sul bottone "Inviami le istruzioni" per procedere con il recupero della password */
    if(isset($_POST['resetRequestBtn'])){

        if(!empty($_POST["email"])){

            /* validazione email */
            $email = strtolower($_POST["email"]);
    
            /* check email in utente base */
            $query = "SELECT * from utentebase WHERE email = $1;";
            $ret = pg_prepare($db, "SelectEmailFromUtenteBase", $query);
            if(!$ret) {
                echo pg_last_error($db);
                exit;
            }
            $ret = pg_execute($db, "SelectEmailFromUtenteBase", array($email));
            if(!$ret){
                echo pg_last_error($db);
                exit;
            }
            $rowBase = pg_fetch_assoc($ret);
    
            /* check email in uetnete editor */
            $check_email_query = "SELECT * from utenteeditor WHERE email = $1;";
            $ret = pg_prepare($db, "SelectEmailFromUtenteEditor", $check_email_query);
            if(!$ret) {
                echo pg_last_error($db);
                exit;
            }
            $ret = pg_execute($db, "SelectEmailFromUtenteEditor", array($email));
            if(!$ret){
                echo pg_last_error($db);
                exit;
            }
            $rowEditor = pg_fetch_assoc($ret);
            
            /* check email in amministratore */
            $check_email_query = "SELECT * from amministratore WHERE email = $1;";
            $ret = pg_prepare($db, "SelectEmailFromAdmin", $check_email_query);
            if(!$ret) {
                echo pg_last_error($db);
                exit;
            }
            $ret = pg_execute($db, "SelectEmailFromAdmin", array($email));
            if(!$ret){
                echo pg_last_error($db);
                exit;
            }
            $rowAdmin = pg_fetch_assoc($ret);
    
            if($rowBase == NULL and $rowEditor == NULL and $rowAdmin==NULL){

                /* non ci sono utenti registrati con quella email */

                ?>
                    <script>
                        alert("La mail inserita non è valida. Riprova!");
                    </script>
                <?php

                $_SESSION["sent"] = "false";

            }else{

                /* c'è un utente registrato con quella mail */
                
                /* Dopo aver controllato che la mail di recupero inserita dall'utente è corretta, proseguiamo con la procedura di recupero passowrd andando a generare due token 
                   crittograficamente sicuri:
                    • $selector, che useremo per individuare all'interno del database il token con cui dobbiamo controllare l'utente quando torna al nostro sito Web attraverso l'url di recupero
                    • $token, che useremo per autenticare effettivamente l'utente
                   Utlizziamo due token e non uno solo, per evitare quelli che vengono chiamati "timing attacks", che rappresentano un modo con cui un hacker cerca di entrare con 
                   attacchi di "forza bruta" nel nostro sito web.
                   Per fare questo ci sono delle funzioni PHP che ci vengono in aiuto, come:
                    • random_bytes che genera dei bytes random che possiamo usare per creare il token 
                    • bin2hex che converte questi bytes in formato esadecimale e il risultato di tale conversione è viene poi inserito all'interno dell'url inviato all'utente tramite email
                */
                $selector = bin2hex(random_bytes(8));
                $token = random_bytes(32);

                $url = "http://localhost/gruppo01/PHP/createNewPassword.php?selector=" . $selector . "&validator=" . bin2hex($token);

                /* La prossima cosa che dobbiamo fare è creare una expiry date per il token, perché un token non dovrebbe essere qualcosa che è valido all'infinito. In particolare,
                   andiamo a scegliere un expire time di 30 min */
                $expires = date("U") + 1800;

                /* Memorizziamo in $email l'email che l'utente ha inserito all'interno del form */
                $email = $_POST['email'];

                /* Dobbiamo ora eliminare tutte le voci esistenti all'interno del database associate allo specifico utente, per assicurarci che non ci siano token esistenti dello stesso 
                utente all'interno del database */
                $query = "DELETE FROM pwdReset WHERE pwdResetEmail = $1;";
                $prep = pg_prepare($db, "checkExistingUser", $query);
                $result = pg_execute($db, "checkExistingUser", array($email));
                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }

                /* Procediamo quindi con l'inserimento del token all'interno del database, dopo averne fatto l'hash */
                $hashedToken = password_hash($token, PASSWORD_DEFAULT);

                $query = "INSERT INTO pwdReset(pwdResetEmail, pwdResetSelector, pwdResetToken, pwdResetExpires) VALUES($1, $2, $3, $4);";
                $prep = pg_prepare($db, "resetRequestPwd", $query);
                $result = pg_execute($db, "resetRequestPwd", array($email, $selector, $hashedToken, $expires));
                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }
                pg_close($db);
                
                /* Creiamo un oggetto PHPMailer e configuriamo i vari parametri per ultimare l'invio della email all'utente */
                $mail = new PHPMailer(true);

                try {
                    //$mail->SMTPDebug = 2;
                    $mail->isSMTP();                                                    /* Set mailer to use SMTP                       */                                                                                     
                    $mail->Host       = 'smtp.tim.it;';                                 /* Specify main SMTP server                     */
                    $mail->SMTPAuth   = true;                                           /* Enable SMTP authentication                   */
                    $mail->Username   = 'techmagazine@tim.it';                          /* SMTP username                                */                
                    $mail->Password   = 'Admin.01tsw';                                  /* SMTP password                                */
                    // $mail->Username   = 'techmagazine01tsw@gmail.com';               // SMTP username
                    // $mail->Password   = 'Admin#01';                                  // SMTP password
                    $mail->SMTPSecure = 'tls';                                          /* Enable TLS encryption, 'ssl' also accepted   */
                    $mail->Port       = 587;                                            /* TCP port to connect to                       */   
                    $mail->setFrom('techmagazine@tim.it', 'Tech Magazine');             /* Set sender of the mail                       */
                    $mail->addAddress("$email");                                        /* Add a receiver                               */

                    $mail->isHTML(true);                                  
                    $mail->Subject = 'Reset your password for Tech Magazine';
                    $mail->Body    = '
                    
                    <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8" />
                            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                            <title>Document</title>
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
                                max-width: 100%;
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

                            h1,
                            p {
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
                                src="https://passwordrecovery.it/wp-content/uploads/2020/02/password-recovery2.jpg"
                                alt=""
                                />
                            </div>
                            <div class="one-col">
                                <h1>Recovery your password!</h1>

                                <p>
                                Abbiamo ricevuto una richiesta di recupero della password associata a questo indirizzo e-mail.
                        Per reimpostare la tua password clicca sul link:
                                </p>

                                <a href="' . $url . '" class="btn">Recupera password!</a>
                                
                                <p>
                                    Se cliccando il link non funziona, puoi copiarlo e incollarlo nella barra degli indirizzi del tuo browser.
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

                    $mail->send(); /* sends the email */
                    unset($email);

                    $_SESSION["sent"] = "true";
                    header("location: login.php"); /* redirect alla pagina di LogIn */
                } catch (Exception $e) {
                    unset($email);
                    $_SESSION["sent"] = "false";
                }
            } 
        }
    }

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../CSS/style.css"> <!-- collegamento del foglio di stile esterno -->
        <link rel="icon" href="logo1.png" type="image/png"/>
        <title> Tech Magazine | Reset Password </title>
    </head>
<body>

    <?php include "../PHP/header.php"; ?>

    <div class="signUpBody">
        <div class="signUpContainer">

            <!-- definizione del form per l'inserimento dell'email -->
            <form action="<?php echo $_SERVER['PHP_SELF']?>"  method="post" class="signUpForm" id="resetPasswordForm">

                <h1>
                    Reset password
                </h1>

                <h6>
                    Ti sarà inviata una e-mail con le istruzioni necessarie al reset della tua password.
                </h6>

                <div class="form-field-SU">
                    <label>E-mail</label>
                    <input type="email" name="email" id="email" required>
                    <small></small>
                </div>

                <div class="form-field-SU">
                    <input type="submit" name="resetRequestBtn" value="Inviami le istruzioni" class="btnSU" id="btnReset">
                </div>

            </form>
        </div> 

        <!-- Inclusione script di validazione del form-->
        <script src="../JS/resetPassword.js"></script>

    </div>  
</body>
</html>

<?php include 'footer.php'; ?>