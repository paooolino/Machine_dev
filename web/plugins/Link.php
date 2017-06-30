<?php
namespace Plugin;

class Link {
	
	private $machine;

	function __construct($machine) {
		$this->machine = $machine;
	}
	
	public function Get($params) {
		$slug = $params[0];
		$state = $this->machine->getState();
		return
			$state["SERVER"]["REQUEST_SCHEME"] . "://" . 
			$state["SERVER"]["HTTP_HOST"] .
			$slug;
	}
}
