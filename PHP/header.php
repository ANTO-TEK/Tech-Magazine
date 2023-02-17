<?php

        /* GESTIONE DURATA DELLA SESSIONE */
        /*Essendo che l'header è incluso in tutte le pagine di interesse, il controllo sulla sessione scaduta viene qui effettuato. In particolar modo, dal momento in cui l'utente effettua il login
        o si registra al sito, tramite la funzione time() viene definito nella variabile di sessione l'istante in cui l'utente accede al sito web. Una sessione , fissato il numero di secondi per la quale
        deve essere mantenuta, si ritiene da distruggere se e solo la differenza fra l'istante in cui viene ad essere valutata la condizione (ovvero ad ogni refresh di qualsiasi pagina, dal
        momento in cui l'header è sempre incluso), tramite la funzione time(), e l'istante in cui l'utente ha effettuato l'accesso al sito (memorizzato in $_SESSION['start']) è maggiore del numero di secondi
        definito. In questa situazione , si procede con l'unset delle variabili di sessione, si distrugge la sessione e tramite header, a seconda della  pagina all'interno della quale l'utente si trova 
        nel momento in cui si distrugge la sessione, si ritorna alla homepage. Questo perchè, visto che alcune funzionalità richiedono l'utilizzo di variabili di sessione, per poter continuare ad utilizzarle è
        necessario effettuare nuovamente il login. */

        if (isset($_SESSION['start']) && (time() - $_SESSION['start'] > 1800)) { //Sessione impostata a 30 minuti
           
            //libera le variabili di sessione
            session_unset(); 
            //distrugge la sessione
            session_destroy(); 

            //reinderizzamento alla homepage in caso di sessione scaduta
            if(basename($_SERVER['PHP_SELF']) == 'index.php'){
                header('location: index.php');
            }
            else{
                header('location: ../index.php');
            } 
        }


        /* GESTIONE ANCORE NELL'HEADER */
        /*
        Gestione delle ancore nell'header, al fine di poter definire il path correto. Difatti,a seconda che la pagina in cui l'header è incluso sia l'home page o una delle restanti
        situate nella cartella PHP, è necessario specificare la cartella in cui sono contenuti gli elementi al quale l'ancora è indirizzata. Tutto questo avviene solo nel caso, dal momento in cui
        situata ad un livello di visibilità più alto, gli elementi dell'header vengano ad essere utilizzati in index.pho Al contrario, nel caso in cui la pagina sia diversa da index.php, e dunque 
        una delle restanti contenuta nella cartella php,il foglio di stile oppure il livello di visibilità della cartella images,  allora  necessario prima tornare ad un livello di visibilità 
        superiore, e poi accedere alla cartella specifica per prelevare l'elemento in questione.
        */

        if(basename($_SERVER['PHP_SELF']) == 'index.php'){
            $path = 'CSS/style.css';
            $path2 = 'PHP/categorie.php';
            $path3 = 'PHP/logout.php';
            $path4 = 'PHP/login.php';
            $path5 = 'PHP/commentiUtente.php';
            $path6 = 'PHP/search.php';
            $path7 = 'images/';
        }
        else{
            $path = '../CSS/style.css';
            $path2 = 'categorie.php';
            $path3 = 'logout.php';
            $path4 = 'login.php';
            $path5 = 'commentiUtente.php';
            $path6 = 'search.php';
            $path7 = '../images/';
        }

 

        /* DEFINIZIONE DELLA FOTO PROFILO DELL'UTENTE NELL'HEADER, CREATA UNA SOLA VOLTA 
        OGNI VOLTA CHE L'UTENTE SI LOGGA SOVRASCRIVENDO LA PRECEDENTE,  AL FINE DI ASSICURARE CHE L'IMMAGINE POSSA ESSERE SEMPRE VISUALIZZATA
        ANCHE A MENO DI ERRORI O CANCELLAZIONI PRECEDENTI SULLA CARTELLA IMAGES, DIPENDENDO DUNQUE UNICAMENTE DAL DATABASE, DOVE LE INFORMAZIONI SONO PERSISTENTI*/

        /* Se l'utente è loggato e appartiene alla tipologia di utente editor o admin, tramite il flag check ci assicuriamo di eseguire questa operazione solo al login. In particolar modo, tramite
        la variabile di sessione usrType , se l'utente è base l'immagine da convertire viene selezionata dalla tabella utentebase dove l'email dell'utente loggato salvata nella variabile di sessione
        $_SESSION['email'] corrisponde ad una delle occorrenze della tabella; nel caso in cui l'utente non fosse base, la stessa operazione viene invece effettuata sulla tabella utente editor. Alla fine 
        di entrambe le operazioni, il flag di sessione 'check' viene impostato  a false, garantendo l'esecuzione dell'operazione solo al login.  */

        /* Convenzioni adottate per il salvataggio delle immagini di profilo di ogni utente: salvare l'immagine come l'email dell'utente, scegliendo i caratteri fino al simbolo '@'*/
        if(!empty($_SESSION['success']) && $_SESSION['success'] == 'logged' && $_SESSION['usrType'] != 'admin' && $_SESSION['check'] == 'true'){
        
            $mail = explode("@",$_SESSION['email']);

            if($_SESSION['usrType'] == 'base'){

                $query = "SELECT imgprofile FROM utentebase where email = $1";
                $prep = pg_prepare($db, "takePhotoUser", $query);
                $result = pg_execute($db, "takePhotoUser", array($_SESSION['email']));

                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }

                $img = convertBinaryToImage(pg_fetch_row($result)[0],$mail[0]);


                $_SESSION['check'] = "false";
            }else{

                $query = "SELECT imgprofile FROM utenteeditor where email = $1";
                $prep = pg_prepare($db, "takePhotoUser", $query);
                $result = pg_execute($db, "takePhotoUser", array($_SESSION['email']));

                if(!$result){
                    echo pg_last_error($db);
                    exit;
                }

                $img = convertBinaryToImage(pg_fetch_row($result)[0],$mail[0]);

                $_SESSION['check'] = "false";
            }
        }

        /* Se l'utente loggato appartiene alla categoria admin, dal momento in cui ad esso è assegnata una immagine di default che lo contraddistingue e non memorizzata nel database, a seconda 
        della pagina in cui l'header è localizzato, nella variabile $imgadmin viene caricato il path per permettere di poter caricare correttamente la foto profilo.
        */

        if(!empty($_SESSION['success']) && $_SESSION['success'] == 'logged' && $_SESSION['usrType'] == 'admin'){
            if(basename($_SERVER['PHP_SELF']) == 'index.php'){
                $imgadmin = 'boss.png';
            }
            else{
                $imgadmin = '../boss.png';
            }

        }

?>



<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" href= <?php echo $path ?>>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> <!-- per simboli -->
    <link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&display=swap" rel="stylesheet">
    <title>Document</title>
</head>
<body>
    <nav>
        <ul>

            <!-- Logo del sito web, definito rispettivamente come un ancora, mediante la quale si viene reindirizzati alla home page a prescindere dalla pagina in cui vi ci si trova -->
            <li class="logo">  <a href= 
                    <?php  
                        if(basename($_SERVER['PHP_SELF']) == 'index.php'){
                            echo 'index.php';
                        }
                        else{
                            echo '../index.php';
                        }
                    
                    ?> 
            > Tech Magazine </a></li>
            

            <?php 
                /* CONTENUTO DA VISUALIZZARE SOLO PER GLI UTENTI LOGGATI*/
                /* Nel caso in cui l 'utente effettui il login o il signup, identificando tale azione tramite la variabile di sessione $_SESSION['success'], viene mostrato
                nell'header la sua immagine profilo e il suo nome utente, mediante il quale può accedere all'area personale. Nel caso in cui l'utente non sia loggato, è presente un ancora
                 che rimanda al login, nel caso in cui l'utente volesse loggarsi.*/
                if(!empty($_SESSION['success'])){

                    ?>
                     <div class="items">
                            <img src= 
                                    <?php 

                                        /* A seguito della creazione, se l'utente loggato è diverso da admin, allora l'immagine visualizzata ad esso associata sarà caricata
                                        al path specifico col nome designato dalla convenzione adottata come nelle restanti pagine per la memorizzazione delle immagini di profilo*/
                                        if($_SESSION['usrType'] != 'admin'){
                                        $mail = explode("@", $_SESSION['email']); 
                                        echo $path7.$mail[0].'.jpg';
                                        }
                                        else{
                                            echo $imgadmin;
                                        } 
                                    ?> 
                            class="imgProfile">
                    </div>

                    <div class="items">
                        <li><a href= <?php echo $path5; ?>
                        >Benvenuto, <?php echo $_SESSION['nome'].' '.$_SESSION['cognome']; ?> </a></li>
                    </div>


                    <div class="items">
                        <li><a href= <?php echo $path3; ?> > Logout </a></li>
                    </div>
                    <?php
                }
                else{
                    ?>
                    <div class="items">
                        <li><a href=<?php echo $path4; ?>>LogIn </a></li>
                    </div>
                    <?php
                }

            
            ?>
        
        <!-- Form associato alla search bar, mediante il quale tramite metodo POST inviamo il contenuto digitato dall'utente alla pagina di ricerca identificata dal path '$path6' -->
        <form action= <?php echo $path6; ?> method="post">
            <li class="search-icon">
                <input name="ricerca" type="search" placeholder="Search..">
                <label class="icon"> <span class="fas fa-search"></span></label>
            </li>
        </form> 

        </ul>
    </nav>

    <!-- DEFINIZIONE DELLA NAVIGATION BAR --> 

        <div class="topnav">

            <?php

                /* GESTIONE DELLA VISUALIZZAZAIONE DEI CONTENUTI DELLA NAVIGATION BAR A SECONDA DELLA PAGINA IN CUI È LOCALIZZATA */

                /* In particolar modo, se la pagina in cui è localizzato l'header riguarda l'home-page, la pagina di visualizzazione delle notizie per categorie, la pagina di visualizzazione
                della singola notizia o la pagina di visualizzazioe delle notizie ricercate per parole chiavi, allora l'header visualizzato sarà quello contenente le categorie delle notizie. Nel caso
                in cui la pagina ospitata dall'header sia relativa all'area personale (e dunque commenti utente, notizie utente, commenti da approvare e notizie da approvare), allora l'header
                sarà costruito dinamicamente tramite la funzione checkUserTypeBar a seconda della categoria di utente loggato. In particolar modo , nel caso di utente base nell'area personale 
                la navigation bar conterrà solo la sezione 'I miei commenti', nel caso di utente editor saranno presenti le sezioni 'I miei commenti' e 'Le mie notizie' e nel caso di utente admin
                saranno presenti oltre alle sezioni appena citate anche le sezioni 'Commenti da approvare' e 'Notizie da approvare' */
                switch(basename($_SERVER['PHP_SELF'])){
                    case "index.php":
                    case "categorie.php":
                    case "news.php":
                    case "search.php";
                        ?> 
                            <ul>
                            <li><a href=<?php echo $path2.'?tipo=Intelligenza_artificiale'; ?>>Intelligenza artificiale</a></li>
                            <li><a href=<?php echo $path2.'?tipo=Digital_economy'; ?>>Digital economy</a></li>
                            <li><a href=<?php echo $path2.'?tipo=Sicurezza'; ?>>Sicurezza</a></li>
                            <li><a href=<?php echo $path2.'?tipo=Digital_life'; ?>>Digital life</a></li>
                            <li><a href=<?php echo $path2.'?tipo=Techno-products'; ?>>Techno-products</a></li>
                            <li><a href=<?php echo $path2.'?tipo=Motors'; ?>>Motors</a></li>
                            </ul>
                        <?php
                        break;
                    case "commentiUtente.php":
                    case "notizieUtente.php":
                    case "commentiDaApprovare.php":
                    case "notizieDaApprovare.php":
                        echo checkUserTypeBar();
                        break;

                }

               
                function checkUserTypeBar(){
                    if(!empty($_SESSION['usrType'])){
                        switch($_SESSION['usrType']){
                            case "base":
                                return "<ul>
                                        <li><a href='./commentiUtente.php' >I miei commenti</a></li></ul>";
                                break;
                            case "editor":
                                return "<ul>
                                        <li><a href='./commentiUtente.php' >I miei commenti</a></li>
                                        <li><a href='./notizieUtente.php' >Le mie notizie</a></li>
                                        </ul>";
                                break;
                            case "admin":
                                return "<ul>
                                    <li><a href='./commentiUtente.php' >I miei commenti</a></li>
                                    <li><a href='./notizieUtente.php' >Le mie notizie</a></li>
                                    <li><a href='./commentiDaApprovare.php'>Commenti da approvare</a></li>
                                    <li><a href='./notizieDaApprovare.php'>Notizie da approvare</a></li>		  		                    
                                    </ul>";
                                break;
                        }
                    }
                }

                ?>
            
            
        </div>
</body>
</html>