<?php 
//include 'cardTemplate.php'; 
function includeFiles($debugMode = false)
{
    $autoloadPath='../' . 'vendor/autoload.php';
    $meekrodbPath='../' . 'meekrodb.2.3.class.php';
    $autoloadFBPath='../' . '/src/Facebook/autoload.php';
    $ActiveCampaignPath='../' . 'includes/ActiveCampaign.class.php';
    $mailChimpPath= '../' . 'MailChimp.php';
    
    //file_exists
    if($debugMode)
    {
        $autoload = file_exists($autoloadPath);
        $meekrodb = file_exists($meekrodbPath);
        $autoloadFB = file_exists($autoloadFBPath);
        $ActiveCampaign = file_exists($ActiveCampaignPath);
        $mailChimp = file_exists($mailChimpPath);
        return sprintf('2file exist autoload = %s , meekrodb: %s , autoloadFB: %s, ActiveCampaign: %s: mailChimp:%s', 
        $autoload, $meekrodb,  $autoloadFB, $ActiveCampaign , $mailChimp
        );
    }
    else
    {
        require_once $autoloadPath;
        require_once $meekrodbPath;
        require_once $autoloadFBPath;
        require_once $ActiveCampaignPath;  
    }
    
  //
}
    
?>