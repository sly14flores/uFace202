var app = angular.module('appImport', ['ui.bootstrap']);

app.directive('fileModel', ['$parse', function ($parse) {
	return {
	   restrict: 'A',
	   link: function(scope, element, attrs) {
		  var model = $parse(attrs.fileModel);
		  var modelSetter = model.assign;
		  
		  element.bind('change', function(){
			  
			scope.$apply(function(){
				modelSetter(scope, element[0].files[0]);
			});
			 
		  });

	   }
	};
}]);

app.service('fileUpload', function (importStatus, initImport) {
	
	this.uploadFileToUrl = function(file, uploadUrl, scope){
	   var fd = new FormData();
	   fd.append('file', file);
	
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener("progress", uploadProgress, false);
        xhr.addEventListener("load", uploadComplete, false);
        xhr.open("POST", uploadUrl)
        xhr.send(fd);
	   
		// upload progress
		function uploadProgress(evt) {
			scope.view.uploadProgressContainer = true;
			scope.$apply(function(){
				scope.view.uploadProgress = 0;				
				if (evt.lengthComputable) {
					scope.view.uploadProgress = Math.round(evt.loaded * 100 / evt.total);
				} else {
					scope.view.uploadProgress = 'unable to compute';
				}
			});
		}

		function uploadComplete(evt) {
			/* This event is raised when the server send back a response */
			scope.$apply(function(){
				scope.view.uploadProgressContainer = false;
				importStatus.show(200,'Log file uploaded','a');
				initImport.start(scope);
			});			

		}

	}
	
});

app.service('initImport', function($http, $timeout, importStatus) {
	
	this.start = function(scope) {
		// console.log(scope.frmImport); return;
		$http({
		  method: 'POST',
		  url: 'ajax.php?r=start',
		  data: scope.frmImport,
		  headers : {'Content-Type': 'application/x-www-form-urlencoded'}
		}).then(function mySucces(response) {
			// console.log(response.data); return;
			response.data.forEach(function(item, index) {
				
				importStatus.show(item[0],item[1],item[2]);
				if ((item[0] == 200) && (index == parseInt(response.data.length) - 1)) collectLogs(scope);
				
			});
			
		}, function myError(response) {
			 
		  // error
			
		});
		
	}
	
	function collectLogs(scope) {
		
		switch (scope.view.source) {
		
		case "uface202_dat":
		
			if (scope.view.usePreviousFile) {
				var logFile = localStorage.previousFile;
			} else {
				var logFile = scope.view.logFile.name;
			}
			
		break;
		
		case "uface202_mdb":

			var logFile = 'MSEEDBioOfficedb.mdb';
			importStatus.show(200,'Locating MSEEDBioOfficedb.mdb...','a');			
		
		break;
		
		case "nitgen":
		
			var logFile = 'NITGENDBAC.mdb';
			importStatus.show(200,'Locating NITGENDBAC.mdb...','a');			
		
		break;
		
		case "backlogs":
			
			var logFile = 'backlogs.accdb';
			importStatus.show(200,'Locating backlogs.accdb...','a');			
			
		break;
		
		}
		
		importStatus.show(200,'Collecting logs from '+scope.view.logSource,'a');
		
		$http({
			method: 'POST',
			url: 'ajax.php?r=collect_logs',
			data: {logFile: logFile, from: scope.frmImport['dateFrom'], to: scope.frmImport['dateTo'], idFrom: scope.frmImport['idFrom'], idTo: scope.frmImport['idTo']},
			headers : {'Content-Type': 'application/x-www-form-urlencoded'}
		}).then(function mySucces(response) {

			if (response.data.length > 0) {
			
				if (response.data[0][0] == 400) {
					importStatus.show(response.data[0][0],response.data[0][1],response.data[0][2]);
				}

				if (response.data[0]['date']) { // if there's at least 1 log put it
					putLogs(scope,response.data);
				}
			
			} else {
				
				importStatus.show(400,'No logs found from date range','a');
				
			}
			
		}, function myError(response) {
		 
		// error

		});		
		
	}
	
	function putLogs(scope,logs) {

		var logsCount = logs.length;
		var logsLeft = logsCount;

		if (logsCount > 0) {
			scope.view.importProgressContainer = true;
			scope.view.importProgressDescription = 'Importing logs';
			scope.view.importProgress = 0;
			scope.view.importProgressDetail = '';

			importStatus.show(200,'Importing logs...','a');
			var i = 1;			
			importLog(logs[0]);
		} else {
			importStatus.show(200,'Nothing to import','a');
		}

		function importLog(item) {
			
			$http({
				method: 'POST',
				url: 'ajax.php?r=put_logs',
				data: item,
				headers : {'Content-Type': 'application/x-www-form-urlencoded'}
			}).then(function mySucces(response) {
					
					logsLeft = logsCount - i;
					var eta = formatSeconds(logsLeft);
					
					response.data.forEach(function(item, index) {			
					
						if ((item[0] == 200) || (item[0] == 300)) {
							var progress = Math.round((i*100)/logsCount);
							scope.view.importProgress = progress;
							importStatus.show(item[0],item[1],item[2]);					
							scope.view.importProgressDetail = 'Processed ' + i + ' of ' + logsCount + ' ('+progress+'%), estimated time remaining: '+eta;
							$('.output').scrollTop(($('.output')[0]).scrollHeight);		
						} else {
							importStatus.show(item[0],item[1],item[2]);
							$('.output').scrollTop(($('.output')[0]).scrollHeight);
							return false;
						}
						
					});
					
					if (logsCount == 1) return false;
					
					if (i == logsCount) {
						
						$timeout(function() {
							// scope.view.importProgressContainer = false;
							// scope.view.importProgressDescription = '';
							// scope.view.importProgress = 0;
							// scope.view.importProgressDetail = '';
							
							importStatus.show(200,'All logs were successfully imported','a');
							$('.output').scrollTop(($('.output')[0]).scrollHeight);
							$('#logFile').val(null);
							scope.view.logFile = null;							
						},1000);							
					} else {
						importLog(logs[i]);
					}
					i = i + 1;
				
			}, function myError(response) {
			 
			// error

			});
			
			
		}
		
	}
	
	function formatSeconds(seconds) {
		
		var date = new Date(1970,0,1);
		date.setSeconds(seconds);
		return date.toTimeString().replace(/.*(\d{2}:\d{2}:\d{2}).*/, "$1");
		
	}
	
});

app.directive('importLogs', function($http, fileUpload, importStatus, initImport) {

	return {
	   restrict: 'A',
	   link: function(scope, element, attrs) {

		  element.bind('click', function() {
				
			   if (scope.view.source == '-') {
					importStatus.show(400,'Please select source','r');
					return;
			   }

				if ( ((scope.frmImport.idFrom != "") && (scope.frmImport.idTo == "")) || ((scope.frmImport.idFrom != null) && (scope.frmImport.idTo == null)) ) {
					importStatus.show(400,'Please fill up both ID field','r');
					return;					
				}

				if ( ((scope.frmImport.idFrom == "") && (scope.frmImport.idTo != "")) || ((scope.frmImport.idFrom == null) && (scope.frmImport.idTo != null)) ) {
					importStatus.show(400,'Please fill up both ID field','r');
					return;					
				}				
				
			   switch (scope.view.source) {
			   
			   case "uface202_dat": // dat				   
					
				   var file = scope.view.logFile;
				   
				   if ((scope.view.source == 'uface202_dat') && (file == undefined) && (!scope.view.usePreviousFile)) {
					   
						importStatus.show(400,'Please select file to upload e.g. dat file','r');
						return;
					   
				   }
				   
				   // console.log(file);			   			   
				   if (scope.view.usePreviousFile) {
					   
						var lf = localStorage.previousFile;

				   } else {			   
				   
						var lf = file['name'];
				   
				   }

				   var en = lf.substring(lf.indexOf("."),lf.length);
				   // console.log(en);			   			   
				   
				   if (en != '.dat') {
					   
					importStatus.show(400,'Invalid file format. File format supported is dat','r');
					$('#logFile').val(null);
					scope.view.logFile = null;
					   
					return;
					   
				   }
							   
					importStatus.show(200,'File format is valid','r');				
					importStatus.show(200,'Filename: '+lf,'a');
				   
					localStorage.previousFile = lf;
				   
					var chk_file = lf.split("_");
					if (chk_file.length > 1) {
						
						var dev = chk_file[0];
						
						$http({
						  method: 'GET',
						  url: 'ajax.php?r=devices',
						  headers : {'Content-Type': 'application/x-www-form-urlencoded'}
						}).then(function mySucces(response) {

							importStatus.show(200,'Log file origin is device No. '+response.data[dev]['No']+', '+response.data[dev]['Description'],'a');
							scope.view.logSource = 'Device No. '+response.data[dev]['No']+', '+response.data[dev]['Description'];
							
						}, function myError(response) {
							 
						  // error
							
						});					
						
					}
				   
				   if (scope.view.usePreviousFile) {
						initImport.start(scope);
						importStatus.show(200,'Using previously uploaded file...','a');
				   } else {
						var uploadUrl = "ajax.php?r=upload_log";
						fileUpload.uploadFileToUrl(file, uploadUrl, scope);
						importStatus.show(200,'Uploading log file...','a');
				   }
				   
			   break; // dat
			   
			   case "uface202_mdb":
					
					scope.view.logSource = 'MSEEDBioOfficedb.mdb, gathered via network from all devices.';					
					initImport.start(scope);
				
			   break;
			   
			   case "nitgen":
					
					scope.view.logSource = 'NITGENDBAC.mdb, gathered from nitgen devices.';		
					initImport.start(scope);			
				
			   break;
			   
			   case "backlogs":

					scope.view.logSource = 'backlogs.accdb, back up logs from previous imports.';		
					initImport.start(scope);						   
			   
			   break;
			   
			   }

		  });

	   }
	};

});

app.directive('selectSource', function(importStatus) {

	return {
	   restrict: 'A',
	   link: function(scope, element, attrs) {
	   
			element.bind('change', function() {
				if (element[0].value == 'uface202_dat') {					
					scope.$apply(function(){
						scope.view.logFileDisabled = false;
						scope.view.pFDisabled = true;
					});
					importStatus.show(200,'Please upload dat file...','r');
				} else {
					scope.$apply(function(){
						scope.view.logFileDisabled = true;
						scope.view.pFDisabled = false;
					});					
					switch (element[0].value) {
						case "uface202_mdb":
							importStatus.show(200,'Please make sure MSEEDBioOfficedb.mdb was uploaded to the server via ftp...','r');
						break;
						
						case "nitgen":
							importStatus.show(200,'Please make sure NITGENDBAC.mdb was uploaded to the server via ftp...','r');
						break;
						
						case "backlogs":
							importStatus.show(200,'Backlogs selected as source...','r');
						break;
						
						default:
							importStatus.show(200,'Waiting for logs file to be imported...','r');
						break;
					}
				}
			});
			
	   }
	};

});

app.service('importStatus', function() {
	
	this.show = function(code,msg,opt = 'a') {
		
		var codeClass = 'success-response';
		if (code == 300) codeClass = 'info-response';
		else if (code == 400) codeClass = 'error-response';
		
		if (opt == 'a') $('.output').append('<span class="'+codeClass+'">'+msg+'</span>');
		else $('.output').html('<span class="'+codeClass+'">'+msg+'</span>');
		
	}
	
});

app.directive('regenerateMonth', function($http,importStatus) {
	return {
	   restrict: 'A',
	   link: function(scope, element, attrs) {
		  
		  element.bind('click', function() {

			scope.$apply(function() {		  
				angular.forEach(scope.regen, function(item,i) {
					scope.view['regen'+i] = false;
				});
				scope.view.regenAlert = false;
				scope.view.regenMsg = '';
			});

			scope.$apply(function() {
				angular.forEach(scope.regen, function(item,i) {
					if ((item == undefined) || (item == '')) {
						scope.view['regen'+i] = true;
						if ((i == 'idFrom') || (i == 'idTo')) if (scope.view.regenMsg == '') scope.view.regenMsg += ' Please specify ID From and ID To. To regen one ID, specify same ID for both fields.';
						if (i == 'month') scope.view.regenMsg += ' Please select month';
						scope.view.regenAlert = true;						
					}
				});
				if (parseInt(scope.regen.idFrom) > parseInt(scope.regen.idTo)) {
					scope.view.regenMsg += ' Invalid ID range. ID From must not be greater than ID To';					
					scope.view.regenAlert = true;
				}
			});
			
			if (scope.view.regenAlert) return;
			
			// regen month
			
			$http({
			  method: 'POST',
			  url: 'ajax.php?r=regen_month',
			  data: scope.regen
			}).then(function mySucces(response) {
				
				response.data.forEach(function(item, index) {
					importStatus.show(item[0],item[1],item[2]);
					$('.output').scrollTop(($('.output')[0]).scrollHeight);
				});
				
			}, function myError(response) {
				 
			  // error
				
			});			
			
		  });

	   }
	};
});

app.controller('appImportCtrl', function($scope, $http, importStatus) {
	
	$scope.view = {};

	$scope.view.uploadProgress = 0;
	$scope.view.uploadProgressContainer = false;
	
	$scope.view.importProgressContainer = false;
	$scope.view.importProgressDescription	= '';
	$scope.view.importProgress = 0;
	$scope.view.importProgressDetail = '';
	
	$scope.view.usePreviousFile = false;
	$scope.view.pFDisabled = false;
	
	$scope.view.regenAlert = false;
	$scope.view.regenMsg = '';
	
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
	
	$scope.regen = {
		idFrom: '',
		idTo: '',
		month: ''
	};
	
	$scope.frmImport = {};
	
	$scope.frmImport.dateFrom = new Date();
	$scope.frmImport.dateTo = new Date();

	$scope.fromOpen = function() {
		$scope.view.popupFrom.opened = true;
	};
	
	$scope.view.popupFrom = {
		opened: false
	};
	
	$scope.toOpen = function() {
		$scope.view.popupTo.opened = true;
	};	
	
	$scope.view.popupTo = {
		opened: false
	};
	
	$scope.view.format = 'shortDate';
	
	importStatus.show(200,'Waiting for logs file to be imported...','r');
	
	if (localStorage.previousFile != undefined) {
		$scope.view.pf = '('+localStorage.previousFile+')';
		// $scope.view.pFDisabled = true;
	}
	
	$scope.view.source = "-";
	$scope.view.logFileDisabled = true;
	
});