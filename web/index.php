<?php
require("../vendor/autoload.php");

$machine = new \Paooolino\Machine([$_SERVER, $_POST], true);

$machine->addPlugin("Link");
$machine->addPlugin("Form");
$machine->addPlugin("Database");

$machine->plugin("Database")->setUp("localhost", "root", "root", "sportgame_test");

// define forms

$machine->plugin("Form")->addForm("Register", [
	"action" => "/register/",
	"fields" => [
		"email",
		"password",
		"password2"
	]
]);

$machine->plugin("Form")->addForm("Login", [
	"action" => "/login/",
	"fields" => [
		"email",
		"password"
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
	$machine->plugin("Database")->addItem("user", [
		"email" => $state["POST"]["email"]
	]);
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);
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

$machine->addPage("/league/{leagueslug}/", function($machine, $leagueslug){
	return $machine->plugin("Database")->getItemByField("league", "slug", $leagueslug);
});
// action to init db

$machine->addAction("/init/", "GET", function($machine) {
	$machine->plugin("Database")->nuke();
	$machine->plugin("Database")->addItem("league", [
		"name" => "Serie A",
		"slug" => $machine->urlify("Serie A")
	]);
	$machine->plugin("Database")->addItem("league", [
		"name" => "Serie B",
		"slug" => $machine->urlify("Serie B")
	]);
	$machine->plugin("Database")->addItem("league", [
		"name" => "Lega Pro",
		"slug" => $machine->urlify("Lega Pro")
	]);
	$machine->plugin("Database")->addItem("league", [
		"name" => "Campionato Nazionale Dilettanti",
		"slug" => $machine->urlify("Campionato Nazionale Dilettanti")
	]);
	$path = $machine->plugin("Link")->Get("/");
	$machine->redirect($path);
});

$machine->run();
