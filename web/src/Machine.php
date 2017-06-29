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
	private $plugins = [];
	
	const TEMPLATE_PATH = "templates/";
	const PLUGINS_PATH = "plugins/";
	
	/**
	 * Machine class constructor
	 *
	 * @param $server Array The $_SERVER array
	 * @param $debug bool	Whether debug mode is enabled
	 */
	 public function __construct($server, $debug = false) {
		$this->SERVER = $server;
		$this->debug = $debug;
	}
	
	public function addPlugin($name) {
		$plugin_path = PLUGINS_PATH . $name . "/index.php"); 
		if (file_exists($plugin_path)) {
			include($plugin_path);
			array_push($this->plugins, $name);
		}
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
		if (!isset($this->routes[$this->SERVER["REQUEST_URI"]])) {
			return [];
		}
		$route = $this->routes[$this->SERVER["REQUEST_URI"]];
		return $route["data"];
	}
	
	private function populate_template($tpl, $data) {
		// populate simple tag with data
		foreach($data as $k => $v) {
			$tpl = str_replace("{{".$k."}}", $v, $tpl);
		}
		
		// find complex tags		
		/*
		$tags = [];
		preg_match_all("/{{(.*)}}/", $tpl, $tags);
		foreach ($tags[1] as $tag) {
			$parts = explode("|", $tag);
			if (count($parts) == 1) {
				// simple tag.
				$tpl = str_replace("{{" . $tag . "}}", $data[$tag], $tpl); 
			}
			if (count($parts) == 2) {
				$command = $parts[0];
				$value = $parts[1];
				switch ($command) {
					case "LINK":
						$tpl = str_replace("{{" . $tag . "}}", $this->getLink($parts[1]), $tpl);
						break;
					case "FORM":
						$tpl = str_replace("{{" . $tag . "}}", $this->getForm($parts[1]), $tpl);
						break;						
				}
			}
		}
		*/
		return $tpl;
	}
	
	/*
	private function getLink($route) {
		return $this->SERVER["REQUEST_SCHEME"] .
			"://" . $this->SERVER["HTTP_HOST"] .
			$route;
	}
	
	private function getForm($formname) {
		return "FORM";
	}
	*/
}