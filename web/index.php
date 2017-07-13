<?php
require("../vendor/autoload.php");

$machine = new \Paooolino\Machine([$_SERVER, $_POST], true);

$machine->addPlugin("Link");
$machine->addPlugin("Form");
$machine->addPlugin("Database");

$machine->plugin("Database")->setUp("localhost", "root", "root", "machinedb");

$machine->plugin("Form")->addForm("Register", [
	"action" => "/register/",
	"fields" => [
		"email",
		"password",
		"password2"
	]
]);

$machine->plugin("Form")->addForm("Login", "/login/", [
	"action" => "/login/",
	"fields" => [
		"email",
		"password"
	]
]);

$machine->addRoute("/", [
	"template" => "page.php",
	"data" => [
		"titolo" => "Home page",
		"testo" => "Questa è la homepage del sito.",
		"foto" => ""
	]
]);

$machine->addRoute("/chi-siamo/", [
	"template" => "page.php",
	"data" => [
		"titolo" => "Chi siamo",
		"testo" => "Abbiamo facce che non conosciamo.",
		"foto" => ""
	]
]);
	
$machine->addRoute("/registrati/", [
	"template" => "page.php",
	"data" => [
		"titolo" => "Registrazione",
		"testo" => "{{Form|Render|Register}}",
		"foto" => ""
	]
]);

$machine->addAction("/register/", function($machine) {
	$state = $machine->getState();
	$machine->plugin("Database")->addItem("user", [
		"email" => $state["POST"]["email"]
	]);
});

$machine->addRoute("/login/", [
	"template" => "page.php",
	"data" => [
		"titolo" => "Accedi",
		"testo" => "{{Form|Render|Login}}",
		"foto" => ""
	]
]);

$machine->run();
