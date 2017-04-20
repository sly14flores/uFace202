<?php

class dtrImportMSeed {
	
	var $dir_db;
	var $dbName;
	var $dbPathFile;
	var $db;
	var $prepare;
	var $sql;
	var $table;
	
	function __construct($table = "") {	
		
		$dir = "sybase";	
		if ($_GET['destination'] == "web") $dir = "web";		
		
		$this->dir_db = (preg_match("/htdocs/i", $_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT']."/uFace202/$dir" : $_SERVER['DOCUMENT_ROOT']."uFace202/$dir";
		$this->dbName = "MSEEDBioOfficedb.mdb";
		$this->dbPathFile = $this->dir_db."/".$this->dbName;
		$this->dbPathFile = str_replace("/","\\",$this->dbPathFile);

		if (!file_exists($this->dbPathFile)) {
			
			$response = array(array(400,"Could not find MSEEDBioOfficedb.mdb file","a"));
			echo json_encode($response);
			exit();			

		}		
		
		try {
		
			$this->db = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=".$this->dbPathFile."; Uid=; Pwd=;");
		
		} catch (PDOException $e) {

			$response = array(array(400,"Could not connect to MSEEDBioOfficedb.mdb","a"));
			echo json_encode($response);
			exit();		
			
		}
		
		$this->table = $table;

	}

	function getData($sql) {

		$stmt = $this->db->query($sql);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $results;

	}

	function logsFiltered($from,$to,$idFrom,$idTo) {
		
		global $devices;
		
		$from = date("Y-m-d",strtotime(implode("-",$from)));
		$to = date("Y-m-d",strtotime(implode("-",$to)));
		
		$logs = [];
		
		$day = $from;
		while (strtotime($day) <= strtotime($to)) {
		
			$sql = "SELECT EmployeeID, Date, Time, Dev FROM DTR WHERE Format(Date,'Short Date') >= #".date("m/d/Y",strtotime($day))."# AND Format(Date,'Short Date') <= #".date("m/d/Y",strtotime($day))."#";
			$results = $this->getData($sql);
				
			foreach($results as $i => $row) {

				$pid = $row['EmployeeID'];
				$log =  substr($row['Date'],0,10).substr($row['Time'],10,strlen($row['Time']));
				$machine = $devices[$row['Dev']]['No'];

				$logs[] = array("date"=>substr($row['Date'],0,10),"pers_id"=>$pid,"log"=>$log,"machine"=>$machine);

			}
			
			$day = date ("Y-m-d", strtotime("+1 day", strtotime($day)));
			
		}
		
		// filter ID(s)		
		if ( ($idFrom != 0) && ($idTo != 0) ) {

			$logIdFiltered = [];

			for ($id=$idFrom; $id<=$idTo; ++$id) {

				foreach($logs as $i => $row) {

					if ($id == $row['pers_id']) $logIdFiltered[] = $row;

				}

			}
			
			$logs = $logIdFiltered;			

		}

		return $logs;
		
	}	

}

?>