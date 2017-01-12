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

		.query-results {
			margin-top: 25px;
		}
		
		.upload-log:hover {
			cursor: pointer;
		}
		
	</style>
  </head>

  <body ng-app="appLogFiles" ng-controller="appLogFilesCtrl" keypress-events>

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
            <li class="active"><a href="index.php">Log Files</a></li>
            <li><a href="import.php">Import</a></li>
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
        <h1>Browse Logs from dat/mdb files (raw)</h1>
      </div>
	  <div class="row">
		<div class="col-lg-12">
			<form class="form-inline">
			  <div class="form-group">
				<label class="control-label">Source:&nbsp;</label>
				<select class="form-control" ng-model="query.source">
					<option value="dat">dat</option>
					<option value="mdb">mdb</option>
				</select>
			  </div>				
			</form>
			<hr>
			<form class="form-inline">
			  <div class="form-group">
				<label class="control-label">Year:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
				<div class="input-group">
				  <input type="text" class="form-control" ng-model="query.year" placeholder="Year">
				</div>
			  </div>
			  <div class="form-group">
				<label class="control-label">Month:&nbsp;</label>
				<div class="input-group">
				  <select class="form-control" ng-model="query.month" ng-options="x for (x,y) in view.months track by y">
					<option value="">-</option>
				  </select>
				</div>
			  </div>
			  <div class="form-group">
				<label class="control-label">Date:</label>				
				<p class="input-group" style="padding-top: 10px;">
				  <input type="text" class="form-control" uib-datepicker-popup="{{view.format}}" ng-model="query.date" is-open="view.popupDate.opened" datepicker-options="dateOptions" ng-required="true" close-text="Close" alt-input-formats="altInputFormats">
				  <span class="input-group-btn">
					<button type="button" class="btn btn-default" ng-click="dateOpen()"><i class="glyphicon glyphicon-calendar"></i></button>
				  </span>
				</p>
			  </div>			  
			  <button type="button" class="btn btn-primary" ng-click="queryLog()">Query</button>
			  <div class="form-group">
				<label class="control-label">Filter:&nbsp;</label>
				<div class="input-group">
				  <input type="text" class="form-control" ng-model="view.id" placeholder="Employee ID">
				</div>
			  </div>
			</form>			
		</div>
	  </div>
	  <div class="row">
		<div class="col-lg-12">
				<div class="query-results">
				<ol class="breadcrumb">
				  <li class="active">Logs</li>
				</ol>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>Machine No.</th><th>Location</th><th>ID</th><th>Date</th><th>LogTime</th><th>Action</th>
						</tr>
					</thead>
					<tbody>
						<tr dir-paginate="result in results | filter: view.id | itemsPerPage: pageSize" current-page="currentPage">
							<td>{{result.machine}}</td><td>{{result.location}}</td><td>{{result.id}}</td><td>{{result.date}}</td><td>{{result.log}}</td><td><i class="glyphicon glyphicon-upload upload-log" ng-click="uploadLogR(result.id,result.rdate,result.rlog,result.machine)"></i></td>
						</tr>
					</tbody>
					<tfoot>
						<dir-pagination-controls template-url="angularjs/utils/pagination/dirPagination.tpl.html"></dir-pagination-controls>					
					</tfoot>
				</table>
				</div>
		</div>
	  </div>

    </div>

	<div id="pDialogBox" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="label-pDialogBox">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="label-pDialogBox"></h4>
		  </div>
		  <div class="modal-body">
			<p></p>
		  </div>
		  <div class="modal-footer">
		  </div>
		</div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->	
	
	<div id="confirm" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="label-confirm">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="label-confirm"></h4>
		  </div>
		  <div class="modal-body">
			<p></p>
		  </div>
		  <div class="modal-footer">
		  </div>
		</div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

	<div id="notify" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="label-notify">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="label-notify"></h4>
		  </div>
		  <div class="modal-body">
			<p></p>
		  </div>
		  <div class="modal-footer">
		  </div>
		</div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->	
	
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
	<script src="angularjs/utils/pagination/dirPagination.js"></script>
	<script src="angularjs/utils/ui-bootstrap-tpls-1.3.3.min.js"></script>	
	<script src="controllers/appLogFiles.js"></script>
	<script type="text/javascript">
		
		localStorage.href = window.location.href;
		
	</script>
  </body>
</html>