<?php 

	if(!empty($_GET['titolo'])){

		/* Crea una sessione o riprende quella corrente , al fine di garantire l'accesso alle variabili di sessione */
		session_start();
		require_once "../connection.php";
		require "../utilityFunctions.php";

		/* Dal momento in cui la notizia viene visualizzata passando tramite metodo GET il titolo di quest'ultima dalla pagina di partenza, quest'ultimo viene recuperato e poi riformattato
		nella sua versione iniziale, aggiungendo gli spazi ove incontra il carattere '_' */
		$titolo = $_GET['titolo'];
		$titolo = str_replace("_"," ",$_GET['titolo']);

		/* Definizione delle strutture d'utilizzo per raggruppare gli elementi restituiti dalle query. In particolar modo, si definisce:
			- $commenti array multivalore il quale andrà a contenere i commenti della notizia selezionata;
			- $idUtenti array associativo il quale andrà ad associare per ogni email uno username;
			- $fotoUtenti array associativo il quale andrà ad associare per ogni username la foto associata, per la visualizzazione di quest'ultima nel commento dell'utente.
		*/
		$commenti = [];
		$idUtenti = [];
		$fotoUtenti = [];

		/*SEZIONE DI SELEZIONE DELLA NOTIZIA */
		/* Selezione della notizia definita dal titolo passato tramite metodo GET, contenuto nella variabile $titolo */
		$query = "SELECT categoria, contenuto, datapubblicazione, immagine, audio, emailpub, titolo, numcommenti FROM notizia WHERE titolo=$1;";
		$prep = pg_prepare($db, "takeNews", $query);
		$result = pg_execute($db, "takeNews", array($titolo));

		if(!$result) {
			echo pg_last_error($db);
			exit;
		}
		
		/* Se la query ha esito positivo, vengono recuperati i seguenti dati: categoria, contenuto, data di pubblicazione, email del pubblicatore e numero di commenti associati alla notizia */
		$row = pg_fetch_array($result);
		$categoria = $row[0]; 
		$contenuto = $row[1];
		$datapubblicazione = substr($row[2], 0, 19);
		$emailpub = $row[5];
		$numcommenti = $row[7];
		
		/* Conversione dell'immagine associata alla notizia, seguendo la stessa convenzione adottata nelle altre pagine */
		$img = convertBinaryToImage2($row[3],str_replace(" ", "", $row[6]));
		
		/* Nel caso in cui è presente, si procede allo stesso modo di come fatto per l'immagine con la conversione dell'audio.*/
		if(!empty($row[4])){
			$audio = convertBinaryToAudio2($row[4],str_replace(" ", "", $row[6]));
		}


		/*SEZIONE DI SELEZIONE DEI COMMENTI ASSOCIATI ALLA NOTIZIA CARICATA*/
		/* Caricamento dei commenti associati alla notizia selezionata */
		$query2 = "SELECT descrizione, datainserimento, emailcom FROM commento WHERE titolo = $1 AND stato = $2";
		$prep2 = pg_prepare($db, "takeComments", $query2);
		$result2 = pg_execute($db, "takeComments", array($titolo, 'true'));

		if(!$result2) {
			echo pg_last_error($db);
			exit;
		}

		/* Memorizzazione di quanto restituito dalla query nell'array multivalore $commenti*/
		while($row = pg_fetch_array($result2)){
			array_push($commenti, $row);
		}


		/**SEZIONE DI SELEZIONE DI TUTTI GLI UTENTI DEL SITO WEB*/
		/* Selezione di tutti gli utenti (base, editor e admin) al fine di poter recuperare l'immagine profilo e lo username ad essi associati, per la corretta visualizzazione dei commenti*/
		$query3 = "SELECT email, nome, cognome, imgprofile, username FROM utentebase UNION SELECT email, nome, cognome, imgprofile, username FROM utenteeditor UNION SELECT email, nome, cognome, imgprofile, username FROM amministratore";
		$prep3 = pg_prepare($db, "takeUser", $query3);
		$result3 = pg_execute($db, "takeUser", array());

		if(!$result3) {
			echo pg_last_error($db);
			exit;
		}

		/*Quanto restituito dalla selezione, viene ad essere utilizzato per costruire gli array associativi $idUtenti, che associa ad 
		ogni email uno username, e $fotoUtenti, che associa ad ogni  email la foto profilo corrispondente. In particolar modo, per la definizione dei nomi associati alle immagini di profilo, si è
		scelto di usare come convenzione la mail dell'utente, tenendo cura di eliminare eventuali caratteri quali '@' dalla sua assegnazione come nome all'immagine del profilo.*/
		while($row = pg_fetch_array($result3)){
			$idUtenti += [$row[0] => $row[4]];

			if(!empty($row[3])){
				$fotoUtenti += [$row[0] => $row[3]];
				$mail = explode("@", $row[0]);
				$row[3]=convertBinaryToImage2($row[3],$mail[0]);
			}
		}

		/* SEZIONE DI UPLOAD DEL COMMENTO */	
		if(!empty($_POST) && !empty($_POST['commento']) && strlen($_POST['commento']) <= 500){

			/* Recupero dell'email dell'utente loggato tramite la variabile di sessione associata, al fine di poter verificare, col numero di occorrenze restituito dalla query successiva,
			se l'utente è o meno admin. Questo perchè, in caso di esito positivo, il commento oltre che ad essere inserito viene anche immediatamente dato per approvato, dal momento in cui 
			l'autore è l'admin stesso, responsabile delle approvazioni del blog.*/
			$emailcom = $_SESSION['email'];
			$query = "SELECT count(*) FROM amministratore WHERE email=$1";
			$prep = pg_prepare($db, "checkAdminCom", $query);
			$result = pg_execute($db, "checkAdminCom", array($emailcom));

			if(!$result) {
				echo pg_last_error($db);
				exit;
			}

			/*Se l'utente loggato è l'admin, la variabile $stato viene ad essere settata a true al fine di poter definire ,in fase di inserimento, anche l'approvazione del commento. Null è il valore
			da essa assunto in caso di esito contrario.*/
			(pg_fetch_row($result)[0] == 1) ? $stato = 'true' : $stato = NULL;


			/* Se l'utente loggato è l'admin, viene effettuato un inserimento del commento inviato all'interno della tabella commento con stato 'true', e al tempo stesso inserendo la stessa occorrenza
			anche all'interno della tabella commento approvato. In caso contrario, in cui l'utente sia editor o base, il commento viene solo inserito all'interno della tabella commento senza specificarne lo
			stato (essendo da approvare da parte dell'admin), il quale assume come valore di default NULL. La funzione date viene utilizzata per definire l'istante in cui il commento viene inserito, specificando
			tale nel formato ANNO-MESE-GIORNO ORA:MINUTI:SECONDI*/
			if($stato == 'true'){

				$date = date('Y-m-d H:i:s');
				
				$query = "INSERT INTO commento(descrizione, datainserimento, emailcom, titolo, stato) VALUES ($1, $2, $3, $4, $5)";
				$prep = pg_prepare($db, "InsertCommento", $query);
				$params = array($_POST['commento'], $date, $emailcom, $titolo, $stato);
				$result = pg_execute($db, "InsertCommento", $params);

				if(!$result) {
					echo pg_last_error($db);
					exit;
				}

				$query = "INSERT INTO commentoapprovato(dataapprovazione, datainserimento, emailcom, titolo) VALUES ($1, $2, $3, $4)";
				$prep = pg_prepare($db, "InsertCommentoApprovato", $query);
				$params = array($date, $date, $emailcom, $titolo);
				$result = pg_execute($db, "InsertCommentoApprovato", $params);

				if(!$result) {
					echo pg_last_error($db);
					exit;
				}

			} else {
				$query = "INSERT INTO commento(descrizione, datainserimento, emailcom, titolo) VALUES ($1, $2, $3, $4)";
				$prep = pg_prepare($db, "InsertCommento", $query);
				$params = array($_POST['commento'],date('Y-m-d H:i:s'), $emailcom, $titolo);
				$result = pg_execute($db, "InsertCommento", $params);	

				if(!$result) {
					echo pg_last_error($db);
					exit;
				}
			}

		
			/*Costruzione del path per ricaricare, a notizia inserita, la pagina della notizia in cui l'utente precedentemente risiedeva, tramite URL e metodo GET*/
			$path = "news.php?titolo=".str_replace(" ","_",$titolo);

			/*Gestione della variabile showAlert settata al valore true al fine di poter visualizzare, a commento inserito, un alert che notifica all'utente il corretto inserimento.*/
			$_SESSION['showAlert']='true';

			/*Ritorno alla pagina della notizia tramite header, utilizzando il metodo exit() al fine di garantire la conservazione delle variabili di sessione nel contesto di utilizzo del metodo header*/
			header("location: $path");
			exit();
			
		}
	?>

	<html>

	<head>
		<title>Tech Magazine | Notizia</title>
		<link rel="stylesheet" href="../CSS/style.css">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" />
		<link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&display=swap" rel="stylesheet">
		<link rel="icon" href="logo1.png" type="image/png"/>
		<script type="text/javascript" src="../utilityFunctions.js"></script>
	</head>

	<body>

		<!-- Inclusione dell'header -->
		<?php include '../PHP/header.php'; ?>	

		<!-- Container dell'immagine della notizia, con titolo, autore e player audio --> 
		<div class="newsContainer">
			<img src="<?php echo $img; ?>" alt="immagine" class="newsImage">
			<div class="newsTitle">
				<h2><?php echo $titolo; ?></h2>
				<h4><?php echo '@'.$idUtenti[$emailpub];  ?></h4>
			</div>
		</div>

		<div class="textNewsContainer">
			<p class="newsContent"><?php echo $contenuto; ?></p>
		</div>

		<?php
			
			/* Il caricamento del player audio avviene se e solo se la notizia visualizzata lo possiede. In caso contrario, tale sezione non sarà presente nella visualizzazione della pagina*/
			if(!empty($audio)){
			
		?>
				<!-- Sezione del player audio -->
				<div class="container-audio">
					<audio id="player" onended="onEnded()">
						<source src="<?php echo $audio; ?> " type="audio/mpeg">
					</audio>
				
					<div class="container-player">

						<div class="container-player-image">

							<div class="cover">
								<img id="gif-audio" src="" alt="gif audio"> 
							</div>

						</div>

						<div class="container-controls">
							<div class="controls">
								<i class="ri-play-circle-line pause"></i>
							</div>
							
						</div>

						<div class="container-player-info-controls">

							<div class="container-info">

								<div>
									Riproduci per ascoltare l'articolo
								</div>

								<div class="container-info-author">
									<?php
										echo "<em>@$idUtenti[$emailpub]</em>";
									?>
								</div>

							</div>

						</div>

					</div>

				</div>
		<?php
		}
		?>

		<!-- Script JS associato al player audio per gestire la riproduzione -->
		<script type="text/javascript" src="../JS/player.js"></script>
		

		<!-- Da mostrare solo in caso di utente loggato -->

		<?php

		/*La sezione di inserimento di un commento in associazione ad una specifica notizia sarà visualizzata solo nel caso in cui il visitatore del blog sia loggato. Questo controllo viene effettuato
		andando a verificare se settata la variabile di sessione 'success'  e se il valore da essa associata è 'logged'. Infatti, quando l'utente effettua il login/signup, tale variabile di sessione
		sarà settata al valore precedentemente definito, proprio per identificare il login/signup al blog.*/
		if(isset($_SESSION['success']) && $_SESSION['success'] == 'logged'){

		?>	
			<h1 class="commentsTitle">Inserisci un commento:</h1>

			<!-- Form associato alla sezione di inserimento del commento, la cui action è la pagina stessa all'interno della quale il 
			commento viene definito, identificata tramite metodo get dal valore assunto dal titolo -->
			<form  id="addComment" action="<?php echo $_SERVER['PHP_SELF'].'?titolo='.$_GET['titolo']; ?>" method="post">
				<div class="newComment">
					<textarea id="commento" class="textAreaComment" name="commento" placeholder="Inserisci qui il testo" maxlength="520" required><?php if(!empty($_POST['commento'])){echo $_POST['commento'];}?></textarea>
					<input type="submit" name="button" value="Invia" class="textAreaButton">
				</div>
				
				<?php 

					/*Gestione dell'alert all'invio del commento, il quale permette di di poter dare un feedback all'utente sull'invio al caricamento successivo della pagina della 
					notizia associata al commento. In particolar modo, la gestione dell'alert, come già descritto in precedenza, avviene tramite la variabile di sessione 'showAlert', la quale
					viene impostata a true dopo che l'inserimento verso il DataBase viene conseguito. Oltretutto, essendo che i commenti inseriti dall'admin non hanno bisogno di approvazione,
					tramite la variabile di sessione 'usrType' settata al momento del login, possiamo evitare che tale alert compaia nel momento in cui è l'admin stesso ad inserire un commento. */
					if(!empty($_SESSION['showAlert']) && $_SESSION['showAlert']=='true' && $_SESSION['usrType'] != 'admin'){ 

						$_SESSION['showAlert']='false';
						?>
							<div class="alert" >
								<div class="closebtn" onclick="this.parentElement.style.display='none';">x</div> 
								<strong>Commento inviato!</strong> Resta in attesa di approvazione.
							</div>
						<?php
						
					}
				?>

			</form>

			<script type="text/javascript">

				/* Attribuzione di un gestore di eventi al form "addNewsForm" sull'evento "submit" */
				document.forms.addComment.addEventListener('submit', function(e) {
					e.preventDefault();
					if(addComment.commento.value == ""){
						alert("Devi inserire un commento!");
						return false;
					}
					checkLength(addComment.commento, 500);
					addComment.submit();
				});

			</script>
		<?php
		}
		?>

		<hr class="rounded">

		<!-- Indicatore della sezione commenti, con il numero delle occorrenze di quest'ultimi in relazione alla notizia associata -->
		<h1 class="commentsTitle">Commenti <?php echo '('.$numcommenti.')'; ?> </h1>

		<!-- SEZIONE DI CARICAMENTO DEI COMMENTI -->

		<?php 

			$count = 0;
			if($numcommenti > 0){
				do{
				
		?>

			<!-- Classe commento composta da :
					- foto profilo del commentatore, 
					- username del commentatore,
					- data di inserimento del commento,
					- corpo del commento.
			-->
			<div class="comment">
				<img src= 
					<?php 
						/* Se è presente una foto fra gli utenti selezionati (e ciò accade sempre tranne nel caso in cui l'utente è l'admin) verificando tramite
						la presenza della coppia email-imgprofile all'interno dell'array associativo $fotoUtenti, allora la foto viene caricata col path specifico, seguendo
						la convenzione adottata anche nelle altre pagine. */
						if(!empty($fotoUtenti[$commenti[$count][2]])){
						$mail = explode("@", $commenti[$count][2]);
						echo '../images/'.$mail[0].'.jpg';
						}
						else{
							echo '../boss.png';
						}
					?> 
				alt="avatar" class="imgComment">
				<div class="commentContent">
					<span class="username">
						<?php echo '@'.$idUtenti[$commenti[$count][2]]; ?>
					</span>
					<span class="date">
						<?php echo $commenti[$count][1]; ?>
					</span>
					<p class="commentText">
						<?php echo $commenti[$count][0]; ?>
					</p>
				</div>
			</div>
		
		<?php 	
				$count = $count + 1;
				}while($count < $numcommenti);

			}else{

				echo '<div class="comment"><p> Nessun commento ancora presente... </p></div>';

			}
		?>


	</div>

	<!-- Script JS per il controllo su inserimenti di commenti non vuoti --> 
	<script src="../JS/comment.js"></script> 

	</body>

	</html>

	<!-- inclusione del footer -->
	<?php include 'footer.php'; 
	
	} else {
		include 'errorPage.php'; /* Pagina di erore */
	}
	
	?>