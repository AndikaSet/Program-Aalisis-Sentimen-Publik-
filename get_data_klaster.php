<?php
    require_once "koneksi.php";
    require_once "kakas/KMeans.php";
    $stmt = $conn->prepare("SELECT d.id_tweet, positif, negatif, id_klaster FROM data_tweet d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet");
    $stmt->execute();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      extract($row);
      $data_hasil[$id_tweet]['x'] = $positif;
      $data_hasil[$id_tweet]['y'] = $negatif;
      $data_hasil[$id_tweet]['id_klaster'] = $id_klaster;
    }


	foreach ($data_hasil as $id_vektor => $nilai_klaster) {
		extract($nilai_klaster);
		$json_output[$id_klaster]['x'][] = $x;
		$json_output[$id_klaster]['y'][] = $y;
		$json_output[$id_klaster]['id_vektor'][] = $id_vektor;	 
	}

	echo json_encode($json_output);
?>