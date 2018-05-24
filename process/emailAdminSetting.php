<?php
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/London');

require_once '../../vendor/autoload.php';
require_once '../../meekrodb.2.3.class.php';




if($_POST['action'] === 'getEmailUserInfo') {
    DB::$user = 'db';
    DB::$password = 'infusionsoft';
    DB::$dbName = 'email';
    
    $results = DB::query("SELECT * FROM relay"); 
    
    $data = array(
        'result' => $results
    );
    echo json_encode($results);
}

if($_POST['action'] === 'getEptUserInfo') {
    DB::$user = 'eptdb';
    DB::$password = '5WB5Y6ZPi!@6vY';
    DB::$dbName = 'eptdb';   

    $userList = DB::query("select userId from  tblEptUsers"); 

    $data = array(
        'result' => $userList
    );
    echo json_encode($userList);
}
  

?>
