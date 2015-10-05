<?php

/**
 * Login route [PUBLIC]
 *
 * attemps to authenticate the user via username and password
 *
 * POST PARAMS
 * @param email | users email address
 * @param pass | users password
 * @return (object) { success: 'bool', error_code: 'int', data: 'array' }
 */
$app->post('/login/', function () use($session, $app) {

	try{

		// TODO: figure out sending actual email with @ symbol
		$req = json_decode($app->request->getBody(), true);
		$return = $session->authenticate( $req['email'], $req['pass'] );

		echo json_encode($return);

	}
	catch(Exception $e) {
		echo json_encode($e->getMessage());
	}

});

/**
 * New user route [PRIVATE]
 *
 * adds a new users to the users table and the authentication table
 *
 * POST PARAMS
 * @param email | users email address
 * @param pass | users password
 * @return (object) { success: 'bool', error_code: 'int', data: 'array' }
 */
$app->post('/new_user/', function () use($session, $app) {

	try{

		// check for valid session
		if($session->isValidRequest() == 0){
			$session->goodnight();
		}

		$req = json_decode($app->request->getBody(), true);
		$return = $session->createNewUser( $req['email'], $req['pass'] );

		echo json_encode($return);

	}
	catch(Exception $e) {
		echo json_encode($e->getMessage());
	}

});
