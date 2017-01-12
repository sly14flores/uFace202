<?php

$_POST = json_decode(file_get_contents('php://input'), true);

require_once '../devices.php';
require_once '../db.php';
require_once '../dtrExportSybasePMIS.php';

$logs = [];

$filter = "";

$year = (isset($_POST['year'])) ? $_POST['year'] : date("Y");
$month = (isset($_POST['month'])) ? $_POST['month'] : "";
$date = (isset($_POST['date'])) ? date("Y-m-d",strtotime($_POST['date'])) : "";

if ($month != "") {
	$last_day = date("t",strtotime("$year-$month-01"));
	$filter = " WHERE log_time >= #$year-$month-01 00:00:00# AND log_time <= #$year-$month-$last_day 23:59:09#";
}

if ($date != "") $filter = " WHERE log_time >= #$date 00:00:00# AND log_time <= #$date 23:59:09#";

$backlog = new pdo_db("dtr");

$sql = "SELECT * FROM dtr$filter";
$results = $backlog->getData($sql);
			
foreach ($results as $i => $result) {
	$logs[] = array("machine"=>$result['machine_no'],"location"=>$device_description[$result['machine_no']],"pers_id"=>$result['pers_id'],"date"=>date("F j, Y",strtotime($result['log_time'])),"log"=>date("h:i:s A",strtotime($result['log_time'])));
}

echo json_encode($logs);

?>