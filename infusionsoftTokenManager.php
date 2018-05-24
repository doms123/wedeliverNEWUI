<?php
    function manageInfusionToken($infusionsoft, $db, $userId) {
        try {
            $token = $infusionsoft->getToken();
            
            ##### CHECK IF HAS TOKEN #####
            if ($token) {
                ##### CHECK IF TOKEN IS EXPIRED THEN REFRESH IT #####
                $time = time() + 10;
                if ($token->endOfLife <= $time) {
                    $token = $infusionsoft->refreshAccessToken();
                    updateTokenInDB($userId, $token, $db);
                }
            }else {
                ##### NO ACCESS TOKEN THEN SHOW THE GET AUTHORIZATION URL LINK #####
                print '<h2>You need to authorise your Infusionsoft application</h2><a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft and select your app</a>';
                exit;
            }
        } catch (\Infusionsoft\TokenExpiredException $e) {
            ##### IF REQUEST FAILS DUE TO AN EXPIRED ACCESS TOKEN, REFRESH THE TOKEN AND THEN DO THE REQUEST AGAIN #####
            $infusionsoft->refreshAccessToken();

            ##### SAVE THE NEW ACCESS TOKEN TO THE DATABASE #####
            updateTokenInDB($userId, $token, $db);
        }
    }
    
    function updateTokenInDB($userId, $token, $db) {
        ##### UPDATE INFUSIONSOFT INFO IN DATABASE #####
        $scopeArray = explode("|", $token->extraInfo["scope"]);
        $appName = substr($scopeArray[1], 0, strpos($scopeArray[1], "."));
        $db->insertUpdate("tblEptUsers", array(
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
