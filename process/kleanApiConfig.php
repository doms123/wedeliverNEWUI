<?php 
 $uid = $_SESSION['uid'];

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
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8", 'tblEptKlean13', "Not Set");

 ##### MAKE SURE THAT tblEptKlean13Email API TABLE EXIST #####
 DB::query("CREATE TABLE IF NOT EXISTS tblEptKlean13Email (
     `Id` bigInt(20) NOT NULL AUTO_INCREMENT,
     `email` varchar(255) NOT NULL,
     `emailStatus` varchar(255) NOT NULL,
     `DateCreated` DateTime,
     `LastLookUp` DateTime,
     PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8", "Not Set");

 ##### GET THE USER DETAILS #####
 if (isset($_SESSION['uid'])) {
     $userDetails = DB::queryFirstRow("SELECT sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash FROM tblEptSessions s, tblEptUsers u WHERE s.userId=u.userId AND u.userId=%s", $_SESSION['uid']);
 }else {
     $userDetails = array('success' => 0);
     echo 'Error';
 }

 $userId = $userDetails['userId'];
 $tblEptKlean13 = DB::queryFirstRow("SELECT apiKey FROM tblEptKlean13 WHERE UserId=%s", $userId);
 $kleanApiKey = $tblEptKlean13['apiKey'];
 $baseUrl = "https://$_SERVER[HTTP_HOST]/";
 $platform = $userDetails['platform'];

 ##### PROCCESS AJAX CALL #####
 if($op == "ajaxcall") {

     if(isset($_POST['action'])) {

         ##### ADD TAG CATEGORIES #####
         if($_POST['action'] == 'addTagCategory') {
             $userId         = $_POST['userId'];
             $tagCategoryId  = $_POST['tagCategory'];
             $otherTagCat    = $_POST['otherTagCat'];

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
             $userId     = $_POST['userId'];
             $orderBy    = "Id";
             $ascending  = true;
             $page       = 0;
             $table      = "ContactGroupCategory";
             $queryData  = array('Id' => '%'); 

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
             $userId     = $_POST['userId'];
             $tableName  = 'tblEptKlean13';
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
             $apiKey     = $_POST['apiKey'];
             $userId     = $_POST['userId'];
             $testEmail  = 'adrian+301dtest@caldon.uk';
             $tableName  = 'tblEptKlean13';

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
                     ),  "UserId=%s", $userId);
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
                     'res' => $klean13Res,
                     'test' => 123
                 );
             }

             echo json_encode($data);

             die();
         }

         ##### SAVE TAG CATEGORY #####
         if($_POST['action'] == 'saveTagCategory') {
             $userId         = $_POST['userId'];
             $apiKey         = $_POST['apiKey'];
             $tagPrefix      = $_POST['tagPrefix'];
             $tableName      = 'tblEptKlean13';

             $checkExist = DB::queryFirstRow("SELECT UserId FROM tblEptKlean13 WHERE UserId = %i", $userId);
             $count = DB::count();

             if($count) {
                 $tagCatId = $_POST['tagCatId'] ? $_POST['tagCatId'] : 0;
                 DB::update($tableName, array(
                     'tagPrefix' => $tagPrefix,
                     'tagCategoryId' => $tagCatId,
                 ),  "UserId=%s", $userId);
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
             $userId         = $_POST['userId'];
             $apiKey         = $_POST['apiKey'];
             $tagPrefix      = $_POST['tagPrefix'];
             $tableName      = 'tblEptKlean13';

             $checkExist = DB::queryFirstRow("SELECT UserId FROM tblEptKlean13 WHERE UserId = %i", $userId);
             $count = DB::count();

             if($count) {
                 DB::update($tableName, array(
                     'tagPrefix' => $tagPrefix,
                 ),  "UserId=%s", $userId);
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
             $userId         = $_POST['userId'];
             $apiKey         = $_POST['apiKey'];
             $tagPrefix      = $_POST['tagPrefix'];
             $tableName      = 'tblEptKlean13';

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
                     ),  "UserId=%s", $userId);
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
                     ),  "UserId=%s", $userId);
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
             $userId     = $_POST['userId'];
             $results    = $infusionsoft->resthooks()->all();
             $count      = $results->count();

             if($count > 0) {
                 $resultArr = $results->toArray();

                 foreach($resultArr as $row) {

                     $urlUserId = explode("userId=",$row->hookUrl)[1];
                     
                     ##### CHECK RESTHOOK USERID #####
                     if($urlUserId == $userId) {
                         ##### DELETE RESTHOOK BY USERID #####
                         $infusionsoft->resthooks()->find($row->key)->delete();
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
             $appName    = $_POST['appName'];
             $authhash   = $_POST['authhash'];
             $userId     = $_POST['userId'];

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

             $appName    = $_POST['appName'];
             $authhash   = $_POST['authhash'];
             $userId     = $_POST['userId'];

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
                     'success'   => 0,
                     'webURL'    => $webHookUrl,
                     'error'     => $contactHook->error
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
