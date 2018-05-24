<?php 

    ini_set('max_execution_time', 0);
    ini_set('display_errors', 1);
    date_default_timezone_set('Europe/London');

    require_once '../vendor/autoload.php';
    require_once '../meekrodb.2.3.class.php';
    require_once '../src/Facebook/autoload.php';
    require_once("../includes/ActiveCampaign.class.php");

    include 'eptDataFunctions.php';
    include 'eptIncludes.php';
    include "infusionsoftTokenManager.php";
    callIncludeEptFiles();
    
    includeFiles();
    use \DrewM\MailChimp\MailChimp;
    include('./MailChimp.php'); 

    DB::$user = 'eptdb';
    DB::$password = '5WB5Y6ZPi!@6vY';
    DB::$dbName = 'eptdb';    
    $infusionsoft=NULL;
          

    $client="ept";
    $web=true; 
    $GLOBALS['web'] =  $web;
    $GLOBALS['activeAdminEmail']=false;
    if (PHP_SAPI === 'cli') {
        $web=false;
        $GLOBALS['web'] =  $web;
        //maintAddColumn("Contact", "alter table %l add Phone1 varchar(255) after EmailAddress3");
        $args=array_slice($argv, 1);
        if (count($args) > 0) {
            while (count($args) > 0) {
                $arg=array_shift($args);
                switch ($arg) {
                    case "-u":
                        $userId=array_shift($args);
                        $userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers u where userId=%i", $userId);
                        break;
                        
                    case "-uc":
                        $op="updateContactData";
                        break;
                    case "-p":
                        $startPage=array_shift($args);
                        break;
                    default:
                        printf("ERROR: Unknown argument %s\n", $arg);
                        die();
                        break;
                } 
            }
        }
        
        //exit;
    } else {
        $op=readRequest("op", "");
        $reportId=readRequest("reportId", 0);
        $oauthDebug=readRequest("oad", "true");
        $debug=readRequest("debug", "false");
        $reset=readRequest("reset", "false");
        $fbId=readRequest("fbId", 0);
        $isContactId=readRequest("isContactId", 0);
        $membEmail=readRequest("membEmail", "");
        $fbFirstName=readRequest("fbFirstName", "");
        $fbLastName=readRequest("fbLastName", "");
    
        $debug=$debug == "true" ? true:false;

        
    	if ($op == "zapUserAccess") {
	
            $log=fopen("/var/www/html/upd-wdem/ept.log", "a");
            fprintf($log, "%s: Op %s - FbId %d - Name %s %s\n", date("Y-m-d H:i:s"), $op, $fbId, $fbFirstName, $fbLastName);
        
            // Create one time hash
            $oth = md5(sprintf('%s_ept_%d_%s_auth_hash_23492309usdINOWIF', date("Y-m-d-H:i:s"), $fbId, $fbFirstName));
            // Need last 8 characters of hash
            $oth = substr($oth, -8, 8);
        
            DB::insertUpdate("tblEptUsers", array(
                'fbId' => $fbId,
                'fbFirstName' => $fbFirstName,
                'fbLastName' => $fbLastName,
                'OneTimeHash' => $oth,
                'client' => 'ept',
                'oneTimeHashExpires' => time()+1800)
            );	
        
            header('Content-Type: application/json');
        
            printf('{"oth": "%s"}', $oth);
            print "\n";
            
            fclose($log);
            exit;
        }else if ($op == "wdeUserAccess") {
            $log=fopen("/var/www/html/upd-wdem/ept.log", "a");
            fprintf($log, "%s: Op %s - isContact %d - Email %s - Name %s %s\n", date("Y-m-d H:i:s"), $op, $isContactId, $membEmail, $fbFirstName, $fbLastName);
        
            // Create one time hash
            $oth = md5(sprintf('%s_ept_%d_%s_auth_hash_23492309usdINOWIF', date("Y-m-d-H:i:s"), $fbId, $fbFirstName));
            // Need last 8 characters of hash
            $oth = substr($oth, -8, 8);
        
            DB::insertUpdate("tblEptUsers", array(
                'isContactId' => $isContactId,
                'fbFirstName' => $fbFirstName,
                'fbLastName' => $fbLastName,
                'membEmail' => $membEmail,
                'OneTimeHash' => $oth,
                'client' => 'ept',
                'oneTimeHashExpires' => time()+1800)
            );	
        
            header('Content-Type: application/json');
        
            printf('{"oth": "%s"}', $oth);
            print "\n";
            
            fclose($log);
            exit;		
        }

    } 
    if(empty(session_id())) {
		session_start();
    }  
    

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Turn off PHP output compression
	ini_set('zlib.output_compression', false);
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	// Clear, and turn off output buffering
	while (ob_get_level() > 0) {
		// Get the curent level
		$level = ob_get_level();
		// End the buffering
		ob_end_clean();
		// If the current level has not changed, abort
		if (ob_get_level() == $level) break;
	}   

	$sessionId=session_id();
    $fbAuth=false;
    $terminate=true;
    //print_r($_SESSION);
	if (isset($_SESSION['login_status']) && $_SESSION['login_status'] == 'login') {
		$fbAuth=true;
	}else{
		require_once '../'.'login.php';
	}    
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo initializedCSS(true);   //print css     
    ?>
    
</head>

<body>
    <div id="wrapper">       
        <?php 
            $showSession=false;
            if($showSession)
            {
                echo '<pre>';
                echo session_id();
                print_r($_SESSION);
                echo '<br/>';
                print_r(getUserDetailsByUID('233'));
                echo 'sessionId: ' . $sessionId;
                echo '</pre>';
            }
            /*
            echo '<pre>';
            //print_r(getUserDetailsAll());
            print_r(getUserDetailsByPlatform('AC'));
            
            echo '</pre>';
            //echo '<script>alert('.$fbAuth.');</script>';
            */
            $userAgent=$_SERVER['HTTP_USER_AGENT'];
            debugOut("<p>User Agent: $userAgent</p>");
        
            if (preg_match('/mozilla|chrome|safari/i', $userAgent) == 0) {
                debugOut("<p>Invalid user agent</p>");
            }
                
            // Is this going to be a new link from Messenger?
            if ($op == "otsl") { // oneTimeSessionLink
                $userAgent=$_SERVER['HTTP_USER_AGENT'];
                debugOut("<p>User Agent: $userAgent</p>");
                if (preg_match('/mozilla|chrome|safari/i', $userAgent) == 0) {
                    debugOut("<p>Invalid user agent</p>");
                    //htmlTerminate();
                }
                $oneTimeHash=readRequest("oth", "");
                if ($oneTimeHash == "USED") {
                    debugOut("<p>Invalid One Time Hash</p>\n");
                    //htmlTerminate();
                }
            
                debugOut("<p>Setting Session User Details from One Time Hash</p>");
            
                $userDetails=DB::queryFirstRow("select userId, fbId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, oneTimeHashExpires, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where oneTimeHash=%s", $oneTimeHash);
            
                if ($userDetails) {
                    debugOut("<p>One Time Hash Found</p>");
                    // Has the hash expiry time passed?
                    if ($userDetails["oneTimeHashExpires"] <= time()) {
                        debugOut("<p>One Time Hash EXPIRED</p>");
                        //tellThemToLogIn("It looks like you've just clicked on a link from Messenger that's expired.");
                        //exit;
                        //echo fbAuthentication();
                        //exit;
                        $fbAuth=false;
                    }
                    debugPrintR($userDetails);
                    $userId=$userDetails["userId"];
                } else {
                    debugOut("<p>Invalid One Time Hash</p>");
                    //tellThemToLogIn("It looks like you've just clicked on a link from Messenger with a corrupted token.");
                    //exit;
                    //echo fbAuthentication();
                    //exit;
                    $fbAuth=false;
                }
                // Store client details for this user and expire the oneTimeHash (unless it's a special debug expiry time)
                $hashExpires=$userDetails["oneTimeHashExpires"] == 9999999999?9999999999:0;
                DB::insertUpdate("tblEptUsers", array(
                    'userId' => $userDetails["userId"],
                    'client' => $client,
                    'oneTimeHashExpires' => $hashExpires)
                );	
                // Store the user details for this sessio
                DB::insertUpdate("tblEptSessions", array(
                    'sessionId' => $sessionId,
                    'userId' => $userDetails["userId"])
                );
                debugOut("<p>Stored Session ID in Database and Marked OneTimeHash as USED</p>");
                
                if ($userDetails["isContactId"] == NULL) {
                    //tellThemToLogIn(sprintf("Thanks for signing up, %s. Please can you now register via Facebook.", $userDetails["fbFirstName"]));
                    //exit;
                   // echo fbAuthentication();
                    //exit;
                    $fbAuth=false;
                }
            }
            else
            {
                if(!$fbAuth)
                {
                    
                    echo fbAuthentication();
                    //echo navigationMenu();    //print navigation and side panel 
                }        
               else
                {
                    //$fbIntId=$_SESSION['user_id']; //facebook id
                   // $userDetails=getUserDetails($sessionId);
                   //echo '1 <br/>';
                   //$userDetails=getUserDetailsByUID('41');
                  // $userDetails=DB::queryFirstRow("select userId, fbId, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where userId=41");
                    //$userDetails=getUserDetailsByUID('319'); // for testing, jeremy flanagan
                    //$userDetails=getUserDetailsByUID('327'); // for testing, jeremy flanagan
                   $userDetails=getUserDetailsBySessionID($sessionId);
                    //$userDetails=getUserDetailsByUID('237'); //AC user
                   /* echo '<pre>';
                   
                    print_r($userDetails);
                    echo '</pre>';*/
                    //echo '<script>alert('.$fbAuth.');</script>';
                   //echo '2 <br/>';
                    if ($userDetails) {
                        //echo '3 <br/>';
                        debugOut("<p>Getting Details From Database Using Session ID</p>");
                        if ($userDetails["userId"]==233 || $userDetails["userId"]==334 || $userDetails["userId"]==327 || $userDetails["userId"]==324) {
            
                            // 334, 324 it's dominick
                            // It's Adrian, so do clever things
                            $newUserId=233;
                            $superUser=true;
                            if (isset($_SESSION['uid'])) {
                                $newUserId=$_SESSION['uid'];
                            }
                            $newUserId=readRequest("uid",$newUserId);
                            if ($newUserId != 233) {
                                debugOut(sprintf("<p>NEW User ID: %d</p>", $newUserId));
                                $userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers u where userId=%i", $newUserId);
                            }
                            $_SESSION['uid']=$newUserId;
                            debugOut(sprintf("<p>User ID %d - %s %s</p>\n", $newUserId, $userDetails["fbFirstName"], $userDetails["fbLastName"]));
                        }
                        debugPrintR($userDetails);
                        $userId=$userDetails["userId"];
                    }else {
                        $fbIntId=$_SESSION['user_id']; //facebook id
                        $userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from  tblEptUsers u where fbIntId=%s", $fbIntId);
                       // echo '4 <br/>';
                        if ($userDetails) {
                            debugOut("<p>Getting Details From Database Using Facebook ID</p>");
                            if ($userDetails["userId"]==233 || $userDetails["userId"]==334 || $userDetails["userId"]==324) {
                                // 334, 324 it's dominick
                                // It's Adrian, so do clever things
                                $newUserId=233;
                                $superUser=true;
                                if (isset($_SESSION['uid'])) {
                                    $newUserId=$_SESSION['uid'];
                                }
                                $newUserId=readRequest("uid",$newUserId);
                                if ($newUserId != 233) {
                                    debugOut(sprintf("<p>NEW User ID: %d</p>", $newUserId));
                                    $userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers u where userId=%i", $newUserId);
                                }
                                $_SESSION['uid']=$newUserId;
                                debugOut(sprintf("<p>User ID %d - %s %s</p>\n", $newUserId, $userDetails["fbFirstName"], $userDetails["fbLastName"]));
                            }
                            debugPrintR($userDetails);
                            $userId=$userDetails["userId"];
                            $isEmail=$userDetails["isEmail"];
                            //echo '5 <br/>';
                            debugOut("<p>Updating database - tblEptUsers</p>");
                            $updateData=array(
                                'userId' => $userId,
                                'fbIntId' => $fbIntId,
                                'fbEmail' => $userDetails["fbEmail"],
                                'fbFirstName' => $userDetails["fbFirstName"],
                                'fbLastName' => $userDetails["fbLastName"],
                                'client' => $client
                            );
                            //echo '6 <br/>';
                            DB::insertUpdate("tblEptUsers", $updateData);	
                            debugPrintR($updateData);
            
                            if (!isset($superUser) || (isset($superUser) && ($userId == 233))) {
                                DB::insertUpdate("tblEptSessions", array(
                                    'sessionId' => $sessionId,
                                    'userId' => $userId)
                                );
                            }
                            //echo '7 <br/>';
                        } else {
                            printf("<p>Debug info:<br><br>Session ID %s<br>FB ID: %d</p>\n", $sessionId, $fbIntId);
                            //tellThemToLogIn("It looks like we can't find your details in the database. Have you registered via Messenger yet? If not, please <a href='https://m.me/wedeliver.email?ref=ept'>go to Messenger to register</a>");
                            //exit;
                            //echo fbAuthentication();
                            //exit;
                            $fbAuth=false;
                        }
                    }
                    //echo '8 <br/>';
                    $GLOBALS['appName']= $appName=$userDetails["appName"];
                    $fbFirstName=$userDetails["fbFirstName"];
                    $fbLastName=$userDetails["fbLastName"];
                    $fbIntId=$userDetails["fbIntId"];
                    $fbEmail=$userDetails["fbEmail"];
                    $isEmail=$userDetails["isEmail"];
                    //echo '9 <br/>';
                    //$fbFirstName=$_SESSION['first_name']; // first name
                    //$fbLastName=$_SESSION['last_name']; // last name
                    //$fbIntId=$_SESSION['user_id']; //facebook id
                    //$fbEmail=$_SESSION['e-mail']; //e-mail address	
                    
                    if (isset($userDetails["authhash"])) {
                        $authHash=$userDetails["authhash"];
                    } else {
                        // Generate a new unique key, save to database and then return it.
                        $userHash_md5 = md5(sprintf('wedeliveremail_%s_auth_hash_23492309usdINOWIF', $appName));
                    
                        // Need last 8 characters of hash
                        $authHash = substr($userHash_md5, -8, 8);
                    }
                    //echo '10 <br/>';
                    // Update User Information
                    DB::insertUpdate("tblEptUsers", array(
                        'userId' => $userId,
                        'fbIntId' => $fbIntId,
                        'fbEmail' => $fbEmail,
                        'fbFirstName' => $fbFirstName,
                        'fbLastName' => $fbLastName,
                        'authhash' => $authHash)
                    );	
                    
                    //echo '11 <br/>';
                    debugOut("<p>Updating database - tblEptUsers</p>");
                    $updateData=array(
                        'userId' => $userId,
                        'fbIntId' => $fbIntId,
                        'fbEmail' => $fbEmail,
                        'fbFirstName' => $fbFirstName,
                        'fbLastName' => $fbLastName,
                        'authhash' => $authHash);
                    DB::insertUpdate("tblEptUsers", $updateData);	
                    debugPrintR($updateData);
                    
                   // echo '12 <br/>';
                    if ($op == "choose") {
                        $platform=readRequest("platform", "");
                        DB::insertUpdate("tblEptUsers", array(
                            'userId' => $userId,
                            'platform' => $platform)
                        );
                    } else {
                        $platform=$userDetails["platform"];
                    }

                    if ($op == "reset") {
                        $platform="";
                    }
                                        
                    //echo '13 <br/>';
                    //$platform=$userDetails["platform"];               
                    if ($platform == "ISFT") {
                        $infusionsoft = new \Infusionsoft\Infusionsoft(array(
                            'clientId'     => 'ukkty8jzhv523ernkvbzam6u',
                            'clientSecret' => 'qbpxUvVwH4',
                            'redirectUri'  => 'https://wdem.wedeliver.email/eptauth.php',
                        ));	
                    
                        //echo '14 <br/>';
                        if ($reset == "true") {
                            if ($oauthDebug == "true") {
                                echo '<p>Resetting OAuth Tokens</p>';
                                unset($userDetails['refreshToken']);
                            }	
                        }   
                    // echo '15 <br/>';
                        if ($reset == "true") {
                            if ($oauthDebug == "true") {
                            // echo '<p>Resetting OAuth Tokens</p>';
                                debugOut("<p>Resetting OAuth Tokens</p>");
                                unset($userDetails['refreshToken']);
                            }	
                        } 
                        //echo '16 <br/>';
                        if (isset($_GET['code'])) {
                        // $hideDashboard=true;
                            // try {
                            //     //echo '17 <br/>';
                            //     $token=$infusionsoft->requestAccessToken($_GET['code']);
                            // } catch(Infusionsoft\Http\HttpException $e) { 
                            //     print("<h2>Invalid Authorisation Code</h2>\n");
                            //     print('<a href="#" onclick="javascript:ShowAuth("xx' . $infusionsoft->getAuthorizationUrl() . '")">Click here to authorise Infusionsoft</a>');
                            //     //htmlTerminate();
                            // }
                            manageInfusionToken($infusionsoft, new MeekroDB(), $userId);
                        } else if (isset($userDetails['refreshToken'])) {

                            $token=new \Infusionsoft\Token(array(
                                'access_token' => $userDetails['accessToken'],
                                'refresh_token' => $userDetails['refreshToken'],
                                'expires_in'    => $userDetails['expiresAt']-time(),
                                'token_type' => $userDetails['tokenType'],
                                'scope' => sprintf("%s|%s", $userDetails['scope'], $userDetails['appDomainName'])
                            ));
                    
                            $infusionsoft->setToken($token);
                            $token=$infusionsoft->getToken();
                            if ($oauthDebug == "true") {
                                debugOut("<hr>");
                                debugOut("<p>Using Token From Database</p>");
                                debugPrintR($userDetails);
                                debugPrintR($token);                   
                            }	
                        
                            $authHash=$userDetails['authhash'];
                        } else {
                            //echo '19 <br/>';
                        // htmlMinimalPage();
                    //		htmlPageTemplate("--", "--", "--", "--", "--");
                            print "<div class='w3-container'>\n";
                            print "<h2>Welcome to Email Power Tools</h2>\n";
                            print '<h2>You need to connect to an Infusionsoft application</h2><a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft and select your app</a>';
                            print "</div>\n";
                        // htmlTerminate();
                        }

                        try {
                            $token=$infusionsoft->getToken();
                        
                            if ($token) {
                        
                                if ($token->endOfLife <= time()) {
                                    // Token has expired so renew it
                                    $token=$infusionsoft->refreshAccessToken();
                                    debugOut("<p>Refreshing Token</p>");
                                    debugPrintR($token);
                                }
                            }
                        
                            // If we are returning from Infusionsoft we need to exchange the code for an
                            // access token.
                            if (isset($_GET['code']) and !$token) {
                                echo '<h3>Requesting Access Token</h3>\n';
                                $token=$infusionsoft->requestAccessToken($_GET['code']);
                            }
                    
                            if ($token) {
                                // MAKE INFUSIONSOFT REQUEST
                            } else {
                                print('<a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft</a>');
                                // echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
                                //htmlTerminate();
                            }
                        } catch (\Infusionsoft\TokenExpiredException $e) {
                            // If the request fails due to an expired access token, we can refresh
                            // the token and then do the request again.
                            $infusionsoft->refreshAccessToken();
                        } catch(Infusionsoft\Http\HttpException $e) {
                            print("<h2><br><br>Invalid Authorisation Code</h2>\n");
                            print('<a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft</a>');
                            //htmlTerminate();
                        } catch(Exception $e) {
                            debugOut("<h4>Exception</h4>");
                            debugPrintR($e);
                        }
                    
                        if ($oauthDebug=="true") {
                            debugOut('<p>We are authorised</p>');
                            debugOut("<p>Token details:</p>");
                            debugOut("<pre>");
                            debugOut(sprintf('<pre>Current time: %d<br>Access Token: %s<br>Refresh Token: %s<br>End of life: %s<br>Token Type: %s<br>Token Scope: %s<br>', time(), $token->accessToken,$token->refreshToken, $token->endOfLife, $token->extraInfo["token_type"], $token->extraInfo["scope"]));
                            ///debugOut(print_r($token,true));
                            debugOut(sprintf("Expires: %d %d seconds (%d hours %d minutes)<br></pre>", $token->endOfLife, $token->endOfLife-time(), floor(($token->endOfLife-time())/3600), (($token->endOfLife-time())/60)%60));
                        }
                    
                        // Store latest token details in database. Infer appName and create AuthHash
                    
                        $scopeArray=explode("|", $token->extraInfo["scope"]);
                        $appName=substr($scopeArray[1], 0, strpos($scopeArray[1], "."));
                        
                        if (isset($_GET['code'])) {
                        ?>
                            <div class="w3-main" style="margin-left:300px;margin-top:43px;margin-right:25%">
                              <div class="w3-container" style="padding-top:10px">	
                        <?php	
                            printf("<h2>Infusionsoft App %s is Authorised</h2>\n", $appName);
                            //print "<script>window.opener.location.href = window.location.href.split('?')[0];</script>\n";
                            print "<script>window.opener.location.href = window.location.protocol + '//' + window.location.host + '/ept.php';</script>\n";
                            echo '<a href="#" onclick="javascript:window.close();">Click here to close this window.</a>';
                            //terminateHtml();
                            $hideDashboard=true;
                        }

                        try {
                            manageInfusionToken($infusionsoft, new MeekroDB(), $userId);
                        } catch(Infusionsoft\Http\HttpException $e) {
                            debugOut("<h2>Invalid Authorisation Code</h2>");
                            debugOut('<a href="#" onclick="javascript:ShowAuth("xx' . $infusionsoft->getAuthorizationUrl() . '")">Click here to authorise Infusionsoft</a>');
                            //htmlTerminate();
                        } catch(Exception $e) {
                            debugOut('<h4>Exception</h4>');
                            debugPrintR($e);
                        }

                        /*** BUG IN GETUSERINFO ***/
                        if ($oauthDebug=="true")
                            debugOut('<p>Getting OAuth User Info</p>');	
                        try {
                            $isUserInfo=$infusionsoft->data()->getUserInfo();
                        } catch(Exception $e) {
                            debugOut('<h4>Exception</h4>');
                            debugPrintR($e);
                            print "<br><br><br><br><br>\n";
                            print '<h2>You need to re-authorise your Infusionsoft application</h2><a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft and select your app</a>';
                            exit;
                        }
                        if ($oauthDebug=="true")
                            debugPrintR($isUserInfo);
                    
                        // Update Infusionsoft Information
                        DB::insertUpdate("tblEptUsers", array(
                            'userId' => $userId,
                            'isEmail' => $isUserInfo["casUsername"],
                            'platform' => 'ISFT',
                            'appName' => $appName,
                            'appDomainName' => $scopeArray[1],
                            'accessToken' => $token->accessToken,
                            'refreshToken' => $token->refreshToken,
                            'expiresAt' => $token->endOfLife,
                            'tokenType' => $token->extraInfo["token_type"],
                            'scope' => $scopeArray[0])
                        );                        
                    }
                    elseif ($platform == "AC") {
                        
                        if ($op == "acSetApi") {
                            $appName=readRequest("acAccount", $userDetails["appName"]);
                            $apiKey=readRequest("acApiKey", $userDetails["accessToken"]);
                            
                            $terminate=true;
                            $button="Submit";
                            
                            if ($appName != "" && $apiKey != "") {
                                $apiUrl=sprintf("https://%s.api-us1.com", $appName);
                                $ac = new ActiveCampaign($apiUrl, $apiKey);
                                if (!(int)$ac->credentials_test()) {
                                    $message="Access denied: Invalid credentials (URL and/or API key). Please re-enter:";
                                } else {
                                    $message="API credentials valid:";
                                    $button="Update";
                                    $terminate=false;
                                }
                                // Update Infusionsoft Information
                                DB::insertUpdate("tblEptUsers", array(
                                    'userId' => $userId,
                                    'platform' => 'AC',
                                    'appName' => $appName,
                                    'appDomainName' => $apiUrl,
                                    'accessToken' => $apiKey,
                                    'refreshToken' => NULL,
                                    'expiresAt' => NULL,
                                    'tokenType' => "apiKey")
                                );				
                            } else {
                                $message="Please enter your ActiveCamapign account name and API key:";
                                //htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
                                //htmlMinimalPage();
                                //showAcSettingsPage($appName, $apiKey, $message, $button, $terminate);
                                echo ' 4 ';

                            }
                        }
                        // AC API stuff to go here
                        if ($appName) {
                            $apiUrl=sprintf("https://%s.api-us1.com", $appName);
                            $apiKey=$userDetails["accessToken"];
                            $ac = new ActiveCampaign($apiUrl, $apiKey);
                            if (!(int)$ac->credentials_test()) {
                                //htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
                                //htmlMinimalPage();
                                $message = 'Access denied: Invalid credentials (URL and/or API key). Please re-enter:';
                                $button = 'Submit';
                                //showAcSettingsPage($appName, $apiKey, "Access denied: Invalid credentials (URL and/or API key). Please re-enter:", "Submit", true);
                                echo ' 5 ';
                            }                  
                        } else {
                            // Tell the database that AC is selected
                            DB::insertUpdate("tblEptUsers", array(
                                'userId' => $userId,
                                'platform' => "AC")
                            );	
                            //htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
                            //htmlMinimalPage();
                            //showAcSettingsPage();
                            echo ' 6 ';
                            //exit;
                        }
                    } 
                    elseif($platform == "MC") 
                    {
                        
                        if ($op == "mcSetApi") {
                            //$appName=readRequest("mcAccount", $userDetails["appName"]);
                            $appName= '';
                            $apiKey=readRequest("mcApiKey", $userDetails["accessToken"]);
                            
                            $terminate=true;
                            $button="Submit";
                            
                            if ($apiKey != "") {
                                $MailChimp = new MailChimp($apiKey);
                                $result = $MailChimp->get('/');
                                $resheaders =  $MailChimp->getLastResponse();
                                if($resheaders['headers']['http_code'] !=200)
                                {
                                    //Not Ok Response from MC api
                                    //Show error and send back to api setting page
                                    $message="Access denied: Invalid credentials (API key). Please re-enter:";
                                }
                                else
                                {
                                    //'200 ok response'
                                    //Save data to db
                                    $message="API credentials valid:";
                                    $appName = $result['username'];
                                    $button="Update";
                                    $terminate=false;
                                }
                                // Update MailChimp Api and User Information
                                DB::insertUpdate("tblEptUsers", array(
                                    'userId' => $userId,
                                    'platform' => 'MC',
                                    'appName' => $appName,
                                    'appDomainName' => '',
                                    'accessToken' => $apiKey,
                                    'refreshToken' => NULL,
                                    'expiresAt' => NULL,
                                    'tokenType' => "apiKey")
                                );
                                
                            } else {
                                $message="Please enter your MailChimp account name and API key:";
                                //htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
                                //htmlMinimalPage();
                                //showMcSettingsPage($appName, $apiKey, $message, $button, $terminate);
                                
                            }
                        }
                        // MC API stuff to go here
                        if ($appName) {
                            if(empty($apiKey))
                                $apiKey=$userDetails["accessToken"];
                            $MailChimp = new MailChimp($apiKey);
                            $result = $MailChimp->get('/');
                            $resheaders =  $MailChimp->getLastResponse();
                            if($resheaders['headers']['http_code'] !=200)
                            {
                                //htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
                                //htmlMinimalPage();
                                $message='Access denied: Invalid credentials ( API key). Please re-enter:';
                                //showMcSettingsPage($appName, $apiKey, "Access denied: Invalid credentials ( API key). Please re-enter:", "Submit", true);
                                echo ' 2 ';
                            }
                        } else {
                            // Tell the database that MC is selected
                            DB::insertUpdate("tblEptUsers", array(
                                'userId' => $userId,
                                'platform' => "MC")
                            );	
                            //htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
                            //htmlMinimalPage();
                            //showMcSettingsPage();
                            //exit;
                            echo ' 3 ';
                        }
                    }                    
                                  
                        //echo '20 <br/>';
                        /* 
                    // $token=$infusionsoft->getToken(); 
                        $token=$infusionsoft->refreshAccessToken();
                        echo '<pre>';
                        print_r( $token);
                        echo '</pre>';    
                        */         
                    $GLOBALS['op'] = $op;            
                    if($userDetails)
                    {
                        if($op=='reset')
                        {
                            printReset();
                        }
                        else
                        {
                            if($platform=="ISFT")
                            {
                                $GLOBALS['platform']=$platform;
                                $GLOBALS['userDetails']=$userDetails;
                                $GLOBALS['infusionsoft']=$infusionsoft; 
                                $GLOBALS['activeAdminEmail']=false;                               
                                //echo 'platform ' . $platform; 
                                //$GLOBALS['appName'] = $appName=$userDetails["appName"];
                                echo navigationMenu();    //print navigation and side panel                    
                                echo '<div id="page-wrapper">';
                                //echo printDashboardPage();   //print navigation and side panel
                                //echo $infusionsoft->getAuthorizationUrl();
                                
                                $cacheInfo=getAllMySqlLastUpdate($appName);
                                foreach ($cacheInfo as $tableCache) {
                                    $daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
                                }
                                $days=$daysSince["EmailAddStatus"];
                                if(strpos($op, 'opportunityActivityReport') !== false) {
                                    echo printOpportunityActivityReport($appName, $sessionId, $userDetails, $op);
                                }else if(strpos($op, 'adminSettings') !== false) {
                                    echo printAdminSettings($appName, $sessionId, $userDetails, $op); 
                                }else {
                                    switch ($op) {
                                        case "lostCLV":
                                            $dataToString=postActionLostCLV($appName);
                                            echo printLostCLVPage($days, $dataToString);                 
                                            break;
                                        case "lostCustomersReport":
                                            $dataToString=Report_postActionLostCLV($appName, $optTypeTranslate);           
                                            echo Report_printLostCLVPage($days,  $dataToString); 
                                            break;
                                        case "showstatus":       
                                            $cacheInfo=getAllMySqlLastUpdate($appName);    
                                            //print_r($cacheInfo);                                         
                                            echo printShowStatus($cacheInfo); 
                                            break;   
                                        case "emailOpenReport":                         
                                            echo printOpenEmailReport($appName); 
                                            break;    
                                        case "updateContactData":                         
                                            echo printUpdateContractData($appName); 
                                            break;          
                                            //emailOpenReport
                                        case "updateOpportunityData": 
                                            echo printUpdateOpportunityData($appName, $sessionId, $userDetails, $op); 
                                            break; 
                                        case "kleanApiConfig": 
                                            echo printKleanApiConfig($appName, $sessionId, $userDetails, $op); 
                                            break; 
                                        case "kleanOnDemandScrub": 
                                            echo printKleanOnDemandScrub($appName, $sessionId, $userDetails, $op); 
                                            break;
                                        case "emailAdminSettings":  
                                            
                                            echo '<pre>';
                                            //print_r(getUserDetailsAll());
                                            print_r(getRelay());
                                            //print_r(showCols());   
                                            //maintAddColumn("Contact", "alter table %l add Phone1 varchar(255) after EmailAddress3");         
                                            echo '</pre>';                    
                                            echo printEmailAdminSettings();
                                            //$GLOBALS['activeAdminEmail']=true; 
                                            break;     
                                        case "reset":                         
                                            echo printReset(); 
                                            break;   
                                        default:
                                            echo '<br/>';
                                            echo printDashboardPage();
                                            break;           
                                            //emailOpenReport                                               
                                    }                                     
                                    echo ' </div>';
                                }
                            }
                            elseif ($platform=="AC")
                            {
                                $message = empty($message)?'Access denied: Invalid credentials (URL and/or API key). Please re-enter:':$message;
                                $button = empty($button)?'Submit':$button;
                                
                                echo navigationMenu();
                                echo '<div id="page-wrapper">';
                                if($terminate)
                                {                                    
                                    echo showAcSettingsPage($appName, $apiKey, $message, $button);                                    
                                }
                                else
                                {
                                    echo printDashboardPage();
                                }
                                echo ' </div>';
                            }
                                else{
                                    echo '<div id="page-wrapper">';
                                    echo printDashboardPage();
                                    echo ' </div>';
                                }
                            
                        }

    
                    }
                    else
                    {
                        echo callMannyChat();
                       // echo '<div id="page-wrapper">';
                        echo $_SESSION['user_id'];
                        echo PHP_SAPI;
                       // echo ' </div>';
                    }
                    
                    //print_r(getUserDetails($_SESSION['user_id']));
                }
            }            
 
                //printDebug();   //print navigation and side panel            
                 
        ?>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->   
</body>

</html>
<?php 
    echo initializedJavascript();   //print js script 
    
    echo '<script>';
        echo activeClass(); 
        //if($GLOBALS['activeAdminEmail']) // only show when needed
        //{
        //    echo adminEmailSelection();
        //   
       // }            
    echo '</script>';


    function activeClass()
    {
        return printf('
        $(function() {
            $(".%s").find("a").addClass("active");
        });', $GLOBALS['op']);
    }       
?>