<?php

// all classes that contain sql calls must include Base.php
include_once('Base.php');

class Food extends Base
{
	// get all food items db query
	public static function getFood()
	{
	    try {

	    	$query = sprintf( 'SELECT * FROM STAFF' );
	        $dbConnection = self::getConnection( self::DB_DOGWAX );
	        $statement = $dbConnection->prepare( $query );

			if ( ! $statement->execute( ) ) 
	      	{   
	      		echo $statement->errorInfo();
	    	}

	    	$foodItems = $statement->fetchAll( PDO::FETCH_OBJ );

	    	return $foodItems;

	    }
	    catch(PDOException $e) {

	        return false;

	    }
	}

}
