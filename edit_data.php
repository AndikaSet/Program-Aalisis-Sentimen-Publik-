<?php 
  require_once "koneksi.php";
  require_once "CsvImport.php";
  require_once "kakas/IndonesianSentenceFormalizer.php";
  require_once "kakas/SentimentScoring.php";
  require_once "vendor/autoload.php";
  

  if(isset($_POST['edit_data'])) {
    
    try{

      $tweet = $_POST['tweet'];

      $formalizer = new IndonesianSentenceFormalizer();
        
      $hasil_formalisasi = $formalizer->normalizeSentence($tweet);
        
      $stopwordFactory = new \Sastrawi\StopwordRemover\StopwordRemoverFactory();
      $stopword  = $stopwordFactory->createStopWordRemover();
      $hasil_stopword_removal =  $stopword->remove($hasil_formalisasi); 

      $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
      $stemmer  = $stemmerFactory->createStemmer();
      $hasil_stemming = $stemmer->stem($hasil_stopword_removal);

      $sentimentScoring = new SentimentScoring();

      $listPositive = $sentimentScoring->getListPositive();

      $listNegative = $sentimentScoring->getListNegative();

      $pos = 0;
      $kata_pos = "";

      $neg = 0;
      $kata_neg = "";

      $hasil_preprocessing = explode(" ", $hasil_stemming);

      foreach ($hasil_preprocessing as $value) {
        if (in_array($value, $listPositive)) {
          $pos++;
          $kata_pos .= " ".$value;
        }
      }

      foreach ($hasil_preprocessing as $value) {
        if (in_array($value, $listNegative)) {
          $neg++;
          $kata_neg .= " ".$value;
        }
      }

      $stmt = $conn->prepare("UPDATE data_tweet SET tweet = :tweet, hasil_preprocessing = :hasil_preprocessing, positif = :positif, kata_positif = :kata_positif, negatif = :negatif, kata_negatif = :kata_negatif WHERE id_tweet = :id_tweet");
      
      $stmt->bindParam(':tweet', $tweet, PDO::PARAM_STR);
      $stmt->bindParam(':hasil_preprocessing', $hasil_stemming, PDO::PARAM_STR);
      $stmt->bindParam(':positif', $pos, PDO::PARAM_INT);
      $stmt->bindParam(':kata_positif', $kata_pos, PDO::PARAM_STR);
      $stmt->bindParam(':negatif', $neg, PDO::PARAM_INT);
      $stmt->bindParam(':kata_negatif', $kata_neg, PDO::PARAM_STR);
      $stmt->bindParam(':id_tweet', $id_tweet, PDO::PARAM_INT);

      $stmt->execute();

      header("location:tampil_data.php");

    }catch(PDOException $e){
        echo $e->getMessage();
        
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
                            <li class="active" class="dropdown">
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
						<div class="col-lg-8 col-md-12">
							<div class="card card-nav-tabs">
	                            <div class="card-header" data-background-color="blue">
									<div class="nav-tabs-navigation">
										<div class="nav-tabs-wrapper">
											<span class="nav-tabs-title">EDIT DATA</span>
											<ul class="nav nav-tabs" data-tabs="tabs">
												
											</ul>
										</div>
									</div>
								</div>
	                            <div class="card-content">
									<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
										<div class="col-md-8 input-group">
											<input type="hidden" name="id_tweet" value="<?php echo $_GET['id_tweet']; ?>">
											<?php 
												if (isset($_GET['id_tweet'])) {
													  $stmt = $conn->prepare("SELECT tweet FROM data_tweet WHERE id_tweet = :id_tweet");
													  $stmt->bindParam(':id_tweet', $_GET['id_tweet'], PDO::PARAM_INT);
													  $stmt->execute();

													  $tweet_edit = $stmt->fetch(PDO::FETCH_NUM);
													  $tweet_edit = $tweet_edit[0];
												}
											?>
											<textarea id="tweet" class="form-control" name="tweet" placeholder="Inputkan tweet" rows="3"><?php echo $tweet_edit; ?></textarea>
											<input type="submit" name="edit_data" class="btn btn-info" value="Simpan">
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

	<!-- Material Dashboard javascript methods -->
	<script src="assets/js/material-dashboard.js"></script>

	<!-- Material Dashboard DEMO methods, don't include it in your project! -->
	<script src="assets/js/demo.js"></script>

	<script type="text/javascript">
    	$(document).ready(function(){

			// Javascript method's body can be found in assets/js/demos.js
        	demo.initDashboardPageCharts();

    	});
	</script>
</html>
