<?php
namespace Plugin;

class Database {
	
	private $machine;

	function __construct($machine) {
		$this->machine = $machine;
		
		// dependency check
		if (!class_exists("\RedBeanPHP\R")) {
			die("Error: \RedBeanPHP\R class not defined. Please run<br><pre>composer require gabordemooij/redbean</pre><br>to add it to your project.");
		}
	}
	
	public function setUp($db_host, $db_user, $db_pass, $db_name) {
		\RedBeanPHP\R::setup('mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass);
	}
	
	public function addItem($collectionName, $data) {
		var_dump($collectionName);
		var_dump($data);
		die("TO DO plugins/Database/addItem");
	}
	
	public function findAll($collectionName) {
		return \RedBeanPHP\R::findAll($collectionName);
	}
}
