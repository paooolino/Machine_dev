<?php
require("../vendor/autoload.php");

$machine = new \Paooolino\Machine($_SERVER, true);

$machine->addPlugin("links");
$machine->addPlugin("forms");

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
		"testo" => "{{FORM|Registrazione}}",
		"foto" => ""
	]
]);

$machine->run($_SERVER);
