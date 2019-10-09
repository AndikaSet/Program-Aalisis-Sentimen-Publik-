<?php 
	require_once "koneksi.php";
    require_once "vendor/autoload.php";

    session_start();

    use Phpml\Clustering\KMeans;

    $stmt = $conn->prepare("SELECT id_tweet, positif, negatif FROM data_tweet");
    $stmt->execute();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      extract($row);
      $data[] = array($positif, $negatif);
    }


    $stmt = $conn->prepare("SELECT COUNT(*) juml_hasil FROM hasil_klaster");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $juml_hasil = $row['juml_hasil'];

    if ($juml_hasil > 0) {
		$stmt = $conn->prepare("SELECT * FROM klaster");
	    $stmt->execute();
	    $data_klaster = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (isset($_POST['simpan_label'])) {
    	extract($_POST);
    	
    	$stmt = $conn->prepare("UPDATE klaster SET keterangan = :keterangan WHERE id_klaster = :id_klaster");

    	$conn->beginTransaction();

    	foreach ($id_klaster as $key => $value) {
			$stmt->bindParam(':id_klaster', $id_klaster[$key], PDO::PARAM_STR);
        	$stmt->bindParam(':keterangan', $label[$key], PDO::PARAM_STR);    		
    		$stmt->execute();
    	}
        
		$conn->commit();
	    
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
                            <li class="active">
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
						<div class="col-lg-4 col-md-12">
							<div class="container-fluid">
								<div class="row">
									<div class="col-lg-12 col-md-12">
										<div class="card card-nav-tabs">
											<div class="card-header" data-background-color="purple">
												<div class="nav-tabs-navigation">
													<div class="nav-tabs-wrapper">
														<span class="nav-tabs-title">PROSES KLASTERING</span>
														<ul class="nav nav-tabs" data-tabs="tabs">
														</ul>
													</div>
												</div>
											</div>
											<div class="card-content">
							                   <div id="myDiv" style="width: 230px; height: 270px;"><!-- Plotly chart will be drawn inside this DIV --></div>

							                   <br>
							                   <br>
							                   <form method="POST" action="<?php echo 
							                    $_SERVER['PHP_SELF'] ?>">
							                   		<div id="klaster_button" class="text-center">
					 					                <input type="submit" id="proses_klaster" class="btn btn-primary" value="Proses Klaster">
							                   		</div>
							                   </form>

											</div>
										</div>
									</div>

									<?php  
										if (isset($data_klaster)) { ?>
										<div class="col-lg-12 col-md-12">
											<div class="card card-nav-tabs">
												<div class="card-header" data-background-color="blue">
													<div class="nav-tabs-navigation">
														<div class="nav-tabs-wrapper">
															<span class="nav-tabs-title">TENTUKAN LABEL</span>
															<ul class="nav nav-tabs" data-tabs="tabs">
															</ul>
														</div>
													</div>
												</div>

												<div class="card-content">
													<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
														<div class="col-sm-12">			
													<?php  
														foreach ($data_klaster as $value) {
															extract($value);
															echo '<input type="hidden" name="id_klaster[]" value="'.$id_klaster.'">
																<input type="text" name="label[]" class="form-control" placeholder="'.$id_klaster.'">
															';

														}
														echo '<input type="submit" name="simpan_label" class="btn btn-info" value"Simpan">';

															echo '</div></form>'; 

													?>


												</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="col-lg-8 col-md-12">
							<div class="card card-nav-tabs">
								<div class="card-header" data-background-color="red">
									<div class="nav-tabs-navigation">
										<div class="nav-tabs-wrapper">
											<span class="nav-tabs-title">HASIL KLASTER</span>
											<ul class="nav nav-tabs" data-tabs="tabs">
											</ul>
										</div>
									</div>
								</div>

								<div class="card-content table-responsive">
				                    <table class="table table-condensed table-hover">
				                      <thead>
				                      	  <tr>
					                        <th rowspan="2">ID Tweet</th>
					                        <th rowspan="2">Tweet</th>
					                        <th rowspan="2">Hasil Preprocessing</th>
					                        <th colspan="4" style="text-align: center">Bobot</th>
					                        <th rowspan="2">ID Klaster</th>
					                      </tr>
					                      <tr>
					                        <th>Positif</th>
					                        <th>Kata Positif</th>
					                        <th>Negatif</th>
					                        <th>Kata Negatif</th>
					                      </tr>	
				                      </thead>
				                      <tbody>
					                      <?php 

					                        $stmt = $conn->prepare("SELECT * FROM data_tweet LEFT JOIN hasil_klaster ON data_tweet.id_tweet = hasil_klaster.id_tweet");
					                        $stmt->execute();
					                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

					                        if($data){
					                          $i = 1;
					                          foreach ($data as $value) {
					                            extract($value);
					                            echo "<tr>";
					                              echo "<td>".$i."</td>";
					                              echo "<td>".$tweet."</td>";
					                              echo "<td>".$hasil_preprocessing."</td>";
					                              echo "<td>".$positif."</td>";
					                              echo "<td>".$kata_positif."</td>";
					                              echo "<td>".$negatif."</td>";
					                              echo "<td>".$kata_negatif."</td>";
					                              echo "<td>".$id_klaster."</td>";
					                            echo "</tr>";
					                            $i++;
					                          }
					                        }

					                      ?>	
				                      </tbody>
				                      
				                    </table>
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

	<script type="text/javascript" src="assets/js/plotly-latest.min.js"></script>
	<script type="text/javascript">
    	$(document).ready(function(){

		    $('#proses_klaster').on('click',function(){
		        var simpan = $('<input type="submit" id="simpan_klaster" class="btn btn-primary" value="Simpan Klaster">');
		        var refresh = $('<input type="submit" id="refresh_klaster" class="btn btn-primary" value="Ulangi Proses">');
		        
		        $('#proses_klaster').remove();
		        $('#klaster_button').append(simpan);
		        $('#klaster_button').append(refresh);

			      var d3 = Plotly.d3
			      var data = [];
			      var arr_id_vektor = [];
			      var arr_id_klaster = [];
			      var centroid = [];

			      $.ajax({
			          url: 'get_new_klaster.php', 
			          dataType : 'json',
			          type: "post",
			          success: function(response){
			            if(response){

			            	arr_centroid = response.centroid;
			            	
			                $.each(response.klaster, function(i, response) {
			                  num = i;
			                  arr_id_vektor[num] = response.id_vektor;
			                  arr_id_klaster[num] = 'C'+i;

			                  var data_klaster = {
			                    x: response.x,
			                    y: response.y,
			                    mode: 'markers',
			                    type: 'scatter',
			                    name: 'C'+num,
			                    text: response.id_vektor,
			                    marker: { size: 12 }
			                  };
			                
			                Plotly.addTraces('myDiv', data_klaster);

			                i++;
			              });
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
			        title:'HASIL KLASTER',
			        height: 360,
			        width: 400,
			        showlegend: true
			      };

			      Plotly.newPlot('myDiv', data, layout);

   		        $('#simpan_klaster').click(function(){

		        	$.post('set_data_klaster.php', {id_vektor: arr_id_vektor, id_klaster : arr_id_klaster, centroid : arr_centroid}, function(response){
		            if(!response.status){
		              alert("Error saat proses penyimpanan");
		              return;
		            }
		            if(response.status != 'OK'){
		              alert(response.status);
		              return;
		            }

		            window.location.href = "simpan_klaster.php";
		        });
		      });

		    });

			$('#klaster_button').on('click', '#refresh_klaster', function(event){
		        
		        event.preventDefault();
				
				  var d3 = Plotly.d3
			      var data = [];
			      var arr_id_vektor = [];
			      var arr_id_klaster = [];
			      var centroid = [];

			      $.ajax({
			          url: 'get_new_klaster.php', 
			          dataType : 'json',
			          type: "post",
			          success: function(response){
			            if(response){

			            	arr_centroid = response.centroid;

			                $.each(response.klaster, function(i, response) {
			                  num = i;
			                  arr_id_vektor[num] = response.id_vektor;
			                  arr_id_klaster[num] = 'C'+i;

			                  var data_klaster = {
			                    x: response.x,
			                    y: response.y,
			                    mode: 'markers',
			                    type: 'scatter',
			                    name: 'C'+i,
			                    text: response.id_vektor,
			                    marker: { size: 12 }
			                  };
			                
			                Plotly.addTraces('myDiv', data_klaster);

			                i++;
			              });
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
			        title:'HASIL KLASTER',
			        height: 360,
			        width: 400,
			        showlegend: true
			      };

			      Plotly.newPlot('myDiv', data, layout);

   		        $('#simpan_klaster').click(function(){

		        	$.post('set_data_klaster.php', {id_vektor: arr_id_vektor, id_klaster : arr_id_klaster, centroid : arr_centroid}, function(response){
		            if(!response.status){
		              alert("Error saat proses penyimpanan");
		              return;
		            }
		            if(response.status != 'OK'){
		              alert(response.status);
		              return;
		            }

		            window.location.href = "simpan_klaster.php";
		        });
		      });		        
  
		    });

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
		        title:'HASIL KLASTER',
		        height: 360,
		        width: 400,
		        showlegend: true
		      };

		      Plotly.newPlot('myDiv', data, layout);


    	});

	</script>
</html>
