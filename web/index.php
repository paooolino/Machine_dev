<?php
require("../vendor/autoload.php");

$machine = new \Paooolino\Machine($_SERVER, true);

$machine->addPlugin("Link");
$machine->addPlugin("Form");

$machine->plugin("Form")->addForm("Register", [
	"email",
	"password",
	"password2"
]);

$machine->plugin("Form")->addForm("Login", [
	"email",
	"password"
]);

$machine->addRoute("/", [
	"template" => "home.php",
	"data" => [
		"titolo" => "Home page",
		"testo" => "Questa è la homepage del sito.",
		"foto" => ""
	]
]);

$machine->addRoute("/chi-siamo/", [
	"template" => "single.php",
	"data" => [
		"titolo" => "Chi siamo",
		"testo" => "Abbiamo facce che non conosciamo.",
		"foto" => ""
	]
]);
	
$machine->addRoute("/registrati/", [
	"template" => "single.php",
	"data" => [
		"titolo" => "Registrazione",
		"testo" => "{{Form|Render|Register}}",
		"foto" => ""
	]
]);

$machine->addRoute("/login/", [
	"template" => "single.php",
	"data" => [
		"titolo" => "Accedi",
		"testo" => "{{Form|Render|Login}}",
		"foto" => ""
	]
]);

$machine->run($_SERVER);
