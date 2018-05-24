<?php 
//include 'cardTemplate.php'; 
function printUpdateContractData($appName){
    //$lastUpdate=getMySqlLastUpdate($appName, "EmailAddStatus");

    
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
        <h2>Loading Contact Data</h2>
        <p>Firstly, we will update the Email Address Status data, followed by the Contact data, followed by Invoice data. We do our best to only update data that\'s changed, although this isn\'t 100%% possible</p>
        %s
        </div>
    </div>    
    <!-- /.row -->
    ', multipleEPTCards(), printProgressBarContainer());   
    //echo printProgressBarContainer();


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

		/*
		IDEA: If the contact's LastUpdated value gets updated when the EmailAddStatus changes, just get a list of those contacts and update EmailAddStatus for those addresses
		*/
	
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

		print "</div>";    
    
}
?>

