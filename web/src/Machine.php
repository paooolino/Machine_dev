<?php

namespace Paooolino;

/*
request > side effect	
				> template 				>
				> data 						> output

side effect/template/data may be conditioned by application status

SIDE EFFECTS
send mail
write into db
write into file
set cookies

TEMPLATING
template: use a bunch of templates
	homepage
	single page
	list page
	
content widgets
	login form
	register form
	lost password form
	
scripts
	form validation
	
DATA
read data from database

- front controller (instantiates main class)
*/
class Machine {
	
	private $routes;
	private $SERVER;
	private $debug;
	
	public function __construct($server, $debug = false) {
		$this->SERVER = $server;
		$this->debug = $debug;
	}
	
	public function addRoute($name, $opts) {
		$routes[$name] = $opts;
	}
	
	public function run() {
		if ($this->debug) {
			$this->print_debug_info();
		}
	}
	
	private function print_debug_info() {
		echo "<!--";
		print_r($this->SERVER);
		echo "-->";
	}		
	
}