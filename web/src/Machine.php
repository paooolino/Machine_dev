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
	private $plugins;
	
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
		$this->plugins = [];
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
	
	public function addPlugin($name) {
		$plugin_path = self::PLUGINS_PATH . $name . ".php"; 
		if (file_exists($plugin_path)) {
			include($plugin_path);
			$className = "\\Plugin\\" . $name;
			$this->plugins[$name] = new $className($this);
		} else {
			die("Unable to find " . $plugin_path);
		}
	}
	
	public function getState() {
		return [
			"SERVER" => $this->SERVER,
			"routes" => $this->routes			
		];
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

		// find plugin tags
		//	eg {{<plugin_name>|<param1>|<param2>}}
		$tags = [];
		preg_match_all("/{{(.*?)\|(.*?)}}/", $tpl, $tags);
		for ($i = 0; $i < count($tags[0]); $i++) {
			$pluginName = $tags[1][$i];
			if (isset($this->plugins[$pluginName])) {
				$parts = explode("|", $tags[2][$i]);
				$pluginMethod = array_shift($parts);
				if (method_exists($this->plugins[$pluginName], $pluginMethod)) {
					$value = $this->plugins[$pluginName]->{$pluginMethod}($parts);
					$tpl = str_replace($tags[0][$i], $value, $tpl);
				} else {
					die("Tag plugin not managed " . $pluginName . "->" . $pluginMethod);
				}
			} else {
				die("Plugin not managed " . $pluginName);
			}
		}
		return $tpl;
	}
}