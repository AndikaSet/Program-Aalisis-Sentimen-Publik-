<?php
	require_once "koneksi.php";
	session_start();

  unset($_SESSION['id_vektor'][0]);
  unset($_SESSION['id_klaster'][0]);

  foreach ($_SESSION['id_vektor'] as $key => $values) {
		$id_klaster = $_SESSION['id_klaster'][$key];
		
    $data_klaster[] = $id_klaster;

    $data[$id_klaster] = array_map(function($value){
			return   array('id_vektor' => $value);
		},$_SESSION['id_vektor'][$key]);		
	
  }

  $centroid = $_SESSION['centroid'];

	try{

      $stmt = $conn->prepare("INSERT IGNORE INTO klaster SET id_klaster = :id_klaster");

      $conn->beginTransaction();
      foreach ($data_klaster as $klaster) {
 
          $stmt->bindValue(":id_klaster", $klaster, PDO::PARAM_STR);
          
          $stmt->execute();   
      }
      $conn->commit();


      $stmt = $conn->prepare("INSERT INTO hasil_klaster (id_tweet, id_klaster) VALUES(:id_tweet, :id_klaster) ON DUPLICATE KEY UPDATE id_klaster = :id_klaster");
      
      $conn->beginTransaction();

      foreach ($data as $id_klaster => $klaster) {

      	foreach ($klaster as $nilai) {
			
    			extract($nilai);
	        $stmt->bindValue(":id_tweet", $id_vektor, PDO::PARAM_INT);
	        $stmt->bindValue(":id_klaster", $id_klaster, PDO::PARAM_STR);
	        
	        $stmt->execute();
      		
      	}

      }
      $conn->commit();


      $stmt = $conn->prepare("INSERT INTO centroid (id_klaster, x, y) VALUES(:id_klaster, :x, :y) ON DUPLICATE KEY UPDATE x = :x, y = :y");

      $conn->beginTransaction();

      foreach ($centroid as $id_klaster => $nilai) {
          extract($nilai);
          $stmt->bindValue(":id_klaster", "C".$id_klaster, PDO::PARAM_STR);
          $stmt->bindValue(":x", strval($x), PDO::PARAM_STR);
          $stmt->bindValue(":y", strval($y), PDO::PARAM_STR);
          
          $stmt->execute();   
      }
      $conn->commit();

      $msg = "Berhasil Upload Data";

      session_destroy();
      header("location:klastering.php");
  }catch(PDOException $e)
  {
    $conn->rollback();
    echo $e->getMessage();
  }
?>