<?php

class pdo_db {
	
	var $dir_db;
	var $dbName;
	var $dbPathFile;
	var $db;
	var $prepare;
	var $sql;
	var $table;
	
	function __construct($table = "") {
	
		$this->dir_db = (preg_match("/htdocs/i", $_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT']."/uFace202" : $_SERVER['DOCUMENT_ROOT']."uFace202";
		$this->dbName = "backlogs.accdb";
		$this->dbPathFile = $this->dir_db."/".$this->dbName;
		$this->dbPathFile = str_replace("/","\\",$this->dbPathFile);

		if (!file_exists($this->dbPathFile)) {
			die("Could not find database file.");
		}		
		
		$this->db = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=".$this->dbPathFile."; Uid=; Pwd=;");

		$this->table = $table;
		
	}

	function getData($sql) {

		$stmt = $this->db->query($sql);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $results;

	}

	function auto_increment_one($column) {
		
		$this->sql = "ALTER TABLE ".$this->table." ALTER $column COUNTER(1,1);";
		$this->db->query($this->sql);
		
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
	
	function backLog($log) {
		
		$backLog = [];
		
		// skip duplicate entries
		$pers_id = $log['pers_id'];
		$log_time = date("m/d/Y h:i:s A",strtotime($log['log']));
		$result = $this->getData("SELECT * FROM dtr WHERE pers_id = '$pers_id' AND log_time = #$log_time#");
		if (count($result) > 0) return "log_duplicate";

		$backLog[] = array(
			"pers_id"=>$pers_id,
			"log_time"=>date("m/d/Y H:i:s",strtotime($log['log'])),
			"machine_no"=>$log['machine'],
			"system_log"=>date("m/d/Y H:i:s")			
		);
		
		$results = $this->getData("SELECT * FROM dtr");
		if (count($results) == 0) $this->auto_increment_one("ID");
		if (count($backLog) > 0) $this->insertDataMulti($backLog);	
		
	}
	
	function backLogs($data) {
		
		$logs = [];
		
		foreach ($data as $date => $day) {
			
			foreach ($day as $i => $log) {
				
				// skip duplicate entries
				$pers_id = $log['pers_id'];
				$log_time = date("m/d/Y h:i:s A",strtotime($log['log']));
				$result = $this->getData("SELECT * FROM dtr WHERE pers_id = '$pers_id' AND log_time = #$log_time#");
				if (count($result) > 0) continue;

				$logs[] = array(
					"pers_id"=>$pers_id,
					"log_time"=>date("m/d/Y H:i:s",strtotime($log['log'])),
					"machine_no"=>$log['machine'],
					"system_log"=>date("m/d/Y H:i:s")
				);
				
			}
			
		}

		$results = $this->getData("SELECT * FROM dtr");
		if (count($results) == 0) $this->auto_increment_one("ID");
		if (count($logs) > 0) $this->insertDataMulti($logs);

	}	

}

?>