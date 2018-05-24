<?php
function printUpdateOpportunityData($appName, $sessionId, $op) { ?>

    <?php require('./process/kleanApiConfig.php'); ?>
    <div class="row">
        <div class="col-lg-12">
            <header class="w3-container">
                <h5><b><i class="fa fa-dashboard"></i>Dashboard</b></h5>
            </header>
        </div>
    </div>
    <br/>
    <div class="row">
        <?php echo multipleEPTCards(); ?>
    </div>
    <div class="row updateOpportunityDataPage" data-baseurl="<?php echo $baseUrl; ?>">
        <h2>Loading Opportunity Data</h2>
        <p>We're going to update the Opportunity data. We do our best to only update data that's changed, although this isn't 100% possible</p>
        <p id="eeProgressContainer" style="align:center">
            <span id="pleaseWait">Please Wait:</span>
            <span id="eeProgressTitle"></span>&nbsp;
            <span id="eeProgressDetail"></span>
        </p>
        <div class="w3-dark-grey w3-round-xlarge" style="padding:0px">
            <div id="progressBar" class="w3-container w3-blue w3-round-xlarge" style="padding:0px;height:25px;width:0%"></div>
        </div>
        <?php 
            // Firstly, print some feedback for the user
            printProgressBarContainer();
            
            // Update the Lead data stored in MySQL
            $lastUpdate=getMySqlLastUpdate($appName, "Lead");
            syncXmlToMySql($appName, "Lead", $lastUpdate, [["Id"=>"%"]], [["LastUpdated"=>"~>=~ $lastUpdate"]]);
            
            $lastUpdate=getMySqlLastUpdate($appName, "StageMove");
            syncXmlToMySql($appName, "StageMove", $lastUpdate, [["Id"=>"%"]], [["MoveDate"=>"~>=~ $lastUpdate"]]);
            
            $lastUpdate=getMySqlLastUpdate($appName, "Stage");
            syncXmlToMySql($appName, "Stage", $lastUpdate, [["Id"=>"%"]], [["Id"=>"%"]]);
            
            $lastUpdate=getMySqlLastUpdate($appName, "User");
            syncXmlToMySql($appName, "User", $lastUpdate, [["Id"=>"%"]], [["Id"=>"%"]]);
        ?>
        <p>Completed</p>
        <p>You can now <a href="?op=opportunityActivityReport" class="underline">view the Opportunities report</a>
    </div>


    <script src="./vendor/jquery/jquery.min.js"></script>
    <script>
        $(function() {
            $(".updateOpportunityData").find("a").addClass("active");

            var pcDiv = document.getElementById("pleaseWait"); pcDiv.innerHTML = "Finished:";
            setProgressBar(100);
            function setProgressBar(percent) {
                var elem = document.getElementById("progressBar"); 
                elem.style.width = percent + '%'; 
            }
        });
    </script>
<?php } ?>
