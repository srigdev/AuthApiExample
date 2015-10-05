<?php

//require PHP classes
require 'vendor/autoload.php';
require 'classes/Session.php';

//create slim app object
$app = new \Slim\Slim();
$app->contentType('application/json');

// instantiate session class
require_once('classes/Session.php');
$session = new Session();
$session->start();

//add routes
require 'routes/Food.php';
require 'routes/Login.php';

$app->run();
