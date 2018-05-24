<?php
if(isset($_POST)) {
    ini_set('max_execution_time', 0);
    ini_set('display_errors', 1);
    date_default_timezone_set('Europe/London');

    require_once '../../vendor/autoload.php';
    require_once '../../meekrodb.2.3.class.php';
    require_once '../../infusionsoftTokenManager.php';

    DB::$user = 'eptdb';
    DB::$password = '5WB5Y6ZPi!@6vY';
    DB::$dbName = 'eptdb';

    ##### QUERY AND LOG USER DETAILS #####		
    $userDetails = DB::queryFirstRow("select userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where userId=%s", $_POST['userId']);

    ##### USERID AND USERAPPNAME INITIALIZATION #####
    $userId 		= $userDetails["userId"];
    $userAppName 	= $userDetails["appName"];

    ##### INFUSIONSOFT INSTANTIATION #####
    $infusionsoft = new \Infusionsoft\Infusionsoft(array(
        'clientId'     => 'ukkty8jzhv523ernkvbzam6u',
        'clientSecret' => 'qbpxUvVwH4',
        'redirectUri'  => 'https://wdem.wedeliver.email/eptauth.php',
    ));	

    ##### INFUSIONSOFT TOKEN INSTANTIATION #####
    $token = new \Infusionsoft\Token(array(
        'access_token' 	=> $userDetails['accessToken'],
        'refresh_token' => $userDetails['refreshToken'],
        'expires_in'    => $userDetails['expiresAt'] - time(),
        'token_type' 	=> $userDetails['tokenType'],
        'scope' 		=> sprintf("%s|%s", $userDetails['scope'], $userDetails['appDomainName'])
    ));

    $infusionsoft->setToken($token);

    if($_POST['action'] === 'loadAccountAccess') {
        $results = DB::query("SELECT fbFirstName, fbLastName, appName, fbEmail, userId from tblEptUsers ORDER BY fbLastName, fbFirstName"); 

        $data = array(
            'result' => $results
        );

        echo json_encode($results);
    }

    if($_POST['action'] === 'listResthook') {
        try {
            $results = $infusionsoft->resthooks()->all();
        } catch (\Infusionsoft\InfusionsoftException $e) {
            manageInfusionToken($infusionsoft, new MeekroDB(), $userId);
            $results = $infusionsoft->resthooks()->all();
        }
        $count = $results->count();

        $resArr = [];
        if ($count > 0) {
            $ar = $results->toArray();
            foreach($ar as $r) {
                $object = new stdClass();
                $object->key = $r->key;
                $object->eventKey = $r->eventKey;
                $object->hookUrl = $r->hookUrl;
                $object->status = $r->status;
                array_push($resArr, $object);
            }
        }

        $data = array(
            'result' => $resArr
        );

        echo json_encode($data);
    }
}
