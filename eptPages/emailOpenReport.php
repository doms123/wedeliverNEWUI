<?php 
//include 'cardTemplate.php'; 
function printOpenEmailReport($appName){

    $cacheInfo=getAllMySqlLastUpdate($appName);
    print_r($cacheInfo);
    foreach ($cacheInfo as $tableCache) {
        $daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
    }   
    
    print_r($daysSince);

    $days=$daysSince["EmailSentSummary"]; 

    $emailLastDay= sprintf("<p>Email summary data was last updated within the last %sday%s. Please <a href='?op=updateContactData'>click here</a> to update this data now.</p>", $days > 1?$days." ":"", $days==1?"":"s");
    
    
    
    $dataToStringLostCLV='';

	foreach(array(7, 30, 60, 90, 180) as $days) {
        $stats=getLatestOpenRates($appName, $days);
        $thisString=sprintf("<tr><th align=right>Last %d Days<td align=right>%d<td align=right><span style='font-size:8px'>(%d)</span> %3.1f%%<td align=right><span style='font-size:8px'>(%d)</span> %3.1f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%<td align=right><span style='font-size:8px'>(%d)</span> %4.2f%%</td></tr>\n", $days, $stats["Sent"], $stats["Opened"], $stats["Open%"], $stats["Clicked"], $stats["Click%"], $stats["OptedOut"], $stats["OptOut%"], $stats["Bounced"], $stats["Bounce%"], $stats["Complaints"], $stats["Complaint%"]);
        $dataToStringLostCLV=$dataToStringLostCLV . $thisString;
    }
	
    echo '$dataToStringLostCLV:>>' . $days;
    $table1 = '<table><tr><th>Period<th>Sent<th>Opened<th>Clicked<th>Opted Out<th>Bounced<th>Complained</th></tr>'. $dataToStringLostCLV .'</table>';

    printf('
    <div class="row">
        <div class="col-lg-12">
            <!-- for title -->
            <header class="w3-container" style="padding-top:10px">
            <h5><b><i class="fa fa-dashboard"></i> Dashboard</b></h5>
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
        <h2><h2>Data Needs to be Updated</h2></h2>
        <h2>Tabular Data</h2>
        '. $emailLastDay. '
        
        </div>
    </div>    
    <!-- /.row -->
    ', multipleEPTCards());  
}
?>
