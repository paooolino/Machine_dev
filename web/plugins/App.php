<?php
namespace Plugin;

class App {
	
	private $machine;

	private $turn;
	private $eventQueue;
	
	function __construct($machine) {
		$this->machine = $machine;
	}
}
