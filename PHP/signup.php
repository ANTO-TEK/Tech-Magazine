<?php

    session_start(); /* Crea una sessione o riprende quella corrente */ 

    require_once "../connection.php";

    /* SEZIONE DI VALIDAZIONE DEL FORM DI REGISTRAZIONE */
    /* Se il campo nome , cognome,  username, email, password , password confermata e tipologia di utente sono compilati, si procede allora con la registrazione */
    if(!empty($_POST["nome"]) && !empty($_POST["cognome"] && !empty($_POST["username"])) && !empty($_POST["email"]) && !empty($_POST["pswd"]) && !empty($_POST["pswdConf"]) && !empty($_POST["usrType"])){

        // Validazione email
        $email = strtolower($_POST["email"]);

        /*Affinchè la mail possa essere validata, attraverso 2 query viene effettuato un controllo in merito alla 
        presenza di quest'ultima in una delle due tabelle associate agli utenti.
        */
        $check_email_query = "SELECT email from utentebase WHERE email = $1;";
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
        $rowBase = pg_fetch_row($ret);

        $check_email_query = "SELECT email from utenteeditor WHERE email = $1;";
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
        $rowEditor = pg_fetch_row($ret);

        /* Se la query non ha restituito alcun risultato da entrambe le tabelle, allora il tutto implica che la mail inserita
        per la registrazione è valida, e dunque il flag $validEmail viene impostato a true, false altrimenti */
        if ($rowBase == NULL and $rowEditor == NULL){
            $validEmail = true;
        }else{
            $validEmail = false;
        }

        /* Dopodichè si procede con la validazione dello username, attraverso 2 query effettuate su entrambe le tabelle degli
        utenti al fine di poter verificare la presenza o meno dello username all'interno del database. */
        $username = $_POST["username"];

        $check_user_query = "SELECT username from utentebase WHERE username = $1;";
        $ret = pg_prepare($db, "SelectUsernameFromUtenteBase", $check_user_query);
        if(!$ret) {
            echo pg_last_error($db);
            exit;
        }
        $ret = pg_execute($db, "SelectUsernameFromUtenteBase", array($username));
        if(!$ret){
            echo pg_last_error($db);
            exit;
        }
        $rowBase = pg_fetch_row($ret);
        
        $check_user_query = "SELECT username from utenteeditor WHERE username = $1;";
        $ret = pg_prepare($db, "SelectUsernameFromUtenteEditor", $check_user_query);
        if(!$ret) {
            echo pg_last_error($db);
            exit;
        }
        $ret = pg_execute($db, "SelectUsernameFromUtenteEditor", array($username));
        if(!$ret){
            echo pg_last_error($db);
            exit;
        }
        $rowEditor = pg_fetch_row($ret);

        /* Se la query non ha restituito alcun risultato da entrambe le tabelle, allora il tutto implica che lo username inserito
        per la registrazione è valida, e dunque il flag $validUsername viene impostato a true, false altrimenti */
        if ($rowBase == NULL and $rowEditor == NULL){
            $validUsername = true;
        }else{
            $validUsername = false;
        }

        /* Si procede con la validazione della password, verificando che quella inserita e quella confermata corrispondano. Solo in
        questo caso, si procede alla creazione dell'hash della password e il flag $validPassword viene impostato a true, false altrimenti. */
        if($_POST["pswd"] == $_POST["pswdConf"]){
            $validPassword = true;
            $pswd = password_hash($_POST["pswd"], PASSWORD_DEFAULT);
        }else{
            $validPassword = false;
        }

      
        /* Dopo aver eseguito le validazioni, se non c'è alcun utente esistente con quell'email e quell'username e le password corrispondono, allora si procede
        all'inserimento di quest'ultimo nel database, selezionando la tabella specifica tramite la variabile di sessione usrType settata in fase di scelta dall'utente.
        Si procede dunque con la creazione della foto profilo dell'utente, che viene impostata a default nel caso in cui l'utente non ne inserisca una. Dopodichè si procede
        con l'inserimento e, se la query va a buon fine, rispettivamente le variabili di sessione email,username, nome, cognome, tipo di utente, success per il login e true
        flag utilizzato successivamente per la generazione della foto profilo vengono settati, definendo di conseguenza poi prima di passare alla home page tramite header anche
        l'inizio della sessione. Nel caso in cui uno dei campi non sia valido, viene mostrato a video un'alert.*/
        if ($validEmail and $validUsername and $validPassword){
            $nome = $_POST["nome"];
            $cognome = $_POST["cognome"];

            if($_FILES["fotoProfilo"]["name"]==""){
                $fotoProfilo = "../userDefault.png";
            }else{
                $fotoProfilo = $_FILES["fotoProfilo"]["tmp_name"];
            }
    
            $fh = fopen($fotoProfilo, 'rb');
            $fbytes = fread($fh, filesize($fotoProfilo));
            

            if($_POST["usrType"]=="base"){
    
                $insert_query = "INSERT INTO utentebase (email, nome, cognome, username, pswd, imgprofile) VALUES ($1, $2, $3, $4, $5, $6);";
                $ret = pg_prepare($db,"InsertUtenteBase", $insert_query);

                if(!$ret) {
                    echo pg_last_error($db);
                    exit;
                }

                $ret = pg_execute($db, "InsertUtenteBase", array($email, $nome, $cognome, $username, $pswd, base64_encode($fbytes)));

                if(!$ret){
                    echo pg_last_error($db);
                    exit;
                } else {
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $_SESSION['nome'] = $_POST["nome"];
                    $_SESSION['cognome'] = $_POST["cognome"];
                    $_SESSION['usrType'] = "base"; 
                    $_SESSION['success'] = "logged";
                    $_SESSION['check'] = "true";

                     /* A registrazione completata, viene settato all'interno della variabile di sessione $_SESSION['start] l'istante di tempo
                    in cui l'utente effettua accesso al sito, al fine di poter controllare la scadenza della sessione successivamente.*/
                    if(!isset($_SESSION['start'])){
                        //Setta l'istante di tempo di inizio della sessione
                        $_SESSION['start'] = time();
                    } 

                    header('location: ../index.php');
                }

            } elseif ($_POST["usrType"]=="editor"){
    
                $insert_query = "INSERT INTO utenteeditor (email, nome, cognome, username, pswd, imgprofile) VALUES ($1, $2, $3, $4, $5, $6);";
                $ret = pg_prepare($db,"InsertUtenteEditor", $insert_query);

                if(!$ret) {
                    echo pg_last_error($db);
                    exit;
                }

                $ret = pg_execute($db, "InsertUtenteEditor", array($email, $nome, $cognome, $username, $pswd, base64_encode($fbytes)));

                if(!$ret){
                    echo pg_last_error($db);
                    exit;
                } else {
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $_SESSION['nome'] = $_POST["nome"];
                    $_SESSION['cognome'] = $_POST["cognome"];
                    $_SESSION['usrType'] = "editor"; 
                    $_SESSION['success'] = "logged";
                    $_SESSION['check'] = "true";

                     /* A registrazione completata, viene settato all'interno della variabile di sessione $_SESSION['start] l'istante di tempo
                    in cui l'utente effettua accesso al sito, al fine di poter controllare la scadenza della sessione successivamente.*/
                    if(!isset($_SESSION['start'])){
                        //Setta l'istante di tempo di inizio della sessione
                        $_SESSION['start'] = time();
                    } 
                    
                    header('location: ../index.php');
                }

            } 
            
        }elseif(!$validEmail and !$validUsername){
            /* Nella situazione in cui i campi email e username non siano validi, viene mostrato a video un'alert indicando all'utente l'esistenza di utente con quelle credenziali */
            ?>
            <script type="text/javascript">
                alert("L'utente inserito è già presente! Riprova cambiando username e email.")
            </script>
            <?php
        }elseif(!$validEmail){
            /* Nella situazione in cui il campo email non è valido, viene mostrato a video un'alert indicando all'utente l'esistenza di utente con quella mail */
            ?>
            <script type="text/javascript">
                alert("Questa mail è già stata utilizzata! Riprova.")
            </script>
            <?php
        }elseif(!$validUsername){
            /* Nella situazione in cui il campo username non è valido, viene mostrato a video un'alert indicando all'utente l'esistenza di utente con quell'username */
            ?>
            <script type="text/javascript">
                alert("Questo username è già stato utlizzato! Riprova.")
            </script>
            <?php
        }
    }
?>

<html>
    <head>

        <title> Tech Magazine | Sign up </title>
        <link rel="stylesheet" href="../CSS/style.css">
        <link rel="icon" href="logo1.png" type="image/png"/>

    </head>
    <body>

        <!-- inclusione dell'header -->
        <?php
            include "../PHP/header.php";
        ?>

        <!-- Body del sign up -->
        <div class="signUpBody">

            <div class="signUpContainer">

                <!-- Form di sottomissione della registrazione, avente come action la pagina stessa al fine della validazione pocanzi descritta -->
                <form action="<?php echo $_SERVER['PHP_SELF'] ?>" id="signup" method="post" enctype="multipart/form-data" class="signUpForm">
                    <h1>SignUp</h1>
                    <h6>Crea il tuo account</h6>

                    <!-- Photo selector -->
                    <div class="form-field-SU">
                        <label>Foto profilo</label>
                        <input id="img" type="file" name="fotoProfilo" placeholder="Foto" accept="image/png, image/jpeg">
                        <small></small>
                    </div>

                    <!-- Campo nome -->
                    <div class="form-field-SU">
                        <label>Nome</label>
                        <input type="text" name="nome" id="nome" required="required">
                        <small></small>
                    </div>

                    <!-- Campo cognome -->
                    <div class="form-field-SU">
                        <label>Cognome</label>
                        <input type="text" name="cognome" id="cognome" required="required">
                        <small></small>
                    </div>

                    <!-- Campo username -->
                    <div class="form-field-SU">
                        <label>Username</label>
                        <input type="text" name="username" id="username" required="required">
                        <small></small>
                    </div>

                    <!-- Campo email -->
                    <div class="form-field-SU">
                        <label>E-mail</label>
                        <input type="email" name="email" id="email" required="required">
                        <small></small>
                    </div>

                    <!-- Campo password -->
                    <div class="form-field-SU">
                        <label>Password</label>
                        <input type="password" name="pswd" id="pswd" required="required">
                        <small></small>
                    </div>

                    <!-- Campo conferma password -->
                    <div class="form-field-SU">
                        <label>Conferma Password</label>
                        <input type="password" name="pswdConf" id="pswdConf" required="required">
                        <small></small>
                    </div>

                    <div class="form-field-SU">
                        
                        <!-- Scelta tipologia di utente -->
                        <label class="userTypeLabel">Tipologia di utente:</label><br>
                            <div class="radioContainer">
                                <div class="radio1">
                                <input type="radio" name="usrType" id="usrTypeBase" value="base" required="required" class="radioButton">
                                    <label class="radioLabel">
                                        base
                                    </label> 
                                </div>

                                <div div class="radio2">
                                    <input type="radio" name="usrType" id="usrTypeEditor" value="editor" required="required" class="radioButton">
                                        
                                    <label class="radioLabel">
                                        editor
                                    </label>
                                </div>
                            </div>
                        <small></small>

                    </div>

                    <!-- Ancora verso il login -->
                    <h6 class="textBottom">Hai gi&agrave; un account? <a href="login.php">Accedi</a> qui!</h6>

                    <div class="form-field-SU">
                        <input type="submit" value="SignUp" class="btnSU">
                        <small></small>
                    </div>

                </form>

            </div>

            <!-- Script di validazione del form di signup -->
            <script src="../JS/signup.js"></script>

        </div>
    </body>
</html>

<!--  Inclusione del footer -->
<?php include 'footer.php'; ?>