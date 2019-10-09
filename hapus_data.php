<?php
  require_once "koneksi.php";

  if(isset($_GET['id_tweet'])) {
      $stmt = $conn->prepare("DELETE FROM data_tweet WHERE id_tweet = :id_tweet");
      
      $stmt->bindParam(':id_tweet', $_GET['id_tweet'], PDO::PARAM_INT);

      $stmt->execute();

      header("location:tampil_data.php");

  }
?>