<?php

	require_once "logindb.php";
	
	ini_set('memory_limit','10G');

	/* connessione al db */
	$db = pg_connect($connection_string) or die('Impossibile connettersi al database: ' . pg_last_error());
?>
