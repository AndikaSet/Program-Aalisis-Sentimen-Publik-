<?php 
	require_once "koneksi.php";

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
						<div class="col-lg-6 col-md-12 col-md-offset-3">
							<div class="card">
	                            <div class="card-content">
	                            	<div class="col-md-12 text-center">
		                            	<button class="btn btn-danger" data-toggle="modal" data-target="#myModal">
										  Reset Data
										</button>
									</div>
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

	<!-- Modal Core -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="myModalLabel">Reset Data</h4>
	      </div>
	      <div class="modal-body">
	      	Apakah anda yakin akan menghapus seluruh data ?
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default btn-simple" data-dismiss="modal">Tidak</button>
	        <button type="button" class="btn btn-info btn-simple"><a href="proses_reset_data.php">Ya</a></button>
	      </div>
	    </div>
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
    		$("#myModal").click(function(e) {
			    
			    $(".modal-backdrop").remove();
			});
    	});
	</script>
</html>
