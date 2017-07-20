<?php
namespace Plugin;

class Log {
	
	private $machine;
	
	function __construct($machine) {
		$this->machine = $machine;
	}
	
	public function log($type, $string) {
		$data = [
			date("Y-m-d H:i:s"),
			$type,
			$string
		];
		file_put_contents("log.csv", implode("\t", $data) . "\r\n", FILE_APPEND); 
	}
}
