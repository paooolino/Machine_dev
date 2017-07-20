<?php
require("../vendor/autoload.php");

$machine = new \Paooolino\Machine([$_SERVER, $_POST, $_COOKIE], true);

// ============================================================================
//	plugins configuration
// ============================================================================

// add plugins
$machine->addPlugin("Link");
$machine->addPlugin("Form");
$machine->addPlugin("Database");
$machine->addPlugin("Error");
$machine->addPlugin("Email");
$machine->addPlugin("Auth");
$machine->addPlugin("Breadcrumb");

$machine->addPlugin("App");

// setup database
$machine->plugin("Database")->setUp("localhost", "root", "root", "sportgame_test");

// use email hook to log sent emails in db
$machine->plugin("Email")->addHook("after_mail_send", function($machine, $date, $to, $subject, $html, $result) {
	$machine->plugin("Database")->addItem("logmail", [
		"date" => $date,
		"to" => $to,
		"subject" => $subject,
		"html" => $html,
		"result" => $result
	]);
});

// set data callback for Auth in order to retrieve the user infos
$machine->plugin("Auth")->setDataCallback(function($machine, $user_id) {
	return $machine->plugin("Database")->getItem("user", $user_id);
});

// check Auth login in every page
$machine->plugin("Auth")->checkLogin();

// check if turns to pass
$machine->plugin("App")->checkTime();

// define forms
$machine->plugin("Form")->addForm("Register", [
	"action" => "/register/",
	"fields" => [
		"email",
		["password", "password"],
		["password2", "password"]
	]
]);

$machine->plugin("Form")->addForm("Login", [
	"action" => "/login/",
	"fields" => [
		"email",
		["password", "password"]
	]
]);

// define errors
$machine->plugin("Error")->registerError("EMAIL_REGISTER", "Errore mail");
$machine->plugin("Error")->registerError("PASSWORD_REGISTER", "Errore password");
$machine->plugin("Error")->registerError("PASSWORD_REGISTER_CONFIRM", "Le due password non corrispondono");
$machine->plugin("Error")->registerError("USER_NOT_PRESENT", "L'utente cercato non esiste.");
$machine->plugin("Error")->registerError("USER_YET_ACTIVE", "L'utente è già attivo.");
$machine->plugin("Error")->registerError("LOGIN_FAILED", "Impossibile completare l'operazione: le credenziali inserite non sono corrette, oppure l'utente non è attivo.");

// ============================================================================
//	pages and actions
// ============================================================================

// home
// ============================================================================
$machine->addPage("/", function($machine) {
	return [
		"template" => "home.php",
		"data" => [
			"leagues" => $machine->plugin("Database")->findAll("league")
		]
	];
});

// informative pages
// ============================================================================
$machine->addPage("/chi-siamo/", function() {
	return [
		"template" => "page.php",
		"data" => [
			"titolo" => "Chi siamo",
			"testo" => "Abbiamo facce che non conosciamo.",
			"foto" => ""
		]
	];
});
	
// registration
// ============================================================================
$machine->addPage("/registrati/", function() {
	return [
		"template" => "page.php",
		"data" => [
			"titolo" => "Registrazione",
			"testo" => "{{Form|Render|Register}}",
			"foto" => ""
		]
	];
});

$machine->addAction("/register/", "POST", function($machine) {
	$state = $machine->getState();
	
	// filter input
	$email = filter_var(trim($state["POST"]["email"]), FILTER_VALIDATE_EMAIL);
	$password = filter_var(trim($state["POST"]["password"]), FILTER_VALIDATE_REGEXP, [
		"options" => [
			"regexp" => "/.{6,}/"
		]
	]);
	$password2 = trim($state["POST"]["password2"]);
	
	// look for errors
	if ($email == "") {
		$machine->plugin("Error")->addError("EMAIL_REGISTER");
	}
	if ($password == "") {
		$machine->plugin("Error")->addError("PASSWORD_REGISTER");
	}
	if ($password != "" && $password !== $password2) {
		$machine->plugin("Error")->addError("PASSWORD_REGISTER_CONFIRM");
	}
	$machine->plugin("Error")->raise();
	
	// save in db
	$activid = md5($machine->uuid());
	$machine->plugin("Database")->addItem("user", [
		"email" => $email,
		"password" => password_hash($password, PASSWORD_BCRYPT),
		"activid" => $activid,
		"active" => false
	]);
	
	// send mail
	$machine->plugin("Email")->send([
		"to" => $email,
		"subject" => "La tua registrazione a sportGame",
		"template" => "email/register.php",
		"data" => [
			"activlink" => $machine->plugin("Link")->Get("/activate/" . $activid . "/")
		]
	]);
	
	// success redirect
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);
});

// activation
// ============================================================================
$machine->addAction("/activate/{activid}/", "GET", function($machine, $activid) {
	$user = $machine->plugin("Database")->getItemByField("user", "activid", $activid);
	
	// look for errors
	if (!$user) {
		$machine->plugin("Error")->raiseError("USER_NOT_PRESENT");
	}
	
	if ($user->active) {
		$machine->plugin("Error")->raiseError("USER_YET_ACTIVE");
	}
	
	// save in db
	$user->active = true;	
	$machine->plugin("Database")->update($user);
	
	// success redirect
	$path = $machine->plugin("Link")->Get("/activation-completed/");
	$machine->redirect($path);
});

$machine->addPage("/activation-completed/", function() {
	return [
		"template" => "page.php",
		"data" => [
			"titolo" => "Attivazione completata",
			"testo" => "Complimenti! L'attivazione è stata completata.",
		]
	];
});

// login
// ============================================================================
$machine->addPage("/login/", function() {
	return [
		"template" => "page.php",
		"data" => [
			"titolo" => "Accedi",
			"testo" => "{{Form|Render|Login}}",
			"foto" => ""
		]
	];
});

$machine->addAction("/login/", "POST", function($machine) {
	$state = $machine->getState();
	
	// filter input
	$email = filter_var(trim($state["POST"]["email"]), FILTER_VALIDATE_EMAIL);
	$password = filter_var(trim($state["POST"]["password"]), FILTER_VALIDATE_REGEXP, [
		"options" => [
			"regexp" => "/.{6,}/"
		]
	]);
	
	// look for errors
	if ($email == "") {
		$machine->plugin("Error")->raiseError("LOGIN_FAILED");
	}
	
	if ($password == "") {
		$machine->plugin("Error")->raiseError("LOGIN_FAILED");
	}
	
	// get user
	$user = $machine->plugin("Database")->getItemByField("user", "email", $email);
	if (!$user) {
		$machine->plugin("Error")->raiseError("LOGIN_FAILED");
	}
	
	if (!$user->active) {
		$machine->plugin("Error")->raiseError("LOGIN_FAILED");
	}
	
	if (!password_verify($password, $user->password)) {
		$machine->plugin("Error")->raiseError("LOGIN_FAILED");
	}
	
	// set cookies
	$machine->plugin("Auth")->generateAuthCookies($user->id);

	// success redirect
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);	
});

// league pages
// ============================================================================
$machine->addPage("/league/{leagueslug}/", function($machine, $leagueslug) {
	$Link = $machine->plugin("Link");

	// get content data
	$league = $machine->plugin("Database")->getItemByField("league", "slug", $leagueslug);
	$standings = $machine->plugin("App")->getStandings($league->level);
	$matches = $machine->plugin("App")->getNextMatches($league->level);

	// add breadcrumb
	$machine->plugin("Breadcrumb")->add("Home", $Link->Get("/"));
	$machine->plugin("Breadcrumb")->setLabel($league->name);
	
	return [
		"template" => "league.php",
		"data" => [
			"standings" => $standings,
			"matches" => $matches,
			"titolo" => $league->name,
			"testo" => "League infos."
		]
	];
});

// team pages
// ============================================================================
$machine->addPage("/team/{team_id}/", function($machine, $team_id) {
	$team = $machine->plugin("Database")->getItem("team", $team_id);
	return [
		"template" => "page.php",
		"data" => [
			"titolo" => $team->teamname
		]
	];
});

// ============================================================================
//	debug/testing
// ============================================================================

// sample db
$machine->addAction("/sample-database/nuke/", "GET", function($machine) {
	$machine->plugin("Database")->nuke();
});

$machine->addAction("/sample-database/user/", "GET", function($machine) {
	$machine->plugin("Database")->addItem("user", [
		"email" => "paooolino@gmail.com",
		"password" => '$2y$10$Vy05GvKyPkpl3lhM77GYl.oWUnHC24ZcPPWKvHGXzjvjqG7Q.t0DC',
		"activid" => "770d8878863c42391a8d7be66b127a9e",
		"active" => true
	]);
});

$machine->addAction("/sample-database/loginsession/", "GET", function($machine) {
	$machine->plugin("Database")->addItem("loginsession", [
		"user_id" => 1,
		"sessioncode" => '6450f4122a1b493de1372ad5ad5e8b12',
		"ip" => "127.0.0.1",
		"created" => date("Y-m-d H:i:s")
	]);
});

$machine->addAction("/sample-database/logmail/", "GET", function($machine) {
	$machine->plugin("Database")->addItem("logmail", [
		"date" => date("Y-m-d H:i:s"),
		"to" => 'paooolino@gmail.com',
		"subject" => "La tua registrazione a sportGame",
		"html" => '
			<p>Grazie per esserti registrato su <b>SportGame</b>!</p>
			<p>Per attivare il tuo account, clicca sul seguente link:</p>
			<p>http://machine.local/activate/770d8878863c42391a8d7be66b127a9e/</p>
		',
		"result" => 0
	]);	
});

$machine->addAction("/sample-database/option/", "GET", function($machine) {
	$machine->plugin("Database")->addItem("option", [
		"optkey" => "turn",
		"optvalue" => 0
	]);	
	$machine->plugin("Database")->addItem("option", [
		"optkey" => "gameStartedAt",
		"optvalue" => date("Y-m-d H:i:s")
	]);	
	$machine->plugin("Database")->addItem("option", [
		"optkey" => "turnLengthMinutes",
		"optvalue" => 60
	]);
});

$machine->addAction("/sample-database/country/", "GET", function($machine) {
	$machine->plugin("Database")->importCSV("country", "data/countries.csv");
});

$machine->addAction("/sample-database/team/", "GET", function($machine) {
	$machine->plugin("Database")->importCSV("team", "data/teams.csv");
});

/*
$machine->addAction("/sample-database/reponames/", "GET", function($machine) {
	$machine->plugin("Database")->importCSV("reponames", "data/names.csv");
});

$machine->addAction("/sample-database/reposurnames/", "GET", function($machine) {
	$machine->plugin("Database")->importCSV("reposurnames", "data/surnames.csv");
});

$machine->addAction("/sample-database/player/", "GET", function($machine) {
	//
});
*/

// database initialization
$machine->addAction("/init/", "GET", function($machine) {
	/*
	file_get_contents($machine->siteurl . "/sample-database/nuke/");
	file_get_contents($machine->siteurl . "/sample-database/league/");
	file_get_contents($machine->siteurl . "/sample-database/user/");
	file_get_contents($machine->siteurl . "/sample-database/loginsession/");
	file_get_contents($machine->siteurl . "/sample-database/logmail/");
	file_get_contents($machine->siteurl . "/sample-database/option/");
	file_get_contents($machine->siteurl . "/sample-database/country/");
	file_get_contents($machine->siteurl . "/sample-database/team/");

	$machine->plugin("App")->createLeagues(5);
	$machine->plugin("App")->assignSportrights(10);
	$machine->plugin("App")->createStandings();
	$machine->plugin("App")->createFixtures(10);
	*/
	
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);
});

$machine->run();
