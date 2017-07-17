<?php
namespace Plugin;

class Auth {
	
	private $machine;
	
	const AUTH_COOKIE_NAME = "KfjqrRAVhuJlzvX5ANWz";
	
	function __construct($machine) {
		$this->machine = $machine;
	}
	
	public function generateAuthCookies($user_id) {
		$state = $this->machine->getState();
		
		// generate a unique session code.
		$sessioncode = md5($this->machine->uuid());
		
		// set the auth cookie with the session code.
		setcookie(self::AUTH_COOKIE_NAME, $sessioncode, 0, "/");
		
		// save session in db.
		$this->machine->plugin("Database")->addItem("loginsession", [
			"user_id" => $user_id,
			"sessioncode" => $sessioncode,
			"ip" => $state["SERVER"]["REMOTE_ADDR"],
			"created" => date("Y-m-d H:i:s")
		]);
	}
	
	public function checkLogin() {
		// retrieve cookie value
		$state = $this->machine->getState();
		if (isset($state["COOKIE"][self::AUTH_COOKIE_NAME])) {
			$sessioncode = $state["COOKIE"][self::AUTH_COOKIE_NAME];
			$session = $this->machine->plugin("Database")->getItemByField("loginsession", "sessioncode", $sessioncode);
			if ($session) {
				return $session->user_id;
			}
		}
			
		return 0;
	}
}
