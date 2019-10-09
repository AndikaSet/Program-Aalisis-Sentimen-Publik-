<?php
	$host="localhost";
	$user="root";
	$db="analisis_sentimen";
	$pass="";
	$conn;

	$conn = new PDO("mysql:host=".$host.";dbname=".$db, $user, $pass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>