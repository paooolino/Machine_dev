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
	 * A collection of routes. Routes can be added using addRoute() method.
	 */
	private $routes;
	
	/**
	 * A collection of actions. Actions can be added using addAction() method.
	 */
	private $actions;
	
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
		$this->SERVER = $state[0];
		$this->POST = $state[1];
		$this->debug = $debug;
		$this->plugins = [];
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
	public function addRoute($name, $opts) {
		$this->routes[$name] = $opts;
	}
	
	/**
	 *	Add an action.
	 *	Adding an actions enables the Application to execute a callback function
	 *	and produce side effects.
	 *	
	 *	@param $name String The name of the action should be the complete slug.
	 *	@param $cb Function to be executed
	 */
	public function addAction($name, $cb) {
		$this->actions[$name] = $cb;
	}
	
	/**
	 *	Run the application. Get template and data to be mixed together and
	 *	produce the final HTML output.
	 */
	public function run() {
		// check for an action...
		if (isset($this->actions[$this->SERVER["REQUEST_URI"]])) {
			$this->actions[$this->SERVER["REQUEST_URI"]]($this);
			return;
		}
		
		// if not found, check for a route...
		$data = $this->get_data();
		$template = $this->get_template($data);
		$html = $this->populate_template($template, $data);
		
		echo $html;
		
		if ($this->debug) {
			$this->print_debug_info();
		}
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
	
	/**
	 *	Get a template, based on the current request uri and the routes added.
	 */
	private function get_template($data) {
		if (!isset($this->routes[$this->SERVER["REQUEST_URI"]])) {
			return "404";
		}
		$route = $this->routes[$this->SERVER["REQUEST_URI"]];
		$template_file_name = self::TEMPLATE_PATH . $route["template"];
		if (!file_exists($template_file_name)) {
			return "404";
		}

		// data fields are available as regular php variables in templates
		foreach ($data as $k => $v) {
			$$k = $v;
		}
		
		ob_start();
		require($template_file_name);
		$output = ob_get_contents();
		ob_end_clean();
		
		return $output;
	}

	/**
	 *	Return the data associated to the route.
	 */	
	private function get_data() {
		if (!isset($this->routes[$this->SERVER["REQUEST_URI"]])) {
			return [];
		}
		$route = $this->routes[$this->SERVER["REQUEST_URI"]];
		return $route["data"];
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