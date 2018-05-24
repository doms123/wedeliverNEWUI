<?php 
//include 'cardTemplate.php'; 
function printDebug()
{
    //file_exists
    
    $autoload = file_exists('../' . 'vendor/autoload.php');
    $meekrodb = file_exists('../' . 'meekrodb.2.3.class.php');
    $autoloadFB = file_exists('../' . '/src/Facebook/autoload.php');
    $ActiveCampaign = file_exists('../' . 'includes/ActiveCampaign.class.php');
    /*
    require_once '../' . 'vendor/autoload.php';
    require_once '../' .'meekrodb.2.3.class.php';
    require_once '../' . '/src/Facebook/autoload.php';
    require_once("includes/ActiveCampaign.class.php");   
    */
  return sprintf('file exist autoload = %s , meekrodb: %s , autoloadFB: %s, ActiveCampaign: %s', $autoload, $meekrodb,  $autoloadFB, $ActiveCampaign );
}
    
?>
