
<?php

	if(!empty($_POST['ricerca'])){

		session_start(); /* Crea una sessione o riprende quella corrente */ 
		require_once "../connection.php";
		require "../utilityFunctions.php";

		/*Tramite metodo POST, accediamo al campo ricerca contenente la parola chiave digitata dall'utente tramite searchbar. In particolar modo, per rendere la ricerca case insensitive, 
		si converte in minuscolo la parola chiave ricercara. Oltretutto, per far si che la parola cercata possa essere presente in un qualsiasi punto rispetto al contenuto all'interno del quale viene
		ricercato, si utilizzato rispettivamente a fine ed inizio stringa le wildcards '%'. Dopodiche , come prima criterio la ricerca della parola chiave inserita viene effettuata sui contenuti di ogni singola
		notizia presente nella tabella notizie,avendo cura di convertire tutto in minuscolo anche in questo caso al fine di rendere equo il confronto.*/
		$cerca = $_POST['ricerca'];
		$cerca = strtolower($cerca);
		$cerca = '%'.$cerca.'%';
		$query = "SELECT categoria, contenuto, datapubblicazione, immagine, audio, emailpub, titolo, numcommenti FROM notizia WHERE LOWER(contenuto) LIKE $1";
		$prep = pg_prepare($db, "ricerca", $query);
		$result = pg_execute($db, "ricerca", array($cerca));

		if(!$result) {
			echo pg_last_error($db);
			exit;
		}

		/* Definizione dell'array contenente le notizie */
		$news = [];

		while($row = pg_fetch_array($result)) {
			$row[3] = convertBinaryToImage2($row[3],str_replace(" ", "", $row[6])); //conversione dell'immagine
			array_push($news,$row);
		}		

		

		/* Come nel caso precedentemente descritto, la ricerca per parole chiave è stata definita anche sul titolo di una notizia , sempre rendendola case insensitive. In particolar modo, se
		vengono trovate corrispondenze, quest'ultime vengono aggiunte insieme alle altre già trovate per corrispondenza di descrizione.*/
	
		$query2 = "SELECT categoria, contenuto, datapubblicazione, immagine, audio, emailpub, titolo, numcommenti FROM notizia WHERE LOWER(titolo) LIKE $1";
		$prep2 = pg_prepare($db, "ricerca2", $query2);
		$result2 = pg_execute($db, "ricerca2", array($cerca));

		if(!$result) {
			echo pg_last_error($db);
			exit;
		}
		
		while($row = pg_fetch_array($result2)) {
			$row[3] = convertBinaryToImage2($row[3],str_replace(" ", "", $row[6])); //conversione dell'immagine
			if(!in_array($row,$news)){	 //se non è stata già trovata la stessa notizia con la parola chiave contenuta per descrizione, allora la aggiunge , al fine di evitare duplicati
				array_push($news,$row);
			}
		}	

		/* Memorizzazione numero di occorrenze trovate per ricerca attraverso parola chiave*/
		$numnews = count($news); 

		/*Al fine di poter visualizzare accanto ad ogni notizia lo username dell'utente pubblicatore, dal momento in cui la tabella notizia per scelta progettuale contiene solo l'email del pubblicatore
		, si è deciso di prelevare dal database tutti gli utenti presenti, dati dall'unione delle tre tabelle utenteeditor, utentebase e amministratore, attraverso i quali viene costruito un array associativo
		email-username mediante il quale , accedendo tramite indice con l'email del pubblicatore, sarà possibile prelevare lo username associato. */
		$query3 = "SELECT email, nome, cognome, imgprofile, username FROM utentebase UNION SELECT email, nome, cognome, imgprofile, username FROM utenteeditor UNION SELECT email, nome, cognome, imgprofile, username FROM amministratore";
		$prep3 = pg_prepare($db, "takeUser", $query3);
		$result3 = pg_execute($db, "takeUser", array());
		$idUtenti = [];

		if(!$result3){
			echo pg_last_error($db);
			exit;
		}

		while($row = pg_fetch_array($result3)){
			$idUtenti += [$row[0] => $row[4]];
		}


	?>

	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="../CSS/style.css">
		<link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&display=swap" rel="stylesheet"> <!-- font utilizzato  -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.3.0/fonts/remixicon.css">	<!-- icone utilizzate -->
		<link rel="icon" href="logo1.png" type="image/png"/>
		<title> Tech Magazine | Ricerca </title>
	</head>
	<body>

		<div class="bodyCategorie">

		<?php include '../PHP/header.php'; ?>	


		<?php 

			$num = 0; // inizializzo contatore per visualizzazione delle notizie

			/* Se e solo se la ricerca ha prodotto dei risultati, allora la sezione successiva sarà visualizzata */
			if(!empty($news)){
			
			?>
			<h1> Ricerca effettuata per la parola <?php echo "'".$_POST['ricerca']."' (risultati ottenuti: ".$numnews.')'; ?></h1>
			<?php

			do{
			
			?>
				<!-- container della singola notizia, riprodotto tante volte quanto il numero di occorrenze trovate -->
				<div class="container">

						<!-- card della notizia -->
						<div class="card">
							<!-- immagine card della notizia -->
							<div class="card-image">
								<img src= <?php echo $news[$num][3]; ?> alt="">
							</div>

							<!-- contenuto della notizia -->
							<div class="card-content">

								<!-- dettagli su data di pubblicazione, autore e numero di commenti -->
								<div class="blog-details">

									<div class="icon-text">
										<span class="icon"> <i class ="ri-calendar-line"></i></span>
										<span class="text"><?php echo $news[$num][2]; ?></span>
									</div>

									<div class="icon-text">
										<span class="icon"> <i class ="ri-user-line"></i></span>
										<!-- accesso allo username del pubblicatore tramite la mail attraverso l'array associativo precedentemente costruito -->
										<span class="text"><?php  echo $idUtenti[$news[$num][5]]; ?></span> 
									</div>

									<div class="icon-text">
										<span class="icon"> <i class ="ri-message-line"></i></span>
										<span class="text"><?php echo $news[$num][7]; ?></span>
									</div>

								</div>
								
								<!-- Titolo della notizia -->
								<h2 class="blog-title"> <?php echo $news[$num][6]; ?></h2>
								<!-- Descrizione breve della notizia -->
								<p class="desc">
									<?php 
										/* Se la descrizione della notizia non supera i 250 caratteri, verrà mostrata per intero, altrimenti solo fino al 250esimo caratteri per una questione di visibilità */
										if(strlen($news[$num][1]) < 250){
											echo $news[$num][1];
										}else{
											echo substr($news[$num][1],0,250).'...'; 
										}
									?>
								</p>

								<div class="blog-cta">
									<!-- definizione dell'ancora alla pagina di visualizzazione della notizia, seguendo le medesime convenzioni delle restanti pagine
									utilizzate per il metodo GET nello stesso contesto -->
									<a href=<?php $app = str_replace(" ","_",$news[$num][6]); echo 'news.php?titolo='.$app; ?>  class="catButton">Leggi di più</a>
								</div>


							</div>
						</div>
					</div>

			
			<?php

				$num = $num + 1;
			}while($num < $numnews);
		}
		else{
			?>

			<!-- Valore mostrato a schermo nel caso in cui la ricerca produca alcuna occorrenza a causa dell'assenza di corrispondenze -->
			<h1> Nessun risultato corrisponde alla tua ricerca...</h1>
			<?php
		}
		?>

	</div>

	</body>
	</html>

	<!-- Inclusione del footer -->
	<?php include 'footer.php'; 
	
	} else {
		include 'errorPage.php'; /* Pagina di erore */
	}

	?>


