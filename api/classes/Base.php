<?php

class Base
{
// constants that represent the table names
const DB_DOGWAX = 'Dogwax';
const DB_USERINFO = 'UserInfo';

/**
 * establishes a connection with the mysql db on AWS
 * @param db | ref to one of the table const's above
 * @return (object) new PDO();
 */
public static function getConnection( $db )
{
    try {
    	// TODO: change credentials
        $db_username = "root";
        $db_password = 7177925945;
        
        //host: srig02
        //FQDN: srig02.mysrig.com
        //IP: 192.168.77.12

        $conn = new PDO("mssql:host=srig02.mysrig.com;dbname=SRIGDataSQL, cminniti, Srigcm2841");
        //FROM [SRIGDataSQL].[dbo].[STAFF]
        //$conn = new PDO('mysql:host=ec2-52-24-10-47.us-west-2.compute.amazonaws.com;dbname='.$db, $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
    }
    return $conn;
}

}
