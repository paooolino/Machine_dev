<?php
namespace Plugin;

class Form {
	
	private $machine;
	
	function __construct($machine) {
		$this->machine = $machine;
	}
	
	public function Get($params) {
		return "<form><input /><button>Submit</button></form>";
	}
}