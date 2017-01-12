var app = angular.module('appBacklogs', ['angularUtils.directives.dirPagination', 'ui.bootstrap']);

app.service('bootstrapModal', function($compile) {
	
	this.confirm = function(scope,body,ok,shown = null,hidden = null) {
		
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
	
	this.notify = function(body,shown = null,hidden = null) {
		
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

app.controller('appBacklogsCtrl', function($scope, $http, bootstrapModal) {

	$scope.currentPage = 1;
	$scope.pageSize = 50;

	$scope.view = {};
	$scope.query = {};
	
	$scope.query.year = (new Date()).getFullYear();
	
	$scope.dateOpen = function() {
		$scope.view.popupDate.opened = true;
	};
	
	$scope.view.popupDate = {
		opened: false
	};	
	
	$scope.view.format = 'shortDate';
	$scope.altInputFormats = ['M!/d!/yyyy'];
	
	$scope.query.date = new Date();

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
	}
	
	$scope.queryBacklogs = function() {
		
		if ($scope.query.month == null) delete $scope.query.month;
		if ($scope.query.date == undefined) delete $scope.query.date;
		console.log($scope.query);
		$http({
		  method: 'POST',
		  url: 'controllers/appBacklogs.php',
		  data: $scope.query,
		  headers : {'Content-Type': 'application/x-www-form-urlencoded'}
		}).then(function mySucces(response) {
			
			$scope.results = response.data;
			
		}, function myError(response) {
			 
		  // error
			
		});
	
	}

});