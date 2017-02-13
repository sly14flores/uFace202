<?php

class dtrExportSybasePMIS {
	
	var $db;
	var $prepare;
	var $table;
	var $sql;
	var $rows;	
	
	function __construct($table = "") {
		
		$server = "pmisdb";
		$db_name = "pmisdb";
		$dsn = "odbc:$server";
		$username = "sa";
		$password = "";
		
		try {
		
			$this->db = new PDO($dsn);
		
		} catch(PDOException $e) {
			
			$response = array(array(400,"Cannot connect to Sybase DB, PMIS Server","a"));
			echo json_encode($response);
			exit();
			
		}
		
		$this->table = $table;		

	}
	
	function getData($sql) {

		$stmt = $this->db->query($sql);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->rows = $stmt->rowCount();		
		return $results;

	}

	function updateLog($pers_id,$o,$log,$M,$j,$Y) {
		
		$logOrder = array(0=>"timein_am",1=>"timeout_am",2=>"timein_pm",3=>"timeout_pm",4=>"timein_ot",5=>"timeout_ot");
		
		$logActual = $log.".000";
		$logTime = substr($log,10,strlen($log)).".000";
		$log = "1900-01-01$logTime";
		
		// $sql = "SELECT * FROM ".$this->table." WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM' AND ".$logOrder[$o]." = '2000-01-01 12:00AM'";
		$sql = "SELECT * FROM ".$this->table." WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM'";
		$row = $this->getData($sql);
		
		$update = "UPDATE ".$this->table." SET ".$logOrder[$o]." = '$log' WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM'";
		$formerLog = 0;
		$mostRecentLog = 0;
		if (count($row) > 0) {
			
			if ($row[0][$logOrder[$o]] == "2000-01-01 00:00:00.000") $formerLog = $this->db->exec($update);
			
			$sql = "SELECT * FROM ".$this->table." WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM'";
			$result = $this->getData($sql);			
			$logTimeQ = substr($logActual,0,10).substr($result[0][$logOrder[$o]],10,strlen($result[0][$logOrder[$o]]));			
			if (strtotime($logActual) > strtotime($logTimeQ)) $mostRecentLog = $this->db->exec($update);  // overwrite with most recent log

			// logger("Log: ".strtotime($logActual)." -- LogQ: ".strtotime($logTimeQ));
			
		}
		
		return array("formerLog"=>$formerLog,"mostRecentLog"=>$mostRecentLog);
		
	}
	
	function updateLogR($pers_id,$o,$log,$M,$j,$Y) {
		
		$logOrder = array(0=>"timein_am",1=>"timeout_am",2=>"timein_pm",3=>"timeout_pm",4=>"timein_ot",5=>"timeout_ot");
		
		$logActual = $log.".000";
		$logTime = substr($log,10,strlen($log)).".000";
		$log = "1900-01-01$logTime";
		
		// $sql = "SELECT * FROM ".$this->table." WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM' AND ".$logOrder[$o]." = '2000-01-01 12:00AM'";
		$sql = "SELECT * FROM ".$this->table." WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM'";
		$row = $this->getData($sql);
		
		$update = "UPDATE ".$this->table." SET ".$logOrder[$o]." = '$log' WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM'";
		
		$updateLogR = 0;
		if (count($row) > 0) $updateLogR = $this->db->exec($update);
		
		return array("updateLogR"=>$updateLogR);
		
	}	
	
	function clearLog($pers_id,$o,$log,$M,$j,$Y) {

		$logOrder = array(0=>"timein_am",1=>"timeout_am",2=>"timein_pm",3=>"timeout_pm",4=>"timein_ot",5=>"timeout_ot");

		$log .= ".000";
		$sql = "UPDATE ".$this->table." SET ".$logOrder[$o]." = '$log' WHERE pers_id = '$pers_id' AND date = '$M $j $Y 12:00AM'";		
		$this->db->exec($sql);

	}	
	
	function insertDataMulti($data) {

		$this->prepare = "INSERT INTO ".$this->table." (";	
		$prepare = "VALUES (";		

		foreach ($data as $row) { // Construct Prepared Statement
			foreach ($row as $key => $value) {
				$this->prepare .= $key . ",";
				$prepare .= ":$key,";
			}
			break;
		}	
		
		$prepare = substr($prepare,0,strlen($prepare)-1);
		$prepare .= ")";
		
		$this->prepare = substr($this->prepare,0,strlen($this->prepare)-1);
		$this->prepare .= ") ";
		$this->prepare .= $prepare;

		$this->db->beginTransaction();
		foreach ($data as $insert) {
			$stmt = $this->db->prepare($this->prepare);
			$stmt->execute($insert);
		}	 
		$this->db->commit();

	}	
	
	function buildMonth($pers_id,$month,$year) {
		
		$start = date("Y-m-d",strtotime("$year-$month-01"));
		$end = date("Y-m-t",strtotime("$year-$month-01"));
		
		$day = $start;
		
		$sql = "SELECT * FROM inout WHERE date LIKE '".date("M",strtotime($start))."%$year%' AND pers_id = '$pers_id'";
		$results = $this->getData($sql);
		
		if (count($results) > 0) {
			return "month_exists";
		}
		
		$name = "";
		
		$sql = "SELECT last_name, first_name, middle_name FROM personal WHERE pers_id = '$pers_id'";
		$result = $this->getData($sql);
		
		if (count($result) == 0) {
			return "no_record";
		}
		
		$name = trim($result[0]['last_name'])." ".trim($result[0]['first_name'])." ".trim($result[0]['middle_name']);
		
		while (strtotime($day) <= strtotime($end)) {
			
			// construct array(multi) for days
			$date_ph = "2000-01-01 00:00:00.000";
	
			$isSunday = (date("D",strtotime($day)) == "Sun") ? "Y" : "N";
			$isSaturday = (date("D",strtotime($day)) == "Sat") ? "Y" : "N";
			$isHoliday = isHoliday($day);
			
			$days[date("j",strtotime($day))] = array(
				"pers_id"=>$pers_id,
				"name"=>$name,
				"timein_am"=>$date_ph,
				"timeout_am"=>$date_ph,
				"timein_pm"=>$date_ph,
				"timeout_pm"=>$date_ph,
				"timein_ot"=>$date_ph,
				"timeout_ot"=>$date_ph,
				"date"=>date("Y-m-d",strtotime($day))." 00:00:00.000",
				"issunday"=>$isSunday,
				"issaturday"=>$isSaturday,
				"isholiday"=>$isHoliday
			);
			
			$day = date ("Y-m-d", strtotime("+1 day", strtotime($day)));
			
		}
		
		$this->insertDataMulti($days);
		
		return true;
		
	}

	function clearMonth($pers_id,$month,$year) {
		
		$start = date("Y-m-d",strtotime("$year-$month-01"));
		$end = date("Y-m-t",strtotime("$year-$month-01"));
		
		$day = $start;
		
		$sql = "SELECT * FROM inout WHERE date LIKE '".date("M",strtotime($start))."%$year%' AND pers_id = '$pers_id'";
		$results = $this->getData($sql);
		
		if (count($results) == 0) {
			return "month doesn't exit";
			exit();
		}
		
		while (strtotime($day) <= strtotime($end)) {	
			
			for ($i=0; $i<=5; $i++) {
				$this->clearLog($pers_id,$i,"2000-01-01 00:00:00",date("M",strtotime($day)),date("j",strtotime($day)),date("Y",strtotime($day)));
			}
			
			$day = date ("Y-m-d", strtotime("+1 day", strtotime($day)));
			
		}
		
		return true;
		
	}	
	
	function deleteMonth($pers_id,$month,$year) {

		$start = date("Y-m-d",strtotime("$year-$month-01"));
		$end = date("Y-m-t",strtotime("$year-$month-01"));
		
		$sql = "DELETE FROM inout WHERE date LIKE '".date("M",strtotime($start))."%$year%' AND pers_id = '$pers_id'";
		$this->db->exec($sql);
		
		return true;

	}	

	function buildDay($pers_id,$day) {
		
		$sql = "SELECT * FROM inout WHERE date = '".date("M j Y",strtotime($day))." 12:00AM' AND pers_id = '$pers_id'";
		$results = $this->getData($sql);
		
		if (count($results) > 0) {
			return "day_existing";
		}

		$name = "";
		
		$sql = "SELECT last_name, first_name, middle_name FROM personal WHERE pers_id = '$pers_id'";
		$result = $this->getData($sql);
		
		$name = trim($result[0]['last_name'])." ".trim($result[0]['first_name'])." ".trim($result[0]['middle_name']);
		
		$date_ph = "2000-01-01 00:00:00.000";

		$isSunday = (date("D",strtotime($day)) == "Sun") ? "Y" : "N";
		$isSaturday = (date("D",strtotime($day)) == "Sat") ? "Y" : "N";			
		
		$days = [];
		$days[] = array(
			"pers_id"=>$pers_id,
			"name"=>$name,
			"timein_am"=>$date_ph,
			"timeout_am"=>$date_ph,
			"timein_pm"=>$date_ph,
			"timeout_pm"=>$date_ph,
			"timein_ot"=>$date_ph,
			"timeout_ot"=>$date_ph,
			"date"=>date("Y-m-d",strtotime($day))." 00:00:00.000",
			"issunday"=>$isSunday,
			"issaturday"=>$isSaturday,
			"isholiday"=>"N"
		);
		
		$this->insertDataMulti($days);

		return "day_inserted";
		
	}
	
	function clearDay($pers_id,$day) {
		
		$sql = "SELECT * FROM inout WHERE date = '".date("M j Y",strtotime($day))." 12:00AM' AND pers_id = '$pers_id'";
		$result = $this->getData($sql);
		
		if (count($result) == 0) {
			return "day doesn't exit";
			exit();
		}
			
		for ($i=0; $i<=5; $i++) {
			$this->clearLog($pers_id,$i,"2000-01-01 00:00:00",date("M",strtotime($day)),date("j",strtotime($day)),date("Y",strtotime($day)));
		}
		
		return true;
		
	}
	
	function deleteDay($pers_id,$day) {
		
		$sql = "DELETE FROM inout WHERE date = '".date("M j Y",strtotime($day))." 12:00AM' AND pers_id = '$pers_id'";
		$this->db->exec($sql);
		
		return true;

	}
	
	function logsFiltered($logFile,$from,$to,$idFrom,$idTo) {
		
		$dir = "dtr-files";
		$dtr_file = "$dir/$logFile";
		
		$logs = [];
		
		if (!file_exists($dtr_file)) {
			return $logs; 
			exit();
		}
		
		$file = fopen($dtr_file,"rb");

		$line_txt = [];
		while (! feof($file)) {
			$line_txt[] = fgetcsv($file, 0, "\t");
		}		

		// trim ID
		foreach ($line_txt as $i => $row) {
			$logsUnfiltered[$i][0] = trim($line_txt[$i][0]);
			$logsUnfiltered[$i][1] = trim($line_txt[$i][1]);
			$logsUnfiltered[$i][2] = trim($line_txt[$i][2]);
		}
		
		// filter ID(s)
		if ( ($idFrom != 0) && ($idTo != 0) ) {
			
			$logIdFiltered = [];
			
			for ($id=$idFrom; $id<=$idTo; ++$id) {
				
				foreach($logsUnfiltered as $i => $row) {

					if ($id == $row[0]) $logIdFiltered[] = $row;
				
				}
				
			}
			
			$logsUnfiltered = $logIdFiltered;

		}

		// filter date range
		$day = implode("-",$from);
		while (strtotime($day) <= strtotime(implode("-",$to))) {

			$year = explode("-",$day)[0];
			$month = explode("-",$day)[1];
			$dayc = explode("-",$day)[2];

			foreach($logsUnfiltered as $i => $row) {

				$pid = $row[0];
				$log = $row[1];
				$machine = $row[2];
				
				if (preg_match("/$year-$month-$dayc/i", $log)) {
					
					$logs[] = array("date"=>"$year-$month-$dayc","pers_id"=>$pid,"log"=>$log,"machine"=>$machine);		
					
				}

			}

			$day = date ("Y-m-d", strtotime("+1 day", strtotime($day)));

		}
		
		return $logs;
		
	}
	
}

/* function logOrder($date,$log) {

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

} */

function logger($txt) {

$file = fopen("logs.txt","a+");
fwrite($file,"---- ".date("m/d/Y h:i:s A")." ----\r\n\r\n");
fwrite($file,$txt."\r\n\r\n");
fwrite($file,"-------------- end -------------\r\n");
fclose($file);

}

function isHoliday($day) {
	
	$holidays = file_get_contents("holidays.json");
	$json = json_decode($holidays, true);
	
	foreach ($json as $date => $holiday) {
		
		if ($date == $day) {
			return "Y";
		}
		
	}
	
	return "N";
	
}

?>