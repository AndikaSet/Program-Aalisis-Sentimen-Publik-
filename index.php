<?php 
	require_once "koneksi.php";
	require_once "vendor/autoload.php";
	require_once "kakas/IndonesianSentenceFormalizer.php";
	require_once "vendor/autoload.php";

	use Phpml\Dataset\ArrayDataset;
	use Phpml\FeatureExtraction\TokenCountVectorizer;
	use Phpml\Tokenization\WordTokenizer;
	use Phpml\CrossValidation\StratifiedRandomSplit;
	use Phpml\FeatureExtraction\TfIdfTransformer;
	use Phpml\Metric\Accuracy;
	use Phpml\Classification\SVC;
	use Phpml\SupportVectorMachine\Kernel;


	//DATA TRAINING
    $stmt = $conn->prepare("SELECT COUNT(*) AS juml FROM data_training");
    $stmt->execute();
    $juml_training = $stmt->fetch(PDO::FETCH_NUM);
    $juml_training = $juml_training[0];

    //DATA TRAINING POSITIF
    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_training d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k ON h.id_klaster = k.id_klaster WHERE k.keterangan = 'positif'");
    $stmt->execute();
    $juml_training_positif = $stmt->fetch(PDO::FETCH_NUM);
    $juml_training_positif = $juml_training_positif[0];

	//DATA TRAINING NEGATIF
    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_training d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k ON h.id_klaster = k.id_klaster WHERE k.keterangan = 'negatif'");
    $stmt->execute();
    $juml_training_negatif = $stmt->fetch(PDO::FETCH_NUM);
    $juml_training_negatif = $juml_training_negatif[0];


	//DATA TESTING
    $stmt = $conn->prepare("SELECT COUNT(*) AS juml FROM data_testing");
    $stmt->execute();
    $juml_testing = $stmt->fetch(PDO::FETCH_NUM);
    $juml_testing = $juml_testing[0];

	//DATA TESTING POSITIF
    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_testing d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k ON h.id_klaster = k.id_klaster WHERE k.keterangan = 'positif'");
    $stmt->execute();
    $juml_testing_positif = $stmt->fetch(PDO::FETCH_NUM);
    $juml_testing_positif = $juml_testing_positif[0];

    //DATA TESTING NEGATIF
    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_testing d LEFT JOIN hasil_klaster h ON d.id_tweet = h.id_tweet LEFT JOIN klaster k ON h.id_klaster = k.id_klaster WHERE k.keterangan = 'negatif'");
    $stmt->execute();
    $juml_testing_negatif = $stmt->fetch(PDO::FETCH_NUM);
    $juml_testing_negatif = $juml_testing_negatif[0];

   	//KLASTER
    $stmt = $conn->prepare("SELECT COUNT(*) AS juml_klaster FROM klaster");
    $stmt->execute();
    $klaster = $stmt->fetch(PDO::FETCH_ASSOC);
    $klaster = $klaster['juml_klaster'];

    //KLASTER
    $stmt = $conn->prepare("SELECT COUNT(h.id_klaster) AS juml_data, c.id_klaster, c.x, c.y FROM Centroid c LEFT JOIN hasil_klaster h ON c.id_klaster = h.id_klaster GROUP BY h.id_klaster");
    $stmt->execute();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    	$data_klaster[] = $row;
    }


	//JUMLAH DATA YANG DIKLASIFIKASI DENGAN BENAR
    $stmt = $conn->prepare("SELECT COUNT(*) AS juml_benar FROM data_testing d WHERE d.kelas_prediksi = (SELECT keterangan FROM hasil_klaster h LEFT JOIN klaster k ON h.id_klaster = k.id_klaster WHERE h.id_tweet = d.id_tweet)");
    $stmt->execute();
    $juml_benar = $stmt->fetch(PDO::FETCH_NUM);
    $juml_benar = $juml_benar[0];

    //MENGHITUNG AKURASI
    if($juml_testing){
    	$akurasi = ($juml_benar / $juml_testing) * 100;    	
    }else{
    	$akurasi = 0;
    }

	if(isset($_POST['klasifikasi'])){
		$tweet = $_POST['tweet'];

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

			$formalizer = new IndonesianSentenceFormalizer();
	        
	        $hasil_formalisasi = $formalizer->normalizeSentence($tweet);
	                
		    $stopwordFactory = new \Sastrawi\StopwordRemover\StopwordRemoverFactory();
		    $stopword  = $stopwordFactory->createStopWordRemover();
		    $hasil_stopword_removal =  $stopword->remove($hasil_formalisasi); 

		    $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
		    $stemmer  = $stemmerFactory->createStemmer();
		    $hasil_stemming = array($stemmer->stem($hasil_stopword_removal));

	
			$vectorizer = new TokenCountVectorizer(new WordTokenizer());
			$vectorizer->fit($dataTrainingSamples);
			$vectorizer->transform($dataTrainingSamples);


			$vectorizer->fit($hasil_stemming);
			$vectorizer->transform($hasil_stemming);


			$dataTraining = new ArrayDataset($dataTrainingSamples, $dataTrainingLabels);

			$classifier = new SVC(Kernel::RBF, 10000);

			$classifier->train($dataTrainingSamples, $dataTrainingLabels);

			$predictedLabels = $classifier->predict($hasil_stemming);	
		}
		else{
			$err_msg = "Data tidak terseida, inputkan data dan lakukan proses klastering terlebih dahulu";
		}
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
			<nav class="navbar navbar-info navbar-absolute">
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
							<li class="active">
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
                            <li>
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
						<div class="col-lg-3 col-md-6 col-sm-6">
							<div class="card card-stats">
								<div class="card-header" data-background-color="purple">
									<i class="material-icons">content_paste</i>
								</div>
								<div class="card-content">
									<p class="category">Data Training</p>
									<?php 
									if(isset($juml_training)){
										echo '<h3 class="title">'.$juml_training.'&nbsp;<small>data</small></h3>';
									}else{
										echo '<h3 class="title">- &nbsp;<small>data</small></h3>';
									} ?>
									
								</div>
								<div class="card-footer">
									<table class="table">
										<tbody>
											<tr>
												<td class="td-actions">
													<i class="material-icons">add</i>
												</td>
												<?php 
													if(isset($juml_training_positif)){
														echo '<td>'.$juml_training_positif.' <small>data</small></td>';
													}else{
														echo '<td></td>';
													} 
												?>
											</tr>
											<tr>
												<td class="td-actions">
													<i class="material-icons">remove</i>
												</td>
												<?php 
													if(isset($juml_training_negatif)){
														echo '<td>'.$juml_training_negatif.' <small>data</small></td>';
													}else{
														echo '<td></td>';
													} 
												?>
											</tr>
										</tbody>
									</table>								
								</div>
							</div>
						</div>
						<div class="col-lg-3 col-md-6 col-sm-6">
							<div class="card card-stats">
								<div class="card-header" data-background-color="green">
									<i class="material-icons">find_in_page</i>
								</div>
								<div class="card-content">
									<p class="category">Data Testing</p>
									<?php 
									if(isset($juml_testing)){
										echo '<h3 class="title">'.$juml_testing.'&nbsp;<small>data</small></h3>';
									}else{
										echo '<h3 class="title">- &nbsp;<small>data</small></h3>';
									} ?>
								</div>
								<div class="card-footer">
									<table class="table">
										<tbody>
											<tr>
												<td class="td-actions">
													<i class="material-icons">add</i>
												</td>
												<?php 
													if(isset($juml_testing_positif)){
														echo '<td>'.$juml_testing_positif.' <small>data</small></td>';
													}else{
														echo '<td></td>';
													} 
												?>
											</tr>
											<tr>
												<td class="td-actions">
													<i class="material-icons">remove</i>
												</td>
												<?php 
													if(isset($juml_testing_negatif)){
														echo '<td>'.$juml_testing_negatif.' <small>data</small></td>';
													}else{
														echo '<td></td>';
													} 
												?>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="col-lg-3 col-md-6 col-sm-6">
							<div class="card card-stats">
								<div class="card-header" data-background-color="orange">
									<i class="material-icons">radio_button_checked</i>
								</div>
								<div class="card-content">
									<p class="category">Klaster</p>
									<h3 class="title">
									<?php 
										if (isset($klaster)) {
											echo $klaster;
										}else{
											echo 0;
										}

									?>
										
									</h3>
								</div>
								<div class="card-footer">
									<table class="table">
										<thead>
											<tr>
												<th rowspan="2">ID Klaster</th>
												<th rowspan="2">Jumlah Data</th>
												<th colspan="2">Centroid</th>
											</tr>
											<tr>
												<th>x</th>
												<th>y</th>
											</tr>
										</thead>
										<tbody>
											<?php
												if (isset($data_klaster)) {
													foreach ($data_klaster as $value) {
														extract($value);
														echo '<tr>
															<td>'.$id_klaster.'</td>
															<td>'.$juml_data.'</td>
															<td>'.$x.'</td>
															<td>'.$y.'</td>
														</tr>';
													} 	
												} 
												
											?>


										</tbody>
									</table>
								</div>
							</div>
						</div>

						<div class="col-lg-3 col-md-6 col-sm-6">
							<div class="card card-stats">
								<div class="card-header" data-background-color="red">
									<i class="material-icons">check_box</i>
								</div>
								<div class="card-content">
									<p class="category">Akurasi</p>
									<?php 
									if(isset($akurasi)){
										echo "<h3 class='title'>".$akurasi."%</h3>";
									}else{
										echo '<h3 class="title"></h3>';
									} ?>
									
								</div>
								<div class="card-footer">
									<table class="table ">
										<tbody>
											<tr>
												<td>Data yang diklasifikasi dengan benar : </td>
												<?php 
												if(isset($juml_testing) && isset($juml_benar)){
													echo '<td>'.$juml_benar.'</td>';
												}else{
													echo '<td></td>';
												} ?>
											</tr>
											<tr>
												<td>Jumlah seluruh data testing : </td>
												<?php 
												if(isset($juml_testing)){
													echo '<td>'.$juml_testing.'</td>';
												}else{
													echo '<td></td>';
												} ?>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-lg-6 col-md-12">
							<div class="card card-nav-tabs">
								<div class="card-header" data-background-color="purple">
	                                <h4 class="title">Visualisasi Klaster</h4>
								</div>

								<div class="card-content">
								    <div id="myDiv" style="width: 240px; height: 300px;"><!-- Plotly chart will be drawn inside this DIV --></div>	

								    <br><br><br><br><br><br>
								</div>
							</div>
						</div>

						<div class="col-lg-6 col-md-12">
							<div class="card">
	                            <div class="card-header" data-background-color="orange">
	                                <h4 class="title">Klasifikasi</h4>
	                                <p class="category">Inputkan teks untuk diklasifikasikan</p>
	                            </div>
	                            <div class="card-content">
	                                <?php
						                if(isset($predictedLabels)){
						                  echo '
						                  <div class="alert alert-info">
											<div class="container-fluid">
											  <div class="alert-icon">
											    <i class="material-icons">label</i>
											  </div>
											  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
												<span aria-hidden="true"><i class="material-icons">clear</i></span>
											  </button>
											  '.strtoupper($predictedLabels[0]).'
											</div>
										  </div>';
						                } else if(isset($err_msg)){
						                  echo '
						                  <div class="alert alert-danger">
											<div class="container-fluid">
											  <div class="alert-icon">
											    <i class="material-icons">error_outline</i>
											  </div>
											  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
												<span aria-hidden="true"><i class="material-icons">clear</i></span>
											  </button>
											  '.strtoupper($err_msg).'
											</div>
										  </div>';
						                } {
						                	# code...
						                }
					              	?> 
	                                <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
										<div class="col-md-12 input-group">
											<textarea id="tweet" class="form-control" name="tweet" placeholder="Inputkan tweet" rows="3"></textarea>
											<input type="submit" name="klasifikasi" class="btn btn-warning" value="Klasifikasikan">
										</div>

									</form>
	                            </div>
	                        </div>
						</div>
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

	<!--  Charts Plugin -->
	<script src="assets/js/chartist.min.js"></script>

	<!--  Notifications Plugin    -->
	<script src="assets/js/bootstrap-notify.js"></script>

	<!--  Google Maps Plugin    -->
	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js"></script>

	<!-- Material Dashboard javascript methods -->
	<script src="assets/js/material-dashboard.js"></script>

	<!-- Material Dashboard DEMO methods, don't include it in your project! -->
	<script src="assets/js/demo.js"></script>


	<script type="text/javascript" src="assets/js/plotly-latest.min.js"></script>
	<script type="text/javascript">
    	$(document).ready(function(){

	      var d3 = Plotly.d3
	      var data = [];
	      var arr_id_vektor = [];
	      var arr_id_klaster = [];
	        
	      $.ajax({
	          url: 'get_data_klaster.php', 
	          dataType : 'json',
	          type: "post",
	          success: function(response){
	            if(response){
	                if(Object.keys(response).length == 2){
		                $.each(response, function(i, response) {
		                  num = i;

		                  var data_klaster = {
		                    x: response.x,
		                    y: response.y,
		                    mode: 'markers',
		                    type: 'scatter',
		                    name: num,
		                    text: response.id_vektor,
		                    marker: { size: 12 }
		                  };
		                
		                  Plotly.addTraces('myDiv', data_klaster);

		                  i++;
		                });		            		
	            	}
	            }
	          }
	      });


	      var layout = {
	        xaxis: {
	          range: [-1, 5]
	        },
	        yaxis: {
	          range: [-1, 6]
	        },
	        height: 430,
	        width: 570,
	        showlegend: true
	      };

	      Plotly.newPlot('myDiv', data, layout);

    	});

	</script>
</html>
