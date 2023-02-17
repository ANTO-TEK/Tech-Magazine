<?php

    error_reporting(E_ALL);
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);

    $host = 'localhost';
    $port = '5432';
    $db = 'tsw';
    $username = 'www';
    $password = 'tsw2022';

    $connection_string = "host=$host port=$port dbname=$db user=$username password=$password";
    
?>