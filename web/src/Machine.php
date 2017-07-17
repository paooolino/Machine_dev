<?php

/**
 * The author namespace.
 */
namespace Paooolino;

/**
 * The main Application class.
 */
class Machine {
	
	/**
	 * A collection of routes. Routes can be added using addRoute() or addAction() methods.
	 */
	private $routes;
	
	/**
	 * A copy of the $_SERVER php array. This is set by the constructor.
	 * Passing a custom array may be helpful for testing.
	 */
	private $SERVER;
	private $POST;
	
	/**
	 * Whether activate debug mode. Set by the constructor.
	 */
	private $debug;
	
	/**
	 * A collection of instances of plugin classes. 
	 * This is populated by the addPlugin() method.
	 */
	private $plugins;
	
	/**
	 *	Cocur\Slugify library
	 */
	private $slugify;
	
	/**
	 * Path constants.
	 */
	const TEMPLATE_PATH = "templates/";
	const PLUGINS_PATH = "plugins/";
	
	/**
	 * Machine class constructor
	 *
	 * @param $server Array The $_SERVER array
	 * @param $debug bool	Whether debug mode is enabled
	 */
	 public function __construct($state, $debug = false) {
		 
		// dependency check
		if (!class_exists("\Cocur\Slugify\Slugify")) {
			die("Error: \Cocur\Slugify\Slugify class not defined. Please run<br><pre>composer require cocur/slugify</pre><br>to add it to your project.");
		}
		
		$this->SERVER = $state[0];
		$this->POST = $state[1];
		$this->debug = $debug;
		$this->plugins = [];
		$this->slugify = new \Cocur\Slugify\Slugify();
	}
	
	public function slugify($s) {
		return $this->slugify->slugify($s);
	}
	
	/**
	 *	Add a route.
	 *	Adding a route enables the Application to respond with a page.
	 *	
	 *	@param $name String The name of the route should be the complete slug.
	 *	@param $opts Array like the following example:
	 *		[
	 *			"template" => "<template_name>.php",
	 *			"data" => [
	 *				"<key_1>" => "<value_1>",
	 *				"<key_2>" => "<value_2>",
	 *				...
	 *				"<key_n>" => "<value_n>"
	 *			]
	 *		]
	 */
	public function addPage($name, $cb) {
		if (isset($this->routes[$name]["GET"])) {
			die("Route exists form GET method (" . $name . ")" );
		}
		$this->routes[$name] = [
			"GET" => $cb
		];
	}
	
	/**
	 *	Add an action.
	 *	Adding an actions enables the Application to execute a callback function
	 *	and produce side effects.
	 *	
	 *	@param $name String The name of the action should be the complete slug.
	 *	@param $cb Function to be executed
	 */
	public function addAction($name, $method, $cb) {
		if (isset($this->routes[$name][$method])) {
			die("Route exists form GET method (" . $name . ")" );
		}
		$this->routes[$name] = [
			$method => $cb
		];
	}
	
	// used by plugins
	public function executeHook($arrFunc, $arguments) {
		foreach ($arrFunc as $func) {
			call_user_func_array($func, $arguments);
		}
	}
	
	/**
	 *	Run the application. Get template and data to be mixed together and
	 *	produce the final HTML output.
	 */
	public function run() {
		$output = "404";
		
		$path = $this->SERVER["REQUEST_URI"];
		$method = $this->SERVER["REQUEST_METHOD"];
		$route_matchinfo = $this->match_route($path, $method);
		if ($route_matchinfo) {
			$result = call_user_func_array($route_matchinfo["callback"], $route_matchinfo["params"]);
			
			// actions will not execute the following, because their callback always have to redirect.
			if (isset($result["template"])) {
				$data = isset($result["data"]) ? $result["data"] : [];
				$output = $this->get_output_template($result["template"], $data);
			}
		}
		
		echo $output;
		
		if ($this->debug) {
			$this->print_debug_info();
		}
	}
	
	public function get_output_template($template, $data) {
		$output = "";
		
		$template_file_name = self::TEMPLATE_PATH . $template;
		if (file_exists($template_file_name)) {
			// data fields are available as regular php variables in templates
			foreach ($data as $k => $v) {
				$$k = $v;
			}
			ob_start();
			require($template_file_name);
			$template = ob_get_contents();
			ob_end_clean();
			
			$output = $this->populate_template($template, $data);
		}
		return $output;
	}
	
	private function match_route($path, $method) {
		// $path is for example 
		//	/route/variable/
		
		// $route 
		foreach ($this->routes as $routename => $routearr) {
			// $routename is for example
			//	/route/{parameter}/
			$routename_exp = preg_replace("/\{(.*?)\}/", "(.*?)", $routename);
			$routename_exp = str_replace("/", "\/", $routename_exp);
			$regexp = "/^" . $routename_exp . "$/";
			
			$matches = [];
			$n_matches = preg_match_all($regexp, $path, $matches);
			if ($n_matches > 0) {
				if (isset($this->routes[$routename][$method])) {
					return [
						"callback" => $this->routes[$routename][$method],
						"params" => array_merge([$this], isset($matches[1]) ? $matches[1] : [])
					];
				}
			}
		}
		
		die();
	}
	
	/**
	 *	Add a plugin by name.
	 *
	 *	A plugin is an external class which defines new methods mapped in the
	 *	template tag e.g.
	 *		{{Link|Get|<param_1>|<param_2|...|<param_n>}}
	 *	Link > is the plugin class
	 *	Get > is a public method defined in the plugin class
	 *	optional parameters are passed in.
	 *
	 *	@param $name String The plugin class name.
	 */
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
	
	/**
	 *	Get the Application state.
	 *	This is a collection of values exposed to plugins.
	 */
	public function getState() {
		return [
			"SERVER" => $this->SERVER,
			"POST" => $this->POST,
			"routes" => $this->routes			
		];
	}
	
	public function redirect($path) {
		header("location: " . $path);
		die();
	}
	
	/**
	 *	return the plugin instance.
	 */
	public function plugin($name) {
		return $this->plugins[$name];
	}
	
	private function print_debug_info() {
		echo "<!--";
		print_r($this->SERVER);
		echo "-->";
	}		
	
	public function populate_template($tpl, $data) {
		// populate simple tag with data
		foreach($data as $k => $v) {
			// if a string, try the tag substitution
			if (gettype($v) == "string") {
				$tpl = str_replace("{{".$k."}}", $v, $tpl);
			}
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