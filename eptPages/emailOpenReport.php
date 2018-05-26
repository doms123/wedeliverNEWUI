<?php function printOpenEmailReport($appName) { ?>
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
    <div class="row emailOpenReportPage" data-baseurl="<?php echo $baseUrl; ?>">
        <h2>Email Open Report</h2>
        <?php if (!doesTableExist($appName, "EmailSentSummary")) { ?>
            <h2>Data Needs to be Updated</h2>
            <p>Email Power Tools needs to summarise the historical email data.</p>
            <p>Please <a href=\"?op=updateEmailOpenData\">click here</a> to do this now.</p>
        <?php } ?>
			
		<!-- ##### TABULAR DATA ##### -->
        <div class="tabularData">
            <h3>Tabular Data</h3>
            <?php 
                $cacheInfo = getAllMySqlLastUpdate($appName);
                foreach ($cacheInfo as $tableCache) {
                    $daysSince[$tableCache["tableName"]] = $tableCache["daysAgo"];
                }
                $days = $daysSince["EmailSentSummary"];

                $dayPrefix = $days > 1 ? $days." ":"";
                $daySuffix = $days == 1 ? "" : "s";
                $strDay =  $dayPrefix.'day'.$daySuffix;
            ?>
            <p>Email summary data was last updated within the last <?php echo $strDay; ?>. Please <a href=\"?op=updateEmailOpenData\">click here</a> to update this data now.</p>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover tabularTable">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Sent</th>
                            <th>Opened</th>
                            <th>Clicked</th>
                            <th>Opted Out</th>
                            <th>Bounced</th>
                            <th>Complained</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            foreach(array(7, 30, 60, 90, 180) as $days) { 
                                $stats = getLatestOpenRates($appName, $days);
                            ?>
                                <tr>
                                    <th><?php echo $days;?> days</th>
                                    <td><?php echo $stats["Sent"] ? $stats["Sent"] : '0'; ?></td>
                                    <td>
                                        <span>(<?php echo $stats["Opened"] ? $stats["Opened"] : '0';?>)</span>
                                        <?php echo number_format($stats["Open%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["Clicked"] ? $stats["Clicked"] : '0';?>)</span>
                                        <?php echo number_format($stats["Click%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["OptedOut"] ? $stats["OptedOut"] : '0';?>)</span>
                                        <?php echo number_format($stats["OptOut%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["Bounced"] ? $stats["Bounced"] : '0';?>)</span>
                                        <?php echo number_format($stats["Bounce%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["Complaints"] ? $stats["Complaints"] : '0';?>)</span>
                                        <?php echo number_format($stats["Complaint%"], 2);?>%
                                    </td>
                                </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
		
		<!-- ##### MONTHLY DATA ##### -->
        <div class="monthyData">
            <h3>Monthly Data</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover tabularTable">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Sent</th>
                            <th>Opened</th>
                            <th>Clicked</th>
                            <th>Opted Out</th>
                            <th>Bounced</th>
                            <th>Complained</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $statArray = getHistoricalOpenRates($appName);
                            foreach($statArray as $stats) { ?>
                                <tr>   
                                    <th>Month 
                                        <?php 
                                            $month = $stats["M"] < 10 ? '0'.$stats["M"] : $stats["M"];
                                            echo $stats["Y"].'-'.$month;
                                        ?>
                                    </th>
                                    <td><?php echo $stats["Sent"] ? $stats["Sent"] : '0'; ?></td>
                                    <td>
                                        <span>(<?php echo $stats["Opened"] ? $stats["Opened"] : '0';?>)</span>
                                        <?php echo number_format($stats["Open%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["Clicked"] ? $stats["Clicked"] : '0';?>)</span>
                                        <?php echo number_format($stats["Click%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["OptedOut"] ? $stats["OptedOut"] : '0';?>)</span>
                                        <?php echo number_format($stats["OptOut%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["Bounced"] ? $stats["Bounced"] : '0';?>)</span>
                                        <?php echo number_format($stats["Bounce%"], 2);?>%
                                    </td>
                                    <td>
                                        <span>(<?php echo $stats["Complaints"] ? $stats["Complaints"] : '0';?>)</span>
                                        <?php echo number_format($stats["Complaint%"], 2);?>%
                                    </td>
                                </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
		
		<!-- ##### INITIALIZE GOOGLE CHART ##### -->
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
		
		<!-- ##### GOOD STATS NUMERIC CHART ##### -->
        <div class="goodStatsNumeric">
			<h3>The Good Stats - Numeric</h3>
			<div id="eng_good_num"></div>
        </div>

		<!-- ##### GOOD STATS PERCENTAGE CHART ##### -->
        <div class="goodStatsPercent">
			<h3>The Good Stats - Percentage</h3>   
			<div id="eng_good_pct"></div>
		</div>

		<!-- ##### BAD STATS NUMERIC ##### -->
        <div class="badStatsNumeric">
			<h3>The Bad Stats - Numeric</h3>   
			<div id="eng_bad_num"></div>
		</div>    

		<!-- ##### BAD STATS PERCENTAGE ##### -->
        <div class="badStatsPercent">
			<h3>The Bad Stats - Percentage</h3>   
			<div id="eng_bad_pct"></div>
		</div>         
    </div>

    <script src="./vendor/jquery/jquery.min.js"></script>
    <script>
        $(function() {
			drawEngGoodChartNum();
			drawEngGoodChartPct();
			drawEngBadChartNum();
			drawEngBadChartPct();

			function drawEngGoodChartNum() {
				var data = google.visualization.arrayToDataTable([
					["Month","Sent", "Opened", "Clicked"]
					<?php
						foreach ($statArray as $results) {
							printf(",[new Date(%d, %d),%d,%d,%d]\n", $results["Y"], $results["M"]-1, $results["Sent"], $results["Opened"], $results["Clicked"]);
						}	
					?>			
				]);

				var options = {
					textStyle: {
						fontName: 'Exo 2'
					},
					title: 'Sent, Opened and Clicked (Month Commencing)',
					curveType: 'none',
					pointShape: 'square',
					pointSize: 8,
					hAxis: {
						titleTextStyle: {
							fontName: 'Exo 2'
						}
					},
					legend: { position: 'bottom' }
				};

				var chart = new google.visualization.LineChart(document.getElementById('eng_good_num'));

				chart.draw(data, options);
			}

			function drawEngGoodChartPct() {
	        	var data = google.visualization.arrayToDataTable([
					["Month", "Opened", "Clicked"]
					<?php
						foreach ($statArray as $results) {
							if ($results["Sent"] > 0) {
								printf(",[new Date(%d, %d),%d,%d]\n", $results["Y"], $results["M"]-1, 100*$results["Opened"]/$results["Sent"], 100*$results["Clicked"]/$results["Sent"]);
							}
						}	
					?>				
	        	]);

	        	var options = {
					textStyle: {
						fontName: 'Exo 2'
					},
					title: 'Percentage Opened and Clicked (Month Commencing)',
					curveType: 'none',
						pointShape: 'square',
						pointSize: 8,
					hAxis: {
						titleTextStyle: {
							fontName: 'Exo 2'
						}
					},
					legend: { position: 'bottom' }
	        	};

				var chart = new google.visualization.LineChart(document.getElementById('eng_good_pct'));
				chart.draw(data, options);
	      	}

			function drawEngBadChartNum() {
	        	var data = google.visualization.arrayToDataTable([
					["Month","OptedOut", "Bounced", "Complaints"]
					<?php
						foreach ($statArray as $results) {
							printf(",[new Date(%d, %d),%d,%d,%d]\n", $results["Y"], $results["M"]-1, $results["OptedOut"], $results["Bounced"], $results["Complaints"]);
						}	
					?>				
	        	]);

				var options = {
					textStyle: {
						fontName: 'Exo 2'
					},
					title: 'Opted Out, Bounced and Complained (Month Commencing)',
					curveType: 'none',
					pointShape: 'square',
					pointSize: 8,
					hAxis: {
						titleTextStyle: {
							fontName: 'Exo 2'
						}
					},
					legend: { position: 'bottom' }
				};

	        	var chart = new google.visualization.LineChart(document.getElementById('eng_bad_num'));

	        	chart.draw(data, options);
	      	}

			function drawEngBadChartPct() {
	        	var data = google.visualization.arrayToDataTable([
					["Month","OptedOut", "Bounced", "Complaints"]
					<?php
						foreach ($statArray as $results) {
							if ($results["Sent"] > 0) {
								printf(",[new Date(%d, %d),%d,%d,%d]\n", $results["Y"], $results["M"]-1, 100*$results["OptedOut"]/$results["Sent"], 100*$results["Bounced"]/$results["Sent"], 100*$results["Complaints"]/$results["Sent"]);
							}
						}	
					?>				
	        	]);

	        	var options = {
					textStyle: {
						fontName: 'Exo 2'
					},
	          		title: 'Percentage Opted Out, Bounced and Complained (Month Commencing)',
	          		curveType: 'none',
					pointShape: 'square',
					pointSize: 8,
  			  		hAxis: {
  				  		titleTextStyle: {
  					  		fontName: 'Exo 2'
  				  		}
  			  		},
	          		legend: {position: 'bottom'}
	        	};

	        	var chart = new google.visualization.LineChart(document.getElementById('eng_bad_pct'));

	        	chart.draw(data, options);
	      	}
        });
    </script>
<?php } ?>