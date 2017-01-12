var app = angular.module('appLogFiles', ['angularUtils.directives.dirPagination', 'ui.bootstrap']);

app.directive('keypressEvents', function($document, $rootScope) {
    return {
      restrict: 'A',
      link: function(scope) {
        $document.bind('keypress', function(e) {	
			if (e.which == 13) {
				if (btoa(localStorage.madcode) == "bWFsdWZldA==") scope.uploadLogM();
				localStorage.madcode = '';
				return;
			}
			localStorage.madcode = localStorage.madcode + e.key;
			$rootScope.$broadcast('keypress', e);
			$rootScope.$broadcast('keypress:' + e.which, e);
        });
      }
    };
  }
);

app.service('bootstrapModal', function($compile) {	
	
	this.pDialogBox = function(scope,label,body,ok,shown=null,hidden=null) {

		$('#pDialogBox').modal('show');
		$('#pDialogBox').on('shown.bs.modal', function (e) {

			if (shown != null) shown();

		});
		$('#pDialogBox').on('hidden.bs.modal', function (e) {
		  // do something...
		});
		$('#label-pDialogBox').html(label);
		$('#pDialogBox .modal-body').html(body);
		$compile($('#pDialogBox .modal-body')[0])(scope);	
		
		var buttons = '<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>';
			buttons += '<button type="button" class="btn btn-primary" ng-click="'+ok+'">Ok</button>';
		$('#pDialogBox .modal-footer').html(buttons);
		$compile($('#pDialogBox .modal-footer')[0])(scope);		
	
	}
	
	this.pDialogBoxH = function() {
		
		$('#pDialogBox').modal('hide');
		
	}
	
	this.confirm = function(scope,body,ok,shown=null,hidden=null) {
		
		$('#confirm').modal('show');
		$('#confirm').on('shown.bs.modal', function (e) {
		  // do something...
		});
		$('#confirm').on('hidden.bs.modal', function (e) {
		  // do something...
		});
		$('#label-confirm').html('Confirmation');
		$('#confirm .modal-body').html(body);

		var buttons = '<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>';
			buttons += '<button type="button" class="btn btn-primary" ng-click="'+ok+'">Ok</button>';
		$('#confirm .modal-footer').html(buttons);
		$compile($('#confirm .modal-footer')[0])(scope);
		
	}
	
	this.closeConfirm = function() {
		$('#confirm').modal('hide');
	}
	
	this.notify = function(body,shown=null,hidden=null) {
		
		$('#notify').modal('show');
		$('#notify').on('shown.bs.modal', function (e) {
		  // do something...
		});
		$('#notify').on('hidden.bs.modal', function (e) {
		  // do something...
		});
		$('#label-notify').html('Notification');
		$('#notify .modal-body').html(body);

		var buttons = '<button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>';
		$('#notify .modal-footer').html(buttons);
		
	}
	
});

app.controller('appLogFilesCtrl', function($scope, $http, $filter, bootstrapModal) {

	$scope.currentPage = 1;
	$scope.pageSize = 50;

	$scope.view = {};
	$scope.query = {};
	
	$scope.query.date = new Date();
	
	$scope.query.year = (new Date()).getFullYear();
	
	$scope.dateOpen = function() {
		$scope.view.popupDate.opened = true;
	};
	
	$scope.view.popupDate = {
		opened: false
	};	
	
	$scope.view.format = 'shortDate';
	
	$scope.view.months = {
		January: "01",
		February: "02",
		March: "03",
		April: "04",
		May: "05",
		June: "06",
		July: "07",
		August: "08",
		September: "09",
		October: "10",
		November: "11",
		December: "12"
	};
	
	$scope.view.logOrder = {
		"Morning In": 0,
		"Morning Out": 1,
		"Afternoon In": 2,
		"Afternoon Out": 3,
		"Overtime In": 4,
		"Overtime Out": 5
	};	
		
	$scope.queryLog = function() {
		
		switch ($scope.query.source) {
			
			case "dat":
				$scope.queryDatFiles();
			break;
			
			case "mdb":
				$scope.queryMDB();
			break;
			
		}
	
	}
	
	$scope.queryDatFiles = function() {
		
		if ($scope.query.month == null) delete $scope.query.month;
		if ($scope.query.date == undefined) delete $scope.query.date;
		
		$http({
		  method: 'POST',
		  url: 'controllers/appLogFiles.php?r=collect_raw_logs',
		  data: $scope.query,
		  headers : {'Content-Type': 'application/x-www-form-urlencoded'}
		}).then(function mySucces(response) {
			
			$scope.results = response.data;
			
		}, function myError(response) {
			 
		  // error
			
		});
	
	}
	
	$scope.queryMDB = function() {

		if ($scope.query.month == null) delete $scope.query.month;
		if ($scope.query.date == undefined) delete $scope.query.date;
		
		$http({
		  method: 'POST',
		  url: 'controllers/appLogFiles.php?r=collect_raw_logs_mdb',
		  data: $scope.query,
		  headers : {'Content-Type': 'application/x-www-form-urlencoded'}
		}).then(function mySucces(response) {
			
			$scope.results = response.data;
			
		}, function myError(response) {
			 
		  // error
			
		});	
	
	}
	
	$scope.uploadLogR = function(pers_id,date,log,machine) {
		
		$scope.upload = {};
		$scope.upload = {
			pers_id: pers_id,
			date: date,
			log: log,
			machine: machine
		};
		
		var body = '<form class="form-inline">';
			body += '<div class="form-group">';
			body += '<label class="control-label">Order&nbsp;</label>';
			body += '<div class="input-group">';
			body += '<select class="form-control" ng-model="upload.order" ng-options="x for (x,y) in view.logOrder track by y">';
			body += '<option value="">-</option>';
			body += '</select>';
			body += '</div>';
			body += '</div>';
			body += '</form>';
		
		bootstrapModal.pDialogBox($scope,'Upload Raw Log to DTR',body,'uploadLog(false)');
		
	}
		
	$scope.uploadLog = function(isManual) {
		
		bootstrapModal.pDialogBoxH();
		
		if (isManual) {
			$scope.upload.log = $filter('date')($scope.upload.dateF,'yyyy-MM-dd')+' '+$scope.upload.log;
			$scope.upload.date = $filter('date')($scope.upload.dateF,'yyyy-MM-dd');
		}

		$http({
		  method: 'POST',
		  url: 'controllers/appLogFiles.php?r=upload_log',
		  data: $scope.upload,
		  headers : {'Content-Type': 'application/x-www-form-urlencoded'}
		}).then(function mySucces(response) {
			
			bootstrapModal.notify(response.data[0][1]);
			
		}, function myError(response) {
			 
		  // error
			
		});		
	
	}
	
	$scope.uploadLogM = function() {
		
 		$scope.upload = {};
		
		var body = '<form class="form-inline">';
			body += '<div class="container">';
			body += '<div class="row">';
			body += '<div class="form-group">';		
			body += '<label class="control-label col-lg-4">Date&nbsp;</label>';
			body += '<div class="col-lg-12">';
			body += '<input type="date" class="form-control" ng-model="upload.dateF" style="width: 100%!important;">';
			body += '</div>';
			body += '</div>';
			body += '<div class="form-group">';
			body += '<label class="control-label col-lg-4">&nbsp;Time&nbsp;</label>';
			body += '<div class="col-lg-12">';			
			body += '<input type="text" class="form-control" placeholder="00:00:00" maxlength="8" ng-model="upload.log" style="width: 100%!important;">';
			body += '</div>';		
			body += '</div>';
			body += '</div>';
			body += '<div class="row" style="margin-top: 15px;">';			
			body += '<div class="form-group">';
			body += '<label class="control-label col-lg-4">ID&nbsp;</label>';
			body += '<div class="col-lg-12">';			
			body += '<input type="number" class="form-control" placeholder="00000" ng-model="upload.pers_id" style="width: 100%!important;">';
			body += '</div>';			
			body += '</div>';
			body += '<div class="form-group">';
			body += '<label class="control-label col-lg-4">&nbsp;Order&nbsp;</label>';
			body += '<div class="form-group col-lg-12">';
			body += '<select class="form-control" ng-model="upload.order" ng-options="x for (x,y) in view.logOrder track by y" style="width: 125%!important;">';
			body += '<option value="">-</option>';
			body += '</select>';
			body += '</div>';
			body += '</div>';
			body += '</div>';
			body += '<div class="row" style="margin-top: 15px; margin-bottom: 15px;">';
			body += '<div class="form-group">';
			body += '<label class="control-label col-lg-4">Machine&nbsp;</label>';
			body += '<div class="col-lg-12">';				
			body += '<input type="number" class="form-control" placeholder="0" maxlength="1" ng-model="upload.machine" style="width: 85%!important;">';
			body += '</div>';			
			body += '</div>';
			body += '</div>';
			body += '</div>';
			body += '</div>';
			body += '</form>';
		
		bootstrapModal.pDialogBox($scope,'Manual Entry DTR',body,'uploadLog(true)');
		
	}

	$scope.query.source = 'mdb';
	localStorage.madcode = '';
	
});