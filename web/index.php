<?php
require("../vendor/autoload.php");

use Ramsey\Uuid\Uuid;

$machine = new \Paooolino\Machine([$_SERVER, $_POST], true);

$machine->addPlugin("Link");
$machine->addPlugin("Form");
$machine->addPlugin("Database");
$machine->addPlugin("Error");
$machine->addPlugin("Email");

$machine->plugin("Database")->setUp("localhost", "root", "root", "sportgame_test");
$machine->plugin("Email")->addHook("after_mail_send", function($machine, $date, $to, $subject, $html, $result) {
	$machine->plugin("Database")->addItem("logmail", [
		"date" => $date,
		"to" => $to,
		"subject" => $subject,
		"html" => $html,
		"result" => $result
	]);
});

// define forms

$machine->plugin("Form")->addForm("Register", [
	"action" => "/register/",
	"fields" => [
		"email",
		["password", "password"],
		["password2", "password"]
	]
]);

$machine->plugin("Error")->registerError("EMAIL_REGISTER", "Errore mail");
$machine->plugin("Error")->registerError("PASSWORD_REGISTER", "Errore password");
$machine->plugin("Error")->registerError("PASSWORD_REGISTER_CONFIRM", "Le due password non corrispondono");
$machine->plugin("Error")->registerError("USER_NOT_PRESENT", "L'utente cercato non esiste.");
$machine->plugin("Error")->registerError("USER_YET_ACTIVE", "L'utente è già attivo.");
$machine->plugin("Error")->registerError("LOGIN_FAILED", "Impossibile completare l'operazione: le credenziali inserite non sono corrette.");

$machine->plugin("Form")->addForm("Login", [
	"action" => "/login/",
	"fields" => [
		"email",
		["password", "password"]
	]
]);

// define pages and actions

$machine->addPage("/", function($machine) {
	return [
		"template" => "home.php",
		"data" => [
			"leagues" => $machine->plugin("Database")->findAll("league")
		]
	];
});

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
	$activid = md5(Uuid::uuid4());
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
	} else {
		if (!password_verify($password, $user->password)) {
			$machine->plugin("Error")->raiseError("LOGIN_FAILED");
		}
	}

	// success redirect
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);	
});

$machine->addPage("/league/{leagueslug}/", function($machine, $leagueslug) {
	$league = $machine->plugin("Database")->getItemByField("league", "slug", $leagueslug);
	return [
		"template" => "page.php",
		"data" => [
			"titolo" => $league->name,
			"testo" => "League infos."
		]
	];
});

// action to init db

$machine->addAction("/init/", "GET", function($machine) {
	$machine->plugin("Database")->nuke();
	$machine->plugin("Database")->addItem("league", [
		"name" => "Serie A",
		"slug" => $machine->slugify('Serie A')
	]);
	$machine->plugin("Database")->addItem("league", [
		"name" => "Serie B",
		"slug" => $machine->slugify("Serie B")
	]);
	$machine->plugin("Database")->addItem("league", [
		"name" => "Lega Pro",
		"slug" => $machine->slugify("Lega Pro")
	]);
	$machine->plugin("Database")->addItem("league", [
		"name" => "Campionato Nazionale Dilettanti",
		"slug" => $machine->slugify("Campionato Nazionale Dilettanti")
	]);
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);
});

$machine->run();
