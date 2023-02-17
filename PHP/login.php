<?php

    require_once "../connection.php";

    session_start(); /* Crea una sessione o riprende quella corrente */ 

    if(!empty($_POST["email"]) && !empty($_POST["pswd"])){

        /* Mi assicuro che la mail/username sia costituita da tutti caratteri minuscoli */
        $email = strtolower($_POST["email"]);

        /* Verifico se la mail inserita è di un utente base */
        $check_email_query = "SELECT * from utentebase WHERE email = $1;";
        $ret = pg_prepare($db, "SelectEmailFromUtenteBase", $check_email_query);
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

        /* Verifico se la mail inserita è di un utente editor */
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
        
        /* Verifico se la mail inserita è di un amministratore */
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

        /* Entro se la mail inserita dall'utente non è presente nel database, procedendo con il controllo dell'username (poiché è possibile l'accesso anche con quest'ultimo)  */
        if($rowBase == NULL and $rowEditor == NULL and $rowAdmin==NULL){

            $username = $_POST["email"];
            
            /* Verifico se la username inserita è di un utente base */
            $check_username_query = "SELECT * from utentebase WHERE username = $1;";
            $ret = pg_prepare($db, "SelectUsernameFromUtenteBase", $check_username_query);
            if(!$ret) {
                echo pg_last_error($db);
                exit;
            }
            $ret = pg_execute($db, "SelectUsernameFromUtenteBase", array($username));
            if(!$ret){
                echo pg_last_error($db);
                exit;
            }
            $rowBase = pg_fetch_assoc($ret);

            /* Verifico se la username inserita è di un utente editor */
            $check_username_query = "SELECT * from utenteeditor WHERE username = $1;";
            $ret = pg_prepare($db, "SelectUsernameFromUtenteEditor", $check_username_query);
            if(!$ret) {
                echo pg_last_error($db);
                exit;
            }
            $ret = pg_execute($db, "SelectUsernameFromUtenteEditor", array($username));
            if(!$ret){
                echo pg_last_error($db);
                exit;
            }
            $rowEditor = pg_fetch_assoc($ret);

            /* Verifico se la username inserita è di un amministratore */
            $check_username_query = "SELECT * from amministratore WHERE username = $1;";
            $ret = pg_prepare($db, "SelectUsernameAdmin", $check_username_query);
            if(!$ret) {
                echo pg_last_error($db);
                exit;
            }
            $ret = pg_execute($db, "SelectUsernameAdmin", array($username));
            if(!$ret){
                echo pg_last_error($db);
                exit;
            }
            $rowAdmin = pg_fetch_assoc($ret);

            /* Entro se anche la username inserita dall'utente non è presente nel database generando un alert */
            if($rowBase == NULL and $rowEditor == NULL and $rowAdmin == NULL){
                ?>
                <script type="text/javascript">
                    alert("Le credenziali inserite non sono corrette. Riprova!");
                </script>
                <?php
            }
        }
        
        /* Entro se l'utente esiste nel database ed è un utente base */
        if($rowBase != NULL){

            /* validazione password */
            $passwordInserita = $_POST["pswd"];
            $passwordUtenteBase = $rowBase["pswd"];
            
            /* Entro se le password corrispondono */
            if(password_verify($passwordInserita, $passwordUtenteBase)){
                $_SESSION['email'] = $rowBase["email"];
                $_SESSION['nome'] = $rowBase["nome"];
                $_SESSION['cognome'] = $rowBase["cognome"];
                $_SESSION['username'] = $username;
                $_SESSION['usrType'] = "base"; 
                $_SESSION['success'] = "logged";
                $_SESSION['check'] = "true";

                /* Ad autenticazione completata, viene settato all'interno della variabile di sessione $_SESSION['start] l'istante di tempo
                in cui l'utente effettua accesso al sito, al fine di poter controllare la scadenza della sessione successivamente.*/
                if(!isset($_SESSION['start'])){
                    /* Setta l'istante di tempo di inizio della sessione */
                    $_SESSION['start'] = time();
                } 

                header('location: ../index.php'); /* redirect alla Home Page */
            } else {
                /* le password non corrispondono */
                ?>
                <script type="text/javascript">
                    alert("Le credenziali inserite non sono corrette. Riprova!");
                </script>
                <?php
            }
        
        /* Entro se l'utente esiste nel database ed è un utente editor */
        } else if($rowEditor != NULL){

            /* validazione password */
            $passwordInserita = $_POST["pswd"];
            $passwordUtenteEditor = $rowEditor["pswd"];

            /* Entro se le password corrispondono */
            if(password_verify($passwordInserita, $passwordUtenteEditor)){
                $_SESSION['email'] = $rowEditor["email"];
                $_SESSION['nome'] = $rowEditor["nome"];
                $_SESSION['cognome'] = $rowEditor["cognome"];
                $_SESSION['username'] = $username;
                $_SESSION['usrType'] = "editor"; 
                $_SESSION['success'] = "logged";
                $_SESSION['check'] = "true";

                /* Ad autenticazione completata, viene settato all'interno della variabile di sessione $_SESSION['start] l'istante di tempo
                in cui l'utente effettua accesso al sito, al fine di poter controllare la scadenza della sessione successivamente.*/
                if(!isset($_SESSION['start'])){
                    /* Setta l'istante di tempo di inizio della sessione */
                    $_SESSION['start'] = time();
                } 
                header('location: ../index.php');
            } else {
                /* le password non corrispondono */
                ?>
                <script type="text/javascript">
                    alert("Le credenziali inserite non sono corrette. Riprova!");
                </script>
                <?php
            }
        
        /* Entro se l'utente esiste nel database ed è un amministratore */
        } else if($rowAdmin != NULL){

            /* validazione password */
            $passwordInserita = $_POST["pswd"];
            $passwordAdmin = $rowAdmin["pswd"];
            
            /* Entro se le password corrispondono */
            if($passwordInserita == $passwordAdmin){
                $_SESSION['email'] = $rowAdmin["email"];
                $_SESSION['nome'] = $rowAdmin["nome"];
                $_SESSION['cognome'] = $rowAdmin["cognome"];
                $_SESSION['username'] = $username;
                $_SESSION['usrType'] = "admin"; 
                $_SESSION['success'] = "logged";

                /* Ad autenticazione completata, viene settato all'interno della variabile di sessione $_SESSION['start] l'istante di tempo
                in cui l'utente effettua accesso al sito, al fine di poter controllare la scadenza della sessione successivamente.*/
                if(!isset($_SESSION['start'])){
                    /* Setta l'istante di tempo di inizio della sessione */
                    $_SESSION['start'] = time();
                } 
                
                header('location: ../index.php');
            } else {
                /* le password non corrispondono */
                ?>
                <script type="text/javascript">
                    alert("Le credenziali inserite non sono corrette. Riprova!");
                </script>
                <?php
            }

        }
        
    }

?>

<html>
    <head>
        <title> Tech Magazine | Login </title>
        <link rel="stylesheet" href="../CSS/style.css"> <!-- collegamento del foglio di stile esterno -->
        <link rel="icon" href="logo1.png" type="image/png"/>
    </head>

<body>

    <?php include "../PHP/header.php"; ?>

    <div class="signUpBody">
        <div class="signUpContainer">

            <!-- definizione del form per l'inserimento dei dati di accesso -->
            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" id="login" method="post" enctype="multipart/form-data" class="signUpForm">

                <h1>
                    LogIn
                </h1>

                <h6>
                    Effettua l'accesso.
                </h6>
                
                <!-- email o username -->
                <div class="form-field-SU">
                    <label>E-mail o Username</label>
                    <input type="text" name="email" id="email" required>
                </div>

                <!-- password -->
                <div class="form-field-SU">
                    <label>Password</label>
                    <input type="password" name="pswd" id="pswd" required>
                </div>

                <h6>Non hai un account? 
                    <a href="signup.php">Registrati</a> qui!
                </h6>
                
                <h6>
                    <a href="resetPassword.php">Password dimenticata?</a>
                </h6>

                <!-- Bottone invio dati per LogIn -->
                <div class="form-field-SU">
                    <input type="submit" value="LogIn" class="btnSU">
                </div>

                <?php 
                    /* Entro se l'utente ha richiesto l'invio della email per il recupero della password */
                    if(isset($_SESSION['sent']) && $_SESSION['sent'] == "true"){

                        echo "<h5>Ti abbiamo inviato le istruzioni, controlla la tua mail!</h5>";
                        unset($_SESSION['sent']);
                    
                    /* Entro se la password è stata correttamente modificata */
                    }else if(isset($_SESSION['reset']) && $_SESSION['reset']=="true"){

                        echo "<h5>Password resettata con successo, ora puoi effettuare l'accesso.</h5>";
                        unset($_SESSION['reset']);

                    }else {

                        echo "<h5></h5>";

                    }
                ?>

            </form>
        </div> 
    </div>
    
    <!-- Inclusione script di validazione del form-->
    <script src="../JS/login.js"></script>

</body>
</html>

<?php include 'footer.php'; ?>