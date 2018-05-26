<?php
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/London');

require_once 'vendor/autoload.php';
require_once 'meekrodb.2.3.class.php';
require_once __DIR__ . '/src/Facebook/autoload.php';
require_once("includes/ActiveCampaign.class.php");

use \DrewM\MailChimp\MailChimp;
include('./MailChimp.php'); 

DB::$user = 'eptdb';
DB::$password = '5WB5Y6ZPi!@6vY';
DB::$dbName = 'eptdb';

// Global variables that are referenced by include files
$infusionsoft=NULL;
include "eptDataFunctions.php";
include "infusionsoftTokenManager.php";

$client="ept";
$web=true;

if (PHP_SAPI === 'cli') {
	$web=false;
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
	} else if ($op == "wdeUserAccess") {
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

	if (isset($_SESSION['login_status']) && $_SESSION['login_status'] == 'login') {
		$fbAuth=true;
	}else{
		require_once 'login.php';
	}

	
	
	if($op != "ajaxcall") {
		htmlHeadTemplate();

		htmlScripts();

		htmlTopAndSideTemplate($debug);
	}

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
			htmlTerminate();
		}
		$oneTimeHash=readRequest("oth", "");
		if ($oneTimeHash == "USED") {
			debugOut("<p>Invalid One Time Hash</p>\n");
			htmlTerminate();
		}
	
		debugOut("<p>Setting Session User Details from One Time Hash</p>");
	
		$userDetails=DB::queryFirstRow("select userId, fbId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, oneTimeHashExpires, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where oneTimeHash=%s", $oneTimeHash);
	
		if ($userDetails) {
			debugOut("<p>One Time Hash Found</p>");
			// Has the hash expiry time passed?
			if ($userDetails["oneTimeHashExpires"] <= time()) {
				debugOut("<p>One Time Hash EXPIRED</p>");
				tellThemToLogIn("It looks like you've just clicked on a link from Messenger that's expired.");
				exit;
			}
			debugPrintR($userDetails);
			$userId=$userDetails["userId"];
		} else {
			debugOut("<p>Invalid One Time Hash</p>");
			tellThemToLogIn("It looks like you've just clicked on a link from Messenger with a corrupted token.");
			exit;
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
			tellThemToLogIn(sprintf("Thanks for signing up, %s. Please can you now register via Facebook.", $userDetails["fbFirstName"]));
			exit;
		}
	} else {
		if (!$fbAuth) {
			tellThemToLogIn("It looks like you're not authorised with Facebook. Please click the button below.");
		}
		
		// oneTimeSessionLink always trumps anything else.
		// Next: Do we already have a user linked to this session?
	
		$userDetails=DB::queryFirstRow("select sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and sessionId=%s", $sessionId);
		//$userDetails=DB::queryFirstRow("select userId, fbId, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where userId=233");
	
		if ($userDetails) {
			debugOut("<p>Getting Details From Database Using Session ID</p>");
			if ($userDetails["userId"]==233 || $userDetails["userId"]==334 || $userDetails["userId"]==327 || $userDetails["userId"]==324 || $userDetails["userId"] == 341) {

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
		} else {
			$fbIntId=$_SESSION['user_id']; //facebook id
			$userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from  tblEptUsers u where fbIntId=%s", $fbIntId);

			if ($userDetails) {
				debugOut("<p>Getting Details From Database Using Facebook ID</p>");
				if ($userDetails["userId"]==233 || $userDetails["userId"]==334 || $userDetails["userId"]==324 || $userDetails["userId"] == 341) {
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
			
				debugOut("<p>Updating database - tblEptUsers</p>");
				$updateData=array(
					'userId' => $userId,
					'fbIntId' => $fbIntId,
					'fbEmail' => $userDetails["fbEmail"],
					'fbFirstName' => $userDetails["fbFirstName"],
					'fbLastName' => $userDetails["fbLastName"],
					'client' => $client
				);
				DB::insertUpdate("tblEptUsers", $updateData);	
				debugPrintR($updateData);

				if (!isset($superUser) || (isset($superUser) && ($userId == 233))) {
					DB::insertUpdate("tblEptSessions", array(
						'sessionId' => $sessionId,
						'userId' => $userId)
					);
				}
			
			} else {
				printf("<p>Debug info:<br><br>Session ID %s<br>FB ID: %d</p>\n", $sessionId, $fbIntId);
				tellThemToLogIn("It looks like we can't find your details in the database. Have you registered via Messenger yet? If not, please <a href='https://m.me/wedeliver.email?ref=ept'>go to Messenger to register</a>");
				exit;
			}
		}
	}	
}

$appName=$userDetails["appName"];
$fbFirstName=$userDetails["fbFirstName"];
$fbLastName=$userDetails["fbLastName"];
$fbIntId=$userDetails["fbIntId"];
$fbEmail=$userDetails["fbEmail"];
$isEmail=$userDetails["isEmail"];

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

// Update User Information
DB::insertUpdate("tblEptUsers", array(
	'userId' => $userId,
	'fbIntId' => $fbIntId,
	'fbEmail' => $fbEmail,
	'fbFirstName' => $fbFirstName,
	'fbLastName' => $fbLastName,
	'authhash' => $authHash)
);	


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


if ($op == "choose") {
	$platform=readRequest("platform", "");
	DB::insertUpdate("tblEptUsers", array(
		'userId' => $userId,
		'platform' => $platform)
	);
} else {
	$platform=$userDetails["platform"];
}

/** 

// Connect to WeDeliver Infusionsoft for user management
$eptIs = new \Infusionsoft\Infusionsoft(array(
	'clientId'     => 'ukkty8jzhv523ernkvbzam6u',
	'clientSecret' => 'qbpxUvVwH4',
	'redirectUri'  => 'https://wdem.wedeliver.email/eptauth.php',
));

$eptToken=getInfusionsoftTokenFromDb(1);

if ($oauthDebug == "true") {
	debugOut("<hr>");
	debugOut("<p>Getting EPT Token From Database</p>");
	debugPrintR($eptToken);
}	

$eptIs->setToken($eptToken);
$eptToken=$eptIs->getToken();

if ($eptToken->endOfLife <= time()) {
	// Token has expired so renew it
	debugOut("<p>Refreshing EPT Token</p>");
	$eptToken=doRefreshAccessToken($eptIs, $eptUserId, "uir93022", $eptToken);
	debugPrintR($eptToken);	
}

$eptIsUserInfo=$eptIs->data()->getUserInfo();
debugPrintR($eptIsUserInfo);

**/

//printf("<script>alert(\"Platform: %s -- AppName: %s\");</script>\n", $platform, $appName);

$hideDashboard=false;

if ($op == "reset") {
	$platform="";
}

if ($platform == "ISFT") {
	$infusionsoft = new \Infusionsoft\Infusionsoft(array(
		'clientId'     => 'ukkty8jzhv523ernkvbzam6u',
		'clientSecret' => 'qbpxUvVwH4',
		'redirectUri'  => 'https://wdem.wedeliver.email/eptauth.php',
	));	
	
	if ($reset == "true") {
		if ($oauthDebug == "true") {
			debugOut("<p>Resetting OAuth Tokens</p>");
			unset($userDetails['refreshToken']);
		}	
	} 

	if (isset($_GET['code'])) {
		$hideDashboard=true;
		try {
			$token=$infusionsoft->requestAccessToken($_GET['code']);
		} catch(Infusionsoft\Http\HttpException $e) {
			print("<h2>Invalid Authorisation Code</h2>\n");
			print('<a href="#" onclick="javascript:ShowAuth("xx' . $infusionsoft->getAuthorizationUrl() . '")">Click here to authorise Infusionsoft</a>');
			htmlTerminate();
		}
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
	//		debugPrintR($userDetails);
			debugPrintR($token);
		}	
	
		$authHash=$userDetails['authhash'];
	} else {
		htmlMinimalPage();
//		htmlPageTemplate("--", "--", "--", "--", "--");
	
		print "<div class='w3-container'>\n";
		print "<h2>Welcome to Email Power Tools</h2>\n";
		print '<h2>You need to connect to an Infusionsoft application</h2><a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft and select your app</a>';
		print "</div>\n";
		htmlTerminate();
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
			htmlTerminate();
		}
	} catch (\Infusionsoft\TokenExpiredException $e) {
		// If the request fails due to an expired access token, we can refresh
		// the token and then do the request again.
		$infusionsoft->refreshAccessToken();
	} catch(Infusionsoft\Http\HttpException $e) {
		print("<h2><br><br>Invalid Authorisation Code</h2>\n");
		print('<a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft</a>');
		htmlTerminate();
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
		$token=$infusionsoft->getToken();
	
		if ($token) {
	
			if ($token->endOfLife <= time()) {
				// Token has expired so renew it
				$token=$infusionsoft->refreshAccessToken();
				debugOut("<p>Refreshing Token</p>");
				debugPrintR($token);
				updateDbWithInfusionsoftToken($userId, $token);
			}
		}
	
		// If we are returning from Infusionsoft we need to exchange the code for an
		// access token.
		if (isset($_GET['code']) and !$token) {
			$token=$infusionsoft->requestAccessToken($_GET['code']);
			$_SESSION['token'] = serialize($token);
		}

		if ($token) {
			// MAKE INFUSIONSOFT REQUEST
		} else {
			print '<h2>You need to authorise your Infusionsoft application</h2><a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft and select your app</a>';
			
			// echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
			exit;
		}
	} catch (\Infusionsoft\TokenExpiredException $e) {
		// If the request fails due to an expired access token, we can refresh
	        // the token and then do the request again.
	        $infusionsoft->refreshAccessToken();
	        // Save the serialized token to the current session for subsequent requests
			updateDbWithInfusionsoftToken($userId, $token);
			
//	} catch(Infusionsoft\Http\HttpException $e) {
//		debugOut("<h2>Invalid Authorisation Code</h2>");
//		debugOut('<a href="#" onclick="javascript:ShowAuth("xx' . $infusionsoft->getAuthorizationUrl() . '")">Click here to authorise Infusionsoft</a>');
//		htmlTerminate();
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
			htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
			htmlMinimalPage();
			showAcSettingsPage($appName, $apiKey, $message, $button, $terminate);
		}
	}
	// AC API stuff to go here
	if ($appName) {
		$apiUrl=sprintf("https://%s.api-us1.com", $appName);
		$apiKey=$userDetails["accessToken"];
		$ac = new ActiveCampaign($apiUrl, $apiKey);
		if (!(int)$ac->credentials_test()) {
			htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
			htmlMinimalPage();
			showAcSettingsPage($appName, $apiKey, "Access denied: Invalid credentials (URL and/or API key). Please re-enter:", "Submit", true);
		}

		// Start AC Webhook
		
		// $webHookUrl = sprintf("http://wdem.wedeliver.email/restHookHandler.php?appName=%s&authHash=%s&userId=%d", $appName, $authhash, $userId);

		// ##### WEBHOOK PAYLOAD #####
		// $data = get_object_vars(json_decode('{ "name": "WeDeliverLog", "url": "'.$webHookUrl.'", "lists[0]": "0", "action[subscribe]": "subscribe", "action[unsubscribe]": "unsubscribe", "action[update]": "update", "action[bounce]": "bounce", "init[public]": "public", "init[admin]": "admin", "init[api]": "api", "init[system]": "system" }')); 


		// $contactHook = $ac->api('webhook/add', $data);

		// if($contactHook->success) {
		//     echo 'success';
		// }else {
		//     echo $contactHook->error;
		// }


	} else {
		// Tell the database that AC is selected
		DB::insertUpdate("tblEptUsers", array(
			'userId' => $userId,
			'platform' => "AC")
		);	
		htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
		htmlMinimalPage();
		showAcSettingsPage();
		exit;
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
			htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
			htmlMinimalPage();
			showMcSettingsPage($appName, $apiKey, $message, $button, $terminate);
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
			htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
			htmlMinimalPage();
			showMcSettingsPage($appName, $apiKey, "Access denied: Invalid credentials ( API key). Please re-enter:", "Submit", true);
		}
	} else {
		// Tell the database that MC is selected
		DB::insertUpdate("tblEptUsers", array(
			'userId' => $userId,
			'platform' => "MC")
		);	
		htmlMenuTemplate($fbFirstName, $fbLastName, $fbEmail, $platform, $appName);
		htmlMinimalPage();
		showMcSettingsPage();
		exit;
	}
}

else {
	htmlMinimalPage();
//	htmlPageTemplate("--", "--", "--", "--", "--");

	print "<div class='w3-container'>\n";
	print "<h2>Welcome to Email Power Tools</h2>\n";
	print "<h3>Before we start, you need to select your CRM platform:</h3>\n";
	print "<p><a href=\"?op=choose&platform=AC\" class=\"w3-button w3-blue w3-round\">ActiveCampaign</a></p>\n";
	print "<p><a href=\"?op=choose&platform=ISFT\" class=\"w3-button w3-blue w3-round\">Infusionsoft</a></p>\n";
	print "<p><a href=\"?op=choose&platform=MC\" class=\"w3-button w3-blue w3-round\">MailChimp</a></p>\n";
	print "</div>\n";
	htmlTerminate();
}

if ($web) {
	##### IF AJAXCALL EXCLUDE HTML MARK UP #####
	if($op != "ajaxcall") {
		htmlMenuTemplate($fbFirstName, $fbLastName, $isEmail, $platform, $appName);

		if (doesContactTableExist($appName)) {
			$numContacts=getTotalContacts($appName);	//$infusionsoft->data()->count("Contact", array('Id'=>"%"));
			$numOptedIn=getOptedInContacts($appName);
			$numOptedOut=getOptedOutContacts($appName);
			$numComplained=getComplainedContacts($appName);
			$numBounced=getBouncedContacts($appName);
			htmlPageTemplate($numContacts, $numOptedIn, $numOptedOut, $numComplained, $numBounced);
		} else {
			htmlPageTemplate("--", "--", "--", "--", "--");
		}

		// Update session information
		if (!isset($superUser) || (isset($superUser) && ($userId == 233))) {
			DB::insertUpdate("tblEptSessions", array(
				'sessionId' => $sessionId,
				'userId' => $userId)
			);
		}

		debugOut("<p>Database updated</p>");

		session_write_close();
	}
}

/**
MAIN CODE
**/

if ($op == "" || $op == "acSetApi" || $op == "choose" || $op == "mcSetApi" ) {
	print "<div class='w3-container'>\n";
	print "<h2>Welcome to Email Power Tools</h2>\n";
	if ($op == "acSetApi") {
		print "<h3>API Settings Saved Successfully</h3>\n";
	}
	if ($op == "mcSetApi") {
		print "<h3>API Settings Saved Successfully</h3>\n";
	}
	print "<p>Email Power Tools is provided totally free by Adrian Savage.</p>\n";
	print "<p>We'll be adding additional reports and features as time, resources and ideas allow... please get in touch if you have any feedback, questions or suggestions.</p>\n";
	print "<p><b>Please Note:</b> This is a free product! That means that very limited support will be available via the Facebook group!</p>";
	print "<p>If you haven't already done so, <a href=\"?op=updateContactData\" class=\"w3-button w3-blue\">Click Here to Update Your Contact Data</a></p>\n";
	print "</div>\n";
	print "</div>\n";
}

if ($op == "admin") {
	print "<div class='w3-container'>\n";
	print "<h2>Dynamic Tools Admin</h2>\n";
	
	$results=DB::query("SELECT fbFirstName, fbLastName, appName, fbEmail, userId from tblEptUsers ORDER BY fbLastName, fbFirstName");
	
	printf("<div class='w3-dropdown-hover'><button class='w3-button w3-blue'>Account Access</button>\n");
	printf("<div class='w3-dropdown-content w3-bar-block w3-border' style='height:400px; overflow:auto'>\n");
	
	foreach($results as $r) {
		printf("<p><a href='?uid=%d'>%s %s - %s - %s</p>\n", $r["userId"], $r["fbFirstName"], $r["fbLastName"], $r["appName"], $r["fbEmail"]);
	}

			
	print "</div>\n";
	print "</div>\n";
	
	printf("<br><br><a class='w3-button w3-blue' href='?op=setupresthook'>Set Up Resthook</a>\n");
	printf("<br><br><a class='w3-button w3-blue' href='?op=listresthooks'>List Resthooks</a>\n");
	
	print "</div>\n";
	print "</div>\n";
}

if ($op == "showstatus") {
	print "<div class='w3-container'>\n";
	print "<h2>Welcome to Email Power Tools</h2>\n";
	printf("<p>You are logged into Infusionsoft as <strong>%s</strong> (%s)</p>\n", $isUserInfo["displayName"], $isUserInfo["casUsername"]);
	printf("<p>You are connected to Infusionsoft app <strong>%s</strong> (%s)</p>\n", $isUserInfo["appAlias"], $isUserInfo["appUrl"]);

	##### SETUP RESTHOOK #####
	$restHookUrl=sprintf("http://wdem.wedeliver.email/restHookHandler.php?appName=%s&authHash=%s", $userDetails["appName"], $userDetails["authhash"]);

	if(isset($_GET['setupresthook'])) { 
		
		try {
			$resthook = setupRestHook($infusionsoft, 'contact.add', $restHookUrl); 	
		}
		catch (\Infusionsoft\TokenExpiredException $e) {
			$infusionsoft->refreshAccessToken();
			updateDbWithInfusionsoftToken($userId, $token);
			$resthook = setupRestHook($infusionsoft, 'contact.add', $restHookUrl);
		}

		// Check if Admin Access - Doms
		if ($userDetails["userId"]==233 || $userDetails["userId"]==334) {
			echo '<p>RESThook status: Verified</p>';
			echo "<p>RESThook set up correctly</p>";
			echo '<pre>';
			print "NEW RESTHOOK DETAILS:\n";
			print_r($resthook);
			print "<pre>\n";
		}else {
			echo '<p>RESThook status: Verified</p>';
			echo "<p>RESThook set up correctly</p>";
		}

	}else {

		if ($infusionsoft->getToken()) {
			echo '<p>RESThook status: Verified</p>';
		}else {
			echo "<a class='w3-button w3-blue' href='?op=showstatus&setupresthook=1'>Set Up Resthook</a>";
		}

	}

	print "<hr>\n";
	$cacheInfo=getAllMySqlLastUpdate($appName);
	if ($cacheInfo == NULL) {
		printf("<p>No data has been cached yet</p>\n");
	} else {
		foreach ($cacheInfo as $tableCache) {
			printf("<p>Table %s was last updated %s day%s ago (%s)</p>\n", $tableCache["tableName"], $tableCache["daysAgo"], $tableCache["daysAgo"]==1?"":"s", $tableCache["lastComplete"]);
		}
	}
	printf("<p>User-Agent: %s</p>\n", $userAgent);
	print "</div>\n";
}

if ($op == "OLDshowstatus") {
	// https://stackoverflow.com/questions/5298401/basic-php-and-ajax
	print <<<__EOF
		<p>Hello, click this button: <a id="button" href="#">Click me</a></p>
		<p id="container"><!-- currently it's empty --></p>
__EOF;
	printAjaxScript("receiver.php");
}

if ($op == "ajaxEngagementReport") {
	print "<h1>Report Output</h1>\n";
	print '<div id="container"><!-- empty to start with --></div>';
	print "\n";
	printAjaxScript(sprintf("ept.php?op=ajaxResult&report=engagement&user=%d", $userId));	
}

if ($op == "listresthooks" || $op == "deleteresthooks") {
	echo "<h1>RESThook Details</h1>";
	echo '<pre>';
	$results = $infusionsoft->resthooks()->all();
	$count=$results->count();
	printf("Count: %d\n", $count);
	printf("-----\n");
	if ($count > 0) {
		$ar=$results->toArray();
		foreach($ar as $r) {
			print_r($r->toArray());
//			print_r($r);
			printf("Key: %d\nEventKey: %s\nhookUrl: %s\nstatus: %s\n%s======\n", $r->key, $r->eventKey, $r->hookUrl, $r->status, $op == "deleteresthooks" ? "DELETING\n" : "");			
			if ($op == "deleteresthooks") {
				$infusionsoft->resthooks()->find($r->key)->delete();
			}
		}
	}
}

if ($op == "enableresthooks") {
	$userDetails = DB::queryFirstRow("select userId, isContactId, appName, authhash from tblEptUsers u where userId=%i", $userId);
	$appName = $userDetails['appName'];
	$userId = $userDetails['userId'];
	$authhash = $userDetails['authhash'];

	if (isset($_SESSION['token'])) {
		$infusionsoft->setToken(unserialize($_SESSION['token']));
	}

	##### IF WE ARE RETURNING FROM INFUSIONSOFT WE NEED TO EXCHANGE THE CODE FOR AN ACCESS TOKEN #####
	if (isset($_GET['code']) and !$infusionsoft->getToken()) {
		$infusionsoft->requestAccessToken($_GET['code']);
		$_SESSION['token'] = serialize($infusionsoft->getToken());
	}

	function resthookManager($infusionsoft, $appName, $authhash, $userId) {
		$restHookUrl = sprintf("http://wdem.wedeliver.email/restHookSetupEmail.php?appName=%s&authHash=%s&userId=%d", $appName, $authhash, $userId);
		$resthooks = $infusionsoft->resthooks();

		##### CREATE A NEW RESTHOOK #####
		$resthook = $resthooks->create([
			'eventKey' => 'contact.add',
			'hookUrl' => $restHookUrl
		]);
		$resthook = $resthooks->find($resthook->id)->verify();
		return $resthook;
	}

	if ($infusionsoft->getToken()) {
		try {
			$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId);
		}
		catch (\Infusionsoft\TokenExpiredException $e) {

			##### IF THE REQUEST FAILS DUE TO AN EXPIRED ACCESS TOKEN, WE CAN REFRESH THE TOKEN AND THEN DO THE REQUEST AGAIN #####
			$infusionsoft->refreshAccessToken();

			##### SAVE THE SERIALIZED TOKEN TO THE CURRENT SESSION FOR SUBSEQUENT REQUESTS #####
			$_SESSION['token'] = serialize($infusionsoft->getToken());
			$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId);
		}
	}
	else {
		echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
		die();
	}
}

if (strpos($op, 'weDeliverEmailSettings') !== false) {
	$baseUrl = "https://$_SERVER[HTTP_HOST]/";
	?>
	<style>
		.buttonSaveApiKey {
			margin-left: 0 !important;
		}
	</style>
	<div class="w3-container weDeliverEmailSettings" data-baseurl="<?php echo $baseUrl;?>">
		<h2>WeDeliver Email Settings</h2>
		<div class="onDemandScrub">
			<!-- <a class="w3-button w3-blue" href="?op=weDeliverEmailSettings&enableresthook=1">Enable Resthook</a> -->
			<p>
				<label class="switch" title="Click to switch off">
					<input type="checkbox" class="toggleConfig">
					<span class="slider round"></span>
				</label>
				<span class="switchLabel">Enable/Disable RESThook<span class="switchStatus" style="display: none">OFF</span></span>
			</p>
			<div class="apiConfig">
				<button class="buttonSaveApiKey">Save settings</button>
			</div>
			<p class="appName" style="display: none"><?php echo $appName; ?></p>
	  		<p class="authhash" style="display: none"><?php echo $authHash; ?></p>
			<p class="userId" style="display: none"><?php echo $userId; ?></p>
			<div class='loaderWrap'></div>
			<div class='resMsgWrap'></div>
		</div>
	</div>

	<script>
		$(function() {
			var baseUrl = $(".weDeliverEmailSettings").attr("data-baseurl");

			$(".buttonSaveApiKey").click(function() {
				var toggleConfig = $('.toggleConfig:checked').val();

				if(toggleConfig == 'on') {
					$(".switchStatus").text('ON');
					setupRESThook();
				}else {
					deleteRESThook();
				}
			});

			// ##### SWITCH STATE #####
			switchState();
			function switchState() {
				var userId = $(".userId").text();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'weDeliverSwitchState',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						
						if(data.isEnable) {
							// Switch on
							$(".toggleConfig").attr('checked','checked');
						}else {
							// Switch off
							$(".toggleConfig").removeAttr('checked');
						}
					}
				});
			}

			// ##### SETUP RESTHOOK #####
			function setupRESThook() {
				var appName = $(".appName").text();
				var authhash = $(".authhash").text();
				var userId = $(".userId").text();

				var loader = "<p class='restLoading'><span class='loader'></span> Setting up the RESThook please wait...</p>";
				$(".loaderWrap").append(loader);

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'weDeliverSetupRESThook',
						appName: appName,
						authhash: authhash,
						userId: userId,
					},
					success: function(data) {
						var data = JSON.parse(data);
						if(data.success) {
							$(".loaderWrap .restLoading").remove();
							var res = `
								<div class='w3-green restCbMsg configMessage'>
				  					<p>RESThook was set up.</p>
								</div>
							`;

							$(".resMsgWrap").append(res);
						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error setting up the RESThook.');
						}

						setTimeout(function() {
							$(".successConfigSave, .restCbMsg").remove();
						}, 4000);
					}
				});
			}

			// ##### DELETE RESTHOOK #####
			function deleteRESThook() {
				var userId = $(".userId").text();

				var loader = "<p class='deleteRestLoading'><span class='loader'></span> Deleting RESThook please wait...</p>";
				$(".loaderWrap").append(loader);

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'weDeliverDeleteRestHook',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						if(data.success) {
							$(".toggleConfig").removeAttr("disabled");
							$(".loaderWrap .deleteRestLoading").remove();

							var res = `
							<div class='w3-green delWebCbMsg configMessage'>
								<p>RESThook was deleted.</p>
							</div>
							`;

							$(".resMsgWrap").append(res);

							setTimeout(function() {
								$(".resMsgWrap .delWebCbMsg").remove();
							}, 4000);
						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error deleting RESThook.');
							$(".toggleConfig").removeAttr("disabled");
						}
					}
				});
			}
		})
	</script>
<?php
}

##### FOR TESTING ONLY ADD CONTACT #####
if ($op == "addnewcontactresthook") {
	if ($infusionsoft->getToken()) {

		$email = 'adrian+2016dtest@caldon.uk';
		$email1 = new \stdClass;
	    $email1->field = 'EMAIL1';
	    $email1->email = $email;
	    $contact = ['given_name' => 'John', 'family_name' => 'Doe', 'email_addresses' => [$email1]];

		$infuNewContact = $infusionsoft->contacts()->create($contact);

		echo '<pre>';
		print_r($infuNewContact);
		echo '<pre>';

	}else {
		echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
	}
}

##### SETUP RESTHOOK #####
if ($op == "setupresthook") {
	if (isset($_SESSION['token'])) {
		$infusionsoft->setToken(unserialize($_SESSION['token']));
	}

	if (isset($_GET['code']) and !$infusionsoft->getToken()) {
		$infusionsoft->requestAccessToken($_GET['code']);
		$_SESSION['token'] = serialize($infusionsoft->getToken());
	}
	function resthookManager($infusionsoft, $appName, $authhash, $userId) {
		$restHookUrl = sprintf("http://wdem.wedeliver.email/restHookHandler.php?appName=%s&authHash=%s&userId=%d", $appName, $authhash, $userId);
		$resthooks = $infusionsoft->resthooks();
		// first, create a new task
		$resthook = $resthooks->create([
			'eventKey' => 'contact.add',
			'hookUrl' => $restHookUrl
		]);
		$resthook = $resthooks->find($resthook->id)->verify();
		return $resthook;
	}
	if ($infusionsoft->getToken()) {
		try {
			$resthook = resthookManager($infusionsoft, $userDetails["appName"], $userDetails["authhash"], $userId);
		}
		catch (\Infusionsoft\TokenExpiredException $e) {
			// If the request fails due to an expired access token, we can refresh
			// the token and then do the request again.
			$infusionsoft->refreshAccessToken();
			// Save the serialized token to the current session for subsequent requests
			$_SESSION['token'] = serialize($infusionsoft->getToken());
			$resthook = resthookManager($infusionsoft, $userDetails["appName"], $userDetails["authhash"], $userId);
		}
	}
	else {
		echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
	}
}

if ($op == "listorders") {
	processOrders($infusionsoft);
}

if ($op == "emailSentReport") {
	printf("<h2>Email Sent Report</h2>\n");	
	
	/**
	// Get newest contact
	$orderBy="Id";
	$ascending=false;
	$page=0;
	$table="Contact";
	$queryData=array('Id' => '%'); // Other is EmailSentSearch
	$results=$infusionsoft->data()->count($table, $queryData);
//	$this>$this->infusionsoftService->data()->query($table, 100, 0, ["Id"=>"%"], ["Id"] ,["Id"], 1 );
	print "<pre>\nContact Count:\n";
	print_r($results);
	print "</pre>\n";
	
	$results=$infusionsoft->data()->query($table, 1, $page, $queryData, ["Id", "DateCreated", "Email"], $orderBy, $ascending);
	print "<pre>\n";
	printf("Highest Contact ID: %d\n", $results[0]["Id"]);
	printf("Email: %s\n", $results[0]["Email"]);
	print "</pre>\n";
	
	$newestContactId=$results[0]["Id"];
	$newestEmail=$results[0]["Email"];
	**/
	
	
	/** 
	$results=$infusionsoft->emails()->where(array("email"=>$newestEmail,"limit"=>1))->get();
//	$results=$infusionsoft->emails()->where(array("email"=>"adrian@adriansavage.co.uk","limit"=>2,"offset"=>4,"sent_date"=>"2017-08-04T07:34:40.000Z"))->get();
//	$results=$infusionsoft->emails(array('contact_id'=>3))->first();
	
	print "<pre>";
	printf("Count: %d\n", $results->count());
	print_r($results);
	print "</pre>";
	**/
	
	print "<pre>\n";
	$offset=10000000000;
//	$offset=100000;
	$done=0;
	while (!$done) {
		printf("Offset: %d\n", $offset);
		$results=$infusionsoft->emails()->where(array("offset"=>$offset,"limit"=>1))->get();
		$count=$results->count();
		printf("Count: %d\n", $count);
		printf("-----\n");
		if ($count > 0) {
			$details=$results->toArray()[0];
			$sentTo=$details["sent_to_address"];
			$sentDate=$details["sent_date"];
            $epoch=strtotime($sentDate);
            $newDate=date('Y-m-d', $epoch);
			$opened=$details["opened_date"]==""?"No":"Yes";
			printf("Offset: %9d - Date: %s %s - To: %s - Opened: %s\n", $offset, $sentDate, $newDate, $sentTo, $opened);
			$sentLog[$offset]=$newDate;
			
			$done=1;
		} else if ($offset > 1) {
			$offset = $offset/10;
		} else {
			$done=1;
		}
	}
	printf("Max offset: %d\n==========\n", $offset);	
	
	$done=0;
	$max=$offset;
	$inc=$offset;
	while (!$done) {
		printf("Request: Offset=%d\n", $offset);
		$results=$infusionsoft->emails()->where(array("offset"=>$offset,"limit"=>1))->get();
		$count=$results->count();
		printf("Count: %d\n", $count);
		if ($count > 0) {
			$details=$results->toArray()[0];
			$sentTo=$details["sent_to_address"];
			$sentDate=$details["sent_date"];
            $epoch=strtotime($sentDate);
            $newDate=date('Y-m-d', $epoch);
			$opened=$details["opened_date"]==""?"No":"Yes";
			printf("Offset: %9d - Date: %s %s - To: %s - Opened: %s\n", $offset, $sentDate, $newDate, $sentTo, $opened);
			$sentLog[$offset]=$newDate;
			
//			print_r($results[0]);
			$offset += $inc;
			printf("-----\n");
		} else {
			if ($inc >= ($max/10)) {
				$offset -= $inc;
				$inc = $inc / 10;
				$offset += $inc;
			} else {
				$offset -= $inc;
				$done=1;
			}
		}
	}
	
	printf("Max offset: %d\n==========\n", $offset);	
	
	print "Offset Summary\n";
	foreach ($sentLog as $thisOffset=>$date) {
		printf("%9d: %s\n", $thisOffset, $date);
	}
	
	$results=$infusionsoft->emails()->where(array("offset"=>$offset))->get();
	$count=$results->count();
	printf("Count above %d: %d\n", $offset, $count);
}

if ($op == "updateContactData") {

	if ($platform == "AC") {	
		print "<div class='w3-container'>\n";
		
		printf("<h2>Loading ActiveCampaign Contact Data</h2>\n");	
	
		print "<p>This may take some time, please wait...</p>\n";
		
		// Firstly, print some feedback for the user
		printProgressBarContainer();		
		print "<p>We can't display an accurate progress bar, as ActiveCampaign doesn't tell us how many pages of data it's going to return <i class='fa fa-frown-o w3-large'></i></p>\n";

		// Update the Contact data stored in MySQL
		$lastUpdate=getMySqlLastUpdate($appName, "Contact");
		syncAcContactsToMySql($appName, "Contact", $lastUpdate, array(array("Id"=>"%")), array(array("LastUpdated"=>"~>=~ $lastUpdate")));

		printf("<p>Completed</p>\n");
		printf('<script>setProgressBar(%d);</script>' . "\n", 100);
		print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
	
		print '<p>You can now view the <a href="?op=emailEngagementReport">Email Engagement Report</a> or the <a href="?op=lostCustomersReport">Lost Customers Report</a></p>' . "\n";

		print "</div>\n";
	}
	elseif ($platform == "MC") {	
		print "<div class='w3-container'>\n";
		
		printf("<h2>Loading MailChimp Contact Data</h2>\n");	
	
		print "<p>This may take some time, please wait...</p>\n";
		
		// Firstly, print some feedback for the user
		printProgressBarContainer();		
		print "<p>We can't display an accurate progress bar, as MailChimp doesn't tell us how many pages of data it's going to return <i class='fa fa-frown-o w3-large'></i></p>\n";

		// Update the Contact data stored in MySQL
		$lastUpdate=getMySqlLastUpdate($appName, "Contact");
		//echo 'api key : '.$userDetails["accessToken"];
		syncMcContactsToMySql($appName,$userDetails["accessToken"], "Contact", $lastUpdate, array(array("Id"=>"%")), array(array("LastUpdated"=>"~>=~ $lastUpdate")));

		printf("<p>Completed</p>\n");
		printf('<script>setProgressBar(%d);</script>' . "\n", 100);
		print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
	
		print '<p>You can now view the <a href="?op=emailEngagementReport">Email Engagement Report</a> or the <a href="?op=lostCustomersReport">Lost Customers Report</a></p>' . "\n";

		print "</div>\n";
	}
	elseif ($platform == "ISFT") {	
		print "<div class='w3-container'>\n";
		
		printf("<h2>Loading Contact Data</h2>\n");	
	
		print "<p>Firstly, we will update the Email Address Status data, followed by the Contact data, followed by Invoice data. We do our best to only update data that's changed, although this isn't 100% possible</p>\n";
	
		// Firstly, print some feedback for the user
		printProgressBarContainer();
	
		// Update the EmailAddStatus data stored in MySQL
		$lastUpdate=getMySqlLastUpdate($appName, "EmailAddStatus");
		syncXmlToMySql($appName, "EmailAddStatus", $lastUpdate, array(
			array("Id"=>"%")
		), array(
			array("LastSentDate"=>"~>=~ $lastUpdate"),
			array("LastOpenDate"=>"~>=~ $lastUpdate"),
			array("LastClickDate"=>"~>=~ $lastUpdate"),
			array("DateCreated"=>"~>=~ $lastUpdate"),
			array("Type"=>"Lockdown"),
			array("Type"=>"System"),
			array("Type"=>"NonMarketable"),
			array("Type"=>"ListUnsubscribe"),
			//		array("Type"=>"Bounce"),
			//		array("Type"=>"Invalid"),
			//		array("Type"=>"Admin"),
			array("Type"=>"Spam"),
			array("Type"=>"Feedback"),
			//		array("Type"=>"HardBounce"),
			//		array("Type"=>"Manual"),			
			array("Type"=>"UnengagedNonMarketable")
		));
	
		/**
		IDEA: If the contact's LastUpdated value gets updated when the EmailAddStatus changes, just get a list of those contacts and update EmailAddStatus for those addresses
		**/
	
		// Update the Contact data stored in MySQL
		$lastUpdate=getMySqlLastUpdate($appName, "Contact");
		syncXmlToMySql($appName, "Contact", $lastUpdate, array(array("Id"=>"%")), array(array("LastUpdated"=>"~>=~ $lastUpdate")));

		// Update the EmailAddStatus data stored in MySQL
		$lastUpdate=getMySqlLastUpdate($appName, "Invoice");
		syncXmlToMySql($appName, "Invoice", $lastUpdate, [["Id"=>"%"]], [["LastUpdated"=>"~>=~ $lastUpdate"]]);

		printf("<p>Completed</p>\n");
		printf('<script>setProgressBar(%d);</script>' . "\n", 100);
		print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
	
		print '<p>You can now view the <a href="?op=emailEngagementReport">Email Engagement Report</a> or the <a href="?op=lostCustomersReport">Lost Customers Report</a></p>' . "\n";

		print "</div>\n";
	}
}

if ($op == "updateOpportunityData") {
	
	print "<div class='w3-container'>\n";
		
	printf("<h2>Loading Opportunity Data</h2>\n");	
	
	print "<p>We're going to update the Opportunity data. We do our best to only update data that's changed, although this isn't 100% possible</p>\n";
	
	// Firstly, print some feedback for the user
	printProgressBarContainer();
	
	// Update the Lead data stored in MySQL
	$lastUpdate=getMySqlLastUpdate($appName, "Lead");
	syncXmlToMySql($appName, "Lead", $lastUpdate, [["Id"=>"%"]], [["LastUpdated"=>"~>=~ $lastUpdate"]]);
	
	$lastUpdate=getMySqlLastUpdate($appName, "StageMove");
	syncXmlToMySql($appName, "StageMove", $lastUpdate, [["Id"=>"%"]], [["MoveDate"=>"~>=~ $lastUpdate"]]);
	
	$lastUpdate=getMySqlLastUpdate($appName, "Stage");
	syncXmlToMySql($appName, "Stage", $lastUpdate, [["Id"=>"%"]], [["Id"=>"%"]]);
			
	printf("<p>Completed</p>\n");
	printf('<script>setProgressBar(%d);</script>' . "\n", 100);
	print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
	
	print '<p>You can now <a href="?op=opportunityReport">view the Opportunities report</a>' . "\n";

	print "</div>\n";
}

if ($op == "updateInvoiceData") {
	
	print "<div class='w3-container'>\n";
		
	printf("<h2>Loading Invoice Data</h2>\n");	
	
	print "<p>We're going to update the Invoice data. We do our best to only update data that's changed, although this isn't 100% possible</p>\n";
	
	// Firstly, print some feedback for the user
	printProgressBarContainer();
	
	// Update the EmailAddStatus data stored in MySQL
	$lastUpdate=getMySqlLastUpdate($appName, "Invoice");
	syncXmlToMySql($appName, "Invoice", $lastUpdate, [["Id"=>"%"]], [["LastUpdated"=>"~>=~ $lastUpdate"]]);
		
	printf("<p>Completed</p>\n");
	printf('<script>setProgressBar(%d);</script>' . "\n", 100);
	print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
	
	print '<p>You can now <a href="?op=lostCustomersReport">view the Lost Customers report</a>' . "\n";

	print "</div>\n";
}

if ($op == "reloadInvoiceData") {
	
	print "<div class='w3-container'>\n";
		
	printf("<h2>Reloading Invoice Data</h2>\n");	
	
	print "<p>We're going to reload the Invoice data</p>\n";
	
	// Firstly, print some feedback for the user
	printProgressBarContainer();
	
	// Update the EmailAddStatus data stored in MySQL
	$lastUpdate=NULL;
	syncXmlToMySql($appName, "Invoice", $lastUpdate, [["Id"=>"%"]], [["LastUpdated"=>"~>=~ $lastUpdate"]]);
		
	printf("<p>Completed</p>\n");
	printf('<script>setProgressBar(%d);</script>' . "\n", 100);
	print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
	
	print '<p>You can now <a href="?op=lostCustomersReport">view the Lost Customers report</a>' . "\n";

	print "</div>\n";
}

if (0) {
	$thisCumulativePage=0;		
	
	$esPage=0;
	$count=0;
	$table="EmailAddStatus";
	$queryData=array('Id' => "%");
	$totalAddStatus=$infusionsoft->data()->count($table, $queryData);
	$pagesAddStatus=ceil($totalAddStatus/1000);
	$table="Contact";
	$totalContacts=$infusionsoft->data()->count($table, $queryData);
	$pagesContacts=ceil($totalContacts/1000);
	
	$totalPages=$pagesContacts+$pagesAddStatus;
	
	$startTime=time();
	
	$table="EmailAddStatus";
	$queryData=array('Id' => "%");
	$orderBy="Id";
	$ascending=true;
	
	printProgressBarContainer();
	
	//print '<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Status updates will go here";</script>';
	print '<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading Email Status Table:";</script>';		
		
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		$sentCount[$days]=0;
		$openCount[$days]=0;
		$clickCount[$days]=0;
	}
	
	do {
		$results=$infusionsoft->data()->query($table, 1000, $esPage, $queryData, array("Id","Email","DateCreated","LastOpenDate","LastClickDate","LastSentDate","Type"), $orderBy, $ascending);
	
		foreach($results as $result) {
			$count++;
			
			$now=time();
			
			// print_r($result);
			
			if (isset($result["Email"])) {
				$emailAddr=strtolower($result["Email"]);
			} else {
				$emailAddr="--";
			}
			
			$optStatus[$emailAddr]=$result["Type"];
			
			if (isset($result["LastSentDate"])) {
				$lastSentDate=$result["LastSentDate"]->format('Y-m-d');
				$lastSentTimestamp=$result["LastSentDate"]->getTimestamp();
				$daysSinceSent=($now-$lastSentTimestamp)/86400;
				if ($lastSentTimestamp <= 0) {
					$lastSentDate="NEVER";
					$lastSentTimestamp=0;
					$daysSinceSent=9999;
				}
			} else {
				$lastSentDate="NEVER";
				$lastSentTimestamp=0;
				$daysSinceSent=9999;
			}
			
			if (isset($result["LastOpenDate"])) {
				$lastOpenDate=$result["LastOpenDate"]->format('Y-m-d');
				$lastOpenTimestamp=$result["LastOpenDate"]->getTimestamp();
				$daysSinceOpen=($now-$lastOpenTimestamp)/86400;
			} else {
				$lastOpenDate="NEVER";
				$lastOpenTimestamp=0;
				$daysSinceOpen=9999;
			}
			
			if (isset($result["LastClickDate"])) {
				$lastClickDate=$result["LastClickDate"]->format('Y-m-d');
				$lastClickTimestamp=$result["LastClickDate"]->getTimestamp();
				$daysSinceClick=($now-$lastClickTimestamp)/86400;
			} else {
				$lastClickDate="NEVER";
				$lastClickTimestamp=0;
				$daysSinceClick=9999999999;
				
			}
			
			$lastSentDates["$emailAddr"]=$lastSentDate;
			$lastOpenDates["$emailAddr"]=$lastOpenDate;
			$lastClickDates["$emailAddr"]=$lastClickDate;
			$daysSinceSentArray["$emailAddr"]=$daysSinceSent;
			$daysSinceOpenArray["$emailAddr"]=$daysSinceOpen;
			$daysSinceClickArray["$emailAddr"]=$daysSinceClick;
			
			foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
				if ($daysSinceSent <= $days) {
					$sentCount[$days]++;
				}
				if ($daysSinceOpen <= $days) {
					$openCount[$days]++;
				}
				if ($daysSinceClick <= $days) {
					$clickCount[$days]++;
				}
			}
			
			// printf("Email %-40.40s - Sent %8s - Open %8s - Click %8s\n", $emailAddr, $lastSentDate, $lastOpenDate, $lastClickDate);
			
		}
		$esPage++;
		$thisCumulativePage++;
		if ($thisCumulativePage > $pagesAddStatus) {
			$thisCumulativePage=$pagesAddStatus;
		}

		printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $esPage, $count, $totalAddStatus);
		printf('<script>setProgressBar(%d);</script>' . "\n", 100*$thisCumulativePage/$totalPages);

	} while(1 && (count($results) > 0));
	
/**	
	print "Summary\n<table border><tr><th>Days<th>Sent<th>Opened<th>Clicked</th></tr>\n";
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		printf("<tr><td>%d<td>%d<td>%d<td>%d</td></tr>\n", $days, $sentCount[$days], $openCount[$days], $clickCount[$days]);
	}
	print "</table>\n";
**/
		
	// $startTime=time();
	
	$lastSentDates["--"]="";
	$lastOpenDates["--"]="";
	$lastClickDates["--"]="";
	$daysSinceSentArray["--"]=9999;
	$daysSinceOpenArray["--"]=9999;
	$daysSinceClickArray["--"]=9999;
	
	$optStatus["--"]="---";
		
	$table="Contact";
	$queryData=array('Id' => "%");
	$orderBy="Id";
	$ascending=true;
		
	print '<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading Contact Table:";</script>';		
		
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		$sentCount[$days]=0;
		$openCount[$days]=0;
		$clickCount[$days]=0;
	}
	
	$totalProcessed=0;
	$esPage=0;
	$offset=0;
	do {
		
/**		
		$results=$infusionsoft->contacts()->where(array("offset"=>$offset,"limit"=>1000))->get();
		$count=$results->count();
		printf("Offset: %d - Count of results: %d\n", $offset, $count);
	
		//$details=$results->toArray();
		//print_r($details);
		for ($i=0; $i < $count; $i++) {
			$result=$results->offsetGet($i);
			//print_r($result);

			$emailAddresses=$result->email_addresses;
			$email1="--";
			$email2="--";
			$email3="--";
			foreach ($emailAddresses as $email) {
				if ($email["field"] == "EMAIL1") {
					$email1=strtolower($email["email"]);
				} else if ($email["field"] == "EMAIL2") {
					$email2=strtolower($email["email"]);
					if ($email2 == "nonmarketable@wedeliver.email") {
						$email2="--";
					}
				} else if ($email["field"] == "EMAIL3") {
					$email3=strtolower($email["email"]);
					if ($email3 == "nonmarketable@wedeliver.email") {
						$email3="--";
					}
				}
			}

			$emailStatus=$result->email_status;
		
			$contactId=$result->id;
**/
		
		$results=$infusionsoft->data()->query($table, 1000, $esPage, $queryData, array("Email","EmailAddress2","EmailAddress3","Id"), $orderBy, $ascending);
		$count=count($results);
		//printf("Offset: %d - Count of results: %d\n", $offset, $count);
		foreach($results as $result) {
			$totalProcessed++;
			
			$now=time();
			
			// print_r($result);
			
			$id=$result["Id"];

		
			if (isset($result["Email"])) {
				$email1=strtolower($result["Email"]);
			} else {
				$email1="--";
			}
			if (isset($result["EmailAddress2"])) {
				$email2=strtolower($result["EmailAddress2"]);
			} else {
				$email2="--";
			}
			if (isset($result["EmailAddress3"])) {
				$email3=strtolower($result["EmailAddress3"]);
			} else {
				$email3="--";
			}		
				
			if (isset($optStatus[$email1])) {		
				$opt1=$optStatus[$email1];
			} else {
				$opt1="--";
				$email1="--";
			}
			if (isset($optStatus[$email2])) {		
				$opt2=$optStatus[$email2];
			} else {
				$opt2="--";
				$email2="--";
			}
			if (isset($optStatus[$email2])) {		
				$opt2=$optStatus[$email2];
			} else {
				$opt2="--";
				$email2="--";
			}
		
			$daysSinceSent=$daysSinceSentArray[$email1];
			$lastSent=$lastSentDates[$email1];
			if ($daysSinceSent > $daysSinceSentArray[$email2]) {
				$daysSinceSent = $daysSinceSentArray[$email2];
				$lastSent = $lastSentDates[$email2];
			}
			if ($daysSinceSent > $daysSinceSentArray[$email3]) {
				$daysSinceSent = $daysSinceSentArray[$email3];
				$lastSent = $lastSentDates[$email3];
			}
		
			$daysSinceOpen=$daysSinceOpenArray[$email1];
			$lastOpen=$lastOpenDates[$email1];
			if ($daysSinceOpen > $daysSinceOpenArray[$email2]) {
				$daysSinceOpen = $daysSinceOpenArray[$email2];
				$lastOpen = $lastOpenDates[$email2];
			}
			if ($daysSinceOpen > $daysSinceOpenArray[$email3]) {
				$daysSinceOpen = $daysSinceOpenArray[$email3];
				$lastOpen = $lastOpenDates[$email3];
			}
		
			$daysSinceClick=$daysSinceClickArray[$email1];
			$lastClick=$lastClickDates[$email1];
			if ($daysSinceClick > $daysSinceClickArray[$email2]) {
				$daysSinceClick = $daysSinceClickArray[$email2];
				$lastClick = $lastClickDates[$email2];
			}
			if ($daysSinceClick > $daysSinceClickArray[$email3]) {
				$daysSinceClick = $daysSinceClickArray[$email3];
				$lastClick = $lastClickDates[$email3];
			}
		
			foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
				if ($daysSinceSent <= $days) {
					$sentCount[$days]++;
				}
				if ($daysSinceOpen <= $days) {
					$openCount[$days]++;
				}
				if ($daysSinceClick <= $days) {
					$clickCount[$days]++;
				}
			}		
			
			//printf("ContactID: %d - Email1: %s - Email2: %s - Email3: %s - Status: %s\n", $contactId, $email1, $email2, $email3, $emailStatus);

		}
		$offset += $count;
		$esPage++;
		$thisCumulativePage++;
		if ($thisCumulativePage > $totalPages) {
			$thisCumulativePage=$totalPages;
		}
		printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $esPage, $totalProcessed, $totalContacts);
		printf('<script>setProgressBar(%d);</script>' . "\n", 100*$thisCumulativePage/$totalPages);		
	} while (($count > 0));
					
	// ====================
	
	print '<script>var eeDiv = document.getElementById("eeProgressContainer"); eeDiv.style.display = "none"; </script>';
	
	print "<h4>Summary</h4>\n<table border><tr><th>Days<th>Sent<th>Opened<th>Clicked</th></tr>\n";
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		printf("<tr><td>%d<td>%d<td>%d<td>%d</td></tr>\n", $days, $sentCount[$days], $openCount[$days], $clickCount[$days]);
	}
	print "</table>\n";
	
	printf("<p>Execution time: %d seconds</p><hr>\n", time()-$startTime);
}

if(strpos($op, 'opportunityActivityReport') !== false) {
?>

<style type="text/css">
	tr.rowChange {
	    border-top: 5px solid #948d8d !important;
	}
	.tabularWrap {
	    width: 100%;
	}

	.tabularWrap h2 {
	    display: inline-block;
	}

	.tabularWrap form {
    	text-align: right;
        float: right;
        padding-top: 25px;
        width: 10%;
	}

	.tabularWrap form select {
		width: 100%;
		padding: 5px;
	}

	table.w3-card-4 {
		margin-bottom: 30px !important;
	}
</style>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>

<script>
	$(function() {
		$(".tabularFilter").change(function() {
			var val = $(this).val();
			if(window.location.href.indexOf('filter') > -1) {
				var url = window.location.href.split('filter')[0];
				var redirectUrl = url+'filter='+val;
			}else {
				var redirectUrl = window.location.href+'?filter='+val;
			}

			window.location.href = redirectUrl;
		});

		getFilterValue();
		function getFilterValue() {
			var value = window.location.href.split('filter=')[1];
			$('.tabularFilter option[value="'+value+'"]').attr('selected','selected');
		}
	});
</script>
<?php
	
	$filter = 'daily';

	if(count(explode("filter=",$_GET['op'])) > 1) {
		$filter = explode("filter=",$_GET['op'])[1];

		$activityReports = getOpportunityActivityReport($appName, $filter);
	}else {
		$activityReports = getOpportunityActivityReport($appName, 'daily');
	}

	echo '
		<div class="w3-container">
			<h2>Opportunity Activity Report</h2>

			</table>
				<h2>Summary</h2>
				<table class="w3-table-all w3-card-4">
					<tr>
						<th></th>
						<th>NumOpps</th>
						<th colspan="2" style="width: 20%;">Stage Won</th>
						<th colspan="2" style="width: 20%;">Stage Lost</th>
					</tr>';

					foreach (array(7, 30, 60, 90, 120, 180, 365) as $days) {
						$resultArray[$days] = getOpportunityActivityReportSummary($appName, $days);
					}

					foreach ($resultArray as $res) {
						$totalWonPercentage = number_format(($res['totalWon'] / $res['totalNumOpps']) * 100, 1);
						$totalLostPercentage = number_format(($res['totalLost'] / $res['totalNumOpps']) * 100, 1);
						echo '
							<tr>
								<td>'.$res['days'].' days</td>
								<td>'.$res['totalNumOpps'].'</td>
								<td>'.$res['totalWon'].'</td>
								<td>'.$totalWonPercentage.'%</td>
								<td>'.$res['totalLost'].'</td>
								<td>'.$totalLostPercentage.'%</td>
							</tr>
						';
					}
					
			echo '
				</table>
			<br>
			<div class="tabularWrap">
				<h2>Tabular Data</h2>
				<form>
					<select class="tabularFilter">
						<option value="daily">Daily</option>
						<option value="weekly">Weekly</option>
						<option value="monthly">Monthly</option>
					</select>
				</form>
			</div>
			<table class="w3-table-all w3-card-4">
				<tr>
					<th>Name</th>
					<th>StageName</th>
					<th>NumOpps</th>
					<th>Move Date</th>
				</tr>';
				$weeklyPrefix = '';
				if($filter == 'weekly') {
					$weeklyPrefix = 'w/c';
				}
				$date2 = date("Y-m-d");
				foreach ($activityReports as $row) {
					$date1 = $row['DD'];
					if($date1 != $date2) {
						echo '<tr class="rowChange">';
						$date2 = $date1;
					}else {
						echo '<tr>';
					}
					echo '
							<td>'.$row['FirstName'].' '.$row['LastName'].'</td>
							<td>'.$row['StageName'].'</td>
							<td>'.$row['NumOpps'].'</td>
							<td>'.$weeklyPrefix.' '.$row['moveDateFormatted'].'</td>
						</tr>
					';
				}
				
		echo '
		</div>
	';
}

if ($op == "emailOpenReport") {
	
    print '<div class="w3-container">';
	
	print "<h2>Email Open Report</h2>\n";	
		
	if (!doesTableExist($appName, "EmailSentSummary")) {
		print "<h2>Data Needs to be Updated</h2>\n";
		print "<p>Email Power Tools needs to summarise the historical email data.</p>\n";
		print "<p>Please <a href=\"?op=updateEmailOpenData\">click here</a> to do this now.</p>\n";
		print "</div>\n";
		print "</div>\n";
		print "</html>\n";
		exit;
	}
	
	print "<h2>Tabular Data</h2>\n";
	
	$cacheInfo=getAllMySqlLastUpdate($appName);
	foreach ($cacheInfo as $tableCache) {
		$daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
	}
	
	$days=$daysSince["EmailSentSummary"];
	
	printf("<p>Email summary data was last updated within the last %sday%s. Please <a href=\"?op=updateEmailOpenData\">click here</a> to update this data now.</p>\n", $days > 1?$days." ":"", $days==1?"":"s");

	print "<div class='w3-responsive'>\n<table class='w3-table-all w3-card-4'>\n";
	print "<tr><th>Period<th>Sent<th>Opened<th>Clicked<th>Opted Out<th>Bounced<th>Complained</th></tr>\n";
	foreach(array(7, 30, 60, 90, 180) as $days) {
		$stats=getLatestOpenRates($appName, $days);
		printf("<tr><th align=right>Last %d Days<td align=right>%d<td align=right><span style='font-size:8px'>(%d)</span> %3.1f%%<td align=right><span style='font-size:8px'>(%d)</span> %3.1f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%</td></tr>\n", $days, $stats["Sent"], $stats["Opened"], $stats["Open%"], $stats["Clicked"], $stats["Click%"], $stats["OptedOut"], $stats["OptOut%"], $stats["Bounced"], $stats["Bounce%"], $stats["Complaints"], $stats["Complaint%"]);
	}
	print "</table>\n";
	
	print "<h2>Monthly Data</h2>\n";
	
	$statArray=getHistoricalOpenRates($appName);
	print "<div class='w3-responsive'>\n<table class='w3-table-all w3-card-4'>\n";
	print "<tr><th>Period<th>Sent<th>Opened<th>Clicked<th>Opted Out<th>Bounced<th>Complained</th></tr>\n";
	foreach ($statArray as $stats) {
		printf("<tr><th align=right>Month %4d-%02d<td align=right>%d<td align=right><span style='font-size:8px'>(%d)</span> %3.1f%%<td align=right><span style='font-size:8px'>(%d)</span> %3.1f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%</td></tr>\n", $stats["Y"], $stats["M"], $stats["Sent"], $stats["Opened"], $stats["Open%"], $stats["Clicked"], $stats["Click%"], $stats["OptedOut"], $stats["OptOut%"], $stats["Bounced"], $stats["Bounce%"], $stats["Complaints"], $stats["Complaint%"]);
	}
	print "</table>\n</div>\n";	
	
	printGoogleChartsJS();	

	?>
	<div class="jumbotron">
		<div class="container">
			<h2>The Good Stats - Numeric</h2>
		</div>
	</div>
	<div class="row"> 
		<div class="col-md-12">
			<div id="eng_good_num"></div>  
		</div>
	</div>
	<div class="jumbotron">
		<div class="container">
			<h2>The Good Stats - Percentage</h2>
		</div>
	</div>
	<div class="row"> 
		<div class="col-md-12">
			<div id="eng_good_pct"></div>  
		</div>
	</div>
	<div class="jumbotron">
		<div class="container">
			<h2>The Bad Stats - Numeric</h2>
		</div>
	</div>
	<div class="row"> 
		<div class="col-md-12">
			<div id="eng_bad_num"></div>  
		</div>
	</div>
	<div class="jumbotron">
		<div class="container">
			<h2>The Bad Stats - Percentage</h2>
		</div>
	</div>
	<div class="row"> 
		<div class="col-md-12">
			<div id="eng_bad_pct"></div>  
		</div>
	</div>

	<script>
	      function drawEngGoodChartNum() {
	        var data = google.visualization.arrayToDataTable([
	["Month","Sent", "Opened", "Clicked"]
<?php
		foreach ($statArray as $results) {
			printf(",[new Date(%d, %d),%d,%d,%d]\n", $results["Y"], $results["M"]-1, $results["Sent"], $results["Opened"], $results["Clicked"]);
		}	
?>				
	        ]);

	        var options = {
				textStyle: {
					fontName: 'Exo 2'
				},
	          title: 'Sent, Opened and Clicked (Month Commencing)',
	          curveType: 'none',
				pointShape: 'square',
				pointSize: 8,
  			  hAxis: {
  				  titleTextStyle: {
  					  fontName: 'Exo 2'
  				  }
  			  },
	          legend: { position: 'bottom' }
	        };

	        var chart = new google.visualization.LineChart(document.getElementById('eng_good_num'));

	        chart.draw(data, options);
	      }

	      function drawEngGoodChartPct() {
	        var data = google.visualization.arrayToDataTable([
	["Month", "Opened", "Clicked"]
<?php
		foreach ($statArray as $results) {
			if ($results["Sent"] > 0) {
				printf(",[new Date(%d, %d),%d,%d]\n", $results["Y"], $results["M"]-1, 100*$results["Opened"]/$results["Sent"], 100*$results["Clicked"]/$results["Sent"]);
			}
		}	
?>				
	        ]);

	        var options = {
				textStyle: {
					fontName: 'Exo 2'
				},
	          title: 'Percentage Opened and Clicked (Month Commencing)',
	          curveType: 'none',
				pointShape: 'square',
				pointSize: 8,
  			  hAxis: {
  				  titleTextStyle: {
  					  fontName: 'Exo 2'
  				  }
  			  },
	          legend: { position: 'bottom' }
	        };

	        var chart = new google.visualization.LineChart(document.getElementById('eng_good_pct'));

	        chart.draw(data, options);
	      }

	      function drawEngBadChartNum() {
	        var data = google.visualization.arrayToDataTable([
	["Month","OptedOut", "Bounced", "Complaints"]
<?php
		foreach ($statArray as $results) {
			printf(",[new Date(%d, %d),%d,%d,%d]\n", $results["Y"], $results["M"]-1, $results["OptedOut"], $results["Bounced"], $results["Complaints"]);
		}	
?>				
	        ]);

	        var options = {
				textStyle: {
					fontName: 'Exo 2'
				},
	          title: 'Opted Out, Bounced and Complained (Month Commencing)',
	          curveType: 'none',
				pointShape: 'square',
				pointSize: 8,
  			  hAxis: {
  				  titleTextStyle: {
  					  fontName: 'Exo 2'
  				  }
  			  },
	          legend: { position: 'bottom' }
	        };

	        var chart = new google.visualization.LineChart(document.getElementById('eng_bad_num'));

	        chart.draw(data, options);
	      }

	      function drawEngBadChartPct() {
	        var data = google.visualization.arrayToDataTable([
	["Month","OptedOut", "Bounced", "Complaints"]
<?php
		foreach ($statArray as $results) {
			if ($results["Sent"] > 0) {
				printf(",[new Date(%d, %d),%d,%d,%d]\n", $results["Y"], $results["M"]-1, 100*$results["OptedOut"]/$results["Sent"], 100*$results["Bounced"]/$results["Sent"], 100*$results["Complaints"]/$results["Sent"]);
			}
		}	
?>				
	        ]);

	        var options = {
				textStyle: {
					fontName: 'Exo 2'
				},
	          title: 'Percentage Opted Out, Bounced and Complained (Month Commencing)',
	          curveType: 'none',
				pointShape: 'square',
				pointSize: 8,
  			  hAxis: {
  				  titleTextStyle: {
  					  fontName: 'Exo 2'
  				  }
  			  },
	          legend: { position: 'bottom' }
	        };

	        var chart = new google.visualization.LineChart(document.getElementById('eng_bad_pct'));

	        chart.draw(data, options);
	      }
	  
		  drawEngGoodChartNum();	  
		  drawEngGoodChartPct();
  		  drawEngBadChartNum();
  		  drawEngBadChartPct();
	</script>
	<?php	    	
  print "</div>\n";
}

if ($op == "emailEngagementReport") {

    print '<div class="w3-container">';
	
	print "<h2>Email Engagement Report</h2>\n";	
	
	print "<div id='description'>\n";
	
	print "<p>This isn't a standard email open report!</p>\n";
	print "<p>This report shows you how many people (and the percentage) have opened <strong>something</strong> within the last X days. The open percentage will normally be quite a bit higher than the open rate you'll see in other email stats, because it's looking at the overall engagement in the time period shown. So as long as a contact has opened <strong>something</strong> in the last X days, they'll show as an open here.</p>";
	print "<p>Typically, anyone who hasn't opened or clicked anything in the last 90 days is considered to be disengaged. Best practice is to add those people to a \"last chance\" re-engagement campaign and, if they don't respond to that, stop mailing them.</p>\n";
	if ($platform == "ISFT") {
		print "<p>This report also shows you everyone who you've <strong>not</strong> sent anything to in the last X days. Infusionsoft considers anybody who's not been mailed within the last 4 months as \"cold\" and will probably throttle any mails sent to those cold contacts the next time you send them a broadcast.</p>\n";		
	} else {
		print "<p>This report also shows you everyone who you've <strong>not</strong> sent anything to in the last X days.</p>\n";
	}
	print "<p>Please <a href='#' onclick=\"document.getElementById('description').style.display='none'\">click here</a> to dismiss this text</p>\n";
	
	print "</div>\n";
	
	if (!doesTableExist($appName, "Contact") || !doesTableExist($appName,"EmailAddStatus")) {
		print "<h2>Data Needs to be Updated</h2>\n";
		print "<p>Email Power Tools needs to summarise the contact and engagement data.</p>\n";
		print "<p>Please <a href=\"?op=updateContactData\">click here</a> to do this now.</p>\n";
		print "</div>\n";
		print "</div>\n";
		print "</html>\n";
		exit;
	}
	
	print "<h2>Tabular Data</h2>\n";
	
	$cacheInfo=getAllMySqlLastUpdate($appName);
	foreach ($cacheInfo as $tableCache) {
		$daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
	}
	
	$days=max($daysSince["Contact"], $daysSince["EmailAddStatus"]);
	
	printf("<p>Email summary data was last updated within the last %sday%s. Please <a href=\"?op=updateContactData\">click here</a> to update this data now.</p>\n", $days > 1?$days." ":"", $days==1?"":"s");
	
	print "<div id='progress'>\n";
	
	print "<p>This may take a few moments to collate the data, please bear with me...</p>";

	printProgressBarContainer();
	
	print "</div>";

	$count=0;
	foreach (array(7, 30, 60, 90, 120, 180, 365) as $days) {
		$resultArray[$days]=getEngagementStats($appName, $days);
		$count++;
		printf('<script>setProgressBar(%d);</script>' . "\n", 100*$count/7);
	}

	print '<script>var eeDiv = document.getElementById("progress"); eeDiv.style.display = "none"; </script>';
	
	print "<div class='w3-responsive'>\n<table class='w3-table-all w3-card-4'>\n";
	print "<thead><tr class='w3-light-grey'><th>&nbsp;<th colspan=6>Number of contacts who...</th></tr>\n";
	print "<tr class='w3-light-grey'><th>&nbsp;<th class='w3-centered'>Were Sent Something<th class='w3-centered' colspan=2>Opened Something<th colspan=2>Clicked Something<th>Were Marketable but Not Sent Anything</th></tr>\n";
	print "</thead>\n";
	foreach ($resultArray as $days=>$results) {
		if ($results["Sent"] > 0) {
			printf("<tr class='w3-right-align'><td>%d days<td align=right>%d<td align=right>%d<td>%3.1f%%<td align=right>%d<td>%3.1f%%<td>%d</td></tr>\n", $days, $results["Sent"], $results["Opened"], 100*$results["Opened"]/$results["Sent"], $results["Clicked"], 100*$results["Clicked"]/$results["Sent"], $results["OptInNotSent"]);
		} else {
			printf("<tr class='w3-right-align'><td>%d days<td colspan=6>No emails sent</td></tr>\n", $days);
		}
		/** 
		ADD Mailed but NEVER opened to this
		**/
	}
	print "</table>\n";

	print "</div>\n";

	printGoogleChartsJS();	

	?>
		<div class="jumbotron">
			<div class="container">
				<h2>Chart: Numbers</h2>
			</div>
		</div>
			<div class="row"> 
				<div class="col-md-12">
					<div id="eng_numeric"></div>  
				</div>
			</div>
		<div class="jumbotron">
			<div class="container">
				<h2>Chart: Percentages</h2>
			</div>
		</div>
			<div class="row"> 
				<div class="col-md-12">
					<div id="eng_percent"></div>  
				</div>
			</div>

	<script>
	      function drawEngNumericChart() {
	        var data = google.visualization.arrayToDataTable([
	["Days","Sent", "Opened", "Clicked"]
<?php
		foreach ($resultArray as $days=>$results) {
			printf(",[%d,%d,%d,%d]\n", $days, $results["Sent"], $results["Opened"], $results["Clicked"]);
		}	
?>				
	        ]);

	        var options = {
				textStyle: {
					fontName: 'Exo 2'
				},
	          title: 'Number of Contacts Engaged: Mails Sent In The Last...',
	          curveType: 'none',
				pointShape: 'square',
				pointSize: 8,
  			  hAxis: {
				  ticks: [7, 30, 60, 90, 120, 180, 365],
  				  titleTextStyle: {
  					  fontName: 'Exo 2'
  				  }
  			  },
	          legend: { position: 'bottom' }
	        };

	        var chart = new google.visualization.LineChart(document.getElementById('eng_numeric'));

	        chart.draw(data, options);
	      }

	      function drawEngPercentChart() {
	        var data = google.visualization.arrayToDataTable([
	["Days", "Opened", "Clicked"]
<?php
		foreach ($resultArray as $days=>$results) {
			if ($results["Sent"] > 0) {
				printf(",[%d,%d,%d]\n", $days, 100*$results["Opened"]/$results["Sent"], 100*$results["Clicked"]/$results["Sent"]);
			}
		}	
?>				
	        ]);

	        var options = {
				textStyle: {
					fontName: 'Exo 2'
				},
	          title: 'Percentage of Contacts Engaged: Mails Sent In The Last...',
	          curveType: 'none',
				pointShape: 'square',
				pointSize: 8,
  			  hAxis: {
				  ticks: [7, 30, 60, 90, 120, 180, 365],
  				  titleTextStyle: {
  					  fontName: 'Exo 2'
  				  }
  			  },
	          legend: { position: 'bottom' }
	        };

	        var chart = new google.visualization.LineChart(document.getElementById('eng_percent'));

	        chart.draw(data, options);
	      }
	  
		  drawEngNumericChart();	  
		  drawEngPercentChart();
	</script>
	<?php
	print "</div>\n";
}

if ($op == "lostCustomersReport") {
	
    print '<div class="w3-container">';
	
	print "<h2>Lost Customers Report</h2>\n";	
		
	if (!doesTableExist($appName, "EmailAddStatus")) {
		print "<h2>Data Needs to be Updated</h2>\n";
		print "<p>Email Power Tools needs to summarise the Infusionsoft contact and invoice data.</p>\n";
		print "<p>Please <a href=\"?op=updateContactData\">click here</a> to do this now.</p>\n";
		print "</div>\n";
		print "</div>\n";
		print "</html>\n";
		exit;
	}
		
	$cacheInfo=getAllMySqlLastUpdate($appName);
	foreach ($cacheInfo as $tableCache) {
		$daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
	}
	
	$days=$daysSince["EmailAddStatus"];
	
	printf("<p>Email summary data was last updated within the last %sday%s. Please <a href=\"?op=updateContactData\">click here</a> to update this data now.</p>\n", $days > 1?$days." ":"", $days==1?"":"s");
	
	printf("<p>By default, this report shows you up to 100 people who have purchased something from you (value $50 or more) in the last 12 months who haven't engaged with your emails in the last 12 months. In many cases, this will be because their email address has never worked correctly, in other cases it might be because it has set to Hard Bounce in Infusionsoft - even though it might still be valid. We strongly recommend that you contact each of these people individually and correct their email address and opt it back in manually if needed, as these people generally still want to buy from you! Of course, if they've reported you for spam or opted out, they may no longer be interested.</p>\n");

	print "<div class='w3-responsive'>\n<table class='w3-table-all w3-card-4'>\n";
	print "<tr><th>Name<th>Email<th>Phone<th>Latest Order<th><span style='white-space: nowrap'>Total Value</span><th>Email Status<th>Last Opened Something</th></tr>\n";

	$stats=getLostCustomerData($appName);
	
	https://uir93022.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID=11139
	
	foreach($stats as $row) {
		printf("<tr><td><a href='https://%s.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID=%d' target='_new'>%s %s</a><td>%s<td>%s<td>%s<td style='text-align: right'>%s<td>%s<td>%s</td></tr>\n", $appName, $row["Id"], $row["FirstName"], $row["LastName"], $row["Email"], $row["Phone1"], $row["LatestOrder"], number_format($row["TotalValue"],2), $optTypeTranslate[$row["Type"]], $row["LastOpenDate"]?$row["LastOpenDate"]:"Never");
	}
	print "</table>\n";
	print "</div>\n";
}

if(isset($_POST["submitLostCLV"])) { 

	$firstClick = $_POST["firstClick"];
	$lastClick = $_POST["lastClick"];
	$earliestOrder = $_POST["earliestOrder"];
	$latestOrder = $_POST["latestOrder"];

	$firstClickValue = $_POST["firstClickValue"];
	$earliestClickValue = $_POST["earliestClickValue"];

	$firstClick = !(empty($firstClick))?$firstClick:'1980-01-01';
	$lastClick = !(empty($lastClick))?$lastClick:'2080-12-31';
	$earliestOrder = !(empty($earliestOrder))?$earliestOrder:'1980-01-01';
	$latestOrder = !(empty($latestOrder))?$latestOrder:'2080-12-31';

	//reset to default is  firstClickValue and  earliestClickValue == 0
	$firstClick=$firstClickValue=="0"?'1980-01-01':$firstClick;
	$lastClick=$firstClickValue=="0"?'2080-12-31':$lastClick;
	$earliestOrder=$earliestClickValue=="0"?'1980-01-01':$earliestOrder;
	$latestOrder=$earliestClickValue=="0"?'2080-12-31':$latestOrder;	

	print "<h2>Lost Customer Lifetime Value Report</h2>\n";
	print "<div class='w3-responsive'>\n<table class='w3-table-all w3-card-4'>\n";
	print "<tr><th>Name</th><th>Email</th><th>Phone</th><th>Latest Order</th><th><span style='white-space: nowrap'>Total Purchased</span></th><th>Email Status</th><th>Last Click Date</th></tr>\n";

	$stats=getLostCustomerValueReportData($appName, $firstClick, $lastClick, $earliestOrder, $latestOrder);
	foreach($stats as $row) {
            $TotalPurchased = 0;
            $TotalPurchased = number_format($row["TotalPurchased"],2);
		printf("<tr><td><a href='https://%s.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID=%d' target='_new'>%s %s</a></td><td>%s</td><td>%s</td><td>%s</td><td style='text-align: right'>%s</td><td>%s</td><td>%s</td></tr>\n",$appName,$row["Id"],$row["FirstName"], $row["LastName"],$row["Email"],$row["Phone1"],$row["LatestOrder"],$TotalPurchased,$optTypeTranslate[$row["Type"]], !empty($row["LastClickDate"])?$row["LastClickDate"]:"Never");
	}
	print "</table>\n";
	print "</div>\n";
	
}

if ($op == "lostCLV") {
	
    print '<div class="w3-container">';
	
	print "<h2>Lost Customer Lifetime Value Report</h2>\n";	

		/*
		print "<form name=\"frmDateRange\" method=\"POST\">\n";
			print "<div><table>\n";
					print "<tr> <td> <a style=\"font-weight:bold\">Earliest unsubscribe date</a>	</td> </tr>\n";
					print "<tr> <td> <input type=\"date\" name=\"firstClick\" disabled=\"disabled\"><br/> </td>\n";			
					print "</tr> <tr>  <td> <a style=\"font-weight:bold\">Latest  unsubscribe date</a> </td> </tr>\n";
					print "<tr> <td> <input type=\"date\" name=\"lastClick\" disabled=\"disabled\"><br/> </td> </tr>\n";	
					print "<tr> <td> &nbsp; </td> </tr>\n";
					print "<tr> <td> <a style=\"font-weight:bold\">Specific date range for customer value</a> </td> </tr>\n";
					print "<tr> <td> <input type=\"date\" name=\"earliestOrder\" disabled=\"disabled\"><input type=\"date\" name=\"latestOrder\" disabled=\"disabled\"> </td> </tr>\n";	
					print "<tr> <td><br/> \n";				  
					print "</td> </tr>\n";					 
					print "<tr> <td>\n";
			
					print '<input style="font-weight:bold" type="radio" id="radTest1" name="showdData" value="all" checked="true" onclick="testFunc(1);"> Show All Data &nbsp;&nbsp;&nbsp;';
					print '<input style="font-weight:bold" type="radio" id="radTest2" name="showdData" value="dates" onclick="testFunc(2);"> Show Data By Dates<br>';
					print "<input class=\"w3-bar-item w3-button w3-padding w3-blue\" type=\"submit\" name=\"submitLostCLV\" value=\"Show Lost Customer Lifetime Value\">\n";		 
					print "</td> </tr></td></tr>\n";							
				print "</table></div>\n";
			print "<p id=\"outputTest\"></p></form>\n";	
			*/
	echo '

<form name="frmDateRange" method="POST">
    <div><table width="10%">
            <tr> <td colspan="2"> <a style="font-weight:bold">Unsubscribe Date Range</a>	</td> </tr>
            <tr> 
                <td colspan=2> 
                    <input style="font-weight:bold" type="radio" name="showdata" checked="true" onclick="subscrideDateController(1);"> All Dates &nbsp;                
                    <br/>
                    <input style="font-weight:bold" type="radio" name="showdata" onclick="subscrideDateController(2);"> Specific Date Range<br>
                </td>
            </tr>            
            <tr id="firstDate"> 
                <td> 
                    <a style="display:none"  id="earliestDateLabel">Earliest Date</a><br/> 
                    <input type="hidden" name="firstClick"><br/>
                    <input type="hidden" name="firstClickValue"  id="firstClickValue" value="0">
                </td>			
                <td> 
                    <a style="display:none"  id="latestDateLabel">Latest Date</a><br/> 
                    <input type="hidden" name="lastClick">
                </td>	
            </tr>
            <tr> <td colspan="2"> <a style="font-weight:bold">Date Range for Customer Value</a>	</td> </tr>  
            <tr> 
                <td colspan="2"> 
                   <input style="font-weight:bold" type="radio" name="showdata2" checked="true" onclick="subscrideDateController(3);"> All Dates &nbsp;                
                   <br/>
                   <input style="font-weight:bold" type="radio" name="showdata2" onclick="subscrideDateController(4);"> Specific Date Range<br>
                </td>
            </tr>                     	
            <tr id="secondDate"> 
                <td>                   
                    <a style="display:none" id="earliestOrderLabel">Earliest Order</a><br/> 
                    <input type="hidden" name="earliestOrder"> 
                    <input type="hidden" name="earliestClickValue" id="earliestClickValue" value="0">                   
                </td>			
                <td>
                    <a style="display:none" id="latestOrderLabel">Latest Order</a><br/> 
                    <input type="hidden" name="latestOrder">
                </td>	
            </tr>                     				 
            <tr> 
                <td colspan="2">
                    <br/>
                    <input class="w3-bar-item w3-button w3-padding w3-blue" type="submit" name="submitLostCLV" value="Show Lost Customer Lifetime Value">		 
                </td>
            </tr>							
        </table>
    </div>
</form>	
	';			

	if (!doesTableExist($appName, "EmailAddStatus")) {
		print "<h2>Data Needs to be Updated</h2>\n";
		print "<p>Email Power Tools needs to summarise the Infusionsoft contact and invoice data.</p>\n";
		print "<p>Please <a href=\"?op=updateContactData\">click here</a> to do this now.</p>\n";
		print "</div>\n";
		print "</div>\n";
		print "</html>\n";
		exit;
	}
		
	$cacheInfo=getAllMySqlLastUpdate($appName);
	foreach ($cacheInfo as $tableCache) {
		$daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
	}
	
	$days=$daysSince["EmailAddStatus"];
	
	printf("<p>Email summary data was last updated within the last %sday%s. Please <a href=\"?op=updateContactData\">click here</a> to update this data now.</p>\n", $days > 1?$days." ":"", $days==1?"":"s");
	
	printf("<p>By default, this report shows you up to 100 people who have purchased something from you (value $50 or more) in the last 12 months who haven't engaged with your emails in the last 12 months. In many cases, this will be because their email address has never worked correctly, in other cases it might be because it has set to Hard Bounce in Infusionsoft - even though it might still be valid. We strongly recommend that you contact each of these people individually and correct their email address and opt it back in manually if needed, as these people generally still want to buy from you! Of course, if they've reported you for spam or opted out, they may no longer be interested.</p>\n");
	
}

if ($op == "OLDemailEngagementReport") {
	$thisCumulativePage=0;		
	
	$esPage=0;
	$count=0;
	$table="EmailAddStatus";
	$queryData=array('Id' => "%");
	$totalAddStatus=$infusionsoft->data()->count($table, $queryData);
	$pagesAddStatus=ceil($totalAddStatus/1000);
	$table="Contact";
	$totalContacts=$infusionsoft->data()->count($table, $queryData);
	$pagesContacts=ceil($totalContacts/1000);
	
	$totalPages=$pagesContacts+$pagesAddStatus;
	
	$startTime=time();
	
	$table="EmailAddStatus";
	$queryData=array('Id' => "%");
	$orderBy="Id";
	$ascending=true;
	
	printProgressBarContainer();
	
	print '<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading Email Status Table:";</script>';		
		
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		$sentCount[$days]=0;
		$openCount[$days]=0;
		$clickCount[$days]=0;
	}
	
	do {
		$results=$infusionsoft->data()->query($table, 1000, $esPage, $queryData, array("Id","Email","DateCreated","LastOpenDate","LastClickDate","LastSentDate","Type"), $orderBy, $ascending);
	
		foreach($results as $result) {
			$count++;
			
			$now=time();
			
			// print_r($result);
			
			if (isset($result["Email"])) {
				$emailAddr=strtolower($result["Email"]);
			} else {
				$emailAddr="--";
			}
			
			$optStatus[$emailAddr]=$result["Type"];
			
			if (isset($result["LastSentDate"])) {
				$lastSentDate=$result["LastSentDate"]->format('Y-m-d');
				$lastSentTimestamp=$result["LastSentDate"]->getTimestamp();
				$daysSinceSent=($now-$lastSentTimestamp)/86400;
				if ($lastSentTimestamp <= 0) {
					$lastSentDate="NEVER";
					$lastSentTimestamp=0;
					$daysSinceSent=9999;
				}
			} else {
				$lastSentDate="NEVER";
				$lastSentTimestamp=0;
				$daysSinceSent=9999;
			}
			
			if (isset($result["LastOpenDate"])) {
				$lastOpenDate=$result["LastOpenDate"]->format('Y-m-d');
				$lastOpenTimestamp=$result["LastOpenDate"]->getTimestamp();
				$daysSinceOpen=($now-$lastOpenTimestamp)/86400;
			} else {
				$lastOpenDate="NEVER";
				$lastOpenTimestamp=0;
				$daysSinceOpen=9999;
			}
			
			if (isset($result["LastClickDate"])) {
				$lastClickDate=$result["LastClickDate"]->format('Y-m-d');
				$lastClickTimestamp=$result["LastClickDate"]->getTimestamp();
				$daysSinceClick=($now-$lastClickTimestamp)/86400;
			} else {
				$lastClickDate="NEVER";
				$lastClickTimestamp=0;
				$daysSinceClick=9999999999;
				
			}
			
			$lastSentDates["$emailAddr"]=$lastSentDate;
			$lastOpenDates["$emailAddr"]=$lastOpenDate;
			$lastClickDates["$emailAddr"]=$lastClickDate;
			$daysSinceSentArray["$emailAddr"]=$daysSinceSent;
			$daysSinceOpenArray["$emailAddr"]=$daysSinceOpen;
			$daysSinceClickArray["$emailAddr"]=$daysSinceClick;
			
			foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
				if ($daysSinceSent <= $days) {
					$sentCount[$days]++;
				}
				if ($daysSinceOpen <= $days) {
					$openCount[$days]++;
				}
				if ($daysSinceClick <= $days) {
					$clickCount[$days]++;
				}
			}
			
			// printf("Email %-40.40s - Sent %8s - Open %8s - Click %8s\n", $emailAddr, $lastSentDate, $lastOpenDate, $lastClickDate);
			
		}
		$esPage++;
		$thisCumulativePage++;
		if ($thisCumulativePage > $pagesAddStatus) {
			$thisCumulativePage=$pagesAddStatus;
		}

		printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $esPage, $count, $totalAddStatus);
		printf('<script>setProgressBar(%d);</script>' . "\n", 100*$thisCumulativePage/$totalPages);

	} while(1 && (count($results) > 0));
	
/**	
	print "Summary\n<table border><tr><th>Days<th>Sent<th>Opened<th>Clicked</th></tr>\n";
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		printf("<tr><td>%d<td>%d<td>%d<td>%d</td></tr>\n", $days, $sentCount[$days], $openCount[$days], $clickCount[$days]);
	}
	print "</table>\n";
**/
	
		
	// $startTime=time();
	
	$lastSentDates["--"]="";
	$lastOpenDates["--"]="";
	$lastClickDates["--"]="";
	$daysSinceSentArray["--"]=9999;
	$daysSinceOpenArray["--"]=9999;
	$daysSinceClickArray["--"]=9999;
	
	$optStatus["--"]="---";
		
	$table="Contact";
	$queryData=array('Id' => "%");
	$orderBy="Id";
	$ascending=true;
		
	print '<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading Contact Table:";</script>';		
		
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		$sentCount[$days]=0;
		$openCount[$days]=0;
		$clickCount[$days]=0;
	}
	
	$totalProcessed=0;
	$esPage=0;
	$offset=0;
	do {
		
/**		
		$results=$infusionsoft->contacts()->where(array("offset"=>$offset,"limit"=>1000))->get();
		$count=$results->count();
		printf("Offset: %d - Count of results: %d\n", $offset, $count);
	
		//$details=$results->toArray();
		//print_r($details);
		for ($i=0; $i < $count; $i++) {
			$result=$results->offsetGet($i);
			//print_r($result);

			$emailAddresses=$result->email_addresses;
			$email1="--";
			$email2="--";
			$email3="--";
			foreach ($emailAddresses as $email) {
				if ($email["field"] == "EMAIL1") {
					$email1=strtolower($email["email"]);
				} else if ($email["field"] == "EMAIL2") {
					$email2=strtolower($email["email"]);
					if ($email2 == "nonmarketable@wedeliver.email") {
						$email2="--";
					}
				} else if ($email["field"] == "EMAIL3") {
					$email3=strtolower($email["email"]);
					if ($email3 == "nonmarketable@wedeliver.email") {
						$email3="--";
					}
				}
			}

			$emailStatus=$result->email_status;
		
			$contactId=$result->id;
**/
		
		$results=$infusionsoft->data()->query($table, 1000, $esPage, $queryData, array("Email","EmailAddress2","EmailAddress3","Id"), $orderBy, $ascending);
		$count=count($results);
		//printf("Offset: %d - Count of results: %d\n", $offset, $count);
		foreach($results as $result) {
			$totalProcessed++;
			
			$now=time();
			
			// print_r($result);
			
			$id=$result["Id"];

		
			if (isset($result["Email"])) {
				$email1=strtolower($result["Email"]);
			} else {
				$email1="--";
			}
			if (isset($result["EmailAddress2"])) {
				$email2=strtolower($result["EmailAddress2"]);
			} else {
				$email2="--";
			}
			if (isset($result["EmailAddress3"])) {
				$email3=strtolower($result["EmailAddress3"]);
			} else {
				$email3="--";
			}		
				
			if (isset($optStatus[$email1])) {		
				$opt1=$optStatus[$email1];
			} else {
				$opt1="--";
				$email1="--";
			}
			if (isset($optStatus[$email2])) {		
				$opt2=$optStatus[$email2];
			} else {
				$opt2="--";
				$email2="--";
			}
			if (isset($optStatus[$email2])) {		
				$opt2=$optStatus[$email2];
			} else {
				$opt2="--";
				$email2="--";
			}
		
			$daysSinceSent=$daysSinceSentArray[$email1];
			$lastSent=$lastSentDates[$email1];
			if ($daysSinceSent > $daysSinceSentArray[$email2]) {
				$daysSinceSent = $daysSinceSentArray[$email2];
				$lastSent = $lastSentDates[$email2];
			}
			if ($daysSinceSent > $daysSinceSentArray[$email3]) {
				$daysSinceSent = $daysSinceSentArray[$email3];
				$lastSent = $lastSentDates[$email3];
			}
		
			$daysSinceOpen=$daysSinceOpenArray[$email1];
			$lastOpen=$lastOpenDates[$email1];
			if ($daysSinceOpen > $daysSinceOpenArray[$email2]) {
				$daysSinceOpen = $daysSinceOpenArray[$email2];
				$lastOpen = $lastOpenDates[$email2];
			}
			if ($daysSinceOpen > $daysSinceOpenArray[$email3]) {
				$daysSinceOpen = $daysSinceOpenArray[$email3];
				$lastOpen = $lastOpenDates[$email3];
			}
		
			$daysSinceClick=$daysSinceClickArray[$email1];
			$lastClick=$lastClickDates[$email1];
			if ($daysSinceClick > $daysSinceClickArray[$email2]) {
				$daysSinceClick = $daysSinceClickArray[$email2];
				$lastClick = $lastClickDates[$email2];
			}
			if ($daysSinceClick > $daysSinceClickArray[$email3]) {
				$daysSinceClick = $daysSinceClickArray[$email3];
				$lastClick = $lastClickDates[$email3];
			}
		
			foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
				if ($daysSinceSent <= $days) {
					$sentCount[$days]++;
				}
				if ($daysSinceOpen <= $days) {
					$openCount[$days]++;
				}
				if ($daysSinceClick <= $days) {
					$clickCount[$days]++;
				}
			}		
			
			//printf("ContactID: %d - Email1: %s - Email2: %s - Email3: %s - Status: %s\n", $contactId, $email1, $email2, $email3, $emailStatus);

		}
		$offset += $count;
		$esPage++;
		$thisCumulativePage++;
		if ($thisCumulativePage > $totalPages) {
			$thisCumulativePage=$totalPages;
		}
		printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $esPage, $totalProcessed, $totalContacts);
		printf('<script>setProgressBar(%d);</script>' . "\n", 100*$thisCumulativePage/$totalPages);		
	} while (($count > 0));
					
	// ====================
	
	print '<script>var eeDiv = document.getElementById("eeProgressContainer"); eeDiv.style.display = "none"; </script>';
	
	print "<h4>Summary</h4>\n<table border><tr><th>Days<th>Sent<th>Opened<th>Clicked</th></tr>\n";
	foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
		printf("<tr><td>%d<td>%d<td>%d<td>%d</td></tr>\n", $days, $sentCount[$days], $openCount[$days], $clickCount[$days]);
	}
	print "</table>\n";
	
	printf("<p>Execution time: %d seconds</p><hr>\n", time()-$startTime);
}

if($op == "kleanOnDemandScrub") {
	$baseUrl = "https://$_SERVER[HTTP_HOST]/";

	echo "<div class='w3-container kleanOnDemandScrub' data-baseurl='".$baseUrl."' data-userid='".$userId."'>
			<h2>Klean13 On-Demand Scrub</h2>
			<div class='onDemandScrub'>
				<div>
					<input type='checkbox' class='checkEverything' id='checkEverything'/>
					<label for='checkEverything'>Select this checkbox to scrub ALL contacts in your database (use with caution)</label>
				</div>
				<div class='tagGroup'>
					<label for='tagCategory'>Category</label>
					<select id='tagCategory' class='tagCategory'>
						<option>Loading...</option>
					</select>
				</div>
				<button class='scrubButton w3-blue' disabled>Start Scrub</button>

				<p class='cbMessage'></p>
				<label class='outputLabel'>Result:</label><br>
				<div class='printOutput'></div>
				<div class='autoUpdateWrap'>
					<input type='checkbox' id='autoUpdate' class='autoUpdate' checked>
					<label for='autoUpdate'>automatically update status of list scrub</label>
				</div>
			</div>

			<div class='modal fade' id='myModal' role='dialog' style='display none;'>
			    <div class='modal-dialog'>
			      	<!-- Modal content-->
			      	<div class='modal-content'>
			        	<div class='modal-header'>
			          		<h4 class='modal-title'>Klean13 On-Demand Scrub</h4>
			        	</div>
			        	<div class='modal-body'>
			          		<p>This will use XXX credits from your Klean13 account - click <strong>YES</strong> to continue or <strong>NO</strong> to cancel</p>

			        	</div>
			        	<div class='modal-footer'>
			        		<button type='button' class='btn btn-primary startScrub'>Yes</button>
			          		<button type='button' class='btn btn-default' data-dismiss='modal'>No</button>
			        	</div>
			      </div>
			    </div>
	  		</div>

	  		<p class='appName' style='display: none'>".$userDetails["appName"]."</p>
	  		<p class='authhash' style='display: none'>".$userDetails["authhash"]."</p>
		</div>
	";
?>

<link rel="stylesheet" type="text/css" href="https://wdem.wedeliver.email/dominick/modal.css">
<style type="text/css">
	.onDemandScrub label {
		display: inline-block;
		text-align: right;
		padding-right: 10px;
	}

	.onDemandScrub input, 
	.onDemandScrub select {
		margin-top: 10px;
		width: 15%;
		padding: 5px;
		margin-left: 20px;
	}

	.checkEverything {
		margin-left: 96px !important;
		margin-right: 5px;
	}

	.scrubButton {
		margin: 15px 0 0 94px;
	    border: 0;
	    padding: 5px 15px;
	    cursor: pointer;
	}

	.checkEverything {
		width: auto !important;
		margin-top: 30px !important;
	}

	.modal-dialog {
		width: 400px !important;
	}
 
	.scrubButton:disabled {
		background: #94d0ff !important;
	}

	.printOutput {
		width: 930px;
		height: 309px;
		margin-left: 10px;
		background: #fff;
		overflow-y: auto; 
		padding: 21px;
		border: 1px solid #ded6d6;
	}

	.outputLabel {
		text-align: left !important;
	    margin-left: 12px;
	    margin-bottom: 7px;
	}

	.cbMessage {
		margin-left: 11px;
	}

	.autoUpdateWrap .autoUpdate {
	    width: auto;
	    margin: 10px 5px 40px 10px;
	}

	.autoUpdateWrap label {
		width: inherit;
	}
</style>

<script src='https://code.jquery.com/jquery-2.2.4.min.js'></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script type="text/javascript">
	$(function() {
		var baseUrl = $(".kleanOnDemandScrub").attr("data-baseurl");
		var userId = $(".kleanOnDemandScrub").attr("data-userid");
		
		$(".scrubButton").click(function() {
			$("#myModal").modal('show'); 
		});

		$(".startScrub").click(function() {
			var appName = $(".appName").text();
			var authhash = $(".authhash").text();
			var tag = $(".tagCategory").val();

			if($(".checkEverything").is(":checked")) {
				tag = 0;
			}
			
			$("#myModal").modal("hide");
			$.ajax({
				type: 'GET',
				url: 'https://wdem.wedeliver.email/start_update.php?app=uir93022&auth=8a9ec2&tag='+tag+'&mode-full',
				success: function(data) {
					showUpdate();
					$(".scrubButton").attr('disabled', true);
				}
			});
		});

		localStorage.setItem("autoUpdateScrub", 1);
		showUpdate();
		function showUpdate() {
			console.log('update func')
			$.ajax({
				type: 'GET',
				url: 'https://wdem.wedeliver.email/show_update.php?app=uir93022&auth=8a9ec2',
				data: {},
				success: function(data) {
					setTimeout(function() {
					$(".cbMessage").text('');
					}, 4000);
					$(".printOutput").html(data);
					$(".scrubButton").attr('disabled', false);

					if(localStorage.getItem("autoUpdateScrub")) {
						setTimeout(showUpdate, 1000);
					}
				}
			})
		}

		$(".autoUpdate").click(function() {
			console.log('localStorage', localStorage.getItem("autoUpdateScrub"));
			if($(this).is(":checked")) {
				localStorage.setItem("autoUpdateScrub", 1);
				showUpdate();
			}else {
				localStorage.removeItem("autoUpdateScrub");
			}
		})

		$(".tagCategory").change(function() {
			$(".scrubButton").attr('disabled', false);
		});
		
		$(".checkEverything").click(function() {
			if($(this).is(":checked")) {
				$(".tagCategory").attr('disabled', true).attr('title', 'please uncheck "Everything" option to select tag category');
				getTagList();
				$(".scrubButton").attr('disabled', false);
			}else {
				$(".tagCategory").attr('disabled', false).removeAttr('title');

				if($(".tagCategory").val() === null) {
					$(".scrubButton").attr('disabled', true);
				}
			}
		})

		getTagList();
		function getTagList() {
			console.log('baseUrl', baseUrl)
			$.ajax({
				type: 'POST',
				url: baseUrl+'ept.php?op=ajaxcall',
				data: {
					action: 'getTagList',
					userId: userId
				},
				success: function(data) {
					var data = JSON.parse(data);
					var data = data.result;
					var html = '<option value="0" disabled selected>Please select a category</option>';
					for(var x = 0; x < data.length; x++) {
						html += '<option value="'+data[x].Id+'">'+data[x].CategoryName+': '+data[x].GroupName+'</option>';
					}

					$(".tagCategory").html(html);
				}
			});
		}
	})
</script>
<?php
	}
?>

<?php 
	if($op == "ajaxcall") {

		if(isset($_POST['action'])) {
			if($_POST['action'] == 'getTagList') {
				$userId 	= $_POST['userId'];
				$orderBy 	= "Id";
				$ascending 	= true;
				$page 		= 0;
				$table 		= "ContactGroup";
				$queryData 	= array('Id' => '%'); 

				$listArr = [];
				$category = $infusionsoft->data()->query('ContactGroupCategory', 1000, 0, $queryData, ["Id", "CategoryName"], $orderBy, $ascending);
				$tags = $infusionsoft->data()->query($table, 1000, $page, $queryData, ["Id", "GroupName", "GroupDescription", "GroupCategoryId"], $orderBy, $ascending);

				for($x = 0; $x < count($category); $x++) {
					for($y = 0; $y < count($tags); $y++) {

						if($tags[$y]['GroupCategoryId'] == $category[$x]['Id']) {
							$listItem = new StdClass();
							$listItem->GroupCategoryId 	= $tags[$y]['GroupCategoryId'];
							$listItem->GroupName 		= $tags[$y]['GroupName'];
							$listItem->Id 				= $tags[$y]['Id'];

							if(count($category)) {
								$listItem->CategoryName = $category[$x]['CategoryName'];
							}else {
								$listItem->CategoryName = 'Null';
							}

							array_push($listArr, $listItem);
						}	
					}
				}

				$data = array(
					'category' => $category,
					'tags' => $tags,
					'result' => $listArr,
					// 'result' => $listArr,
					'success' => 2
				);

				echo json_encode($data);

				die();
			}

			##### WEDELIVER AJAXCALL #####
			if($_POST['action'] == 'weDeliverSetupRESThook') {
				$appName 	= $_POST['appName'];
				$authhash 	= $_POST['authhash'];
				$userId 	= $_POST['userId'];

				if (isset($_SESSION['token'])) {
					$infusionsoft->setToken(unserialize($_SESSION['token']));
				}
		
				##### IF WE ARE RETURNING FROM INFUSIONSOFT WE NEED TO EXCHANGE THE CODE FOR AN ACCESS TOKEN #####
				if (isset($_GET['code']) and !$infusionsoft->getToken()) {
					$infusionsoft->requestAccessToken($_GET['code']);
					$_SESSION['token'] = serialize($infusionsoft->getToken());
				}
		
				function resthookManager($infusionsoft, $appName, $authhash, $userId, $action) {
					$restHookUrl = sprintf("http://wdem.wedeliver.email/restHookSetupEmail.php?appName=%s&authHash=%s&userId=%d", $appName, $authhash, $userId);
					$resthooks = $infusionsoft->resthooks();
		
					##### CREATE A NEW RESTHOOK - CONTACT ADD #####
					$resthook = $resthooks->create([
						'eventKey' => $action,
						'hookUrl' => $restHookUrl
					]);

					$resthook = $resthooks->find($resthook->id)->verify();

					return $resthook;
				}
		
				if ($infusionsoft->getToken()) {
					try {
						$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId, 'contact.add');
						try {
							$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId, 'contact.edit');
						} catch (\Infusionsoft\TokenExpiredException $e) {
		
							##### IF THE REQUEST FAILS DUE TO AN EXPIRED ACCESS TOKEN, WE CAN REFRESH THE TOKEN AND THEN DO THE REQUEST AGAIN #####
							$infusionsoft->refreshAccessToken();
			
							##### SAVE THE SERIALIZED TOKEN TO THE CURRENT SESSION FOR SUBSEQUENT REQUESTS #####
							$_SESSION['token'] = serialize($infusionsoft->getToken());
							$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId, 'contact.edit');
						}
					} catch (\Infusionsoft\TokenExpiredException $e) {
		
						##### IF THE REQUEST FAILS DUE TO AN EXPIRED ACCESS TOKEN, WE CAN REFRESH THE TOKEN AND THEN DO THE REQUEST AGAIN #####
						$infusionsoft->refreshAccessToken();
		
						##### SAVE THE SERIALIZED TOKEN TO THE CURRENT SESSION FOR SUBSEQUENT REQUESTS #####
						$_SESSION['token'] = serialize($infusionsoft->getToken());
						$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId, 'contact.add');
					}
		
					$data = array(
						'success' => 1
					);
				}
				else {
					echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
		
					$data = array(
						'success' => 0
					);
				}
		
				echo json_encode($data);
		
				die();
			}

			##### DELETE RESTHOOK #####
			if($_POST['action'] == 'weDeliverDeleteRestHook') {
				$userId 	= $_POST['userId'];
				$results 	= $infusionsoft->resthooks()->all();
				$count 		= $results->count();

				if($count > 0) {
					$resultArr = $results->toArray();

					foreach($resultArr as $row) {

						$urlUserId = explode("userId=",$row->hookUrl)[1];
						$urlArr = explode("restHookSetupEmail",$row->hookUrl);
						##### CHECK RESTHOOK USERID #####
						if($urlUserId == $userId) {
							##### DELETE RESTHOOK BY USERID #####
							if(count(array_filter($urlArr)) > 1) {
								$infusionsoft->resthooks()->find($row->key)->delete();
							}
						}
					}
				}

				$data = array(
					'success' => 1,
					'result' => $results->toArray()
				);

				echo json_encode($data);

				die();
			}

			##### SWITCH STATE #####
			if($_POST['action'] == 'weDeliverSwitchState') {
				$userId 	= $_POST['userId'];
				$results 	= $infusionsoft->resthooks()->all();
				$count 		= $results->count();
				$isEnable 	= 0;

				if($count > 0) {
					$resultArr = $results->toArray();

					foreach($resultArr as $row) {

						$urlUserId = explode("userId=",$row->hookUrl)[1];
						$urlArr = explode("restHookSetupEmail",$row->hookUrl);
						##### CHECK RESTHOOK USERID #####
						if($urlUserId == $userId) {
							##### DELETE RESTHOOK BY USERID #####
							if(count(array_filter($urlArr)) > 1) {
								$isEnable = 1;
							}
						}
					}
				}

				$data = array(
					'success' => 1,
					'isEnable' => $isEnable
				);

				echo json_encode($data);

				die();
			}
		}
	}
?>
<?php

##### KLEAN13 API PAGE #####
if ($op == "kleanApiConfig") {
	$tableName = 'tblEptKlean13';
	
	##### MAKE SURE THAT tblEptKlean13 API TABLE EXIST #####
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` bigInt(20) NOT NULL AUTO_INCREMENT,
		`UserId` varchar(255) NOT NULL,
		`apiKey` varchar(255) NOT NULL,
		`tagCategoryId` int(100) NOT NULL,
		`tagPrefix` varchar(255) NOT NULL,
		`lookUpCount` int(100) NOT NULL,
		`lookUpMaxLimit` int(100) NOT NULL,
		`DateCreated` DateTime,
		`LastUpdated` DateTime,
		`isActiveISFT` int(1) NOT NULL DEFAULT 0,
		`isActiveAC` int(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", $tableName, "Not Set");

	##### MAKE SURE THAT tblEptKlean13Email API TABLE EXIST #####
	DB::query("CREATE TABLE IF NOT EXISTS tblEptKlean13Email (
		`Id` bigInt(20) NOT NULL AUTO_INCREMENT,
		`email` varchar(255) NOT NULL,
		`emailStatus` varchar(255) NOT NULL,
		`DateCreated` DateTime,
		`LastLookUp` DateTime,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", "Not Set");

	##### IF ADRIAN ACCOUNT #####
	if (isset($_SESSION['uid'])) {
		if($_SESSION['uid'] == 233) {
			// Get the user details of logged in user
			$userDetails = DB::queryFirstRow("select sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and sessionId=%s", $sessionId);
		}else {
			$userDetails = DB::queryFirstRow("select sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and u.userId=%s", $_SESSION['uid']);
		}
	}else {
		$userDetails = DB::queryFirstRow("select sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and u.userId=%s", $sessionId);
	}

	$userId = $userDetails['userId'];
	
	// Query tblEptKlean13
	$tblEptKlean13 = DB::queryFirstRow("SELECT apiKey FROM $tableName WHERE UserId=%s", $userId);

	// Get Klean13 API Key
	$kleanApiKey = $tblEptKlean13['apiKey'];

	// Get Full URL
	$baseUrl = "https://$_SERVER[HTTP_HOST]/";

	$sessionKlean = $_SESSION;
	echo "
	<div class='w3-container klean13ApiConfig' data-baseurl='".$baseUrl."'>
			<h2>Klean13 API Configuration</h2>
			<div class='pageLoader'><span class='loader'></span> Loading, please wait...</div>
			<div class='apiConfig'>
				<p>
					<label class='switch' title='Click to switch off'>
				  		<input type='checkbox' class='toggleConfig'>
				  		<span class='slider round'></span>
					</label>
					<span class='switchLabel'>Enable real-time scrubbing<span class='switchStatus' style='display: none'>OFF</span></span>
				</p>
				<div class='apiForm'
					<label>API Key</label>
					<input type='hidden' value='".$userId."' class='userId'>
					<input type='hidden' class='tableName' value='".$tableName."'>
					<input type='text' class='txtApiKey' placeholder='Enter API Key' value='".$kleanApiKey."'>					
				</div>

				<div class='addTagWrap'>
					<h4>Add Tag</h4>
					<div class='tagGroup'>
						<label for='tagPrefix'>Tag Prefix</label>
						<input type='text' id='tagPrefix' class='tagPrefix' value='K13 - '>
					</div>
					<div class='tagGroup hiddenAC'>
						<label for='tagCategory'>Category</label>
						<select id='tagCategory' class='tagCategory'>
							<option>Loading...</option>
						</select>
					</div>
					<div class='tagGroup hiddenAC'>
						<label class='otherTagLabel'>Other Tag</label>
						<input type='text' class='otherTagCat'>
						<input type='hidden' class='otherTagId'>
						<label for='otherTag' class='left'>(Other)</label>
					</div>
					<button class='buttonSaveApiKey'>Save settings</button>
				</div>

				<p class='platform' style='display: none;'>".$platform."</p>

				<div class='loaderWrap'>

				</div>

				<div class='resMsgWrap'>

				</div>
				
			</div>
			<p class='appName' style='display: none'>".$userDetails["appName"]."</p>
			<p class='authhash' style='display: none'>".$userDetails["authhash"]."</p>
		  </div>
	"; 

?>

<style type="text/css">
	.addTagWrap {
		margin-top: 20px;
	}

	.addTagWrap label {
		width: 92px;
		display: inline-block;
		text-align: right;
		padding-right: 10px;
	}

	.addTagWrap input, 
	.addTagWrap select {
		width: 15%;
		padding: 5px;
	}

	.addTagWrap .tagGroup {
		margin-bottom: 8px;
	}

	.otherTagLabel {
		visibility: hidden;
	}

	.addTagWrap .tagGroup .left {
		text-align: left;
		display: inline-block;
		margin-left: 5px;
	}

	.tagDesc {
		width: 15%;
		height: 120px;
	}

	.apiConfig {
		display: none;
	}


</style>
	<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js'></script>

<?php if($platform == 'ISFT') { ?>
	<script>	
		$(function() {
			var baseUrl = $(".klean13ApiConfig").attr("data-baseurl");

			// ##### TOGGLE SWITCH ON AND OFF #####
			$('.toggleConfig').click(function() {
				var toggleConfig = $('.toggleConfig:checked').val();
				var apiKey = $(".txtApiKey").val().trim();

				if(toggleConfig == 'on') {
					$(".switchStatus").text('ON');
					if(apiKey.length) {
						var tagCategory = $(".tagCategory").val();
						var otherTagCat = $(".otherTagCat").val().trim();
						if(tagCategory == 0 && otherTagCat == '') {
							$(this).removeAttr("checked");
							$(".switchStatus").text('OFF');
							var errorMsg = "<p class='errorMsg'>Tag category is required.</p>";
							$(".loaderWrap").append(errorMsg);

							setTimeout(function() {
								$(".loaderWrap").find(".errorMsg").remove();
							}, 4000);
						}else {
							var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";
							$(".loaderWrap").append(loader);

							// ##### MAKE SURE TO CHECK THE API KEY IF VALID #####
							$(this).attr('disabled', true);
							validateAPIKeyUsingSwitch();
						}
						
					}else {
						$(".switchStatus").text('OFF');
						var errorMsg = "<p class='errorMsg'>Switching on failed! API key is required.</p>";
						if(!$(".txtApiKey.error").length) {
							$(".loaderWrap").append(errorMsg);
							$(".txtApiKey").addClass('error');

							setTimeout(function() {
								$(".loaderWrap").find(".errorMsg").remove();
								$(".txtApiKey").removeClass('error');
							}, 4000);
						}

						$(this).removeAttr("checked");
					}
				}else {
					disabledHook();
				}
			});

			$(".buttonSaveApiKey").click(function() {
				var apiKey = $(".txtApiKey").val().trim();
				var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";

				if(apiKey.length) {
					$(".loaderWrap").append(loader);
					validateAPIKey();

					return false;
				}else {
					$(".switchStatus").text('OFF');
					var errorMsg = "<p class='errorMsg'>Failed to save, API key is required.</p>";
					if(!$(".txtApiKey.error").length) {
						$(".loaderWrap").append(errorMsg);
						$(".txtApiKey").addClass('error');

						setTimeout(function() {
							$(".loaderWrap").find(".errorMsg").remove();
							$(".txtApiKey").removeClass('error');
						}, 4000);
					}

					$(this).removeAttr("checked");
				}
			});

			function disabledHook() {
				var userId = $(".userId").val();
				$(".toggleConfig").attr("disabled", true);
				$(".switchStatus").text('OFF');
				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'disabledHook',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);

						if(data.success) {
							deleteRESThook();
						}
					}
				});
			}

			function validateAPIKeyUsingSwitch() {
				var apiKey = $(".txtApiKey").val();
				var userId = $(".userId").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'validateAPIKey',
						apiKey: apiKey,
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						$(".loaderWrap .webLoading").remove();

						if(data.success) {
							var res = `
								<div class='w3-green apiValidMsg configMessage'>
				  					<p>API Key was saved</p>
								</div>
							`;

							$(".resMsgWrap").append(res);

							setTimeout(function() {
								$(".resMsgWrap .apiValidMsg").remove();
							}, 4000);

							// ##### SETUP RESTHOOK #####
							setupRESThook();
						}else {
							// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
							$(".toggleConfig").removeAttr("disabled");
							var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
							$(".loaderWrap").append(errorMsg);
							$(".toggleConfig").removeAttr("checked");

							setTimeout(function() {
								$(".loaderWrap .errorMsg").remove();
							}, 4000);
						}
					}
				});
			}

			function validateAPIKey() {
				var apiKey = $(".txtApiKey").val();
				var userId = $(".userId").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'validateAPIKey',
						apiKey: apiKey,
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						$(".loaderWrap .webLoading").remove();

						if(data.success) {
							var res = `
								<div class='w3-green apiValidMsg configMessage'>
				  					<p>Settings was saved</p>
								</div>
							`;

							$(".resMsgWrap").append(res);

							addTagCategory('saveSettings');

							setTimeout(function() {
								$(".resMsgWrap .apiValidMsg").remove();
							}, 4000);
						}else {
							// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
							$(".toggleConfig").removeAttr("disabled");
							var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
							$(".loaderWrap").append(errorMsg);
							$(".toggleConfig").removeAttr("checked");

							setTimeout(function() {
								$(".loaderWrap .errorMsg").remove();
							}, 4000);
						}
					}
				});
			}

			function addTagCategory(type) {
				var userId 			= $(".userId").val();
				var tagCategory 	= $(".tagCategory").val();
				var otherTagCat 	= $(".otherTagCat").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'addTagCategory',
						userId: userId,
						tagCategory: tagCategory,
						otherTagCat: otherTagCat,
					}, 
					success: function(data) {
						var data = JSON.parse(data);
						console.log(data);
						$(".otherTagId").val(data.tagCategoryId);

						if(type == 'saveSettings') {
							saveTagCategory();
						}else {
							saveAPIkey();	
						}
						

						allTagCategories();
						$(".otherTagCat").val('');
					}
				});
			}

			// ##### SETUP RESTHOOK #####
			function setupRESThook() {
				var appName = $(".appName").text();
				var authhash = $(".appName").text();
				var userId = $(".userId").val();

				var loader = "<p class='restLoading'><span class='loader'></span> Setting up the RESThook please wait...</p>";
				$(".loaderWrap").append(loader);

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'setupRESThook',
						appName: appName,
						authhash: authhash,
						userId: userId,
					},
					success: function(data) {
						var data = JSON.parse(data);
						if(data.success) {

							// ##### SETUP NEXT THE WEBHOOK #####
							// setupWebHook();

							var res = `
								<div class='w3-green restCbMsg configMessage'>
				  					<p>RESThook was set up.</p>
								</div>
							`;

							$(".resMsgWrap").append(res);

							addTagCategory('setupRestHook');
						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error setting up the RESThook.');
						}

						setTimeout(function() {
							$(".successConfigSave, .restCbMsg").remove();
						}, 4000);
					}
				});
			}

			// ##### SAVE API KEY  #####
			function saveAPIkey() {
				var userId = $(".userId").val();
				var apiKey = $(".txtApiKey").val();
				var tagCategoryId = $(".tagCategory").val();
				var otherTagId = $(".otherTagId").val();
				var otherTagCat = $(".otherTagCat").val();
				var tagPrefix = $(".tagPrefix").val();
				var tagCatId = 0;
				if(otherTagCat != '') {
					tagCatId = otherTagId;
				}else {
					tagCatId = tagCategoryId;
				}


				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'configSave',
						userId: userId,
						apiKey: apiKey,
						tagCatId: tagCatId,
						tagPrefix: tagPrefix
					}, 
					success: function(data) {
						var data = JSON.parse(data);

						if(data.success) {
							$(".toggleConfig").removeAttr("disabled");
							$(".loaderWrap .restLoading").remove();

						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error saving API key.');
						}
					}
				});
			}

			function saveTagCategory() {
				var userId = $(".userId").val();
				var apiKey = $(".txtApiKey").val();
				var tagCategoryId = $(".tagCategory").val();
				var otherTagId = $(".otherTagId").val();
				var otherTagCat = $(".otherTagCat").val();
				var tagPrefix = $(".tagPrefix").val();
				var tagCatId = 0;

				if(otherTagCat != '') {
					tagCatId = otherTagId;
				}else {
					tagCatId = tagCategoryId;
				}

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'saveTagCategory',
						userId: userId,
						apiKey: apiKey,
						tagCatId: tagCatId,
						tagPrefix: tagPrefix
					}, 
					success: function(data) {
						var data = JSON.parse(data);
					}
				});
			}

			function deleteRESThook() {
				var userId = $(".userId").val();

				var loader = "<p class='deleteRestLoading'><span class='loader'></span> Deleting RESThook please wait...</p>";
				$(".loaderWrap").append(loader);

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'deleteRestHook',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						if(data.success) {
							$(".toggleConfig").removeAttr("disabled");
							$(".loaderWrap .deleteRestLoading").remove();

							var res = `
							<div class='w3-green delWebCbMsg configMessage'>
			  					<p>RESThook was deleted.</p>
							</div>
							`;

							$(".resMsgWrap").append(res);

							setTimeout(function() {
								$(".resMsgWrap .delWebCbMsg").remove();
							}, 4000);
						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error deleting RESThook.');
							$(".toggleConfig").removeAttr("disabled");
						}
					}
				});
			}

			configButtonState();
			function configButtonState() {
				var userId = $(".userId").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'configButtonState',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						console.log('data', data);

						if(parseInt(data.isActive) != 0) {
							// Switch on
							$(".toggleConfig").attr('checked','checked');
							$(".switchStatus").text('ON');
						}else {
							// Switch off
							$(".toggleConfig").removeAttr('checked');
						}

						setTimeout(function() {
							$(".pageLoader").hide();
							$(".apiConfig").show();
						}, 400);
					}
				});
			}

			$(".tagCategory").change(function() {
				var val = $(this).val();
				if(val > 0) {
					$(".otherTagCat").attr('disabled', true).val('');
				}else {
					$(".otherTagCat").attr('disabled', false).val('');
				}
			});

			allTagCategories();
			function allTagCategories() {
				var userId = $(".userId").val();
				// tagCategory
				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'allTagCategories',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						var active = data.active;
						var data = data.result;
						var html = "<option value='0'>Please select a category</option>";
						console.log('data', data.length);
						for(var x = 0; x < data.length; x++) {
							html += '<option value="'+data[x].Id+'">'+data[x].CategoryName+'</option>';
						}

						$(".tagCategory").html(html);
						$('.tagCategory option[value="'+active+'"]').attr('selected', 'selected');
					}
				});
			}

			getTagPrefix();
			function getTagPrefix() {
				var userId = $(".userId").val();
				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'getTagPrefix',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						console.log('data', data);
						if(data.tagPrefix != null && data.tagPrefix != '') {
							$(".tagPrefix").val(data.tagPrefix);
						}
						
					}
				});
			}
		});
	</script>
<?php } else if($platform == 'AC') { ?>

	<!-- ##### HIDE DROPDOWN AND INPUT WHEN AC ##### -->
	<style type="text/css">
		.hiddenAC {
			display: none;
		}

		.apiConfig {
			display: none;
		}
	</style>

	<script>
		$(function() {
			var baseUrl = $(".klean13ApiConfig").attr("data-baseurl");

			configButtonState();
			function configButtonState() {
				var userId = $(".userId").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'configButtonState',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						console.log('data', data);

						if(parseInt(data.isActive) != 0) {
							// Switch on
							$(".toggleConfig").attr('checked','checked');
							$(".switchStatus").text('ON');
						}else {
							// Switch off
							$(".toggleConfig").removeAttr('checked');
						}

						setTimeout(function() {
							$(".pageLoader").hide();
							$(".apiConfig").show();
						}, 400);
					}
				});
			}

			$(".buttonSaveApiKey").click(function() {
				var apiKey = $(".txtApiKey").val().trim();
				var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";

				if(apiKey.length) {
					$(".loaderWrap").append(loader);
					validateAPIKey();

					return false;
				}else {
					$(".switchStatus").text('OFF');
					var errorMsg = "<p class='errorMsg'>Failed to save, API key is required.</p>";
					if(!$(".txtApiKey.error").length) {
						$(".loaderWrap").append(errorMsg);
						$(".txtApiKey").addClass('error');

						setTimeout(function() {
							$(".loaderWrap").find(".errorMsg").remove();
							$(".txtApiKey").removeClass('error');
						}, 4000);
					}

					$(this).removeAttr("checked");
				}
			});

			// ##### TOGGLE SWITCH ON AND OFF #####
			$('.toggleConfig').click(function() {
				var toggleConfig = $('.toggleConfig:checked').val();
				var apiKey = $(".txtApiKey").val().trim();
				console.log('apiKey', apiKey)
				if(toggleConfig == 'on') {
					$(".switchStatus").text('ON');
					if(apiKey.length) {
						var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";
						$(".loaderWrap").append(loader);

						// ##### MAKE SURE TO CHECK THE API KEY IF VALID #####
						$(this).attr('disabled', true);
						validateAPIKeyUsingSwitch();
					}else {
						$(".switchStatus").text('OFF');
						var errorMsg = "<p class='errorMsg'>Switching on failed! API key is required.</p>";
						if(!$(".txtApiKey.error").length) {
							$(".loaderWrap").append(errorMsg);
							$(".txtApiKey").addClass('error');

							setTimeout(function() {
								var errorMsg = "<p class='errorMsg'>Switching on failed! API key is required.</p>";
								$(".loaderWrap").find(".errorMsg").remove();
								$(".txtApiKey").removeClass('error');
							}, 4000);
						}

						$(this).removeAttr("checked");
					}
				}else {
					disabledHook();
				}
			});

			function disabledHook() {
				var userId = $(".userId").val();
				$(".toggleConfig").attr("disabled", true);
				$(".switchStatus").text('OFF');
				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'disabledHook',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);

						if(data.success) {
							deleteWebhook();
						}
					}
				});
			}

			function validateAPIKeyUsingSwitch() {
				var apiKey = $(".txtApiKey").val();
				var userId = $(".userId").val();
				console.log('userIduserId', userId)

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'validateAPIKey',
						apiKey: apiKey,
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						$(".loaderWrap .webLoading").remove();

						if(data.success) {
							var res = `
								<div class='w3-green apiValidMsg configMessage'>
				  					<p>API Key was saved</p>
								</div>
							`;

							$(".resMsgWrap").append(res);

							setTimeout(function() {
								$(".resMsgWrap .apiValidMsg").remove();
							}, 4000);

							// ##### SETUP WEBHOOK #####
							setupWebHook();
						}else {
							// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
							$(".toggleConfig").removeAttr("disabled");
							var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
							$(".loaderWrap").append(errorMsg);
							$(".toggleConfig").removeAttr("checked");

							setTimeout(function() {
								$(".loaderWrap .errorMsg").remove();
							}, 4000);
						}
					}
				});
			}

			function validateAPIKey() {
				var apiKey = $(".txtApiKey").val();
				var userId = $(".userId").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'validateAPIKey',
						apiKey: apiKey,
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						$(".loaderWrap .webLoading").remove();

						if(data.success) {
							var res = `
								<div class='w3-green apiValidMsg configMessage'>
				  					<p>Settings was saved</p>
								</div>
							`;

							$(".resMsgWrap").append(res);

							saveTagPrefix();

							setTimeout(function() {
								$(".resMsgWrap .apiValidMsg").remove();
							}, 4000);
						}else {
							// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
							$(".toggleConfig").removeAttr("disabled");
							var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
							$(".loaderWrap").append(errorMsg);
							$(".toggleConfig").removeAttr("checked");

							setTimeout(function() {
								$(".loaderWrap .errorMsg").remove();
							}, 4000);
						}
					}
				});
			}

			function saveTagPrefix() {
				var userId = $(".userId").val();
				var apiKey = $(".txtApiKey").val();
				var tagPrefix = $(".tagPrefix").val();
			
				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'saveTagPrefix',
						userId: userId,
						apiKey: apiKey,
						tagPrefix: tagPrefix
					}, 
					success: function(data) {
						var data = JSON.parse(data);
					}
				});
			}

			function setupWebHook() {
				var appName = $(".appName").text();
				var authhash = $(".authhash").text();
				var userId = $(".userId").val();
				console.log('userId', userId)
				var loader = "<p class='webLoading'><span class='loader'></span> Setting up the Webhook please wait...</p>";
				$(".loaderWrap").append(loader);

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'setupWebHook',
						userId: userId,
						appName: appName,
						authhash: authhash
					},
					success: function(data) {
						var data = JSON.parse(data);
						// ##### REMOVE LOADING SPINNER #####
						$(".webLoading").remove();

						if(data.success) {
							var res = `
								<div class='w3-green webCbMsg configMessage'>
				  					<p>Webhook was set up.</p>
								</div>
							`;

							$(".resMsgWrap").append(res);

							saveAPIkey();
						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error setting up Webhook.');
						}

						setTimeout(function() {
							$(".successConfigSave, .webCbMsg").remove();
						}, 4000);
					}
				});
			}		

			function saveAPIkey() {
				var userId = $(".userId").val();
				var apiKey = $(".txtApiKey").val();
				var platform = $(".platform").text();
				var tagPrefix = $(".tagPrefix").val();
				var tagName = '';

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'configSave',
						userId: userId,
						apiKey: apiKey,
						tagPrefix: tagPrefix
					}, 
					success: function(data) {
						var data = JSON.parse(data);

						if(data.success) {
							$(".toggleConfig").removeAttr("disabled");
						}else {
							$(".successConfigSave").fadeIn().find('p').text('Error saving API key.');
						}
					}
				});
			}

			function deleteWebhook() {
				var userId = $(".userId").val();

				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'deleteWebHook',
						userId: userId
					},
					success: function(data) {
						$(".toggleConfig").removeAttr("disabled");
						$(".loaderWrap .deleteRestLoading").remove();

						var res = `
						<div class='w3-green delWebCbMsg configMessage'>
		  					<p>Webhook was deleted.</p>
						</div>
						`;

						$(".resMsgWrap").append(res);

						setTimeout(function() {
							$(".resMsgWrap .delWebCbMsg").remove();
						}, 4000);
					}
				});
			}


			getTagPrefix();
			function getTagPrefix() {
				var userId = $(".userId").val();
				$.ajax({
					type: 'POST',
					url: baseUrl+'ept.php?op=ajaxcall',
					data: {
						action: 'getTagPrefix',
						userId: userId
					},
					success: function(data) {
						var data = JSON.parse(data);
						if(data.tagPrefix != null && data.tagPrefix != '') {
							$(".tagPrefix").val(data.tagPrefix);
						}
						
					}
				});
			}
		});
	</script>
<?php } ?>
<?php
	
}  ##### END KLEAN13 API CONFIG ##### //

##### PROCCESS AJAX CALL #####
if($op == "ajaxcall") {

	if(isset($_POST['action'])) {

		##### ADD TAG CATEGORIES #####
		if($_POST['action'] == 'addTagCategory') {
			$userId 		= $_POST['userId'];
			$tagCategoryId 	= $_POST['tagCategory'];
			$otherTagCat 	= $_POST['otherTagCat'];

			if($platform == 'ISFT') {
				##### ADD NEW CATEGORY #####
				if($otherTagCat != null || $otherTagCat != '') {

					$findTagArr = $infusionsoft->data()->findByField('ContactGroupCategory', 1000, 0, 'CategoryName', $otherTagCat, array('Id','CategoryName'));

					##### TAG CATEGORY ALREADY EXIST #####
					if(count($findTagArr)) {
						$tagCategoryId = $findTagArr[0]['Id'];
					}else {
						$data = array(
							'CategoryName' => $otherTagCat,
						);

						$tagCategoryId = $infusionsoft->data('xml')->add('ContactGroupCategory', $data);
					}
				}else {
					$tagCategoryId = 0;
				}

				$data = array(
					'tagCategoryId' => $tagCategoryId
				);

				echo json_encode($data);

				die();
			}
		}

		##### ALL TAG CATEGORIES INFUSIONSOFT #####
		if($_POST['action'] == 'allTagCategories') {
			$userId 	= $_POST['userId'];
			$orderBy 	= "Id";
			$ascending 	= true;
			$page 		= 0;
			$table 		= "ContactGroupCategory";
			$queryData 	= array('Id' => '%'); 

			$results = $infusionsoft->data()->query($table, 1000, $page, $queryData, ["Id", "CategoryName", "CategoryDescription"], $orderBy, $ascending);


			$query = DB::queryFirstRow("SELECT tagCategoryId FROM tblEptKlean13 WHERE UserId = %i", $userId);
			$tagCategoryId = $query['tagCategoryId'];

			$data = array(
				'result' => $results,
				'success' => 1,
				'active' => $tagCategoryId
			);

			echo json_encode($data);

			die();
		}

		##### GET TAG PREFIX #####
		if($_POST['action'] == 'getTagPrefix') {
			$userId = $_POST['userId'];

			$query = DB::queryFirstRow("SELECT tagPrefix FROM tblEptKlean13 WHERE UserId = %i", $userId);
			$queryCount = DB::count();
			if($queryCount) {
				$tagPrefix = $query['tagPrefix'];

				$data = array(
					'success' => 1,
					'tagPrefix' => $tagPrefix
				);
			}else {
				$data = array(
					'success' => 1,
					'tagPrefix' => ''
				);
			}

			echo json_encode($data);

			die();
		}

		##### DISABLED WEBHOOK AND RESTHOOK #####
		if($_POST['action'] == 'disabledHook') {
			$userId 	= $_POST['userId'];
			$tableName 	= 'tblEptKlean13';
			$column = '';

			if($platform == 'ISFT') {
				$column = 'isActiveISFT';
			}else if($platform == 'AC') {
				$column = 'isActiveAC';
			}

			DB::query("UPDATE $tableName SET $column=%i WHERE UserId=%i", 0, $userId);
			$counter = DB::affectedRows();

			$data = array(
				'success' => 1,
				'affectedRows' => $counter
			);

			echo json_encode($data);

			die();
		}

		##### VALIDATE API KEY #####
		if($_POST['action'] == 'validateAPIKey') {
			$apiKey 	= $_POST['apiKey'];
			$userId 	= $_POST['userId'];
			$testEmail 	= 'adrian+301dtest@caldon.uk';
			$tableName 	= 'tblEptKlean13';

			$payload = json_encode( 
				array( 
					'api_key' => $apiKey,
			    	'address' => $testEmail
				)
			);

			##### MAKE HTTP POST REQUEST #####
			$ch = curl_init('https://app.klean13.com/api/validate-one');

			##### CURL OPTION #####
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

			##### EXECUTE #####
			$response = curl_exec($ch);

			##### CLOSE ALL CURL RESOURCES #####
			curl_close($ch);

			$klean13Res = json_decode($response);

			##### CHECK API KEY IF VALID #####
			if($klean13Res->status === 'ok') {
				$checkExist = DB::queryFirstRow("SELECT UserId FROM tblEptKlean13 WHERE UserId = %i", $userId);
				$count = DB::count();

				if($count) {
					DB::update($tableName, array(
						'apiKey' => $apiKey,
					), 	"UserId=%s", $userId);
				}else {
					DB::insert($tableName, array(
					  	'UserId' => $userId,
					  	'apiKey' => $apiKey,
					  	'tagCategoryId' => 0,
					  	'tagPrefix' => '',
					  	'lookUpCount' => 0,
					  	'lookUpMaxLimit' => 5,
					  	'DateCreated' => date('Y-m-d H:i:s'),
					  	'LastUpdated' => date('Y-m-d H:i:s'),
					  	'isActiveISFT' => 0,
					  	'isActiveAC' => 0
					));
				}

				$data = array(
					'success' => 1
				);
			}else {
				$data = array(
					'success' => 0,
					'message' => $klean13Res->message
				);
			}

			echo json_encode($data);

			die();
		}

		##### SAVE TAG CATEGORY #####
		if($_POST['action'] == 'saveTagCategory') {
			$userId 		= $_POST['userId'];
			$apiKey 		= $_POST['apiKey'];
			$tagPrefix 		= $_POST['tagPrefix'];
			$tableName 		= 'tblEptKlean13';

			$checkExist = DB::queryFirstRow("SELECT UserId FROM tblEptKlean13 WHERE UserId = %i", $userId);
			$count = DB::count();

			if($count) {
				$tagCatId = $_POST['tagCatId'] ? $_POST['tagCatId'] : 0;
				DB::update($tableName, array(
					'tagPrefix' => $tagPrefix,
					'tagCategoryId' => $tagCatId,
				), 	"UserId=%s", $userId);
			}else {
				DB::insert($tableName, array(
				  	'UserId' => $userId,
				  	'apiKey' => '',
				  	'tagCategoryId' => 0,
				  	'tagPrefix' => '',
				  	'lookUpCount' => 0,
				  	'lookUpMaxLimit' => 5,
				  	'DateCreated' => date('Y-m-d H:i:s'),
				  	'LastUpdated' => date('Y-m-d H:i:s'),
				  	'isActiveISFT' => 0,
				  	'isActiveAC' => 0
				));
			}
			
			$data = array(
				'success' => 1,
			);

			echo json_encode($data);

			die();
		}

		##### SAVE TAG CATEGORY #####
		if($_POST['action'] == 'saveTagPrefix') {
			$userId 		= $_POST['userId'];
			$apiKey 		= $_POST['apiKey'];
			$tagPrefix 		= $_POST['tagPrefix'];
			$tableName 		= 'tblEptKlean13';

			$checkExist = DB::queryFirstRow("SELECT UserId FROM tblEptKlean13 WHERE UserId = %i", $userId);
			$count = DB::count();

			if($count) {
				DB::update($tableName, array(
					'tagPrefix' => $tagPrefix,
				), 	"UserId=%s", $userId);
			}else {
				DB::insert($tableName, array(
				  	'UserId' => $userId,
				  	'apiKey' => '',
				  	'tagCategoryId' => 0,
				  	'tagPrefix' => '',
				  	'lookUpCount' => 0,
				  	'lookUpMaxLimit' => 5,
				  	'DateCreated' => date('Y-m-d H:i:s'),
				  	'LastUpdated' => date('Y-m-d H:i:s'),
				  	'isActiveISFT' => 0,
				  	'isActiveAC' => 0
				));
			}
			
			$data = array(
				'success' => 1,
			);

			echo json_encode($data);

			die();
		}
		
		##### SAVE CONFIGURATION #####
		if($_POST['action'] == 'configSave') {
			$userId 		= $_POST['userId'];
			$apiKey 		= $_POST['apiKey'];
			$tagPrefix 		= $_POST['tagPrefix'];
			$tableName 		= 'tblEptKlean13';

			$checkExist = DB::queryFirstRow("SELECT UserId FROM tblEptKlean13 WHERE UserId = %i", $userId);
			$count = DB::count();

			if($platform == 'AC') {
				if($count) {
					DB::update($tableName, array(
						'apiKey' => $apiKey,
						'tagPrefix' => $tagPrefix,
						'DateCreated' => date('Y-m-d H:i:s'),
						'LastUpdated' => date('Y-m-d H:i:s'),
						'isActiveAC' => 1
					), 	"UserId=%s", $userId);
				}else {
					DB::insert($tableName, array(
					  	'UserId' => $userId,
					  	'apiKey' => $apiKey,
					  	'tagCategoryId' => 0,
					  	'tagPrefix' => $tagPrefix,
					  	'lookUpCount' => 0,
					  	'lookUpMaxLimit' => 5,
					  	'DateCreated' => date('Y-m-d H:i:s'),
					  	'LastUpdated' => date('Y-m-d H:i:s'),
					  	'isActiveISFT' => 0,
					  	'isActiveAC' => 1
					));
				}
			}else {
				$tagCatId = $_POST['tagCatId'] ? $_POST['tagCatId'] : 0;

				if($count) {
					DB::update($tableName, array(
						'apiKey' => $apiKey,
						'tagCategoryId' => $tagCatId,
						'tagPrefix' => $tagPrefix,
						'DateCreated' => date('Y-m-d H:i:s'),
						'LastUpdated' => date('Y-m-d H:i:s'),
						'isActiveISFT' => 1
					), 	"UserId=%s", $userId);
				}else {
					DB::insert($tableName, array(
					  	'UserId' => $userId,
					  	'apiKey' => $apiKey,
					  	'tagCategoryId' => $tagCatId,
					  	'tagPrefix' => $tagPrefix,
					  	'lookUpCount' => 0,
					  	'lookUpMaxLimit' => 5,
					  	'DateCreated' => date('Y-m-d H:i:s'),
					  	'LastUpdated' => date('Y-m-d H:i:s'),
					  	'isActiveISFT' => 1,
					  	'isActiveAC' => 0
					));
				}
			}
			
			$data = array(
				'success' => 1,
			);

			echo json_encode($data);

			die();
		}

		##### DELETE RESTHOOK #####
		if($_POST['action'] == 'deleteRestHook') {
			$userId 	= $_POST['userId'];
			$results 	= $infusionsoft->resthooks()->all();
			$count 		= $results->count();

			if($count > 0) {
				$resultArr = $results->toArray();

				foreach($resultArr as $row) {

					$urlUserId = explode("userId=",$row->hookUrl)[1];
					$urlArr = explode("restHookHandler",$row->hookUrl);
					##### CHECK RESTHOOK USERID #####
					if($urlUserId == $userId) {
						##### DELETE RESTHOOK BY USERID #####
						if(count(array_filter($urlArr)) > 1) {
							$infusionsoft->resthooks()->find($row->key)->delete();
						}
					}
				}
			}

			$data = array(
				'success' => 1,
			);

			echo json_encode($data);

			die();
		}

		##### CONFIG BUTTON STATE - SWITCH ON & OFF #####
		if($_POST['action'] == 'configButtonState') {
			$userId = $_POST['userId'];
			
			if($platform == 'ISFT') {
				$query = DB::query("SELECT COUNT(Id) as isActiveISFT FROM tblEptKlean13 WHERE UserId=%s AND isActiveISFT = %i", $userId, 1);

				if(count($query)) {
					$data = array(
						'success' => 1,
						'isActive' => $query[0]['isActiveISFT']
					);
				}else {
					$data = array(
						'success' => 1,
						'isActive' => 0
					);
				}
			}else {
				$query = DB::query("SELECT COUNT(Id) as isActiveAC FROM tblEptKlean13 WHERE UserId=%s AND isActiveAC = %i", $userId, 1);

				if(count($query)) {
					$data = array(
						'success' => 1,
						'isActive' => $query[0]['isActiveAC']
					);
				}else {
					$data = array(
						'success' => 1,
						'isActive' => 0
					);
				}
			}

			echo json_encode($data);

			die();
		}

		##### SETUP RESTHOOK #####
		if($_POST['action'] == 'setupRESThook') {
			$appName 	= $_POST['appName'];
			$authhash 	= $_POST['authhash'];
			$userId 	= $_POST['userId'];

			if (isset($_SESSION['token'])) {
				$infusionsoft->setToken(unserialize($_SESSION['token']));
			}

			##### IF WE ARE RETURNING FROM INFUSIONSOFT WE NEED TO EXCHANGE THE CODE FOR AN ACCESS TOKEN #####
			if (isset($_GET['code']) and !$infusionsoft->getToken()) {
				$infusionsoft->requestAccessToken($_GET['code']);
				$_SESSION['token'] = serialize($infusionsoft->getToken());
			}


			function resthookManager($infusionsoft, $appName, $authhash, $userId) {
				$restHookUrl = sprintf("http://wdem.wedeliver.email/restHookHandler.php?appName=%s&authHash=%s&userId=%d", $appName, $authhash, $userId);
				$resthooks = $infusionsoft->resthooks();

				##### CREATE A NEW RESTHOOK #####
				$resthook = $resthooks->create([
					'eventKey' => 'contact.add',
					'hookUrl' => $restHookUrl
				]);
				$resthook = $resthooks->find($resthook->id)->verify();
				return $resthook;
			}

			if ($infusionsoft->getToken()) {
				try {
					$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId);
				}
				catch (\Infusionsoft\TokenExpiredException $e) {

					##### IF THE REQUEST FAILS DUE TO AN EXPIRED ACCESS TOKEN, WE CAN REFRESH THE TOKEN AND THEN DO THE REQUEST AGAIN #####
					$infusionsoft->refreshAccessToken();

					##### SAVE THE SERIALIZED TOKEN TO THE CURRENT SESSION FOR SUBSEQUENT REQUESTS #####
					$_SESSION['token'] = serialize($infusionsoft->getToken());
					$resthook = resthookManager($infusionsoft, $appName, $authhash, $userId);
				}

				$data = array(
					'success' => 1
				);
			}
			else {
				echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';

				$data = array(
					'success' => 0
				);
			}

			echo json_encode($data);

			die();
		}

		##### SETUP WEBHOOK #####
		if($_POST['action'] == 'setupWebHook') {
			$query = DB::queryFirstRow("SELECT accessToken, appName FROM tblEptUsers WHERE userId = %i", $userId);
			$appName = $query['appName'];
			$apiKey = $query['accessToken'];

			##### INITIALIZATION #####
			$acURL = "https://".$appName.".api-us1.com";

			##### CREATING NEW INSTANCE OF AN OBJECT ACTIVECAMPAIGN #####
			$ac = new ActiveCampaign($acURL, $apiKey);

			##### CHECK IF CREDENTIALS IS VALID #####
			if (!(int)$ac->credentials_test()) {
				echo "<p>Access denied: Invalid credentials (URL and/or API key).</p>";
				exit();
			}

			$appName 	= $_POST['appName'];
			$authhash 	= $_POST['authhash'];
			$userId 	= $_POST['userId'];

			$webHookUrl = sprintf("http://wdem.wedeliver.email/webHookHandler.php?appName=%s&authHash=%s&userId=%d", $appName, $authhash, $userId);

			##### WEBHOOK PAYLOAD #####
			$data = get_object_vars(json_decode('{ "name": "WeDeliverLog", "url": "'.$webHookUrl.'", "lists[0]": "0", "action[subscribe]": "subscribe", "action[unsubscribe]": "unsubscribe", "action[update]": "update", "action[bounce]": "bounce", "init[public]": "public", "init[admin]": "admin", "init[api]": "api", "init[system]": "system" }')); 

			##### WEBHOOK ADD METHOD #####
			$contactHook = $ac->api('webhook/add', $data);

			if($contactHook->success) {
			    $data = array(
			    	'success' => 1
			    );
			}else {
			    $data = array(
			    	'success' 	=> 0,
			    	'webURL' 	=> $webHookUrl,
			    	'error' 	=> $contactHook->error
			    );	
			}

			echo json_encode($data);

			die();
		}

		##### DELETE WEBHOOK #####
		if($_POST['action'] == 'deleteWebHook') {
			$query = DB::queryFirstRow("SELECT accessToken, appName FROM tblEptUsers WHERE userId = %i", $userId);
			$appName = $query['appName'];
			$apiKey = $query['accessToken'];

			##### INITIALIZATION #####
			$acURL = "https://".$appName.".api-us1.com";

			##### CREATING NEW INSTANCE OF AN OBJECT ACTIVECAMPAIGN #####
			$ac = new ActiveCampaign($acURL, $apiKey);

			##### CHECK IF CREDENTIALS IS VALID #####
			if (!(int)$ac->credentials_test()) {
				echo "<p>Access denied: Invalid credentials (URL and/or API key).</p>";
				exit();
			}

			$userId = $_POST['userId'];

			$allHooks = $ac->api('webhook/list');

			##### UNSET ALL UNNECESSARY OBJECT PROPERTIES #####
			unset($allHooks->result_code);
			unset($allHooks->result_message);
			unset($allHooks->result_output);
			unset($allHooks->http_code);
			unset($allHooks->success);

			$hookIds = array();
			foreach($allHooks as $row) {
				if (is_object($row)) {
				    array_push($hookIds, $row->id);

				    $urlUserId = explode("userId=",$row->url)[1];
				    
				    ##### CHECK WEBHOOK USERID #####
				    if($urlUserId == $userId) {

				    	##### DELETE WEBHOOK BY USERID #####
				    	$ac->api("webhook/delete?id=".$row->id);
				    }
				}
			}

			$data = array(
				'success' => 1,
				'data' => $hookIds
			);

			echo json_encode($data);

			die();
		}

 	}else {

 		$data = array(
 			'success' => 0,
 			'message' => 'Please provide $_POST action'
 		);
 	} 
}

if ($op == "updateEmailOpenData") {
	if ($reportId == 0) {
		print "<div class='w3-container'>\n";
		printf("<h2>Update Historical Email Open Data</h2>\n");


		printf("Please choose a saved report</h2>\n");
		
	
/**
				<!-- Trigger/Open the Modal -->
		<button onclick="document.getElementById('id01').style.display='block'"
		class="w3-button">Open Modal</button>
**/

?>
		<p>Email Power Tools retrieves your historical email open data from Infusionsoft so that you can quickly view reports showing your historical email performance (e.g. open, click, bounce, spam etc.)</p>
		<p>You need to set up a specific saved report in your Infusionsoft app to be able to retrieve the historical data. Please <a href='#' onclick="document.getElementById('videoLightBox').style.display='block'">click here</a> to watch the video showing how this is done.</p>

		<!-- The Modal -->
		<div id="videoLightBox" class="w3-modal">
		  <div class="w3-modal-content">
		    <div class="w3-container">
		      <span onclick="document.getElementById('videoLightBox').style.display='none'" 
		      class="w3-button w3-display-topright"><i class="fa fa-remove fa-fw"></i></span>
		      <p>How to set up a saved report that Email Power Tools can use to retrieve your historical email open data</p>
		 		<iframe src="https://player.vimeo.com/video/248994520" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>  
		      <p>Please <a href="#" onclick="document.getElementById('videoLightBox').style.display='none'">click here</a> to close this window</p>
		    </div>
		  </div>
		</div>		
				
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
		<script src="https://player.vimeo.com/api/player.js"></script>  

		<script type="text/javascript">  
			var iframe = document.querySelector('iframe');  
			var player = new Vimeo.Player(iframe);  

			jQuery(document).ready(function(){  
				jQuery('.closeButton').click(function(){  
					player.pause();  
				});  
			});  
		</script>
				
<?php		
		printf("<h3>Please select a Saved Report</h3>\n");
		print "<p>The data from the selected report will be stored in the Email Power Tools database</p>\n";
		$orderBy="Id";
		$ascending=true;
		$page=0;
		$table="SavedFilter";
		$queryData=array('ReportStoredName' => 'EmailBroadcastConversionReport'); // Other is EmailSentSearch
		$results=$infusionsoft->data()->count($table, $queryData);

		$results=$infusionsoft->data()->query($table, 1000, $page, $queryData, ["Id", "FilterName"], $orderBy, $ascending);
		foreach ($results as $result) {
			printf('<a href="?op=updateEmailOpenData&reportId=%d">%s</a><br>' . "\n", $result["Id"], $result["FilterName"]);
		}	
		print "</div>\n";
	} else {
		
		print "<div class='w3-container'>\n";
		
		$searchUserId=1;
		$returnFields=array();
		//$results=$infusionsoft->search()->getSavedSearchResults($savedSearchId, $searchUserId, $pageNumber, $returnFields);
		//$results=$infusionsoft->search()->getSavedSearchResults($savedSearchId, $searchUserId, $pageNumber, $returnFields);
		
		printf("<h2>Loading Saved Search Data</h2>\n");	
		
		printProgressBarContainer();
		
		print "<p>We can't display an accurate progress bar, as Infusionsoft doesn't tell us how many pages of data it's going to return <i class='fa fa-frown-o'></i></p>\n";
		
		// ======
		
		$tableName="EmailSentSummary";
		$lastUpdate=getMySqlLastUpdate($appName, "EmailSentSummary");
		debugOut(sprintf("<p>%s table last updated %s</p>", $tableName, $lastUpdate));
		
		// 1. Save the current date and time, as we'll need that later to record when we last updated the MySql table
		$queryStartTime=new DateTime("now");
	
		// 2. Ensure table exists
		createMySqlTable($appName, $tableName);
		
		// 3. Query everything in the right order, update the MySQL database and update the screen status
		$cumulativePages=0;
		$cumulativeRecords=0;
		$page=0;
		do {				
			$count=getSavedSearchPage($appName, $tableName, $reportId, $searchUserId, $page);
				
			$page++;
			$cumulativePages++;
			$cumulativeRecords+=$count;
			printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d records";</script>', $cumulativePages, $cumulativeRecords);
			printf('<script>setProgressBar(%d);</script>' . "\n", 10*((($cumulativePages-1) % 10)+1));
		} while($count > 0);

		printf("<p>Completed</p>\n");
		printf('<script>setProgressBar(%d);</script>' . "\n", 100);
		print'<script>var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";</script>';
		
		print '<p>You can now <a href="?op=emailOpenReport">view the historical email report</a> or <a href="?op=updateEmailOpenData">update using another saved search</a>' . "\n";
	
		// 4. Update the Date and Time that the table was last updated
		setMySqlLastUpdate($appName, $tableName, $queryStartTime);	
		
		print "</div>\n";
	}
}

if ($op == "OLDemailOpenReport") {
	if ($reportId == 0) {
		printf("<h3>Please choose a saved report</h3>\n");
		
		printf("<h2>Available Saved Searches</h2>\n");
		$orderBy="Id";
		$ascending=true;
		$page=0;
		$table="SavedFilter";
		$queryData=array('ReportStoredName' => 'EmailBroadcastConversionReport'); // Other is EmailSentSearch
		$results=$infusionsoft->data()->count($table, $queryData);
	//	$this>$this->infusionsoftService->data()->query($table, 100, 0, ["Id"=>"%"], ["Id"] ,["Id"], 1 );
		print "<pre>\n";
		print_r($results);
		print "</pre>\n";

		$results=$infusionsoft->data()->query($table, 1000, $page, $queryData, ["Id", "FilterName"], $orderBy, $ascending);
		foreach ($results as $result) {
			printf('<a href="?op=emailOpenReport&reportId=%d">%s</a><br>' . "\n", $result["Id"], $result["FilterName"]);
		}
	
		
//		print "<pre>\n";
//		print_r($results);
//		print "</pre>\n";		
	} else {
		$searchUserId=1;
		$returnFields=array();
		//$results=$infusionsoft->search()->getSavedSearchResults($savedSearchId, $searchUserId, $pageNumber, $returnFields);
		//$results=$infusionsoft->search()->getSavedSearchResults($savedSearchId, $searchUserId, $pageNumber, $returnFields);
		
		printf("<h2>Saved Search Result</h2>\n");	
		
		print "<pre>\n";
		
		$page = 0;
		do{
			/**
			printf("Page: %d\n", $page);
			$results=$infusionsoft->search()->getSavedSearchResultsAllFields($reportId, $searchUserId, $page);
			**/
			
			$done=0;
			$results=array();
			for ($attempt = 1; $attempt <= 5 && !$done; $attempt++) {
				try {
					printf("Page: %d; Attempt: %d\n", $page, $attempt);
					$results=$infusionsoft->search()->getSavedSearchResultsAllFields($reportId, $searchUserId, $page);
					$done = true;
				} catch(Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "\n";
					sleep($attempt*2);
				}
			}
			if (!$done) {
				printf("Page %d timed out\n", $page);
			}
			
			foreach ($results as $stat) {
				$date=$stat['DateSent'];
				/**
				print "<pre>";
				print_r($date);
				print_r($date->getTimestamp());
				print "</pre>";
				terminateHtml();
				**/
				$epoch=$date->getTimestamp();
				$newdate=date('Y-m-d H:i:s', $epoch);
				$ISOyear=date('o', $epoch);
				$ISOweek=date('W', $epoch);
				$year=date('Y', $epoch);
				$month=date('m', $epoch);
				$day=date('d', $epoch);
				$sent=$stat['#Sent'];
				$opened=$stat['#Opened'];
				$clicked=$stat['#Clicked'];
				#printf("%d:%02d:%s:%6d:%6d\n", $ISOyear, $ISOweek, $newdate, $sent, $opened);
				if (isset($summaryWeek[$ISOyear][$ISOweek]["Sent"])) {
					$summaryWeek[$ISOyear][$ISOweek]["Sent"] += $sent;
				} else {
					$summaryWeek[$ISOyear][$ISOweek]["Sent"] = $sent;
				}
				if (isset($summaryWeek[$ISOyear][$ISOweek]["Opened"])) {
					$summaryWeek[$ISOyear][$ISOweek]["Opened"] += $opened;
				} else {
					$summaryWeek[$ISOyear][$ISOweek]["Opened"] = $opened;
				}
				if (isset($summaryWeek[$ISOyear][$ISOweek]["Clicked"])) {
					$summaryWeek[$ISOyear][$ISOweek]["Clicked"] += $clicked;
				} else {
					$summaryWeek[$ISOyear][$ISOweek]["Clicked"] = $clicked;
				}
				if (isset($summaryMonth[$year][$month]["Sent"])) {
					$summaryMonth[$year][$month]["Sent"] += $sent;
				} else {
					$summaryMonth[$year][$month]["Sent"] = $sent;
				}
				if (isset($summaryMonth[$year][$month]["Opened"])) {
					$summaryMonth[$year][$month]["Opened"] += $opened;
				} else {
					$summaryMonth[$year][$month]["Opened"] = $opened;
				}
				if (isset($summaryMonth[$year][$month]["Clicked"])) {
					$summaryMonth[$year][$month]["Clicked"] += $clicked;
				} else {
					$summaryMonth[$year][$month]["Clicked"] = $clicked;
				}
			}
			$page++;
		}while(count($results) > 0);

		print "<pre>\n";

		/**
		foreach (array_keys($summaryWeek) as $year) {
			foreach (array_keys($summaryWeek[$year]) as $week) {
				printf("%4d W%02d: %8d - %8d - %3.2f%%\n", $year, $week, $summaryWeek[$year][$week]["Sent"], $summaryWeek[$year][$week]["Opened"], $summaryWeek[$year][$week]["Opened"]/$summaryWeek[$year][$week]["Sent"]*100);
			}
		}
		**/

		print "<table border><tr><th>--";
		foreach (array_keys($summaryMonth) as $year) {
			printf("<th colspan=5>%d</th>", $year);
			foreach (array_keys($summaryMonth[$year]) as $month) {
				$months[$month]=1;
			}
		}
		print "</tr>\n";
		ksort($months);
		foreach (array_keys($months) as $month) {
			print "<tr><th>$month</th>";		
			foreach (array_keys($summaryMonth) as $year) {
				if (isset($summaryMonth[$year][$month]["Sent"])) {
					printf("<td>%8d<td>%8d<td>%3.2f%%<td>%8d<td>%3.2f%%</td>\n", $summaryMonth[$year][$month]["Sent"], $summaryMonth[$year][$month]["Opened"], $summaryMonth[$year][$month]["Opened"]/$summaryMonth[$year][$month]["Sent"]*100, $summaryMonth[$year][$month]["Clicked"], $summaryMonth[$year][$month]["Clicked"]/$summaryMonth[$year][$month]["Sent"]*100);
				} else {
					printf("<td><td><td><td><td></td>\n");
				}
			}
			print "</tr>\n";
		}
		
		print "</table>\n";		
	}
}
echo "</html>\n";

exit;

function processOrders($infusionsoft) {	
	print "<div class='w3-container'>\n";	
	echo "<h1>Order Details</h1>";
	//echo "Methods:\n";
	//$methods=get_class_methods($infusionsoft->orders());
	//print_r($methods);
	$orders = $infusionsoft->orders()->where('since', '2017-08-31T00:00:00.00Z')->where(['limit'=>10])->get();
	printf("<p>Count of orders: %d</p>\n", $orders->count());
	foreach ($orders->toArray() as $order) {
//		printf("Order Date: %s\n", $order->order_date);
//		printf("Title: %s\n", $order->title);
		$contactId=$order->contact['id'];
//		printf("Contact: %s\n", $contactId);
		$contact=$infusionsoft->contacts()->find($contactId);
//		printf("First name: %s\n", $contact->given_name);
//		printf("Last name: %s\n", $contact->family_name);
//		printf("Company name (1): %s\n", $contact->company['company_name']);
				
		$email="";
		$emailAddresses=$contact->email_addresses;
		foreach ($emailAddresses as $email) {
			if ($email["field"] == "EMAIL1") {
				$email=strtolower($email["email"]);
			}
		}
		
		printf("<p>Id: %d Name: %s %s (%s)</p>\n", $contactId, $contact->given_name, $contact->family_name, $email);		
				
		$taxAmount=0;
		$subTotal=0;
		
		//print_r($contact->attributesToArray());
		$orderItems=$order->order_items;
		foreach ($orderItems as $item) {
			$itemName=$item['name'];
			$itemDescription=$item['description'];
			$itemType=$item['type'];
			$itemQuantity=$item['quantity'];
			$itemPrice=$item['price'];
			$itemDiscount=$item['discount'];
			// printf("Item: %s - Qty %d - Price %1.2f - Discount %1.2f - Type %s\n", $itemName, $itemQuantity, $itemPrice, $itemDiscount, $itemType);
			if ($itemType == "Tax") {
				$taxAmount=$itemPrice;
			} else {
				$subTotal+=$itemQuantity*($itemPrice-$itemDiscount);
			}
		}
		printf("<p>Calculations: Total value of order: %4.2f plus %4.2f tax<br>\n", $subTotal, $taxAmount);
		printf("From Infusionsoft: Invoice total: %1.2f</p>\n", $order->total);
		
				
		//print_r($order->attributesToArray());
	}
	//print "Array:\n";
	//print_r($orders->toArray());
	echo '</pre>';
}

function oldProcessOrders($infusionsoft) {		
	echo "<h1>Order Details</h1>";
	echo '<pre>';
	//echo "Methods:\n";
	//$methods=get_class_methods($infusionsoft->orders());
	//print_r($methods);
	$orders = $infusionsoft->orders()->where('since', '2017-08-31T00:00:00.00Z')->get();
	printf("Count of orders: %d\n\n", $orders->count());
	foreach ($orders->toArray() as $order) {
		printf("Order Date: %s\n", $order->order_date);
		printf("Title: %s\n", $order->title);
		$contactId=$order->contact['id'];
		printf("Contact: %s\n", $contactId);
		$contact=$infusionsoft->contacts()->find($contactId);
		printf("First name: %s\n", $contact->given_name);
		printf("Last name: %s\n", $contact->family_name);
		printf("Company name (1): %s\n", $contact->company['company_name']);
		$companyName=$contact->company['company_name'];
		printf("Company name (2): %s\n", $order->shipping_information['company']);
		$companyName=$companyName==""?$order->shipping_information['company']:$companyName;
		printf("Email address: %s\n", $contact->email_addresses[0]['email']);
		$email=$contact->email_addresses[0]['email'];
		$a1=$contact->addresses[0]['line1'];
		$a1p=$a1;
		$a2=$contact->addresses[0]['line2'];
		$a2p=$a2 == ""?"":", " . $a2;
		$a3=$contact->addresses[0]['locality'];
		$a3p=$a3 == ""?"":", " . $a3;
		$a4=$contact->addresses[0]['region'];
		$a4p=$a4 == ""?"":", " . $a4;
		$a5=$contact->addresses[0]['postal_code'];
		$a5p=$a5 == ""?"":", " . $a5;
		$a6=$contact->addresses[0]['country_code'];
		$a6p=$a6 == ""?"":", " . $a6;
		printf("Billing address: %s%s%s%s%s%s\n", $a1p, $a2p, $a3p, $a4p, $a5p, $a6p); 
				
		$lineItemDetails=array();
		$lineItems=array();
		$tax=0;
		$taxAmount=0;
		$subTotal=0;
		$taxRate=0;
		
		//print_r($contact->attributesToArray());
		$orderItems=$order->order_items;
		foreach ($orderItems as $item) {
			$itemName=$item['name'];
			$itemDescription=$item['description'];
			$itemType=$item['type'];
			$itemQuantity=$item['quantity'];
			$itemPrice=$item['price'];
			$itemDiscount=$item['discount'];
			printf("Item: %s - Qty %d - Price %1.2f - Discount %1.2f - Type %s\n", $itemName, $itemQuantity, $itemPrice, $itemDiscount, $itemType);
			$lineItemDetails[]=array("name" => $itemName, "quantity" => $itemQuantity, "type" => $itemType, "price" => $itemPrice, "discount" => $itemDiscount);
			if ($itemType == "Tax") {
				$tax=1;
				$taxAmount=$itemPrice;
			} else {
				$subTotal+=$itemQuantity*($itemPrice-$itemDiscount);
			}
		}
		if ($tax == 1) {
			$taxRate = round(100*$taxAmount/$subTotal);
		}
		foreach ($lineItemDetails as $item) {
			if ($item["type"] != "Tax") {
				$totalAmount=$item["quantity"]*($item["price"]-$item["discount"]);
			}
		}
				
		printf("Invoice total: %1.2f\n", $order->total);
		//print_r($order->attributesToArray());
		printf("\n");
	}
	//print "Array:\n";
	//print_r($orders->toArray());
	echo '</pre>';
}

function printAjaxScript($url) {
?>	
		<!-- including jQuery from the google cdn -->
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>

		<script type="text/javascript">
		// This is our actual script
		$(document).ready(function(){
		    $('a#button').click(function(){
		        $.ajax({
<?php									
printf('		            url: "%s",', $url);
print "\n";
?>
		            type: 'GET',
		            dataType: 'html',
		            success: function (data) {
		                $('#container').html(data);
		            }
		        });
		    });
		});
		
		var timer, delay = 2000;

		timer = setInterval(function(){
	        $.ajax({
	            url: "receiver.php",
	            type: 'GET',
	            dataType: 'html',
	            success: function (data) {
	                $('#container').html(data);
	            }
	        });
		}, delay);
		
		</script>
<?php	
}

function syncRestToMySql($appName, $tableName, $lastUpdate, $fullCriteria, $updateCriteria) {
	// 1. Save the current date and time, as we'll need that later to record when we last updated the MySql table
	$queryStartTime=new DateTime("now");
	
	// 2. Ensure table exists
	createMySqlTable($appName, $tableName);
	
	// 3. Has the table been updated before? If not, populate the whole thing; otherwise just do an update
	debugOut(sprintf("<p>%s table last updated %s</p>", $tableName, $lastUpdate));
	
	if ($lastUpdate == NULL) {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading %s Table:";</script>', $tableName);
		$criteria=$fullCriteria;
	} else {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Updating %s Table:";</script>', $tableName);
		$criteria=$updateCriteria;
	}
	
	// 4. Query the count of everything in the right order to estimate the time it'll take
	$totalPages=0;
	$totalRecords=0;
	foreach ($criteria as $queryData) {
		$count=countRestTableRows($tableName, $queryData);
		$totalPages+=ceil($count/1000)+1;
		$totalRecords+=$count;
	}
	printf("<p>Total pages: %d</p>\n", $totalPages);
	
	// 5. Query everything in the right order, update the MySQL database and update the screen status
	$cumulativePages=0;
	$cumulativeRecords=0;
	foreach ($criteria as $queryData) {
		$page=0;
		do {
			$count=getRestDataQueryPage($appName, $tableName, $queryData, $page);
			$page++;
			$cumulativePages++;
			$cumulativeRecords+=$count;
			printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $cumulativePages, $cumulativeRecords, $totalRecords);
			printf('<script>setProgressBar(%d);</script>' . "\n", 100*$cumulativePages/$totalPages);
		} while($count > 0);
	}
	
	// 6. Update the Date and Time that the table was last updated
	setMySqlLastUpdate($appName, $tableName, $queryStartTime);	
}

function syncAcContactsToMySql($appName, $tableName, $lastUpdate, $fullCriteria, $updateCriteria) {
	global $ac, $startPage;
	// 1. Save the current date and time, as we'll need that later to record when we last updated the MySql table
	$queryStartTime=new DateTime("now");
	
	// 2. Ensure table exists
	createMySqlTable($appName, $tableName);
	createMySqlTable($appName, "EmailAddStatus");
	createMySqlTable($appName, "EmailSentSummary");
	
	// 3. Has the table been updated before? If not, populate the whole thing; otherwise just do an update
	debugOut(sprintf("<p>%s table last updated %s</p>", $tableName, $lastUpdate));
	
	if ($lastUpdate == NULL) {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading %s Table:";</script>', $tableName);
		$criteria=$fullCriteria;
	} else {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Updating %s Table:";</script>', $tableName);
		$criteria=$updateCriteria;
	}
	
	$DEBUG=0;
	
	$page = 1;
	if (isset($startPage)) {
		$page=$startPage;
	}
	$globalSummary=[];
	$contactCount=["total"=>0,"unconfirmed"=>0,"subscribed"=>0,"unsubscribed"=>0,"bounced"=>0];
	$total=0;
	do{
		$itemCount=0;
		printf("<p>Fetching page %d</p>\n", $page);
		$response = $ac->api(sprintf("contact/list?ids=all&sort=id&sort_direction=ASC&page=%d", $page));
		if ((int)$response->success) {
			# print_r($response);
			foreach ($response as $key => $contact) { 
				if ($DEBUG) {
					printf("<hr>\n");
				}
				$hasCorrectTag=0;
				// Ignore status values etc. and only look at actual data returned
				if (is_numeric($key)) { 
					$singleContact=["Id" => $contact->id, "FirstName" => $contact->first_name, "LastName" => $contact->last_name, "Email" => $contact->email, "Phone1" => $contact->phone];
					//printf("<pre>%s</pre>\n", print_r($contact->actions, true));
					$actionCount=["email"=>0,"open"=>0,"click"=>0];
					$latest=["email"=>NULL, "open"=>NULL, "click"=>NULL];
					
					$actionLog=[];
					foreach($contact->actions as $action) {
						$thisDateTime=new DateTime($action->tstamp);
//						$thisDateTime->setTimezone(new DateTimeZone('Europe/London'));
						$actionType=$action->type;
						if (in_array($actionType, ["email", "open", "click"])) {
							if ($latest[$actionType] == NULL) {
								$latest[$actionType]=$thisDateTime;								
							} elseif ($thisDateTime > $latest[$actionType]) {
								$latest[$actionType]=$thisDateTime;
							}
							$detail=$action->text;
							$detail=str_replace("Contact opened campaign - ","",$detail);
							$detail=str_replace("Sent Campaign - ","",$detail);
							$detail=str_replace("Contact clicked a link  in ","",$detail);
							//printf("<pre>Email %s: %s - %s</pre>\n", $actionType, $detail, $thisDateTime->format('Y-m-d H:i:s e'));
							if ($actionType == "email") {
								if (!isset($actionLog[$detail][$actionType])) {
									$actionCount[$actionType]++;
//									$actionLog[$detail][$actionType]=$thisDateTime->format('Y-m-d H:i:s e');
									$actionLog[$detail][$actionType]=$thisDateTime->format('Y-m-d');
								}
							} else {
								if (isset($actionLog[$detail][$actionType])) {
									$actionLog[$detail][$actionType]=$actionLog[$detail][$actionType]+1;
								} else {
									$actionLog[$detail][$actionType]=1;									
									$actionCount[$actionType]++;
								}
							}
						} elseif (in_array($actionType, ["series_enter","series_end","tracking","subscribe","note"])) {
						} else {
							printf("Unknown action: %s - %s<br>\n", $actionType, $thisDateTime->format('Y-m-d H:i:s e'));
						}
					}
					if ($DEBUG) {
						foreach ($actionLog as $k=>$v) {
							printf("ACT: Date sent: %s - %s - Opens: %d - Clicks: %d<br>\n", isset($v["email"])?$v["email"]:"???", $k, isset($v["open"])?$v["open"]:0, isset($v["click"])?$v["click"]:0);
						}
					}
//					printf("<p>Contact ID: %d - Email: %s - Status: %s - Sent %d (latest %s) - Opened %d (latest %s) - Clicked %d (latest %s)</p>\n", $contact->id, $contact->email, $contact->status, $actionCount["email"], $latest["email"]->format('Y-m-d'), $actionCount["open"], $latest["open"]->format('Y-m-d'), $actionCount["click"], $latest["click"]->format('Y-m-d'));
					if ($DEBUG) {
						printf("<p>Contact ID: %d - Email: %s - Status: %s - Sent %d - Opened %d - Clicked %d</p>\n", $contact->id, $contact->email, $contact->status, $actionCount["email"], $actionCount["open"], $actionCount["click"]);
					}
					$contactCount["total"]++;
					$statusType="NonMarketable";
					if ($contact->status == 0) {
						$contactCount["unconfirmed"]++;
						$statusType="NonMarketable";
					} else if ($contact->status == 1) {
						$contactCount["subscribed"]++;
						$statusType="Subscribed";
					} elseif ($contact->status == 2) {
						printf("Unsubscribe reason: %s<br>\n", $contact->unsubreason);
						if (preg_match('/spam/i', $contact->unsubreason)) {
							$statusType="Spam Complaint";
							$contactCount["spam"]++;
						} else {
							$statusType="Opted Out";
							$contactCount["unsubscribed"]++;							
						}
					} elseif ($contact->status == 3) {
						$contactCount["bounced"]++;
					}
					
					$singleStatus=["Id"=>$contact->id, "Email"=>$contact->email, "LastOpenDate"=>$latest["open"], "LastClickDate"=>$latest["click"], "LastSentDate"=>$latest["email"], "Type"=>$statusType];
							
					$doneThis=false;
					for ($attempt = 1; $attempt <= 20 && !$doneThis; $attempt++) {
						try {
							DB::insertUpdate(sprintf("tblEpt%s_%s",$tableName,$appName), $singleContact);
							$doneThis = true;
						} catch(Exception $e) {
							echo 'Caught exception: ',  $e->getMessage(), "\n";
							sleep($attempt*2);
						}
					}
										
					$doneThis=false;
					for ($attempt = 1; $attempt <= 20 && !$doneThis; $attempt++) {
						try {
							DB::insertUpdate(sprintf("tblEptEmailAddStatus_%s",$appName), $singleStatus);
							$doneThis = true;
						} catch(Exception $e) {
							echo 'Caught exception: ',  $e->getMessage(), "\n";
							sleep($attempt*2);
						}
					}
										
					
					$h=$contact->automation_history;
					foreach ($contact->automation_history as $a) {
						foreach ($a->messages as $m) {
							$detail=$m->campaignname;
							$dateSent=new DateTime("$m->sdate" . " -0500");
							$dateSent->setTimezone(new DateTimeZone("Europe/London"));
//							$dateTxt=$dateSent->format("Y-m-d H:i:s e");
							$dateTxt=$dateSent->format("Y-m-d");
							$openCount=count($m->reads);
							$clickCount=count($m->links);
							if (!isset($actionLog[$detail])) {
								$actionLog[$detail]["email"]=$dateTxt;
								$actionLog[$detail]["open"]=$openCount;
								$actionLog[$detail]["click"]=$clickCount;
								if ($DEBUG) {
									print "ALL ADDED: ";
								}
							} elseif (!isset($actionLog[$detail]["email"])) {
								$actionLog[$detail]["email"]=$dateTxt;
								if ($DEBUG) {
									print "SENT DATE ADDED: ";									
								}
							}
							if ($DEBUG) {
								printf("CAM: Date sent: %s - %s - Opens: %d - Clicks: %d<br>\n", $dateTxt, $detail, $openCount, $clickCount);
							}
						}
					}
//					printf("<pre>%s</pre>\n", print_r($h, true));

					$c=$contact->campaign_history;
//					printf("<pre>%s</pre>\n", print_r($c, true));
					foreach ($contact->campaign_history as $c) {
						$detail=$c->campaignname;
						$dateSent=new DateTime("$c->sdate" . " -0500");
						$dateSent->setTimezone(new DateTimeZone("Europe/London"));
//						$dateTxt=$dateSent->format("Y-m-d H:i:s e");
						$dateTxt=$dateSent->format("Y-m-d");
						$openCount=count($c->reads);
						$clickCount=count($c->links);
						if (!isset($actionLog[$detail])) {
							$actionLog[$detail]["email"]=$dateTxt;
							$actionLog[$detail]["open"]=$openCount;
							$actionLog[$detail]["click"]=$clickCount;
							if ($DEBUG) {
								print "ALL ADDED: ";
							}
						} elseif (!isset($actionLog[$detail]["email"])) {
							$actionLog[$detail]["email"]=$dateTxt;
							if ($DEBUG) {
								print "SENT DATE ADDED: ";
							}
						}
						if ($DEBUG) {
							printf("AUT: Date sent: %s - %s - Opens: %d - Clicks: %d<br>\n", $dateTxt, $detail, $openCount, $clickCount);
						}
					}
					
					foreach ($actionLog as $subject) {
						$dateSent=$subject["email"];
						$open=isset($subject["open"])?1:0;
						$click=isset($subject["click"])?1:0;
						$globalSummary[$dateSent]["sent"]=isset($globalSummary[$dateSent]["sent"])?$globalSummary[$dateSent]["sent"]+1:1;
						$globalSummary[$dateSent]["open"]=isset($globalSummary[$dateSent]["open"])?$globalSummary[$dateSent]["open"]+$open:$open;
						$globalSummary[$dateSent]["click"]=isset($globalSummary[$dateSent]["click"])?$globalSummary[$dateSent]["click"]+$click:$click;
						if ($DEBUG) {
							printf("Adding to summary: Date %s - Sent 1 - Open %d - Click %d<br>\n", $dateSent, $open, $click);
						}
					}
					
					
					
					/** Keys:
					
a_unsub_date	2011-03-08
a_unsub_time	14:24:44
actions	Array
adate 
automation_history 
bounced_date	Date of most recent bounce for this contact.
bounced_hard	Number of times this contact has hard-bounced. Example: 0
bounced_soft	Number of times this contact has soft-bounced. Example: 0
bounces	Array
bouncescnt	Number of total bounces for this contact. Example: 0
campaign_history 
cdate	Date subscribed. Example: 2011-03-02 14:47:01
deleted 
edate 
email	Email address of contact. Example: test@testing.com
email_domain 
email_local 
fields	Array
first_name	First name of contact. Example: Name
formid	Subscription form ID used when subscribing. Example: 0
geo
gravatar 
hash	Unique hash for the contact. Example: dfdsfdsfefr345345wfdrs3r
id	ID of the contact. Example: 2
ip	IP address of the contact. Example: 38.104.242.98
ip4 
ip4_last 
ip4_sub 
ip4_unsub 
last_name	Last name of contact. Example: One
lid	1
listid	ID of the list this contact is part of. Example: 1
listname 
lists	Array
listslist	String of lists for this contact. Example: 1
name	Full name of this contact. Example: Name One
orgid	ID of the contact's organization. Example: 1
orgname	Name of the contact's organization. Example: ACME, Inc.
phone 
rating 
rating_tstamp 
responder	1
sdate	Date subscribed. Example: 2011-03-09 09:59:12
sdate_iso 
segmentio_id 
sentcnt 
seriesid 
socialdata_lastcheck	Last time social data was fetched for this contact.
sourceid 
sourceid_autosync 
status	Status for this list. Example: 1 -- Status value: 0: unconfirmed, 1: active, 2: unsubscribed, 3: bounced
subscriberid	ID of the contact. Example: 2
sync	0
tags 
ua 
udate	Date unsubscribed. Example: 2011-03-08 14:24:44
unsubcampaignid	Campaign sent when unsubscribed. Example: 10
unsubmessageid	Message sent when unsubscribed. Example: 1
unsubreason	Unsubscribe reason.
					**/
					
					//$array = json_decode(json_encode($contact), true);
					//foreach ($array as $key=>$value) {
					//	printf("<br>%s\n", $key);
					//}
//					print_r($contact);
					// Status value: 0: unconfirmed, 1: active, 2: unsubscribed, 3: bounced
					//if ($contact->status == 0 || $contact->status == 1) 
					$itemCount++;
					//exit;
				}
			}
		} else {
			if ($response->result_message != "Failed: Nothing is returned") {
				printf("<p>API Error: %s - %s</p>\n", $response->result_code, $response->result_message);
			}
		}
		printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d records";</script>', $page, $contactCount["total"]);
		printf('<script>setProgressBar(%d);</script>' . "\n", 10*((($page-1) % 10)+1));
		$page++;		
	}while($itemCount == 20);
	
	foreach ($globalSummary as $d=>$v) {
		if ($d != "" && substr($d, 0, 1) != "-") {
			printf("Summary: %s - Sent %d - Open %d - Click %d<br>\n", $d, $v["sent"], $v["open"], $v["click"]);
			$batchId=new DateTime($d);
			$batchNumId=$batchId->getTimestamp();
			$result=['MailBatchId'=>$batchNumId, 'DateSent'=>$d, '#Sent'=>$v["sent"], '#Opened'=>$v["open"], '#Clicked'=>$v["click"], '#OptOut'=>0, '#Bounce'=>0, '#ISPSpamComplaints'=>0, '#InternalSpamComplaints'=>0];
			$doneThis=false;
			for ($attempt = 1; $attempt <= 20 && !$doneThis; $attempt++) {
				try {
					DB::insertUpdate(sprintf("tblEpt%s_%s","EmailSentSummary",$appName), $result);
					$doneThis = true;
				} catch(Exception $e) {
					echo 'Caught exception: ',  $e->getMessage(), "\n";
					sleep($attempt*2);
				}
			}
		}
	}
		
	// 6. Update the Date and Time that the table was last updated
	setMySqlLastUpdate($appName, $tableName, $queryStartTime);	
	setMySqlLastUpdate($appName, "EmailAddStatus", $queryStartTime);	
	setMySqlLastUpdate($appName, "EmailSentSummary", $queryStartTime);	
}

//Mail Chimp Sync data from Api
function syncMcContactsToMySql($appName,$mcapikey, $tableName, $lastUpdate, $fullCriteria, $updateCriteria) {
	$lastupd=getAllMySqlLastUpdate($appName);
	if (!empty($lastupd))
	{
		$lastupdatedon = $lastupd [0]['lastComplete'];
		$lastupdatedon =date('Y-m-d', strtotime($lastupdatedon." -90 days"));
	}
	
	
	
	//global $MailChimp;
	// 1. Save the current date and time, as we'll need that later to record when we last updated the MySql table
	$queryStartTime=new DateTime("now");
	// 2. Ensure table exists
	createMySqlTable($appName, $tableName);
	createMySqlTable($appName, "EmailAddStatus");
	createMySqlTable($appName, "EmailSentSummary");
	// 3. Has the table been updated before? If not, populate the whole thing; otherwise just do an update
	/*debugOut(sprintf("<p>%s table last updated %s</p>", $tableName, $lastUpdate));
	if ($lastUpdate == NULL) {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading %s Table:";</script>', $tableName);
		$criteria=$fullCriteria;
	} else {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Updating %s Table:";</script>', $tableName);
		$criteria=$updateCriteria;
	}*/
	$DEBUG=0;
	$page = 1;
	//$globalSummary=[];
	$substatusarr = array();
	$total=0;
	$MailChimp = new MailChimp($mcapikey);
	$result = $MailChimp->get('lists/');
	//var_dump($result);
	// call MC api, go through each list, fetch members and save details in tblEptContact
	foreach($result['lists'] as $res)
	{
		$membercount = $res["stats"]['member_count'].'<br/>';
		$members = $MailChimp->get('lists/'.$res['id'].'/members',array("count"=>$membercount));
		foreach($members['members'] as $member)
		{
			$lastupdated = $member['last_changed'];
			$lastupdated = date('Y-m-d H:i:s', strtotime($lastupdated));
			$singleContact=["Id" =>$member['id'], "FirstName" => $member['merge_fields']['FNAME'], "LastName" => $member['merge_fields']['LNAME'], "Email" => $member['email_address'], "Phone1" => '',"LastUpdated"=>$lastupdated];
			$arrkey=$member['email_address'];
			$substatusarr[$arrkey] = $member['status'];
			DB::insertUpdate(sprintf("tblEpt%s_%s",$tableName,$appName), $singleContact);
		}
	}
	printf('<script>setProgressBar(%d);</script>' . "\n", $page);
	printf("<p>Fetching Contact Data %d</p>\n", $page);
	//call MC api,get campaigns whose status = "sent", for each campaign, run email activity report for each email id and save it in tblEptEmailAddStatus
	$allemailstat = array();
	if (!empty($lastupdatedon))
		$result1 = $MailChimp->get('/campaigns', array("status"=>"sent","since_send_time"=>$lastupdatedon));
	else
		$result1 = $MailChimp->get('/campaigns', array("status"=>"sent"));
	//get page count for pagination loader
	$totalcampcount=1;
	foreach($result1['campaigns'] as $res1)
	{
		$campaign_id = $res1['id'];
		$totalcampcount++;
	}
	$tempcamp = 1;
	foreach($result1['campaigns'] as $res1)
	{
		$campaign_id = $res1['id'];
		$lastsenddate = '';
		$lastsenddate = $res1['send_time'];
		if(!empty($lastsenddate))
			$lastsenddate = date('Y-m-d H:i:s', strtotime($lastsenddate));
		$emailcount = $MailChimp->get("/reports/".$campaign_id."/email-activity");
		$itemcount = $emailcount["total_items"];
		$count = 100;
		$offset = 0;
		//iterate over itemcount till itemcount > 0 and set count and offset values
		while($itemcount > 0)
		{
				$emailactivityreports = $MailChimp->get("/reports/".$campaign_id."/email-activity",array("count"=>$count,"offset"=>$offset));
				foreach($emailactivityreports["emails"] as $emailactivityreport)
				{
					$Id = $emailactivityreport["email_id"];
					$Emailid = $emailactivityreport["email_address"];
					$statustype = '';
					foreach($substatusarr as $arrkey=>$val)
					{
						if($arrkey == $Emailid)
						{
							$statustype = $val;
							break;
						}
					}
					$lastopendate = '';
					$lastclickdate = '';
					foreach($emailactivityreport["activity"] as $activity)
					{
						$action = '';
						$action = $activity["action"];
						$actionon = '';
						$actionon = $activity["timestamp"];
						if(!empty($actionon))
							$actionon = date('Y-m-d H:i:s', strtotime($actionon));
						if($action == 'open')
							$lastopendate = $actionon;
						if($action == 'click')
							$lastclickdate = $actionon;
						
					}
					if(!empty($lastopendate) && !empty($lastclickdate))
							$singleStatus=["Id"=>$Id, "Email"=>$Emailid, "LastOpenDate"=>$lastopendate, "LastClickDate"=>$lastclickdate, "LastSentDate"=>$lastsenddate, "Type"=>$statustype];
						if(!empty($lastopendate) && empty($lastclickdate))
							$singleStatus=["Id"=>$Id, "Email"=>$Emailid, "LastOpenDate"=>$lastopendate, "LastClickDate"=>NULL, "LastSentDate"=>$lastsenddate, "Type"=>$statustype];
						if(empty($lastopendate) && !empty($lastclickdate))
							$singleStatus=["Id"=>$Id, "Email"=>$Emailid, "LastOpenDate"=>NULL, "LastClickDate"=>$lastclickdate, "LastSentDate"=>$lastsenddate, "Type"=>$statustype];
						if(empty($lastclickdate) && empty($lastclickdate) )
							$singleStatus=["Id"=>$Id, "Email"=>$Emailid, "LastOpenDate"=>NULL, "LastClickDate"=>NULL, "LastSentDate"=>$lastsenddate, "Type"=>$statustype];//Once data is fetched for an email id, check if this email already exists in $allemailstat
					//If yes, then update LastOpenDate, LastClickDate, LastSentDate
					//If No, insert $singleStatus array to $allemailstat
					
					$existsflag = 0;
					foreach($allemailstat as &$emailstat)
					{
						if($emailstat['Id'] == $singleStatus['Id'])
						{
							$existsflag = 1;
							if(!empty($singleStatus['LastOpenDate']) && ($singleStatus['LastOpenDate'] > $emailstat['LastOpenDate']))
								$emailstat['LastOpenDate'] = $singleStatus['LastOpenDate'];
							if(!empty($singleStatus['LastClickDate']) && ($singleStatus['LastClickDate'] > $emailstat['LastClickDate']))
								$emailstat['LastClickDate'] = $singleStatus['LastClickDate'];
							if(!empty($singleStatus['LastSentDate']) && ($singleStatus['LastSentDate'] > $emailstat['LastSentDate']))
								$emailstat['LastSentDate'] = $singleStatus['LastSentDate'];
							break;
						}
					}
					if($existsflag == 0)
					{
						array_push($allemailstat,$singleStatus);
					}
					
				}
				$itemcount = $itemcount - $count;
				 if($itemcount > $count)
					$offset = $offset + $count;
				 else
				 {
				 	$offset = $offset + $count;
					$count = $itemcount;
				}
		}
		$tempcamp++;
		printf('<script>setProgressBar(%d);</script>' . "\n", ($tempcamp/$totalcampcount)*100);
		printf("<p>Fetching Email Activity Data %d</p>\n", ($tempcamp/$totalcampcount)*100);
		
	}
	
	foreach($allemailstat as $emailstat)
	{
		DB::insertUpdate(sprintf("tblEptEmailAddStatus_%s",$appName), $emailstat);
	}
	$page = 1;
	//call MC api,get campaigns whose status = "sent", for each campaign, run reports api call for each campaign and save it in tblEptEmailSentSummary
	//DO not call for campaigns list again, use the one from previous call for tblEptEmailAddStatus
	foreach($result1['campaigns'] as $res1)
	{
		$campaign_id = $res1['id'];
		$campaignreports = $MailChimp->get("/reports/".$campaign_id."/");
		$lastsenddate = $campaignreports['send_time'];
		if(!empty($lastsenddate))
			$lastsenddate = date('Y-m-d H:i:s', strtotime($lastsenddate));
		//echo 'Date Sent : '.$lastsenddate.'<br/>';
		//echo 'Emails Sent : '.$campaignreports["emails_sent"].'<br/>';
		//echo 'Emails Opened : '.$campaignreports["opens"]["unique_opens"].'<br/>';
		//echo 'Emails Clicked : '.$campaignreports["clicks"]["unique_clicks"].'<br/>';
		//echo 'Opt Out : '.$campaignreports["unsubscribed"].'<br/>';
		//echo 'Bounced : '.($campaignreports["bounces"]["hard_bounces"] + $campaignreports["bounces"]["soft_bounces"]+$campaignreports["bounces"]["syntax_errors"]).'<br/>';
		//echo 'InternalSpamComplaints : '.$campaignreports["abuse_reports"].'<br/>';
		$singleStatus=["MailBatchId"=>$campaign_id, "DateSent"=>$lastsenddate, "#Sent"=>$campaignreports["emails_sent"], "#Opened"=>$campaignreports["opens"]["unique_opens"],
		"#Clicked"=>$campaignreports["clicks"]["unique_clicks"], "#OptOut"=>$campaignreports["unsubscribed"], "#Bounce"=>($campaignreports["bounces"]["hard_bounces"] + $campaignreports["bounces"]["soft_bounces"]+$campaignreports["bounces"]["syntax_errors"]),
		"#ISPSpamComplaints"=>0, "#InternalSpamComplaints"=>$campaignreports["abuse_reports"]];
		DB::insertUpdate(sprintf("tblEpt%s_%s","EmailSentSummary",$appName), $singleStatus);
	}
	printf('<script>setProgressBar(%d);</script>' . "\n", $page);
	printf("<p>Fetching Email Summary Data %d</p>\n", $page);
	// 6. Update the Date and Time that the table was last updated
	setMySqlLastUpdate($appName, $tableName, $queryStartTime);	
	setMySqlLastUpdate($appName, "EmailAddStatus", $queryStartTime);	
	setMySqlLastUpdate($appName, "EmailSentSummary", $queryStartTime);
	
}

function syncXmlToMySql($appName, $tableName, $lastUpdate, $fullCriteria, $updateCriteria) {
	// 1. Save the current date and time, as we'll need that later to record when we last updated the MySql table
	$queryStartTime=new DateTime("now");
	
	// 2. Ensure table exists
	createMySqlTable($appName, $tableName);
	
	// 3. Has the table been updated before? If not, populate the whole thing; otherwise just do an update
	debugOut(sprintf("<p>%s table last updated %s</p>", $tableName, $lastUpdate));
	
	if ($lastUpdate == NULL) {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading %s Table:";</script>', $tableName);
		$criteria=$fullCriteria;
	} else {
		printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Updating %s Table:";</script>', $tableName);
		$criteria=$updateCriteria;
	}
	
	// 4. Query the count of everything in the right order to estimate the time it'll take
	$totalPages=0;
	$totalRecords=0;
	foreach ($criteria as $queryData) {
		$count=countXmlTableRows($tableName, $queryData);
		$totalPages+=ceil($count/1000)+1;
		$totalRecords+=$count;
	}
	printf("<p>Total pages: %d</p>\n", $totalPages);
	
	// 5. Query everything in the right order, update the MySQL database and update the screen status
	$cumulativePages=0;
	$cumulativeRecords=0;
	foreach ($criteria as $queryData) {
		$page=0;
		do {
			$count=getXmlDataQueryPage($appName, $tableName, $queryData, $page);
			$page++;
			$cumulativePages++;
			$cumulativeRecords+=$count;
			printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $cumulativePages, $cumulativeRecords, $totalRecords);
			printf('<script>setProgressBar(%d);</script>' . "\n", 100*$cumulativePages/$totalPages);
		} while($count > 0);
	}
	
	// 6. Update the Date and Time that the table was last updated
	setMySqlLastUpdate($appName, $tableName, $queryStartTime);	
}

function readRequest($param, $default) {
        if (isset($_REQUEST[$param])) {
                return $_REQUEST[$param];
        } else {
                return $default;
        }
}

function terminateHtml() {
	printf("</body>\n</html>\n");
	exit;
}

/**

	OLD CODE FROM ENGAGEMENT REPORT 
	
	$startTime=time();
	
	$esPage=0;
				
	do {
		$results=$infusionsoft->data()->query($table, 1000, $esPage, $queryData, array("Email","EmailAddress2","EmailAddress3","Id"), $orderBy, $ascending);
	
		foreach($results as $result) {
			$count++;
			
			$now=time();
			
			// print_r($result);
			
			$id=$result["Id"];
			
			if (isset($result["Email"])) {
				$email1=strtolower($result["Email"]);
			} else {
				$email1="--";
			}
			if (isset($result["EmailAddress2"])) {
				$email2=strtolower($result["EmailAddress2"]);
			} else {
				$email2="--";
			}
			if (isset($result["EmailAddress3"])) {
				$email3=strtolower($result["EmailAddress3"]);
			} else {
				$email3="--";
			}
						
			$opt1=$optStatus[$email1];
			$opt2=$optStatus[$email2];
			$opt3=$optStatus[$email3];
			
			$daysSinceSent=$daysSinceSentArray[$email1];
			$lastSent=$lastSentDates[$email1];
			if ($daysSinceSent > $daysSinceSentArray[$email2]) {
				$daysSinceSent = $daysSinceSentArray[$email2];
				$lastSent = $lastSentDates[$email2];
			}
			if ($daysSinceSent > $daysSinceSentArray[$email3]) {
				$daysSinceSent = $daysSinceSentArray[$email3];
				$lastSent = $lastSentDates[$email3];
			}
			
			$daysSinceOpen=$daysSinceOpenArray[$email1];
			$lastOpen=$lastOpenDates[$email1];
			if ($daysSinceOpen > $daysSinceOpenArray[$email2]) {
				$daysSinceOpen = $daysSinceOpenArray[$email2];
				$lastOpen = $lastOpenDates[$email2];
			}
			if ($daysSinceOpen > $daysSinceOpenArray[$email3]) {
				$daysSinceOpen = $daysSinceOpenArray[$email3];
				$lastOpen = $lastOpenDates[$email3];
			}
			
			$daysSinceClick=$daysSinceClickArray[$email1];
			$lastClick=$lastClickDates[$email1];
			if ($daysSinceClick > $daysSinceClickArray[$email2]) {
				$daysSinceClick = $daysSinceClickArray[$email2];
				$lastClick = $lastClickDates[$email2];
			}
			if ($daysSinceClick > $daysSinceClickArray[$email3]) {
				$daysSinceClick = $daysSinceClickArray[$email3];
				$lastClick = $lastClickDates[$email3];
			}
			
			foreach (array(7, 30, 60, 90, 180, 365, 9999) as $days) {
				if ($daysSinceSent <= $days) {
					$sentCount[$days]++;
				}
				if ($daysSinceOpen <= $days) {
					$openCount[$days]++;
				}
				if ($daysSinceClick <= $days) {
					$clickCount[$days]++;
				}
			}
			
			
			
			// printf("Email %-40.40s - Sent %8s - Open %8s - Click %8s\n", $emailAddr, $lastSentDate, $lastOpenDate, $lastClickDate);
			
		}
		$esPage+=1;

		printf("Page %d - Fetched %d/%d records\n", $esPage, $count, $total);

	} while((count($results) > 0));
	
**/

function htmlHeadTemplate() {
	
?>
<!DOCTYPE html>
<html>
<title>Email Power Tools</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link href="https://fonts.googleapis.com/css?family=Exo+2:100,200,300,400,500,600,700,800,900" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
html, body, h1, h2, h3, h4, h5 {
	font-family : 'Exo 2', 'Open Sans', sans-serif;
	font-weight : 300;
}
strong {
	font-weight : 700;
}
.ept-dash-container {
	float:left;
	width:49.99999%;
	padding:8px
}
.ept-dash-icon {
	font-size:36px!important
}
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input {display:none;}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

.switchLabel {
	position: relative;
    top: -11px;
    left: 10px;
}

.apiForm label {
	display: block;
	margin-bottom: 8px;
}


.apiForm input {
	width: 24%;
}

.apiConfig button {
	margin-top: 15px;
	background-color: #2196F3!important;
	color: #fff;
	border: none;
	padding: 5px 10px;
	cursor: pointer;
	display: block;
	margin-left: 95px;
}

.apiConfig button i {
	margin-right: 5px;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

.configSaveBtn:disabled {
	background-color: #7bc2fb!important;
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
.ept-dash-icon {
	font-size:36px!important
}

.configMessage {
	margin-top: 30px;
	padding: 8px;
	font-size: 14px;
	width: 24%;
}

.configMessage p {
	margin: 0;
}

.loader {
 	position: relative;
  	top: 4px;
  	border: 2px solid #dadada;
  	border-radius: 50%;
  	border-top: 2px solid #3498db;
  	width: 20px;
  	height: 20px;
  	-webkit-animation: spin 2s linear infinite; /* Safari */
  	animation: spin 2s linear infinite;
  	display: inline-block;
  	margin-right: 3px;
}

.errorMsg {
	width: 23%;
    background: #f44336!important;
    padding: 7px;
    color: #fff;
}

.txtApiKey {
	border: 1px solid #a9a9a9;
	padding: 5px;
}

.txtApiKey.error {
	border: 1px solid #f44336;
}

.toggleConfig:disabled   + .slider {
    background: #b5c1ca;
}


/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (min-width:601px) {
	.ept-dash-container {
		width:33.33333%
	}
	.ept-dash-icon {
		font-size:36px!important
	}
}
@media (min-width:801px) {
	.ept-dash-container {
		width:19.99999%
	}
	.ept-dash-icon {
		font-size:36px!important
	}
}

@media (max-width:992px) {
	.ept-animate-left {
		position:relative;
		animation:animateleft 0.4s
	}
}

@keyframes animateleft {
	from {
		left:-300px;
		opacity:0
	} 
	to {
		left:0;
		opacity:1
	}
}

@media (min-width:993px) {
	.ept-dash-container {
		width:33.33333%
	}
	.ept-dash-icon {
		font-size:36px!important
	}
}

@media (min-width:1280px) {
	.ept-dash-container {
		width:19.999%
	}
	.ept-dash-icon {
		font-size:48px!important
	}
}

</style>
<body class="w3-light-grey">
<?php
	
}

function htmlScripts() {
?>
	<script>
	function ShowAuth(url) {
	      var newWindow = window.open(url, "name", "height=600,width=450");
	      if (window.focus) {
	        newWindow.focus();
	      }
	    }
		function toggleDivById(id) {
		    var div = document.getElementById(id);
		    div.style.display = div.style.display == "none" ? "block" : "none";
		}
		function toggleDivByClass(cl){
		   var els = document.getElementsByClassName(cl);
		   for(var i=0; i<els.length; ++i){
		      var s = els[i].style;
		      s.display = s.display==='none' ? 'block' : 'none';
		   };
		}
		function replaceTextInDiv(id, strtoadd) {
			var div = document.getElementById(id);
			div.innerHTML = strtoadd;
		}
		function addTextToDiv(id, strtoadd) {
			var div = document.getElementById(id);
			div.innerHTML += strtoadd;
		}
		function w3_open() {
		    document.getElementById("mySidebar").style.display = "block";
		}
		function w3_close() {
		    document.getElementById("mySidebar").style.display = "none";
		}
	</script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
	<script type="text/javascript">
		function subscrideDateController(radButton)
		{
			
			switch (radButton) 
			{ 
				case 1    : 
                        document.frmDateRange.firstClick.type  = 'hidden'; 
						document.frmDateRange.lastClick.type  = 'hidden';  
                        document.getElementById('earliestDateLabel').style.display = 'none';
                        document.getElementById('latestDateLabel').style.display = 'none';
                        document.getElementById('firstClickValue').value="0";
                        //alert(document.getElementById('firstClickValue').value);
                        //firstClickValue
						break; 
				case 2    : 
                        document.frmDateRange.firstClick.type  = 'date'; 
                        document.frmDateRange.lastClick.type  = 'date'; 
                        document.getElementById('earliestDateLabel').style.display = 'inline';
                        document.getElementById('latestDateLabel').style.display = 'inline';
                        document.getElementById('firstClickValue').value="1";
                        //alert(document.getElementById('firstClickValue').value);
						break;  
                case 3    : 
						document.frmDateRange.earliestOrder.type  = 'hidden'; 
						document.frmDateRange.latestOrder.type  = 'hidden'; 
                        document.getElementById('latestOrderLabel').style.display = 'none';
                        document.getElementById('earliestOrderLabel').style.display = 'none'; 
                        document.getElementById('earliestClickValue').value="0";                           
						break;
                case 4    : 
						document.frmDateRange.earliestOrder.type  = 'date'; 
						document.frmDateRange.latestOrder.type  = 'date';
                        document.getElementById('latestOrderLabel').style.display = 'inline';
                        document.getElementById('earliestOrderLabel').style.display = 'inline'; 
                        document.getElementById('earliestClickValue').value="1";                             
						break;                                                   
				default    : 
						alert('What to do?');  
			}
			
		} 		
	</script>		
<?php
	
}

function htmlTopAndSideTemplate($debug=false) {
?>
<!-- Top container -->
<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <button class="w3-bar-item w3-button w3-hide-large w3-hover-none w3-hover-text-light-grey" onclick="w3_open();"><i class="fa fa-bars"></i> Menu</button>
  <span class="w3-bar-item w3-right"><strong>Email Power Tools</strong> | <strong><a href="logout.php">Logout</a></strong></span>
</div>

<?php
	printf("<div class=\"w3-sidebar w3-bar-block w3-card\" style=\"width:25%%;right:0;display:%s;\">\n", $debug?"block":"none");
?>
  <div id="debugWindow" class="w3-container" style="padding-bottom: 60px;">
    <h5>Debug</h5>
	<div id="debugDiv" style="font-size:8px"></div>
  </div>
</div>
<?php
}	

function htmlMenuTemplate($firstName, $lastName, $email, $platform, $appName) {	
	global $infusionsoft;
	global $superUser;
	global $op;
?>  
<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-collapse w3-white ept-animate-left" style="z-index:3;width:300px" id="mySidebar"><br>
  <div class="w3-container w3-row">
    <div class="w3-col s4">
<?php	
	$gUrl="https://w3schools.com/w3images/avatar2.png";
	$gUrl=getGravatar($email);
	printf('      <img src="%s" alt="Gravatar Image" class="w3-circle w3-margin-right" style="width:46px">'."\n", $gUrl);
?>	  
    </div>
    <div class="w3-col s8 w3-bar">
<?php		
	printf("      <span>Welcome, <strong>%s</strong></span><br>\n", $firstName);
	if ($platform == "ISFT") {
		printf("      <span>App: %s (", $appName);
		print '<a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Change</a>';
		print ")</span><br>\n";	
	} elseif ($platform == "AC") {
		printf("      <span>App: %s (", $appName);
		print '<a href="?op=acSetApi">Change</a>';
		print ")</span><br>\n";			
	}
	elseif ($platform == "MC") {
		printf("      <span>App: %s (", $appName);
		print '<a href="?op=mcSetApi">Change</a>';
		print ")</span><br>\n";			
	}
?>		  
	<!--
      <a href="#" class="w3-bar-item w3-button"><i class="fa fa-envelope"></i></a>
      <a href="#" class="w3-bar-item w3-button"><i class="fa fa-user"></i></a>
      <a href="#" class="w3-bar-item w3-button"><i class="fa fa-cog"></i></a>
	-->
    </div>
  </div>
  <hr>
  <div class="w3-container">
    <h5>Dashboard</h5>
  </div>
  <div class="w3-bar-block">
    <a href="#" class="w3-bar-item w3-button w3-padding-16 w3-hide-large w3-dark-grey w3-hover-black" onclick="w3_close()" title="close menu"><i class="fa fa-remove fa-fw"></i> Close Menu</a>
<?php	
	printf('    <a href="?" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-users fa-fw"></i> Overview</a>' . "\n", $op==""?" w3-blue":"");
	if ($platform == "ISFT") {
		printf('    <a href="?op=showstatus" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-eye fa-fw"></i> Show Status</a>' . "\n", $op=="showstatus"?" w3-blue":"");
	}
	printf('    <a href="?op=emailEngagementReport" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-envelope-o fa-fw"></i> Email Engagement Report</a>' . "\n", $op=="emailEngagementReport"?" w3-blue":"");
	printf('    <a href="?op=emailOpenReport" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-envelope-o fa-fw"></i> Email Open Report</a>' . "\n", $op=="emailOpenReport"?" w3-blue":"");
	if ($platform == "ISFT") {
		printf('    <a href="?op=lostCustomersReport" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-dollar fa-fw"></i> Lost Customers Report</a>' . "\n", $op=="lostCustomersReport"?" w3-blue":"");
		printf('    <a href="?op=lostCLV" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-dollar fa-fw"></i> Lost Customer Lifetime Value</a>' . "\n", $op=="lostCLV"?" w3-blue":"");
	}
	printf('    <a href="?op=updateContactData" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-database fa-fw"></i> Update Contact Data</a>' . "\n", $op=="updateContactData"?" w3-blue":"");
	if ($platform == "ISFT") {
		printf('    <a href="?op=updateOpportunityData" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-handshake-o fa-fw"></i> Update Opportunity Data</a>' . "\n", $op=="updateOpportunityData"?" w3-blue":"");
		printf('    <a href="?op=updateEmailOpenData" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-database fa-fw"></i> Update Historical Email Open Data</a>' . "\n", $op=="updateEmailOpenData"?" w3-blue":"");
		printf('    <a href="?op=kleanApiConfig" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-cog fa-fw"></i> Klean13 Integration</a>' . "\n", $op=="kleanApiConfig"?" w3-blue":"");
		printf('<a href="?op=kleanOnDemandScrub" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-cog fa-fw"></i>  Klean13 On-Demand Scrub</a>' . "\n", $op=="kleanOnDemandScrub"?" w3-blue":"");
		printf('<a href="?op=weDeliverEmailSettings" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-cog fa-fw"></i> WeDeliver Email settings</a>' . "\n", $op=="weDeliverEmailSettings"?" w3-blue":"");
	}
	if ($platform == "AC") {
		printf('    <a href="?op=acSetApi" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-cog fa-fw"></i> API Settings</a>' . "\n", $op=="acSetApi"?" w3-blue":"");
		printf('    <a href="?op=kleanApiConfig" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-cog fa-fw"></i>  Klean13 Integration</a>' . "\n", $op=="kleanApiConfig"?" w3-blue":"");
	}
	if ($platform == "MC") {
		printf('    <a href="?op=mcSetApi" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-cog fa-fw"></i> API Settings</a>' . "\n", $op=="mcSetApi"?" w3-blue":"");		
	}
	if (isset($superUser)) {
		printf('    <a href="?op=admin" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-gears fa-fw"></i> Admin Settings</a>' . "\n", $op=="admin"?" w3-blue":"");				
		printf('    <a href="?op=listresthooks" class="w3-bar-item w3-button w3-padding %s"><i class="fa fa-flash fa-fw"></i> List RESThooks</a>' . "\n", $op=="listresthooks"?" w3-blue":"");				
	}
?>	
<!--
    <a href="?op=emailSentReport" class="w3-bar-item w3-button w3-padding"><i class="fa fa-envelope-o fa-fw"></i> Email Sent Report</a>
    <a href="?reset=true" class="w3-bar-item w3-button w3-padding"><i class="fa fa-remove fa-fw"></i> Reset Authorisation</a>
    <a href="#" class="w3-bar-item w3-button w3-padding"><i class="fa fa-history fa-fw"></i> History</a>
    <a href="?op=listorders" class="w3-bar-item w3-button w3-padding"><i class="fa fa-dollar fa-fw"></i> List Orders</a>
    <a href="#" class="w3-bar-item w3-button w3-padding"><i class="fa fa-cog fa-fw"></i> Settings</a><br><br>
	-->
  </div>
</nav>

<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>
<?php
}
	
function htmlPageTemplate($numContacts, $numOptedIn, $numOptedOut, $numComplained, $numBounced) {	
?>
<!-- !PAGE CONTENT! -->
<!-- Right margin of 25% is to support debug sidebar -->
<!-- <div class="w3-main" style="margin-left:300px;margin-top:43px;margin-right:25%"> -->
<div class="w3-main" style="margin-left:300px;margin-top:43px">
<!-- <div class="w3-main" style="margin-left:300px"> -->

  <!-- Header -->
  <header class="w3-container" style="padding-top:10px">
    <h5><b><i class="fa fa-dashboard"></i> Dashboard</b></h5>
  </header>

  <div class="w3-row-padding w3-margin-bottom">
    <div class="ept-dash-container">
      <div class="w3-container w3-blue w3-padding-16">
        <div class="w3-left"><i class="fa fa-vcard-o ept-dash-icon"></i></div>
        <div class="w3-right">
<?php			
printf("          <h3 style='margin-top: 0px' id='numContacts'>%s</h3>\n", $numContacts);
?>		  
        </div>
        <div class="w3-clear"></div>
        <h4>Contacts</h4>
      </div>
    </div>
    <div class="ept-dash-container">
      <div class="w3-container w3-teal w3-padding-16">
        <div class="w3-left"><i class="fa fa-check-square-o ept-dash-icon"></i></div>
        <div class="w3-right">
<?php			
printf("          <h3 style='margin-top: 0px' id='numOptedIn'>%s</h3>\n", $numOptedIn);
?>		  
        </div>
        <div class="w3-clear"></div>
        <h4>Marketable</h4>
      </div>
    </div>
    <div class="ept-dash-container">
      <div class="w3-container w3-yellow w3-padding-16">
        <div class="w3-left"><i class="fa fa-thumbs-o-down ept-dash-icon"></i></div>
        <div class="w3-right">
<?php			
printf("          <h3 style='margin-top: 0px' id='numOptedOut'>%s</h3>\n", $numOptedOut);
?>		  
        </div>
        <div class="w3-clear"></div>
        <h4>Opted Out</h4>
      </div>
    </div>
    <div class="ept-dash-container">
      <div class="w3-container w3-orange w3-text-white w3-padding-16">
        <div class="w3-left"><i class="fa fa-remove ept-dash-icon"></i></div>
        <div class="w3-right">
<?php			
printf("          <h3 style='margin-top: 0px' id='numBounced'>%s</h3>\n", $numBounced);
?>		  
        </div>
        <div class="w3-clear"></div>
        <h4>Bounced</h4>
      </div>
    </div>
    <div class="ept-dash-container">
      <div class="w3-container w3-red w3-text-white w3-padding-16">
        <div class="w3-left"><i class="fa fa-exclamation-triangle ept-dash-icon"></i></div>
        <div class="w3-right">
<?php			
printf("          <h3 style='margin-top: 0px' id='numComplained'>%s</h3>\n", $numComplained);
?>		  
        </div>
        <div class="w3-clear"></div>
        <h4>Reported Spam</h4>
      </div>
    </div>
  </div>
<?php

}

function htmlMinimalPage() {
?>
<!-- !PAGE CONTENT! -->
<!-- Right margin of 25% is to support debug sidebar -->
<div class="w3-main" style="margin-left:300px;margin-top:43px;margin-right:25%">
<?php	
}

function htmlTerminate() {
?>  
</div>
</body>
</html>
<?php
exit;
}

function tellThemToLogIn($message="It doesn't look like you've used Email Power Tools from this browser session before.") {
?>	
<div class="w3-main" style="margin-left:300px;margin-top:43px;margin-right:25%">
  <div class="w3-container" style="padding-top:10px">	
<?php
	print "<p>" . $message . "<p>\n";
	$fb = new Facebook\Facebook([
	//    'app_id' => '1800827109959122',
	//    'app_secret' => 'd16608ffc0e1073c9820c4cadb434e08',
	    'app_id' => '177981752936708',
	    'app_secret' => '2c96617ecbae0925cc5ed9f17bf7b59c',
	    'default_graph_version' => 'v2.11',
	]);	
    $helper = $fb->getRedirectLoginHelper();
	$permissions = ['email']; // optional
	$loginUrl = $helper->getLoginUrl('https://wdem.wedeliver.email/login.php', $permissions);
	print '<p><a href="' . $loginUrl . '"><img src="/images/fb-button.png" alt="Continue with Facebook"></a></p>';
	print "</div>\n";
	htmlTerminate();
}

function showAcSettingsPage($appName="", $apiKey="", $message="Please enter your ActiveCamapign account name and API key:", $button="Submit", $terminate=true) {
	print "<div class='w3-container'>\n";
	print "<h2>Welcome to Email Power Tools</h2>\n";
	printf("<h3>%s</h3>\n", $message);
	printf("<form method=\"POST\" action=\"%s?op=acSetApi\">\n", $_SERVER['PHP_SELF']);
	printf("<table class=\"w3-table\">\n<tr>\n<th>Account Name</th>\n<td><input type=\"text\" name=\"acAccount\" value=\"%s\"></td>\n</tr>\n", $appName);
	printf("<tr>\n<th>API Key</th>\n<td><input type=\"text\" name=\"acApiKey\" value=\"%s\"></td>\n</tr>\n</table>\n", $apiKey);
	printf("<input type=\"submit\" value=\"%s\" class=\"w3-button w3-blue w3-round\">\n</div>\n", $button);
	if ($terminate) {
		htmlTerminate();		
	}
}
function showMcSettingsPage($appName="", $apiKey="", $message="Please enter your MailChimp account name and API key:", $button="Submit", $terminate=true) {
	print "<div class='w3-container'>\n";
	print "<h2>Welcome to Email Power Tools</h2>\n";
	printf("<h3>%s</h3>\n", $message);
	printf("<form method=\"POST\" action=\"%s?op=mcSetApi\">\n", $_SERVER['PHP_SELF']);
	//printf("<table class=\"w3-table\">\n<tr>\n<th>Account Name</th>\n<td><input type=\"text\" name=\"mcAccount\" value=\"%s\"></td>\n</tr>\n", $appName);
	print("<table class=\"w3-table\">\n");
	printf("<tr>\n<th>API Key</th>\n<td><input type=\"text\" name=\"mcApiKey\" value=\"%s\"></td>\n</tr>\n</table>\n", $apiKey);
	printf("<input type=\"submit\" value=\"%s\" class=\"w3-button w3-blue w3-round\">\n</div>\n", $button);
	if ($terminate) {
		htmlTerminate();		
	}
}

function printProgressBarContainer() {	
?>
<script>
function setProgressBar(percent) {
    var elem = document.getElementById("progressBar"); 
    elem.style.width = percent + '%'; 
}
</script>
<p id="eeProgressContainer" style="align:center">
	<span id="pleaseWait">Please Wait:</span> <span id="eeProgressTitle"><!-- empty to start with --></span>&nbsp;<span id="eeProgressDetail"><!-- empty to start with --></span>
</p>
<div class="w3-dark-grey w3-round-xlarge" style="padding:0px">
	<div id="progressBar" class="w3-container w3-blue w3-round-xlarge" style="padding:0px;height:25px;width:0%"></div>
</div>
<?php
}

function printGoogleChartsJS() {
?>
<script type="text/javascript"
	src="https://www.google.com/jsapi?autoload={
		'modules':[{
			'name':'visualization',
			'version':'1',
			'packages':['corechart']
		}]
	}">
</script>
<?php
}

function debugPrintR($var) {
	debugOut("<pre>".str_replace("\n","",nl2br(print_r($var,true)))."</pre>");
}

function debugOut($txt) {
	global $debug;
	if ($debug) {
		printf('<script>addTextToDiv("debugDiv",\'%s\'); window.scrollTo(0, document.getElementById("debugWindow").scrollHeight);</script>%s', $txt, "\n");
	}
}


/** Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source https://gravatar.com/site/implement/images/php/
 */


function getGravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
	$url = 'https://www.gravatar.com/avatar/';
	$url .= md5( strtolower( trim( $email ) ) );
	$url .= "?s=$s&d=$d&r=$r";
	if ( $img ) {
		$url = '<img src="' . $url . '"';
		foreach ( $atts as $key => $val )
		$url .= ' ' . $key . '="' . $val . '"';
		$url .= ' />';
	}
	return $url;
}

function updateDbWithInfusionsoftToken ($userId, $token) {
	// Update Infusionsoft Information stored in Database
	$scopeArray=explode("|", $token->extraInfo["scope"]);
	$appName=substr($scopeArray[1], 0, strpos($scopeArray[1], "."));
	DB::insertUpdate("tblEptUsers", array(
		'userId' => $userId,
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

function getInfusionsoftTokenFromDb($userId) {
	$userDetails=DB::queryFirstRow("select userId, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from eptdb.tblEptUsers where userId=%d", $userId);
	$token=new \Infusionsoft\Token(array(
		'access_token' => $userDetails['accessToken'],
		'refresh_token' => $userDetails['refreshToken'],
		'expires_in'    => $userDetails['expiresAt']-time(),
		'token_type' => $userDetails['tokenType'],
		'scope' => sprintf("%s|%s", $userDetails['scope'], $userDetails['appDomainName'])
	));
	return $token;
}

function doRefreshAccessToken($infusionsoft, $userId, $appName, $token) {
	// First get the latest version of the token from the database in case someone else has updated it
	$token=getInfusionsoftTokenFromDb($userId);
	$infusionsoft->setToken($token);
	$token=$infusionsoft->refreshAccessToken();
	updateDbWithInfusionsoftToken($userId, $token);
	return $token;
}

function setupRestHook($infusionsoft, $eventKey, $hookUrl) {
	$resthooks = $infusionsoft->resthooks();
	$resthook = $resthooks->create([
		'eventKey' => $eventKey,
		'hookUrl' => $hookUrl
	]);
	var_dump($resthook);
	$resthook = $resthooks->find($resthook->id)->verify();
	return $resthook;	
}

?>
