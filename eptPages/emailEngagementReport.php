<?php 
function printEmailEngagementReport($appName, $sessionId, $op) { ?>
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
    <div class="row emailEngagementReportPage" data-baseurl="<?php echo $baseUrl; ?>">
        <h2>Email Engagement Report</h2>
        <div class="description">
            <p>This isn't a standard email open report!</p>
            <p>This report shows you how many people (and the percentage) have opened <strong>something</strong> within the last X days. The open percentage will normally be quite a bit higher than the open rate you'll see in other email stats, because it's looking at the overall engagement in the time period shown. So as long as a contact has opened <strong>something</strong> in the last X days, they'll show as an open here.</p>
            <p>Typically, anyone who hasn't opened or clicked anything in the last 90 days is considered to be disengaged. Best practice is to add those people to a \"last chance\" re-engagement campaign and, if they don't respond to that, stop mailing them.</p>
            <?php if ($op['platform'] == "ISFT") { ?>
                <p>This report also shows you everyone who you've <strong>not</strong> sent anything to in the last X days. Infusionsoft considers anybody who's not been mailed within the last 4 months as \"cold\" and will probably throttle any mails sent to those cold contacts the next time you send them a broadcast.</p>	
            <?php } else { ?>
                <p>This report also shows you everyone who you've <strong>not</strong> sent anything to in the last X days.</p>
            <?php } ?>
        </div>

        <p>Please <a href='#' class="dismissText">click here</a> to show <span class="changeText">more</span> details</p>

        <?php if (!doesTableExist($appName, "Contact") || !doesTableExist($appName,"EmailAddStatus")) { ?>
            <h2>Data Needs to be Updated</h2>
            <p>Email Power Tools needs to summarise the contact and engagement data.</p>
            <p>Please <a href=\"?op=updateContactData\">click here</a> to do this now.</p>
        <?php } ?>
        
        <h2>Tabular Data</h2>
	
        <?php 
            $cacheInfo=getAllMySqlLastUpdate($appName);
            foreach ($cacheInfo as $tableCache) {
                $daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
            }

            $days = max($daysSince["Contact"], $daysSince["EmailAddStatus"]);

            $dayPrefix = $days > 1 ? $days." ":"";
            $daySuffix = $days == 1 ? "" : "s";
            $strDay =  $dayPrefix.'day'.$daySuffix;
        ?>
        
        <p>Email summary data was last updated within the last <?php echo $strDay; ?>. Please <a href=\"?op=updateContactData\">click here</a> to update this data now.</p></p>
        
        <div id="progress">
            <p>This may take a few moments to collate the data, please bear with me...</p>
            <?php printProgressBarContainer(); ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th colspan="5">Number of contacts who...</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th>Were Sent Something</th>
                        <th>Opened Something</th>
                        <th>Clicked Something</th>
                        <th>Were Marketable but Not Sent Anything</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        foreach (array(7, 30, 60, 90, 120, 180, 365) as $days) {
                            $resultArray[$days] = getEngagementStats($appName, $days);
                        }

                        foreach ($resultArray as $days=>$results) { ?>
                        <?php if($results["Sent"] > 0) { ?>
                            <tr>
                                <td><?php echo $days;?> days</td>
                                <td><?php echo $results["Sent"];?></td>
                                <td><?php echo $results["Opened"];?></td>
                                <td><?php echo $results["Clicked"];?></td>
                                <td><?php $clicked = 100*$results["Clicked"]/$results["Sent"]; echo $clicked;?></td>
                                <td><?php echo $results["OptInNotSent"];?></td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td><?php echo $days;?> days</td>
                                <td colspan="6">No emails sent</td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
            function printGoogleChartsJS() { ?>
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
            printGoogleChartsJS();
        ?>
        <div class="chartArea">
            <div class="chartBlock01">
                <h2>Chart: Numbers</h2>
                <div class="row"> 
                    <div class="col-md-12">
                        <div id="eng_numeric"></div>  
                    </div>
                </div>
            </div>
            <div class="chartBlock02">
                <h2>Chart: Percentages</h2>
                <div class="row"> 
                    <div class="col-md-12">
                        <div id="eng_percent"></div>  
                    </div>
                </div>
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
                        },
                        width: '100%',
                        height: '100%',
                    },
                    width: '100%',
                    height: '100%',
                    legend: { 
                        position: 'bottom'
                    }
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
    </div>

    <script src="./vendor/jquery/jquery.min.js"></script>
    <script>
        $(function() {
            var isHidden = 0;
            $(".dismissText").click(function() {
                isHidden = !isHidden
                if(isHidden) {
                    $(".description").hide();
                    $(".changeText").text('more');
                }else {
                    $(".description").show();
                    $(".changeText").text('less');
                }
            });
        });
    </script>
<?php } ?>
