<!DOCTYPE html>
<html lang="en">
<head>


	<!-- Gestione delle ancore nel footer, al fine di poter definire il path correto. Difatti, se la pagina in cui il footer è incluso è l'home page, rispettivamente per accedere al 
	foglio di stile o ad una delle categorie di interesse, è necessario specificare la cartella in cui sono contenuti. Per quanto riguarda il logo del blog , essendo situato allo stesso
	livello di index.php, non ha bisogno di un percorso specifico. Al contrario, nel caso in cui la pagina sia diversa da index.php, e dunque una delle restanti contenuta nella tabella php, allora
	è necessario prima tornare ad un livello di visibilità superiore, e poi accedere alla cartella specifica per prelevare l'elemento in questione. -->
	
	<?php
		if(basename($_SERVER['PHP_SELF']) == 'index.php'){
			$path = 'CSS/style.css';
			$path1 = 'logo1.png';
			$path2 = 'PHP/categorie.php';
		}
		else{
			$path = '../CSS/style.css';
			$path1 = '../logo1.png';
			$path2 = 'categorie.php';
		}
	?>

	<link rel="stylesheet" href=<?php echo $path; ?>>
	
	<meta charset="UTF-8">
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title>Footer</title>
</head>
<body>
	
	<footer class="footer">
		<div class="footer-sx">

			<img src= <?php echo $path1; ?> alt=""> 

			<p>
				Il settore tech è in continua evoluzione e rimanere aggiornati sull’argomento è di estrema importanza. Affidati a Tech Magazine, un blog di tecnologia che diffonde notizie 
				aggiornate e sicure, per comprendere meglio il panorama presente e futuro del mondo digitale, garantendo agli utenti oltre che alla consultazione anche la pubblicazione di notizie
				e commenti relativi all'argomento di interesse.
			</p>
		</div>

		<ul class="footer-dx">
		
			<li>
				<h2>Servizi offerti</h2>

				<ul class="list-categorie">
					<li> Lettura notizie </li>
					<li> Pubblicazione di commenti  </li>
					<li> Pubblicazione di notizie </li>
					
				</ul>

			</li>

			<li>
				<h2>Categorie</h2>

				<ul class="list-categorie">
					<li><a href=<?php echo $path2.'?tipo=Intelligenza_artificiale'; ?>>Intelligenza artificiale</a></li>
					<li><a href=<?php echo $path2.'?tipo=Digital_economy'; ?>>Digital economy</a></li>
					<li><a href=<?php echo $path2.'?tipo=Sicurezza'; ?>>Sicurezza</a></li>
					<li><a href=<?php echo $path2.'?tipo=Digital_life'; ?>>Digital life</a></li>
					<li><a href=<?php echo $path2.'?tipo=Techno-products'; ?>>Techno-products</a></li>
					<li><a href=<?php echo $path2.'?tipo=Motors'; ?>>Motors</a></li>
				</ul>

			</li>
		

		
			<li>
				<h2>Contattaci</h2>

				<ul class="list-contatto">
					<li> <a href="mailto:techmagazine01tsw@outlook.com"> Contattaci via Mail </a> </li>
					<li> <a href="https://www.google.it/maps/place/Università+degli+Studi+di+Salerno/@40.7713104,14.7885268,17z/data=!3m1!4b1!4m5!3m4!1s0x133bc5c7456b88bd:0x80bab96149d2993d!8m2!3d40.7713104!4d14.7907155?hl=it&authuser=1" target="_blank"> Via Giovanni Paolo II, 132 </a> </li>
					<li> Fisciano (SA) </li>
					<li>  Italia </li>
				</ul>

			</li>
		
			

		</ul>

		<div class="footer-bottom">
			<p> All Right reserved by &copy; Tech Magazine</p>
		</div>
	</footer>
	
</body>
</html>