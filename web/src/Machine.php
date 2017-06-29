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
	
	const TEMPLATE_PATH = "templates/";
	
	public function __construct($server, $debug = false) {
		$this->SERVER = $server;
		$this->debug = $debug;
	}
	
	public function addRoute($name, $opts) {
		$this->routes[$name] = $opts;
	}
	
	public function run() {
		$template = $this->get_template();
		$data = $this->get_data();
		$html = $this->populate_template($template, $data);
		
		echo $html;
		
		if ($this->debug) {
			$this->print_debug_info();
		}
	}
	
	private function print_debug_info() {
		echo "<!--";
		print_r($this->SERVER);
		echo "-->";
	}		
	
	private function get_template() {
		if (!isset($this->routes[$this->SERVER["REQUEST_URI"]])) {
			return "404";
		}
		$route = $this->routes[$this->SERVER["REQUEST_URI"]];
		$template_file_name = self::TEMPLATE_PATH . $route["template"];
		if (!file_exists($template_file_name)) {
			return "404";
		}
		
		ob_start();
		require($template_file_name);
		$output = ob_get_contents();
		ob_end_clean();
		
		return $output;
	}
	
	private function get_data() {
		return [];
	}
	
	private function populate_template($template, $data) {
		return $template;
	}
	
}