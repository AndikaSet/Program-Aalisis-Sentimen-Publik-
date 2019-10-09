<?php
    require_once "koneksi.php";
    require_once "kakas/KMeans.php";
    $stmt = $conn->prepare("SELECT id_tweet, positif, negatif FROM data_tweet");
    $stmt->execute();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      extract($row);
      $data[$id_tweet] = array($positif, $negatif);
    }
    
	//PROSES INISIALISASI CENTROID

	$k = 2;

	function inisialisasiCentroid($data, $k){
		$dimensi = count($data[1]);
		$centroid_inisialisasi = array();
		$max_nilai = array();
		$min_nilai = array(); 
		foreach($data as $vektor) {
			foreach($vektor as $dim => $nilai) {
				if(!isset($max_nilai[$dim]) || $nilai > $max_nilai[$dim]) {
					$max_nilai[$dim] = $nilai;
				}
				if(!isset($min_nilai[$dim]) || $nilai < $min_nilai[$dim]) {
					$min_nilai[$dim] = $nilai;
				}
			}
		}
		
		for($i = 1; $i <= $k; $i++) {

			$total = 0;
			$vektor = array();
			for($j = 0; $j < $dimensi; $j++) {
				$vektor[$j] = (rand($min_nilai[$j] * 500, $max_nilai[$j] * 500));
				$total += $vektor[$j] * $vektor[$j];
			}

			foreach($vektor as &$nilai) {
				$nilai = round($nilai/sqrt($total), 4);
			}

			$centroid_inisialisasi[$i] = $vektor;
		}

		return $centroid_inisialisasi;
	}


	//PROSES KLASTERING

	$klaster = array();
	while(true) {

		//PEMBERIKAN KLASTER PADA DATA DENGAN MENGUKUR JARAK DATA DENGAN KLASTER
		if (!isset($centroid)) {
			$centroid = inisialisasiCentroid($data, $k);
		}

		foreach($data as $id_vektor => $vektor) {
			$min_jarak = 100;
			$centroid_terdekat = null;
			foreach($centroid as $id_centroid => $nilai_centroid) {
				$jarak = 0;
				foreach($nilai_centroid as $dim => $nilai) {
					$jarak += pow($nilai - $vektor[$dim],2);
				}
				$jarak = sqrt($jarak);
				if($jarak < $min_jarak) {
					$min_jarak = $jarak;
					$centroid_terdekat = $id_centroid;
				}
			}
			$klaster[$id_vektor] = $centroid_terdekat;
			
		}

		//TAHAPAN PERUBAHAN KLASTER
		
		$klaster_baru = $klaster;
		$perubahan = false;
		foreach($klaster_baru as $id_vektor => $id_centroid) {
			if(!isset($klaster[$id_vektor]) || $id_centroid != $klaster[$id_vektor]) {
				$klaster = $klaster_baru;
				$perubahan = true;
				break;
			}
		}
		if(!$perubahan){
			$klaster_hasil  = array();
			$centroid_hasil['centroid'] = $centroid;
			foreach($klaster as $id_vektor => $id_centroid) {
				$klaster_hasil[$id_vektor][$id_centroid] = $data[$id_vektor];
			}
			break;
		}

		$centroid_baru = array();
		$juml_klaster = array_count_values($klaster);
		
		foreach($klaster as $id_vektor => $id_centroid) {
			foreach($data[$id_vektor] as $dim => $value) {
				if(!isset($centroid_baru[$id_centroid][$dim])) {
					$centroid_baru[$id_centroid][$dim] = 0;
				}
				$centroid_baru[$id_centroid][$dim] += ($value/$counts[$id_centroid]); 
			}
		}
		if(count($centroid_baru) < $k) {
			$centroid_baru = array_merge($centroid_baru, $centroid = inisialisasiCentroid($data, $k - count($centroid_baru)));
		}
		$centroid = updateCentroid($klaster, $data, $k); 
	}


	foreach ($klaster_hasil as $id_vektor => $nilai_vektor) {
		foreach ($nilai_vektor as $id_klaster => $value) {
			$data_hasil[$id_klaster][$id_vektor]['x'] = $value[0];
			$data_hasil[$id_klaster][$id_vektor]['y'] = $value[1];
		}
	}

	foreach ($centroid as $id_centroid => $nilai) {
		$json_centroid[$id_centroid]['x'] = $nilai[0];
		$json_centroid[$id_centroid]['y'] = $nilai[1];
	}

	foreach ($data_hasil as $id_klaster => $nilai_klaster) {
		foreach ($nilai_klaster as $id_vektor => $nilai) {
			
			$json_klaster[$id_klaster]['x'][] = $nilai['x'];
			$json_klaster[$id_klaster]['y'][] = $nilai['y'];
			$json_klaster[$id_klaster]['id_vektor'][] = $id_vektor;
		} 	
	}
	
	$json_output = array('centroid' => $json_centroid, 'klaster' => $json_klaster);

	echo json_encode($json_output);
?>