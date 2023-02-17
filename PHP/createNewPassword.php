
<?php

    session_start(); /* Crea una sessione o riprende quella corrente */

    require_once "../connection.php";

    $_SESSION["reset"] = "false";

    /* Verifichiamo se l'utente ha premuto sul bottone "Reset password" per procedere con il recupero di questa */
    if(isset($_POST['resetPasswordSubmit'])){
        
        /* Prendiamo i dati arrivati dal form sottomesso dall'utente */
        $selector = $_POST['selector'];
        $validator = $_POST['validator'];
        $password = $_POST['pswd'];
        $passwordRepeat = $_POST['pswdConf'];


        if($password == $passwordRepeat){
            
            /* Otteniamo l'ora corrente in secondi in modo da confrontarlo con la che abbiamo inserito precedentemente all'interno del database per 
               verificare se il token è scaduto o meno  
            */
            $currentDate = date("U");
            
            /* A questo punto selezioniamo dall'interno della tabella pwdReset del database il token effettivo utilizzando il "selector" e non il "validator", come precedentemente esposto 
               nel file "resetPassword.php" */
            $query = "SELECT * FROM pwdReset WHERE pwdResetSelector = $1 AND pwdResetExpires >= $2;";
            $prep = pg_prepare($db, "resetPwd", $query);
            $result = pg_execute($db, "resetPwd", array($selector, $currentDate));

            if(!$result){
                echo pg_last_error($db);
                $_SESSION["reset"] = "false";
                exit;
            }

            $row = pg_fetch_assoc($result);
            
            /* Controllo che sia effettivamente presente la riga associata a quell'utente nella tabella pwdReset */
            if(!empty($row)){
                
                /* Dobbiamo ora confrontare il token presente all'interno del database con il token ricevuto dal modulo e dobbiamo assicurarci che questi token siano in formato binario. 
                   In particolare, il token ricevuto dal modulo non è in binario, ma nel suo formato esadecimale, quindi dobbiamo riconvertirlo in binario */
                $tokenBin = hex2bin($validator);
                $tokenCheck = password_verify($tokenBin, $row['pwdresettoken']);
                
                /* Se il controllo va a buon fine andiamo ad aggiornare la password dell'utente, dopo averne fatto l'hash */
                if($tokenCheck === true){

                    $tokenEmail = $row['pwdresetemail'];
                    $newPwdhash = password_hash($password, PASSWORD_DEFAULT);

                    $query = "SELECT * FROM utentebase WHERE email = $1;";
                    $prep = pg_prepare($db, "checkTypeUser", $query);
                    $result = pg_execute($db, "checkTypeUser", array($tokenEmail));

                    if(!$result){
                        echo pg_last_error($db);
                        $_SESSION["reset"] = "false";
                        exit;
                    }

                    if(!pg_fetch_assoc($result)){

                        /* email di un utente base */

                        $query = "UPDATE utenteeditor SET pswd = $1 WHERE email = $2;";
                        $prep = pg_prepare($db, "resetPwdUser", $query);

                    } else {

                        /* email di un utente editor */

                        $query = "UPDATE utentebase SET pswd = $1 WHERE email = $2;";
                        $prep = pg_prepare($db, "resetPwdUser", $query);

                    }

                    /* eseguo aggiornamento password */

                    $result = pg_execute($db, "resetPwdUser", array($newPwdhash, $tokenEmail));

                    if(!$result){
                        echo pg_last_error($db);
                        $_SESSION["reset"] = "false";
                        exit;
                    }

                    /* infine eliminiamo dalla tabella pwdReset la riga che contiene il token che abbiamo precedentemente creato per lo specifico utente che ha chieto
                       il reset della password */
                    $query = "DELETE FROM pwdReset WHERE pwdResetEmail = $1;";
                    $prep = pg_prepare($db, "resetTable", $query);
                    $result = pg_execute($db, "resetTable", array($tokenEmail));

                    if(!$result){
                        echo pg_last_error($db);
                        exit;
                    }
                    
                    $_SESSION["reset"] = "true";
                    
                    header("location: ./login.php"); /* redirect alla pagina di LogIn */
                    
                }
            }

            pg_close($db);
        }

    }

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../CSS/login.css"> <!-- collegamento del foglio di stile esterno -->
        <link rel="icon" href="logo1.png" type="image/png"/>
        <title> Tech Magazine | Reset Password </title>
    </head>
<body>

    <?php include "../PHP/header.php"; ?>

    <div class="signUpBody">
        <div class="signUpContainer">
            
            <?php
                /* Controlliamo se i tokens sono all'interno dell'URL */
                if(empty($_GET)){

                    echo "Could not validate your request!";

                } else {

                    $selector = $_GET['selector'];
                    $validator = $_GET['validator'];

                    /* Dobbiamo ora verificare che questi token siano effettivamente in formato esadecimale, e possiamo farlo attraverso la funzione ctype_xdigits */
                    if(ctype_xdigit($selector) !== false && ctype_xdigit($validator) !== false){
                        ?>
                            <!-- definizione del form per il recupero della password -->
                            <form id = "createNewPwdForm" action="<?php echo $_SERVER['PHP_SELF']?>" method="post" class="signUpForm">
                                <h1>Nuova password</h1>
                                <h6>Scegli una nuova password</h6>

                                <input type="text" name="selector" value="<?php echo $selector ?>" hidden>
                                <input type="text" name="validator" value="<?php echo $validator ?>" hidden>
                                
                                <div class="form-field-SU">
                                    <label>Password</label>
                                    <input type="password" name="pswd" id="pswd" placeholder="Inserisci la nuova password..." required="required">
                                    <small></small>
                                </div>

                                <div class="form-field-SU">
                                    <label>Conferma Password</label>
                                    <input type="password" name="pswdConf" id="pswdConf" placeholder="Conferma la nuova password..." required="required">
                                    <small></small>
                                </div>

                                <div class="form-field-SU">
                                    <button type="submit" name="resetPasswordSubmit" class="btnSU">Reset password</button>
                                    <small></small>
                                </div>
                            </form>

                        <?php
                    }
                }

            ?>

        </div> 
        
        <!-- Inclusione script di validazione del form-->
        <script src="../JS/createNewPassword.js"></script>

    </div> 
    
</body>
</html>

<?php include 'footer.php'; ?>