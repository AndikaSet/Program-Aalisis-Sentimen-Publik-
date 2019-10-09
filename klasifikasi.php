<?php
	require_once "koneksi.php";
	require_once "vendor/autoload.php";

	use Phpml\Dataset\ArrayDataset;
	use Phpml\FeatureExtraction\TokenCountVectorizer;
	use Phpml\Tokenization\WordTokenizer;
	use Phpml\Classification\SVC;
	use Phpml\SupportVectorMachine\Kernel;


	if(isset($_POST['klasifikasi'])){
		$stmt = $conn->prepare("(SELECT d.id_tweet, d.hasil_preprocessing, k.keterangan AS kelas FROM data_tweet d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k on h.id_klaster = k.id_klaster WHERE h.id_klaster = 'C1' ORDER BY h.id_tweet ASC LIMIT 200)
		UNION 
		(SELECT d.id_tweet, d.hasil_preprocessing, k.keterangan AS kelas FROM data_tweet d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k on h.id_klaster = k.id_klaster WHERE h.id_klaster = 'C2'  ORDER BY h.id_tweet ASC LIMIT 200)
		ORDER by id_tweet");

		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row);
			$dataTrainingSamples[$id_tweet] = $hasil_preprocessing;
			$dataTrainingLabels[$id_tweet] = $kelas;
		}

		if(isset($dataTrainingSamples)){
			$dataTrainingPreprocessing = $dataTrainingSamples;			
		}

		$stmt = $conn->prepare("(SELECT d.id_tweet, d.hasil_preprocessing, k.keterangan AS kelas FROM data_tweet d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k on h.id_klaster = k.id_klaster WHERE h.id_klaster = 'C1' ORDER BY h.id_tweet DESC LIMIT 50)
			UNION 
			(SELECT d.id_tweet, d.hasil_preprocessing, k.keterangan AS kelas FROM data_tweet d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k on h.id_klaster = k.id_klaster WHERE h.id_klaster = 'C2'  ORDER BY h.id_tweet DESC LIMIT 50)
			ORDER by id_tweet");

		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row);
			$dataTestingSamples[$id_tweet] = $hasil_preprocessing;
			$dataTestingLabels[$id_tweet] = $kelas;
		}

		if(isset($dataTrainingSamples) && isset($dataTestingSamples)){
			$dataTestingPreprocessing = $dataTestingSamples;

			$vectorizer = new TokenCountVectorizer(new WordTokenizer());
			$vectorizer->fit($dataTrainingSamples);
			$vectorizer->transform($dataTrainingSamples);

			$vectorizer->fit($dataTestingSamples);
			$vectorizer->transform($dataTestingSamples);

			$dataTraining = new ArrayDataset($dataTrainingSamples, $dataTrainingLabels);
			$dataTesting = new ArrayDataset($dataTestingSamples, $dataTestingLabels);

			$classifier = new SVC(Kernel::RBF, 10000);

			$classifier->train($dataTrainingSamples, $dataTrainingLabels);

			$predictedLabels = $classifier->predict($dataTestingSamples);

			$predictedLabels = array_combine(array_keys($dataTestingLabels), array_values($predictedLabels));

			try{

		      $stmt = $conn->prepare("INSERT INTO data_training VALUES(:id_data_training, :id_tweet) ON DUPLICATE KEY UPDATE id_tweet = :id_tweet");
		      
		      $conn->beginTransaction();

		      $i = 1;
		      foreach ($dataTrainingPreprocessing as $id_tweet => $value) {

					$stmt->bindValue(":id_data_training", $i, PDO::PARAM_INT);
			        $stmt->bindParam(":id_tweet", $id_tweet, PDO::PARAM_INT);
			        
			        $stmt->execute();

			        $i++;
		      }
		      $conn->commit();

		      
		      $stmt = $conn->prepare("INSERT INTO data_testing VALUES(:id_data_testing, :id_tweet, :kelas_prediksi)  ON DUPLICATE KEY UPDATE id_tweet = :id_tweet, kelas_prediksi = :kelas_prediksi");
		      
		      $conn->beginTransaction();

		      $i = 1;
		      foreach ($dataTestingPreprocessing as $id_tweet => $value) {

					$stmt->bindValue(":id_data_testing", $i, PDO::PARAM_INT);
			        $stmt->bindParam(":id_tweet", $id_tweet, PDO::PARAM_INT);
			        $stmt->bindParam(":kelas_prediksi", $predictedLabels[$id_tweet], PDO::PARAM_STR);
			        
			        $stmt->execute();
			        $i++;
		      }
		      $conn->commit();


		    }catch(PDOException $e)
		    {
		      $conn->rollback();
		      echo $e->getMessage();
		    }				
		}
		else{
			$err_msg = "Data training dan data testing tidak tersedia";
		}

	}
		
	$stmt = $conn->prepare("SELECT d.id_tweet, dt.hasil_preprocessing, k.keterangan AS kelas_aktual, d.kelas_prediksi FROM data_testing d LEFT JOIN data_tweet dt ON d.id_tweet = dt.id_tweet LEFT JOIN hasil_klaster h ON dt.id_tweet = h.id_tweet LEFT JOIN klaster k ON h.id_klaster = k.id_klaster ");

	$stmt->execute();
	$data_hasil_klasifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if(!isset($data_hasil_klasifikasi)) {
		$err_msg = "Data tidak tersedia";	
	}

?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<title>Analisis Sentimen</title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
    <meta name="viewport" content="width=device-width" />

    <!-- Bootstrap core CSS     -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />

    <!--  Material Dashboard CSS    -->
    <link href="assets/css/material-dashboard.css" rel="stylesheet"/>

    <!--  CSS for Demo Purpose, don't include it in your project     -->
    <link href="assets/css/demo.css" rel="stylesheet" />

    <!--     Fonts and icons     -->
    <link href="http://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" rel="stylesheet">
    <link href='http://fonts.googleapis.com/css?family=Roboto:400,700,300|Material+Icons' rel='stylesheet' type='text/css'>
</head>

<body>

	<div class="wrapper">

	    <div class="main-panel">
			<nav class="navbar navbar-info navbar-fixed-top navbar-color-on-scroll">
				<div class="container-fluid">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand" href="index.php">Analisis Sentimen<div class="ripple-container"></div></a>
					</div>
					<div class="collapse navbar-collapse" id="example-navbar-primary">
						<ul class="nav navbar-nav navbar-right">
							<li>
                                <a href="index.php">
									<i class="material-icons">dashboard</i>
									Dashboard
                                <div class="ripple-container"></div></a>
                            </li>
                            <li class="dropdown">
	                    		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
	                    			<i class="material-icons">assignment</i>Data
									<b class="caret"></b>
								<div class="ripple-container"></div></a>
	                        	<ul class="dropdown-menu dropdown-menu-right">
	                            	<li><a href="tampil_data.php">Tampil Data</a></li>
	                                <li><a href="input_data.php">Input Data</a></li>
	                                <li><a href="reset_data.php">Reset Data</a></li>
	                            </ul>
	                    	</li>
                            <li>
                                <a href="klastering.php">
									<i class="material-icons">assessment</i>
									Klastering
                                </a>
                            </li>
                            <li class="active">
                                <a href="Klasifikasi.php">
									<i class="material-icons">label</i>
									Klasifikasi
                                </a>
                            </li>
						</ul>
					</div>
				</div>
			</nav>

			<div class="content">
				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-4 col-md-12">
							<div class="card card-nav-tabs">
	                            <div class="card-header" data-background-color="purple">
									<div class="nav-tabs-navigation">
										<div class="nav-tabs-wrapper">
											<span class="nav-tabs-title">PROSES KLASIFIKASI</span>
											<ul class="nav nav-tabs" data-tabs="tabs">
												
											</ul>
										</div>
									</div>
								</div>
	                            <div class="card-content table-responsive">
	                                <div class="text-center">
	                                	<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		                                	<input type="submit" name="klasifikasi" class="btn btn-primary" value="Lakukan Klasifikasi">
	                                	</form>
	                                </div>
	                            </div>
	                        </div>
						</div>
						<?php if (!empty($data_hasil_klasifikasi)) { ?>
						<div class="col-lg-8 col-md-12">
							<div class="card card-nav-tabs">
	                            <div class="card-header" data-background-color="green">
									<div class="nav-tabs-navigation">
										<div class="nav-tabs-wrapper">
											<span class="nav-tabs-title">HASIL KLASIFIKASI</span>
											<ul class="nav nav-tabs" data-tabs="tabs">
												
											</ul>
										</div>
									</div>
								</div>
	                            <div class="card-content table-responsive">
	                                <div class="tab-content">
										<table id="data_training_positif" class="table table-hover">
											<thead class="text-warning">
												<tr>
													<th>No</th>
													<th>ID Tweet</th>
													<th>Hasil Preprocessing</th>
													<th>Kelas Aktual</th>
													<th>Kelas Prediksi</th>
												</tr>
											</thead>
											<tbody>
												<?php 
													$i = 1;
													foreach ($data_hasil_klasifikasi as $value) {
														extract($value);
														echo '<tr>
															<td>'.$i.'</td>
															<td>'.$id_tweet.'</td>
															<td>'.$hasil_preprocessing.'</td>
															<td>'.$kelas_aktual.'</td>
															<td>'.$kelas_prediksi.'</td>
														</tr>';
														$i++;		
													}

												?>

											</tbody>
										</table>
									</div>
	                            </div>
	                        </div>
						</div>
						<?php } else { ?>
						  	<div class="col-lg-6">
						  	<?php
				                if(isset($err_msg)){
				                  echo '
				                  <div class="alert alert-danger">
									<div class="container-fluid">
									  <div class="alert-icon">
									    <i class="material-icons">error_outline</i>
									  </div>
									  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
										<span aria-hidden="true"><i class="material-icons">clear</i></span>
									  </button>
									  '.$err_msg.'
									</div>
								  </div>';
				                }
			              	?> 
			              	</div>
						<?php } ?>
					</div>
				</div>
			</div>

			<footer class="footer">
				<div class="container-fluid">
					<p class="copyright pull-right">
						&copy; <script>document.write(new Date().getFullYear())</script> <a href="http://www.creative-tim.com">Creative Tim</a>, made with love for a better web
					</p>
				</div>
			</footer>
		</div>
	</div>

</body>

	<!--   Core JS Files   -->
	<script src="assets/js/jquery-3.1.0.min.js" type="text/javascript"></script>
	<script src="assets/js/bootstrap.min.js" type="text/javascript"></script>
	<script src="assets/js/material.min.js" type="text/javascript"></script>

	<!-- Material Dashboard javascript methods -->
	<script src="assets/js/material-dashboard.js"></script>

	<!-- Material Dashboard DEMO methods, don't include it in your project! -->
	<script src="assets/js/demo.js"></script>

	<script type="text/javascript">
    	$(document).ready(function(){


    	});
	</script>
</html>
