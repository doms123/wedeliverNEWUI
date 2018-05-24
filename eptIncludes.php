<?php 
function callIncludeEptFiles()
    {
    include 'eptUtilities.php'; 
    include './eptPages/dashboard.php'; 
    include './eptPages/lostCLV.php';
    include './eptPages/lostCustomerReport.php';
    include './eptPages/debug.php'; 
    include './eptPages/showStatus.php'; 
    include './eptPages/emailOpenReport.php'; 
    include './eptPages/updateContactData.php';
    include './eptPages/opportunityActivityReport.php'; 
    include './eptPages/updateOpportunityData.php'; 
    include './eptPages/kleanApiConfig.php';
    include './eptPages/kleanOnDemandScrub.php'; 
    include './eptPages/reset.php'; 
    include './eptPages/AC.php';  
    include './eptPages/emailAdminSettings.php';
    include './eptPages/adminSettings.php';
    include 'includeFiles.php'; 
    }
?>