<?php

require_once 'dtrExportSybasePMIS.php';

$log = "2000-01-01 00:00:00";
$date = "2016-12-30";

$sybase = new dtrExportSybasePMIS("inout");
$putLog = $sybase->clearLog(93009,0,$log,date("M",strtotime($date)),date("j",strtotime($date)),date("Y",strtotime($date)));

?>