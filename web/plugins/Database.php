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
		$item = \RedBeanPHP\R::dispense($collectionName);
		foreach ($data as $k => $v) {
			$item->{$k} = $v;
		}
		$id = \RedBeanPHP\R::store($item);
		return $id;
	}
	
	public function findAll($collectionName) {
		return \RedBeanPHP\R::findAll($collectionName);
	}
	
	public function getItemByField($table, $field, $value) {
		return \RedBeanPHP\R::findOne($table, " $field = ? ", [$value]);
	}
	
	public function update($bean) {
		\RedBeanPHP\R::store($bean);
	}
	
	public function nuke() {
		\RedBeanPHP\R::nuke();
	}
}
