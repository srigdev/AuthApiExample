<?php

require 'classes/Food.php';

// get all food items route
$app->get('/food/', function () use($session) {

	// if($session->isValidRequest() == 0){
	// 	$session->goodnight();
	// }

	try{

		$return = array();
		$return['success'] = true;
		$return['data'] = Food::getFood();
		$return['time'] = time();
		//$return['id'] = $_SESSION['user_id'];
		$return['sid'] = session_id();

		echo json_encode($return);
	}
	catch(Exception $e) {
		echo json_encode($e->getMessage());
	}

});
