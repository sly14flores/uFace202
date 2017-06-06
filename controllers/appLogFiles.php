<?php

$_POST = json_decode(file_get_contents('php://input'), true);

require_once '../devices.php';
require_once '../db.php';
require_once '../dtrExportSybasePMIS.php';
require_once '../dtrImportMSeed.php';

switch ($_GET['r']) {

case "collect_raw_logs":

$dir_ar = array("dtr-transition","dtr-files");

$logs = [];

// filter year and month/date
$year = $_POST['year'];
if (isset($_POST['date'])) {
	
	$date = substr($_POST['date'],0,10);
	$filter = "$date";

}
if (isset($_POST['month'])) {
	
	$month = $_POST['month'];
	$filter = "$year-$month-";
	
}

foreach ($devices as $key => $device) {

foreach ($dir_ar as $d) {
	
	// $dir = "../dtr-raw";
	$dir = "../$d";
	$logFile = $device['File'];
	$dtr_file = "$dir/$logFile";
	
	if (!file_exists($dtr_file)) {
		continue;
	}
	$logsUnfiltered = [];	

	$file = fopen($dtr_file,"rb");

	$line_txt = [];
	while (! feof($file)) {
		$line_txt[] = fgetcsv($file, 0, "\t");
	}		

	// trim
	foreach ($line_txt as $i => $row) {
		$logsUnfiltered[$i][0] = trim($line_txt[$i][0]); // id
		$logsUnfiltered[$i][1] = trim($line_txt[$i][1]); // log
		$logsUnfiltered[$i][2] = trim($line_txt[$i][2]); // machine
	}

	foreach($logsUnfiltered as $i => $row) {

		$pid = $row[0];
		$log = $row[1];
		$machine = $row[2];
		
		if (preg_match("/$filter/i", $log)) {
			
			$logs[] = array("machine"=>$device['No'],"location"=>$device['Description'],"id"=>$pid,"date"=>date("F j, Y",strtotime($log)),"rdate"=>date("Y-m-d",strtotime($log)),"log"=>date("h:i:s A",strtotime($log)),"rlog"=>$log);
			
		}

	}

}	

}

echo json_encode($logs);

break;

case "collect_raw_logs_mdb":

$logs = [];

// filter year and month/date
$year = $_POST['year'];
if (isset($_POST['date'])) {
	
	$date = substr($_POST['date'],0,10);
	$filter = "WHERE Format(Date,'Short Date') = #".date("m/d/Y",strtotime($_POST['date']))."#";

}
if (isset($_POST['month'])) {
	
	$month = $_POST['month'];
	$filter = "WHERE Format(Month(Date)) = '".date("n",strtotime($_POST['year']."-".$_POST['month']."-01"))."' AND Format(Year(Date)) = '".date("Y",strtotime($_POST['year']."-".$_POST['month']."-01"))."'";
	
}

$mseed = new dtrImportMSeed("DTR");

$sql = "SELECT EmployeeID, Date, Time, Dev FROM DTR $filter";
$results = $mseed->getData($sql);

foreach($results as $i => $row) {

	$pid = $row['EmployeeID'];
	$log =  substr($row['Date'],0,10).substr($row['Time'],10,strlen($row['Time']));
	
	$logs[] = array("machine"=>$devices[$row['Dev']]['No'],"location"=>$devices[$row['Dev']]['Description'],"id"=>$pid,"date"=>date("F j, Y",strtotime($log)),"rdate"=>date("Y-m-d",strtotime($log)),"log"=>date("h:i:s A",strtotime($log)),"rlog"=>$log);	

}

echo json_encode($logs);

break;

case "upload_log":

$orderDesc = array("Morning In","Morning Out","Afternoon In","Afternoon Out","Overtime In","Overtime Out");

$sybase = new dtrExportSybasePMIS("inout");
$backlog = new pdo_db("dtr");

/**
*** insert month if not created yet
**/
$buildMonth = $sybase->buildMonth($_POST['pers_id'],date("m",strtotime($_POST['date'])),date("Y",strtotime($_POST['date'])));

if ($buildMonth == "no_record") {
	$response[] = array(300,$_POST['pers_id']." has no record in database","a");
	echo json_encode($response);
	exit();
}

$order = (isset($_POST['order'])) ? $_POST['order'] : logOrder($_POST['date'],$_POST['log']);

$putLog = $sybase->updateLogR($_POST['pers_id'],$order,$_POST['log'],date("M",strtotime($_POST['date'])),date("j",strtotime($_POST['date'])),date("Y",strtotime($_POST['date'])));
$backlog->backLog($_POST);

if ($putLog['updateLogR'] == 1) $response[] = array(200,"Uploaded <strong>".date("h:i A m/d/Y",strtotime($_POST['log']))."</strong> for <strong>".$_POST['pers_id']."</strong> as ".$orderDesc[$order],"a");
else $response[] = array(300,"Something went wrong, log wat not uploaded.","a");

echo json_encode($response);

break;

}

?>