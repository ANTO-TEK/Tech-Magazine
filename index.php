<?php

    /* Creazione della sessione nella home page, pagina di primo accesso */
    session_start();

    /* Connessione al DataBase */
    require_once "connection.php";
    require "utilityFunctions.php";
     
    /* Selezione dal DataBase di tutte e sole le notizie approvate, riconosciute dallo stato impostato a true */
    $query = "SELECT categoria, contenuto, datapubblicazione, immagine, audio, emailpub, titolo, numcommenti FROM notizia WHERE stato = 'true';";
    $prep = pg_prepare($db, "newsApprovate", $query);
    $result = pg_execute($db, "newsApprovate", array());

    /* Categorizzazione delle notizie */
    $intart = []; 
    $digeconomy = []; 
    $sicurezza = []; 
    $diglife = []; 
    $techprod = []; 
    $motors = []; 
  
    /* Suddivisione per categorie delle notizie ottenute dalla query. L'utilizzo del metodo convertBinaryToImage è finalizzato alla creazione dell'immagine
    della notizia, da utilizzare per la visualizzazione. In particolar modo, per una scelta progettuale si è altresì deciso di assegnare ai nomi delle immagini delle specifiche notizie
    il titolo di quest'ultime, costruito appositamente senza spazi.  */

    /* Convenzione adottata per salvare le immagini delle notizie: usando il titolo della notizia associata, tenendo cura di eliminare gli spazi */
    while($row = pg_fetch_array($result)) { 

        if($row[0] == "Intelligenza artificiale"){
            $row[3] = convertBinaryToImage($row[3],str_replace(" ", "", $row[6]));
            array_push($intart,$row);
        }

        if($row[0] == "Digital economy"){
            $row[3] = convertBinaryToImage($row[3],str_replace(" ", "", $row[6]));
            array_push($digeconomy,$row);
        }

        if($row[0] == "Sicurezza"){
            $row[3] = convertBinaryToImage($row[3],str_replace(" ", "", $row[6]));
            array_push($sicurezza,$row);
        }

        if($row[0] == "Digital life"){
            $row[3] = convertBinaryToImage($row[3],str_replace(" ", "", $row[6]));
            array_push($diglife,$row);
        }

        if($row[0] == "Techno-products"){
            $row[3] = convertBinaryToImage($row[3],str_replace(" ", "", $row[6]));
            array_push($techprod,$row);
        }

        if($row[0] == "Motors"){
            $row[3] = convertBinaryToImage($row[3],str_replace(" ", "", $row[6]));
            array_push($motors,$row);
        }


    }


    /* Unione di tutte le categorie nell'array multivalore $recent, ordinato per date dalla più recente alla meno recente tramite metodo array_multisort, sul campo 
    $data ottenuto come colonna dal metodo array_column richiamato su $recent e specificando il campo da prelevare */
    $recent = array_merge($intart, $digeconomy, $sicurezza, $diglife, $techprod, $motors);
    $data = array_column($recent, 'datapubblicazione');
    array_multisort($data, SORT_DESC, $recent); 

    /* Unione di tutte le categorie nell'array multivalore $trending, ordinato per numero di commenti dal più alto al più basso tramite metodo array_multisort, sul campo 
    $trending ottenuto come colonna dal metodo array_column richiamato su $trending e specificando il campo da prelevare. Si è scelto dunque  di definire come criterio per le trending
    news il numero di commenti. */
    $trending = array_merge($intart, $digeconomy, $sicurezza, $diglife, $techprod, $motors);
    $comm = array_column($trending, 'numcommenti');
    array_multisort($comm, SORT_DESC, $trending); 

    /* Unione di tutte le categorie nell'array multivalore $banner, sezione alta del corpo principale dell'home page posto sulla colonna sinistra, al quale è stato associato un 
    ordinamento randomico mediante il metodo shuffle, per far si che le notizie possano essere aggiorante ogni volta che l'utente accede alla homepage. */
    $banner = array_merge($intart, $digeconomy, $sicurezza, $diglife, $techprod, $motors);
    shuffle($banner); 

    /* Ordinamento randomico alle notizie più recenti assegnato alla colonna destra del banner, per rendere tale sezione dinamica all'aggiornamento della pagina. */
    $recentbanner = $recent;
    shuffle($recentbanner); 

 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Magazine | Home</title>
    <link rel="stylesheet" href="CSS/style.css">
    <!-- Font di google utilizzati per lo stile -->
    <link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&display=swap" rel="stylesheet">
    <link rel="icon" href="logo1.png" type="image/png"/>
</head>
<body>
    
    <!-- Inclusione dell'header -->
    <?php include 'PHP/header.php'; ?>


    <div class="bodyHome">
        <!-- Parte superiore del body -->
        <section class="banner">

            <!-- Banner sinistro -->
            <div class="banner-main-content">
                <h2>LEGGI LE ULTIME NOTIZIE RIGUARDANTI IL MONDO TECH</h2>

                <div class = "current-news-head">

                    <?php 
                        $start = 0;
                        $titolo = "";
                        $autore = "";

                        /* Si è deciso di effettuare un check sul numero di notizie in termini di limite inferiore affinchè la visualizzazione della pagina sia coerente con la struttura 
                        ad essa assegnata. Nella fatti specie, il banner sinistro richiede un minimo di 4 notizie per essere presente.
                        */
                        $count1 = count($recentbanner);
                        if($count1 >= 4){ 

                        do{

                            $titolo = $recentbanner[$start][6];
                            $autore = $recentbanner[$start][5];

                        ?>

                        <!-- In particolar modo ,ogni elemento del banner è un ancora contenente il link diretto alla pagina di visualizzazione della notizia, la quale viene ad essere identificata
                        dal titolo, passato tramite metodo GET nell'URL. Oltretutto, dal momento in cui quest'ultimo non accetta stringhe con spazi, tramite str_replace gli spazi
                        sono stati eliminati e sostituiti da '_', per poi  essere ricostruiti nella pagina della visualizzaione della notizia al fine di poter eseguire correttamente la query al DataBase-->
                        <a href= <?php $app = str_replace(" ","_",$titolo); echo 'PHP/news.php?titolo='.$app; ?>  ><h3> <?php echo $titolo; ?> <span> by <?php echo $autore;?></span></h3></a>
                        <?php

                        $start = $start + 1;
                        }while($start < 4);
                    }

                    ?>

                    </div>
            </div>

            <!-- Banner destro --> 
            <div class="banner-sub-content">

            <?php 
                        $start = 0;
                        /* Per le medesime motivazioni sopra elencate, il limite inferiore per la visualizzaione delle notizie impostato sul banner destro è impostato a 4 */
                        $count2 = count($banner);
                        if($count2 >= 4){

                        
                        do{
                    ?>

                        <div class="important-news">
                            <img src= <?php echo $banner[$start][3]; ?> > 
                            <div class="important-news-content">
                                <h2> <?php echo $banner[$start][6]; ?> </h2>
                                <h3> <?php echo $banner[$start][0]; ?> </h3>
                                <p>
                                    <?php 
                                        /* In particolar modo, alla descrizione breve della notizia viene dato un limite massimo di 100 caratteri, oltre il quale la stringa viene tagliata
                                        per dare una corretta visualizzazione al container associato.  */
                                        if(strlen($banner[$start][1]) < 100){
                                            echo $banner[$start][1];
                                        }else {
                                            echo substr($banner[$start][1],0,100).'...';
                                        }
                                    ?>
                                </p>

                                <!-- La gestione dell'ancora per la visualizzazione della notizia segue la stessa logica sopra definita -->
                                <a href = <?php  $app = str_replace(" ","_",$banner[$start][6]); echo 'PHP/news.php?titolo='.$app; ?> >Read More</a>
                            </div>
                        </div>

                    <?php

                        $start = $start + 1;
                        }while($start < 4);
                    }
                    ?>

            </div>
        </section>

        <hr>

        <!-- Parte principale della pagina -->
        <main>
            <!-- Trending News -->
            <section class="main-container-sx">
                <h2>Trending news</h2>

                <!-- Per le medesime motivazioni sopra elencate, il limite inferiore per la visualizzaione delle notizie impostato nella sezione trending news è impostato a 5 -->
                <?php $count3 = count($trending); if($count3 >= 5) { ?>

                <!-- Prima notizia della sezione trending news, messa in risalto rispetto alle successive -->
                <div class="container-top-sx">
                    <article>
                        <img src=<?php echo $trending[0][3]; ?>>
                        <div>
                            <h3><?php echo $trending[0][6]; ?></h3>
                            <p> 
                                <?php 
                                    /* In particolar modo, alla descrizione breve della notizia viene dato un limite massimo di 100 caratteri, oltre il quale la stringa viene tagliata
                                        per dare una corretta visualizzazione al container associato.  */
                                    if(strlen($trending[0][1]) < 100){
                                    echo $trending[0][1];
                                    }
                                    else{
                                    echo substr($trending[0][1],0,100).'...'; 
                                    } 
                                ?>
                            </p>
                            <!-- La gestione dell'ancora per la visualizzazione della notizia segue la stessa logica sopra definita -->
                            <a href=<?php $app = str_replace(" ","_",$trending[0][6]); echo 'PHP/news.php?titolo='.$app; ?> > Read more <span>>></span></a>
                        </div>
                    </article>
                </div>
                <?php } ?>
                
                <!-- Le restanti 4 notizie della sezione trending news , messe in secondo piano -->
                <div class="container-bottom-sx">
                    <?php

                        /*  Indice dell'array $trending, per il quale partiamo dal secondo elemento dell'array dal momento in cui il primo è stato messo in primo piano */
                        $trend = 1;
                        
                        /* Per le medesime motivazioni sopra elencate, il limite inferiore per la visualizzaione delle notizie impostato nella sezione trending news è impostato a 5 */
                        if($count3 >= 5){ 

                        do{

                            ?>

                            <article>
                                <img src=<?php echo $trending[$trend][3]; ?>>
                                <div>
                                    <h3><?php echo $trending[$trend][6]; ?></h3>

                                    <p> 
                                        <?php 
                                            /* Valgono le stesse considerazioni effettuate per la gestione della descrizione della notizia sopra elencate */
                                            if(strlen($trending[$trend][1]) < 100){
                                                echo $trending[$trend][1];
                                                }
                                                else{
                                                echo substr($trending[$trend][1],0,100).'...'; 
                                                } 
                                        ?>
                                    </p>

                                     <!-- La gestione dell'ancora per la visualizzazione della notizia segue la stessa logica sopra definita -->          
                                    <a href= <?php  $app = str_replace(" ","_",$trending[$trend][6]); echo 'PHP/news.php?titolo='.$app; ?> > Read more <span>>></span></a>
                                </div>
                            </article>  

                            <?php

                        $trend = $trend + 1;

                        }while($trend <  5);
                    }
                    ?>
        

                </div>
            </section>

            <!-- Sezione delle ultime notizie -->
            <section class="main-container-dx">
                <h2>Ultime notizie</h2>
                <?php 
                    $rec = 0;
                    $count4 = count($recent);

                    /* Per le medesime motivazioni sopra elencate, il limite superiore per la visualizzaione delle notizie più recenti, in accordo alle scelte fatte per le altre
                    sezioni, è impostato ad un massimo di 10 notizie che,  nel caso in cui fosseri inferiori, viene richiesto un minimo di 4 notizie affinchè questa sezione possa
                    essere presente */

                    if($count4 < 10){

                        $max = count($recent);
                    }
                    else{
                        $max = 10;
                    }

                    if($count4 > 4){ 

                    do {
                    
                    ?>

                        <article>
                            <!-- Definizione della data di inserimento , presa come sottostringa escludendo gli ultimi 3 caratteri al fine di evitare l'inclusione dei secondi-->
                            <h4> <?php $rest = substr($recent[$rec][2], 0, -3); echo $rest; ?></h4>
                            <div>
                                <h2><?php echo $recent[$rec][6]; ?></h2>

                                <p>
                                    <?php 
                                         /* Valgono le stesse considerazioni effettuate per la gestione della descrizione della notizia sopra elencate */
                                        if(strlen($recent[$rec][1]) < 100){
                                            echo $recent[$rec][1];
                                        }
                                        else{
                                        echo substr($recent[$rec][1],0,100).'...'; 
                                        } 
                                    ?>
                                </p>

                                 <!-- La gestione dell'ancora per la visualizzazione della notizia segue la stessa logica sopra definita -->  
                                <a href=<?php $app = str_replace(" ","_",$recent[$rec][6]); echo 'PHP/news.php?titolo='.$app; ?>> Read more <span>>></span></a>
                            </div>
                            <img src = <?php echo $recent[$rec][3]; ?>>
                        </article>
                            
                    <?php

                    $rec = $rec + 1;

                    }while($rec < $max);
                }

                ?>
            </section>
        </main>
        
    </div>
        

    
</body>
</html>


<!-- Inclusione del footer nella pagina -->
<?php include 'PHP/footer.php'; ?>