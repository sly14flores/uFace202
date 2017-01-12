<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title>DTR Manager - PGLU</title>

    <!-- Bootstrap core CSS -->
    <link href="dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="sticky-footer-navbar.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="assets/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	<style type="text/css">
	
		.console .panel-body {

			background-color: #282828;
			font-size: 12px;

		}
		
		.console .panel-body .output {
			
			height: 250px;
			font-family: 'Lucida Console';
			overflow: auto;
			
		}
		
		.console .panel-body .success-response {
			display: block;
			color: #17b53c;
		}
		
		.console .panel-body .info-response {
			display: block;			
			color: #1ec9c3;
		}		
		
		.console .panel-body .error-response {
			display: block;			
			color: #ef2f2f;
		}
		
		.progress {
			position: relative;
			margin-top: 5px;
		}
		
		.progress-text {
			width: 100%;
			position: absolute;
			top: 0;
			left: 0;
			color: #000;
			text-align: center;
			font-size: 12px;
			padding-top: 3px;
		}
		
	</style>
  </head>

  <body ng-app="appImport" ng-controller="appImportCtrl">

    <!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">DTR Manager</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="index.php">Log Files</a></li>
            <li class="active"><a href="import.php">Import</a></li>
            <li><a href="backlogs.php">Backlogs</a></li>			
            <li><a href="about.php">About</a></li>
            <!--<li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li><a href="#">Action</a></li>
                <li><a href="#">Another action</a></li>
                <li><a href="#">Something else here</a></li>
                <li role="separator" class="divider"></li>
                <li class="dropdown-header">Nav header</li>
                <li><a href="#">Separated link</a></li>
                <li><a href="#">One more separated link</a></li>
              </ul>
            </li>-->
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
	
    <!-- Begin page content -->
    <div class="container">
	
      <div class="page-header">
        <h1>Upload and Import logs</h1>
      </div>
	  <div class="row">
		<div class="col-lg-6">
			<form>
			  <div class="form-group">
				<div class="col-sm-12">	  
					<ol class="breadcrumb">
						<li class="active"><strong>Log File</li>
					</ol>
					<form class="form-inline">
					  <div class="form-group">
						<label class="control-label">Source:&nbsp;</label>
						<select class="form-control" ng-model="view.source" select-source>
							<option value="-">-</option>
							<option value="uface202_mdb">Uface202 dat mdb</option>							
							<option value="uface202_dat">Uface202 dat file</option>
							<option value="nitgen">Nitgen mdb</option>							
						</select>
					  </div>				
					</form>
					<p>&nbsp;</p>
					<input type="file" name="logFile" id="logFile" file-model="view.logFile" ng-disabled="view.logFileDisabled">
					<div class="checkbox" ng-show="view.pFDisabled">
						<label>
						  <input type="checkbox" name="usePreviousFile" ng-model="view.usePreviousFile"> Use previously uploaded file {{view.pf}}
						</label>
					</div>
				</div>
				<div class="col-sm-12" ng-show="view.uploadProgressContainer">
					<div class="progress">
						<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: {{view.uploadProgress}}%;"></div>
						<div class="progress-text">uploading</div>
					</div>
				</div>
			  </div>			
			  <!--<div class="form-group"><div class="col-sm-12"><h3>ID</h3></div></div>
			  <div class="form-group">
				<div class="col-sm-6">
					<label for="" class="control-label">From:</label>				
					<input type="text" class="form-control" placeholder="0" ng-model="frmImport.idFrom">
				</div>
				<div class="col-sm-6">
					<label for="" class="control-label">To:</label>				
					<input type="text" class="form-control" placeholder="99999" ng-model="frmImport.idTo">
				</div>				
			  </div>-->
			  <div class="form-group"><div class="col-sm-12"><h3>Date</h3></div></div>
			  <div class="form-group">
				<div class="col-sm-6">
					<label for="" class="control-label">From:</label>				
					<p class="input-group">
					  <input type="text" class="form-control" uib-datepicker-popup="{{view.format}}" ng-model="frmImport.dateFrom" is-open="view.popupFrom.opened" datepicker-options="dateOptions" ng-required="true" close-text="Close" alt-input-formats="altInputFormats">
					  <span class="input-group-btn">
						<button type="button" class="btn btn-default" ng-click="fromOpen()"><i class="glyphicon glyphicon-calendar"></i></button>
					  </span>
					</p>
				</div>
				<div class="col-sm-6">
					<label for="" class="control-label">To:</label>				
					<p class="input-group">
					  <input type="text" class="form-control" uib-datepicker-popup="{{view.format}}" ng-model="frmImport.dateTo" is-open="view.popupTo.opened" datepicker-options="dateOptions" ng-required="true" close-text="Close" alt-input-formats="altInputFormats">
					  <span class="input-group-btn">
						<button type="button" class="btn btn-default" ng-click="toOpen()"><i class="glyphicon glyphicon-calendar"></i></button>
					  </span>
					</p>
				</div>				
			  </div>
			  <div class="form-group"><div class="col-sm-12"><h3>ID(s)</h3></div></div>			  
			  <div class="form-group">
				<div class="col-sm-6">
					<label for="" class="control-label">From:</label>
					<input type="number" class="form-control" ng-model="frmImport.idFrom">
				</div>
				<div class="col-sm-6">
					<label for="" class="control-label">To:</label>
					<input type="number" class="form-control" ng-model="frmImport.idTo">
				</div>
			  </div>
			  <div class="form-group">&nbsp;</div>			  
			  <button type="button" class="btn btn-primary pull-right" import-logs>Import</button>			  
			</form>
		</div>
		<div class="col-lg-6">
			<div class="console">
				<div class="panel panel-default">
				  <div class="panel-heading">
					<h3 class="panel-title">Status</h3>
				  </div>
				  <div class="panel-body">
					<div class="output"></div>
				  </div>
				</div>
			</div>
			<div ng-show="view.importProgressContainer">
				<p>{{view.importProgressDescription}}</p>
				<div class="progress">
					<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: {{view.importProgress}}%;"></div>
					<div class="progress-text">{{view.importProgressDetail}}</div>
				</div>
			</div>
		</div>
	  </div>

    </div>

    <footer class="footer">
      <div class="container">
        <p class="text-muted">MISD-PGLU, &nbsp; 2016</p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="jquery/1.12.4/jquery.min.js"></script>
	<script src="js/filter.js"></script>	
    <script src="angularjs/angular.min.js"></script>
    <script src="dist/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="assets/js/ie10-viewport-bug-workaround.js"></script>
	<script src="angularjs/utils/ui-bootstrap-tpls-1.3.3.min.js"></script>	
	<script src="controllers/appImport.js"></script>
  </body>
</html>