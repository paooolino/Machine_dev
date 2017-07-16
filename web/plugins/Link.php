<?php
namespace Plugin;

class Link {
	
	private $machine;

	function __construct($machine) {
		$this->machine = $machine;
	}
	
	public function Get($params) {
		if (gettype($params) == "string") {
			$params = [$params];
		}
		$slug = $params[0];
		$state = $this->machine->getState();
		return
			$state["SERVER"]["REQUEST_SCHEME"] . "://" . 
			$state["SERVER"]["HTTP_HOST"] .
			$slug;
	}
}
