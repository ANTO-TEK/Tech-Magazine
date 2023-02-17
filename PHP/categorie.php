<?php

	if(!empty($_GET['tipo'])){

		session_start(); /* Crea una sessione o riprende quella corrente */ 
		require_once "../connection.php";
		require "../utilityFunctions.php";

		/* Dal momento in cui la categoria viene visualizzata passando tramite metodo GET il nome di quest'ultima dalla pagina di partenza, quest'ultimo viene recuperato e poi riformattato
		nella sua versione iniziale, aggiungendo gli spazi ove incontra il carattere '_' */
		$categoria = str_replace("_"," ",$_GET['tipo']);

		/* Selezione dalla tabella notizie di tutte e sole le notizie la cui categoria corrisponde a quella passata dal metodo GET e il cui
		stato, indicante l'approvazione, sia impostato a true */
		$query = "SELECT categoria, contenuto, datapubblicazione, immagine, audio, emailpub, titolo, numcommenti FROM notizia WHERE categoria = $1 AND stato = $2;";
		$prep = pg_prepare($db, "newsCategoria", $query);
		$result = pg_execute($db, "newsCategoria", array($categoria, 't'));

		if(!$result){
			echo pg_last_error($db);
			exit;
		}

		/* Definizione dell'array contenente le notizie */
		$news = [];
		while($row = pg_fetch_array($result)) {
			$row[3] = convertBinaryToImage2($row[3],str_replace(" ", "", $row[6])); //conversione dell'immagine
			array_push($news,$row);
		}		

		/* Memorizzazione numero di occorrenze trovate per ricerca attraverso parola chiave*/
		$numnews = count($news);

		/*Al fine di poter visualizzare accanto ad ogni notizia lo username dell'utente pubblicatore, dal momento in cui la tabella notizia per scelta progettuale contiene solo l'email del pubblicatore
		, si è deciso di prelevare dal database tutti gli utenti presenti, dati dall'unione delle tre tabelle utenteeditor, utentebase e amministratore, attraverso i quali viene costruito un array associativo
		email-username mediante il quale , accedendo tramite indice con l'email del pubblicatore, sarà possibile prelevare lo username associato. */
		$idUtenti = [];  
		$query3 = "SELECT email, nome, cognome, imgprofile, username FROM utentebase UNION SELECT email, nome, cognome, imgprofile, username FROM utenteeditor UNION SELECT email, nome, cognome, imgprofile, username FROM amministratore";
		$prep3 = pg_prepare($db, "takeUser", $query3);
		$result3 = pg_execute($db, "takeUser", array());

		if(!$result3){
			echo pg_last_error($db);
			exit;
		}

		while($row = pg_fetch_array($result3)){
			$idUtenti += [$row[0] => $row[4]];
		}


	?>

	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="../CSS/style.css">
		<link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&display=swap" rel="stylesheet"> <!-- font utilizzato  -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.3.0/fonts/remixicon.css">	<!-- icone utilizzate -->
		<link rel="icon" href="logo1.png" type="image/png"/>

		<title><?php echo "Tech Magazine | $categoria";?></title>
	</head>
	<body>

		<div class="bodyCategorie">

			<?php include 'header.php'; ?>

			<!-- Visualizzazione della categoria specifica -->
			<h1>  
				<?php echo $categoria; ?>  
			</h1>

			<?php 
				$num = 0;

				/* Se e solo se la ricerca ha prodotto dei risultati, allora la sezione successiva sarà visualizzata */
				if(!empty($news)){
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
								<span class="text"><?php echo $idUtenti[$news[$num][5]]; ?></span>
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
