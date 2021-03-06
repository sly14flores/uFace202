<?php

$_POST = json_decode(file_get_contents('php://input'), true);

require_once 'devices.php';
require_once 'db.php';
// require_once 'dtrExportSybasePMIS.php';
require_once 'dtrImportMSeed.php';
require_once 'dtrImportNitgen.php';

$response = [];

$months = array(
	"01"=>"January",
	"02"=>"February",
	"03"=>"March",
	"04"=>"April",
	"05"=>"May",
	"06"=>"June",
	"07"=>"July",
	"08"=>"August",
	"09"=>"September",
	"10"=>"October",
	"11"=>"November",
	"12"=>"December"
);

switch ($_GET['r']) {

case "upload_log":
	
	$dir = "sybase/";
	
	if ($_GET['destination'] == "web") $dir = "web/";
	
	$fn = $_FILES['file']['name'];

	move_uploaded_file($_FILES['file']['tmp_name'],$dir.$fn);

break;

case "devices":
	
	echo json_encode($devices);
	
break;

case "start":
	
	$_POST['dateFrom'] = substr($_POST['dateFrom'],0,10);
	$_POST['dateTo'] = substr($_POST['dateTo'],0,10);
	
	if (strtotime($_POST['dateFrom']) > strtotime($_POST['dateTo'])) {
		
		$response[] = array(400,"'Date From' cannot be greater than 'Date To'","a");
		echo json_encode($response);		
		exit();
		
	} else {
		
		$response[] = array(200,"Date range is valid","a");
		
	}
	
	echo json_encode($response);

break;

case "count_months":

$ids = [];
$buildMonths = [];

require_once 'dtrExportSybasePMIS.php';

$sybase = new dtrExportSybasePMIS("inout");
$results = $sybase->getData("SELECT pers_id FROM personal");

function isIdValid($id,$results) {
	foreach ($results as $i => $result) {
		if ($result['pers_id'] == $id) return true;
	}
	return false;
}

for ($id=$_POST['idFrom']; $id<=$_POST['idTo']; $id++) {
	
	// check if ID is existing
	if (!isIdValid($id,$results)) continue;
	
	$ids[] = $id;
	
	$month = date("Y-m-1", strtotime($_POST['dateFrom']));
	while (strtotime($month) <= strtotime(date("Y-m-1",strtotime($_POST['dateTo'])))) {
		
		$buildMonths[] = array("pers_id"=>$id,"month"=>date("m",strtotime($month)),"year"=>date("Y",strtotime($month)));
		$month = date ("Y-m-d", strtotime("+1 month", strtotime($month)));

	}
	
}

$response = array("ids"=>$ids,"buildMonths"=>$buildMonths,"from"=>date("Y-m-d",strtotime($_POST['dateFrom'])),"to"=>date("Y-m-d",strtotime($_POST['dateTo'])));

echo json_encode($response);

break;

case "build_months":
	
	require_once 'dtrExportSybasePMIS.php';	
	
	$sybase = new dtrExportSybasePMIS("inout");

	$month = date("m");
	
	$buildMonth = $sybase->buildMonth($_POST['pers_id'],$_POST['month'],$_POST['year']);
	
	if ($buildMonth === true) {
		echo json_encode(array(array(200,"Processed ".$months[$_POST['month']]." ".$_POST['year']." for ".$_POST['pers_id'],"a")));
		exit();		
	} elseif ($buildMonth == "month_exists") {
		echo json_encode(array(array(200,$months[$_POST['month']]." ".$_POST['year']." for ".$_POST['pers_id']." already added skipping month","a")));
		exit();		
	} else {
		echo json_encode(array(array(400,"Something went wrong please repeat the process","a")));
		exit();		
	}

break;

case "collect_logs":

$from = array("year"=>date("Y",strtotime($_POST['from'])),"month"=>date("m",strtotime($_POST['from'])),"day"=>date("d",strtotime($_POST['from'])));
$to = array("year"=>date("Y",strtotime($_POST['to'])),"month"=>date("m",strtotime($_POST['to'])),"day"=>date("d",strtotime($_POST['to'])));

$idFrom = (isset($_POST['idFrom'])) ? $_POST['idFrom'] : 0;
$idTo = (isset($_POST['idTo'])) ? $_POST['idTo'] : 0;

$filename = explode(".",$_POST['logFile'])[0];
$src = explode(".",$_POST['logFile'])[1];

$logs = [];

if ($src == "dat") {
	
	require_once 'dtrExportSybasePMIS.php';	
	$sybase = new dtrExportSybasePMIS("inout");
	$logs = $sybase->logsFiltered($_POST['logFile'],$from,$to,$idFrom,$idTo);
	
} else {
	
	if ($filename == "MSEEDBioOfficedb") { // mseed
		$mseed = new dtrImportMSeed("DTR");
		$logs = $mseed->logsFiltered($from,$to,$idFrom,$idTo);
	} else if ($filename == "NITGENDBAC") { // nitgen
		$nitgen = new dtrImportNitgen("NGAC_LOG");
		$logs = $nitgen->logsFiltered($from,$to,$idFrom,$idTo);
	} else { // backlogs
		$backlog = new pdo_db("dtr");
		$filter = " WHERE log_time >= #".implode("-",$from)." 00:00:00# AND log_time <= #".implode("-",$to)." 23:59:09#";

		$sql = "SELECT log_time, pers_id, machine_no FROM dtr$filter";
		$backlogs = $backlog->getData($sql);
		foreach ($backlogs as $i => $log) {
			$logs[] = array("date"=>date("Y-m-d",strtotime($log['log_time'])),"pers_id"=>$log['pers_id'],"log"=>$log['log_time'],"machine"=>$log['machine_no']);
		};
		
		if ( ($idFrom != 0) && ($idTo != 0) ) {
			$logsUnfiltered = $logs;
			$logs = [];
			for ($id=$idFrom; $id<=$idTo; ++$id) {
				
				foreach($logsUnfiltered as $i => $row) {

					if ("$id" == $row['pers_id']) $logs[] = $row;
				
				};
				
			};
		};
	};
	
};

echo json_encode($logs);

break;

case "put_logs":

$response = [];
$backlog = new pdo_db("dtr");

switch ($_GET['destination']) {

case "sybase":

	require_once 'dtrExportSybasePMIS.php';
	
	$sybase = new dtrExportSybasePMIS("inout");

	/**
	*** insert month if not created yet
	**/
	$buildMonth = $sybase->buildMonth($_POST['pers_id'],date("m",strtotime($_POST['date'])),date("Y",strtotime($_POST['date'])));

	if ($buildMonth == "no_record") {
		$response[] = array(300,$_POST['pers_id']." has no record in database","a");
		echo json_encode($response);
		exit();
	}

	$putLog = $sybase->updateLog($_POST['pers_id'],logOrder($_POST['date'],$_POST['log']),$_POST['log'],date("M",strtotime($_POST['date'])),date("j",strtotime($_POST['date'])),date("Y",strtotime($_POST['date'])));
	$backlog->backLog($_POST);

	if ($putLog['formerLog'] == 1) $response[] = array(200,"Imported ".date("h:i A m/d/Y",strtotime($_POST['log']))." for ".$_POST['pers_id'],"a");

	if (($putLog['formerLog'] == 0) && ($putLog['mostRecentLog'] == 0)) $response[] = array(300,"Skipped ".date("h:i A m/d/Y",strtotime($_POST['log']))." for ".$_POST['pers_id'],"a");

	if ($putLog['mostRecentLog'] == 1) $response[] = array(300,"Imported ".date("h:i A m/d/Y",strtotime($_POST['log']))." for ".$_POST['pers_id'].", overwritten previous entry","a");

break;

case "web":

	// require_once 'dtrExportSybasePMIS.php';
	require_once 'db_web.php';
	
	$con = new pdo_db_web("tblempdtr");
	$backlog = new pdo_db("dtr");	
	
	/*
	** build month
	*/
	$start_cache = date("Y-m-01",strtotime($_POST['date']));
	$start = date("Y-m-01",strtotime($_POST['date']));
	$end = date("Y-m-t",strtotime($_POST['date']));		
	
	$DTRID = "DTR".date("Ym01",strtotime($_POST['date'])).$_POST['pers_id'];
	$month = $con->getData("SELECT * FROM tblempdtr WHERE DTRID = '$DTRID' AND EmpID = '$_POST[pers_id]'");
		
	$dtr = [];
	while (strtotime($start) <= strtotime($end)) {			
		
		$sql = "SELECT * FROM tblempdtr WHERE DTRID = 'DTR".date("Ymd",strtotime($start)).$_POST['pers_id']."'";
		$checkDtr = $con->getData($sql);
		if ($con->rows > 0) {
			$start = date("Y-m-d", strtotime("+1 day", strtotime($start)));				
			continue;
		}
		
		$dtr[] = array(
					"DTRID"=>"DTR".date("Ymd",strtotime($start)).$_POST['pers_id'],
					"EmpID"=>$_POST['pers_id'],
					"DayStatusID"=>"",
					"DTRIN01"=>"1970-01-01 00:00:01",
					"DTROUT01"=>"1970-01-01 00:00:01",
					"DTRIN02"=>"1970-01-01 00:00:01",
					"DTROUT02"=>"1970-01-01 00:00:01",
					"DTRIN03"=>"1970-01-01 00:00:01",
					"DTROUT03"=>"1970-01-01 00:00:01",
					"DTRIN04"=>"1970-01-01 00:00:01",
					"DTROUT04"=>"1970-01-01 00:00:01",
					"DTRLates"=>"",
					"DTROverTime"=>"",
					"DTRHrsWeek"=>"",
					"DTRVerCode"=>"",
					"DTRRemarks"=>"",
					"RECORD_TIME"=>date("Y-m-d H:i:s")
				);
		
		$start = date("Y-m-d", strtotime("+1 day", strtotime($start)));	
		
	};
	
	$buildMonth = $con->insertDataMulti($dtr);

	/*
	** update log
	*/
	
	$dtr = [];
	$dtrinout = array("DTRIN01","DTROUT01","DTRIN02","DTROUT02");
	$dtr["DTRID"] = "DTR".date("Ymd",strtotime($_POST['date'])).$_POST['pers_id'];
	$dtr[$dtrinout[logOrder($_POST['date'],$_POST['log'])]] = $_POST['log'];
	
	$updateLog = $con->updateData($dtr,"DTRID");
	$backlog->backLog($_POST);
	
	$response[] = array(200,"Imported ".date("h:i A m/d/Y",strtotime($_POST['log']))." for ".$_POST['pers_id'],"a");
	
	
break;

}

echo json_encode($response);

break;

case "regen_month":

require_once 'dtrExportSybasePMIS.php';

$sybase = new dtrExportSybasePMIS("inout");
$date = date("Y-").$_POST['month']."-01";

for ($pers_id = $_POST['idFrom']; $pers_id <= $_POST['idTo']; $pers_id++) {
	
	$deleteMonth = $sybase->deleteMonth($pers_id,date("m",strtotime($date)),date("Y",strtotime($date)));
	if ($deleteMonth) {
		$buildMonth = $sybase->buildMonth($pers_id,date("m",strtotime($date)),date("Y",strtotime($date)));
	};
	
};

echo json_encode(array(array(200,'Month regeneration successful','a')));

break;

}

function logOrder($date,$log) {

	$order = 0;
	
	$morning_cutoff = strtotime("$date 10:01:00");
	$afternoon_cutoff = strtotime("$date 15:01:00");
	$lunch_cutoff = strtotime("$date 12:30:59");
	$ot_cutoff = strtotime("$date 17:00:00");

	$tlog = strtotime($log);
	
	if ( ($tlog < $morning_cutoff) && ($tlog <= $lunch_cutoff) ) $order = 0;		
	if ( ($tlog >= $morning_cutoff) && ($tlog <= $lunch_cutoff) ) $order = 1;

	if ( ($tlog < $afternoon_cutoff) && ($tlog > $lunch_cutoff) ) $order = 2;
	if ( ($tlog >= $afternoon_cutoff) && ($tlog > $lunch_cutoff) ) $order = 3;
	
	return $order;

}

?>