<?php
require("../vendor/autoload.php");

$machine = new \Paooolino\Machine($_SERVER, true);

$machine->addRoute("/", [
	"template" => "home.php",
	"data" => [
		"titolo" => "",
		"testo" => "",
		"foto" => ""
	]
]);

$machine->addRoute("/chi-siamo/", [
	"template" => "single.php",
	"data" => [
		"titolo" => "",
		"testo" => "",
		"foto" => ""
	]
]);
	
$machine->addRoute("/registrati/", [
	"template" => "single.php",
	"data" => [
		"titolo" => "",
		"testo" => "",
		"foto" => ""
	]
]);

$machine->run($_SERVER);
