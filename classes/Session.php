<?php

// all classes that contain sql calls must include Base.php
include_once('Base.php');

class Session extends Base {

/**
* PUBLIC METHODS
*	below are public methods that can be called from any route that uses the
*	$session parameter, which is probably every route.
*/

/**
 * Starts a php session with the name 'SRIG_SESSION'
 */
public function start()
{
    // Set a custom session name
    $this_name = 'SRIG_SESSION';
    // Set to true if using https.
    $secure = false;
    // This stops javascript being able to access the session id.
    $httponly = true;
    // client must have cookies enabled
    ini_set('session.use_only_cookies', 1);
    
    // Gets current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
    session_name($this_name);
	session_start();
}

/**
 * Kills the php session
 */
public function destroy( )
{
    // Unset all session values
    $_SESSION = array();
    // get session parameters
    $params = session_get_cookie_params();
    // Delete the actual cookie.
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    // Destroy session
    session_destroy();
    session_write_close();
}

/**
 * validates the session by checking the current session against the users session in the db
 * and checks that the users expiration time on their last known session hasn't been surpassed
 * @return (boolean)
 */
public function isValidRequest()
{	
	// session is non existant on the server;
	if(session_status() == 2){
		
		// session exists but is empty
		if(count($_SESSION) == 0){
			self::destroy();
			return false;
		}
		
		// TODO: handle when user_id isn't set
		$sessionResult = self::getSession( $_SESSION['user_id'] );
		
		if($sessionResult == false){
			self::destroy();
			return false;
		}
		
		// check if php session matches users session in the db
		if(session_id() == $sessionResult->session_id && time() < $sessionResult->expiration){
			return true;
		}
		else
		{
			// kill session
			self::destroy();
			return false;
		}
	}
}

/**
 * Session timeout - sends a 419 code as a response for the frontend to route the user to re-login
 */
public function goodnight()
{
	header("HTTP/1.1 419 Authentication Timeout");
	exit();
}

/**
 * attempts to authenticate the user in the db by checking their stored hash
 * @param email | users email address
 * @param pass | users password
 * @return (object) { success: 'bool', error_code: 'int', data: 'array' }
 */
public function authenticate( $email, $pass )
{
	// grab user id from the db if one exists
	$user = self::getUserId( $email );
	if($user != null){

		// get users salt and hash by user_id from the db
		$userAuthData = self::getUserAuthData( $user->id );

		// user same hash technique as account set up will
		$freshHash = sha1( $userAuthData->user_salt . $pass . $email );

		// check if has in db matches the hash you just created
		if( $userAuthData->user_hash == $freshHash ){
			
			$_SESSION['user_id'] = $user->id;

			session_regenerate_id();
			self::addSession( session_id(), $user->id, time() + 60);
			
			// successful authentication
			$response['success'] = true;
			$response['error_code'] = null;
			$response['data'] = [];
			return $response;

		} else {
			// wrong password entered
			$response['success'] = false;
			$response['error_code'] = 600;
			$response['data'] = [];
			return $response;
		}

	} else {
		// user does not exist
		$response['success'] = false;
		$response['error_code'] = 601;
		$response['data'] = [];
		return $response;
	}
}

/**
 * creates a new user by calling 2 methods. one to store the email address, and one to store 
 * the hash, and the salt for authentication when the user tries to log in
 * @param email | users email address
 * @param pass | users password
 * @return (object) { success: 'bool', error_code: 'int', data: 'array' }
 */
public function createNewUser( $email, $pass )
{
	$salt = uniqid(mt_rand(), true);
	$freshHash = sha1( $salt . $pass . $email );

	//error handling for username that already exists
	$newUserId = self::addUser( $email );
	self::addHash( $salt, $freshHash, $newUserId );

	$response['success'] = true;
	$response['error_code'] = null;
	$response['data'] = [];
	return $response;
}


/**
* PROTECTED METHODS
*	below are protected functions that do the actually execution call
*	to MySQL and return whatever data is expected
*/

/**
 * Add record to users table
 */
protected function addUser( $email ){
	try {
		$query = "INSERT INTO users ( email, role_uid ) VALUES ( '$email', 0 )";
        $dbConnection = self::getConnection( self::DB_USERINFO );
        $statement = $dbConnection->prepare( $query );
        
		if ( ! $statement->execute( ) ) 
      	{   
      		echo $statement->errorInfo();
    	}
    	$lastId = $dbConnection->lastInsertId();
		$dbConnection = null;
		return $lastId;

    }
    catch(PDOException $e) {
		return false;
    }
}

/**
 * add record to the authentication table
 */
protected function addHash( $salt, $freshHash, $newUserId){
	try {
		$query = "INSERT INTO authentication ( user_uid, user_hash, user_salt ) VALUES ( '$newUserId', '$freshHash', '$salt' )";
        $dbConnection = self::getConnection( self::DB_USERINFO );
        $statement = $dbConnection->prepare( $query );
        
		if ( ! $statement->execute( ) ) 
      	{   
      		echo $statement->errorInfo();
    	}

		$dbConnection = null;
    }
    catch(PDOException $e) {
		return false;
    }
}

/**
 * Add record to session table.  if one exists update the session_id and expiration
 */
protected function addSession( $session_id, $user_id, $expiration)
{
	try {
		$query = "INSERT INTO sessions (user_uid, session_id, expiration ) VALUES ( '$user_id', '$session_id' , '$expiration') ON DUPLICATE KEY UPDATE expiration = '$expiration', session_id = '$session_id'";
        $dbConnection = self::getConnection( self::DB_USERINFO );
        $statement = $dbConnection->prepare( $query );
        
		if ( ! $statement->execute( ) ) 
      	{   
      		echo $statement->errorInfo();
    	}

    	$dbConnection = null;
    }
    catch(PDOException $e) {
        return false;
    }
}

/**
 * get the current session in the table for the specificed user_id
 */
protected function getSession( $user_id )
{
	try {
		$query = "SELECT session_id, expiration FROM sessions WHERE user_uid = '$user_id'";
        $dbConnection = self::getConnection( self::DB_USERINFO );
        $statement = $dbConnection->prepare( $query );
        
		if ( ! $statement->execute( ) ) 
      	{   
      		echo $statement->errorInfo();
    	}

    	$session = $statement->fetchObject();
    	$dbConnection = null;
		return $session;
    }
    catch(PDOException $e) {
        return false;
    }
}

/**
 * get the user authentication data by user id
 */
protected function getUserAuthData( $userId )
{
	try {

		$query = "SELECT user_hash, user_salt FROM authentication WHERE user_uid = '$userId'";
        $dbConnection = self::getConnection( self::DB_USERINFO );
        $statement = $dbConnection->prepare( $query );

		if ( ! $statement->execute( ) ) 
      	{   
      		echo $statement->errorInfo();
    	}

    	$userData = $statement->fetchObject();
    	$dbConnection = null;
		return $userData;
    }
    catch(PDOException $e) {
        return false;
    }
}

/**
 * check for a user with a matching email address and return the id if there is one
 */
protected function getUserId( $email )
{
	try {

    	$query = "SELECT id, email FROM users WHERE email = '$email'";
        $dbConnection = self::getConnection( self::DB_USERINFO );
        $statement = $dbConnection->prepare( $query );

		if ( ! $statement->execute( ) ) 
      	{   
      		echo $statement->errorInfo();
    	}
    	
		$user = $statement->fetchObject();
		$dbConnection = null;
		return $user;
    }
    catch(PDOException $e) {
        return false;
    }
}

}
