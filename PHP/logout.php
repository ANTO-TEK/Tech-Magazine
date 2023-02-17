<?php
session_start(); //per assicurarsi di stare nella stessa sessione
session_unset(); //libera tutte le variabili di sessione
session_destroy(); //distruzione della sessione
header('location: ../index.php'); //per tornare indietro "index.php" dopo il log out
exit();
?>