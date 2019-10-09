<?php 
	session_start();

	unset($_SESSION['x']);
	unset($_SESSION['y']);
	unset($_SESSION['id_vektor']);
	unset($_SESSION['id_cluster']);
	unset($_SESSION['centroid']);

	if(isset($_POST)) {
		$_SESSION['id_vektor'] = $_POST['id_vektor'];
		$_SESSION['id_klaster'] = $_POST['id_klaster'];
		$_SESSION['centroid'] = $_POST['centroid'];

		$msg = "OK";
	} else {
		$msg = "No data was supplied";
	}

	Header('Content-Type: application/json; charset=utf8');
	die(json_encode(array('status' => $msg)));
?>