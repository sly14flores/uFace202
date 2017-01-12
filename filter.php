<?php

class filter_client {

	var $allowed_clients;

	function __construct() {
		
		$this->allowed_clients = array("Sly","Mac","Errol","Zild");

	}

	function isAllowed($client) {

		$isAllowed = 'denied';

		foreach ($this->allowed_clients as $key => $value) {
			if ($value == $client) $isAllowed = 'granted';
		}

		return $isAllowed;

	}

}

$client = new filter_client();
$isAllowed = $client->isAllowed($_POST['clientName']);

echo $isAllowed;

?>