<?php 
//include 'cardTemplate.php'; 
function printShowStatus( $cacheInfo){
    $userAgent= $_SERVER['HTTP_USER_AGENT']; 

    $dataToStringLostCLV='';
    if ($cacheInfo == NULL) {
        $dataToStringLostCLV="<p>No data has been cached yet</p>";
    } else {
        foreach ($cacheInfo as $tableCache) {
            $thisString=sprintf("<p>Table %s was last updated %s day%s ago (%s)</p>\n", $tableCache["tableName"], $tableCache["daysAgo"], $tableCache["daysAgo"]==1?"":"s", $tableCache["lastComplete"]);
            $dataToStringLostCLV=$dataToStringLostCLV . $thisString;
        }
    }

    printf('
    <div class="row">
        <div class="col-lg-12">
            <!-- for title -->
            <header class="w3-container" style="padding-top:10px">
            <h5><b><i class="fa fa-fw fa-eye"></i> Show Status</b></h5>
            </header>
        
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <br/>
    <div class="row">
        <!-- for cards -->             
        %s               
    </div>
    <!-- /.row -->
    <div class="row">
        <!-- for main contents --> 
        <div class=\'w3-container\'>
        <h2>Welcome to Email Power Tools</h2>
        %s
        %s
        </div>
    </div>    
    <!-- /.row -->
    ', multipleEPTCards(), $dataToStringLostCLV, $userAgent);  
}


//printf("<p>User-Agent: %s</p>\n", $userAgent);
/*
	printf("<p>You are logged into Infusionsoft as <strong>%s</strong> (%s)</p>\n", $isUserInfo["displayName"], $isUserInfo["casUsername"]);
	printf("<p>You are connected to Infusionsoft app <strong>%s</strong> (%s)</p>\n", $isUserInfo["appAlias"], $isUserInfo["appUrl"]);
*/
?>
