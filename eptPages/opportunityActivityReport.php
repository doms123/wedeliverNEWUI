<?php
function printOpportunityActivityReport($appName, $sessionId, $op) { ?>

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
    <div class="row opportunityActivityReportPage" data-baseurl="<?php echo $baseUrl; ?>">
        <h2>Opportunity Activity Report</h2>
        <h2>Summary</h2>
        <?php
            $filter = 'daily';

            if(count(explode("filter=",$_GET['op'])) > 1) {
                $filter = explode("filter=",$_GET['op'])[1];

                $activityReports = getOpportunityActivityReport($appName, $filter);
            }else {
                $activityReports = getOpportunityActivityReport($appName, 'daily');
            } 
        ?>
        <table class="w3-table-all w3-card-4">
            <tr>
                <th></th>
                <th>NumOpps</th>
                <th colspan="2" style="width: 20%;">Stage Won</th>
                <th colspan="2" style="width: 20%;">Stage Lost</th>
            </tr>

            <?php 
                foreach (array(7, 30, 60, 90, 120, 180, 365) as $days) {
                    $resultArray[$days] = getOpportunityActivityReportSummary($appName, $days);
                }
                foreach ($resultArray as $res) {
                    if($res['totalWon'] > 0) {
                        $totalWonPercentage = number_format(($res['totalWon'] / $res['totalNumOpps']) * 100, 1);
                    }else {
                        $totalWonPercentage = 0.0;
                    }
                    
                    if($res['totalLost'] > 0) {
                        $totalLostPercentage = number_format(($res['totalLost'] / $res['totalNumOpps']) * 100, 1);
                    }else {
                        $totalLostPercentage = 0.0;
                    }
                    
                    echo '
                        <tr>
                            <td>'.$res['days'].' days</td>
                            <td>'.$res['totalNumOpps'].'</td>
                            <td>'.$res['totalWon'].'</td>
                            <td>'.$totalWonPercentage.'%</td>
                            <td>'.$res['totalLost'].'</td>
                            <td>'.$totalLostPercentage.'%</td>
                        </tr>
                    ';
                }
            ?>
        </table>
        <br>
        <div class="tabularWrap">
            <h2>Tabular Data</h2>
            <form>
                <select class="tabularFilter">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </form>
        </div>
        <table class="w3-table-all w3-card-4">
            <tr>
                <th>Name</th>
                <th>StageName</th>
                <th>NumOpps</th>
                <th>Move Date</th>
            </tr>
            <?php
                $weeklyPrefix = '';
                if($filter == 'weekly') {
                    $weeklyPrefix = 'w/c';
                }
                $date2 = date("Y-m-d");
                if(count($activityReports)) {
                    foreach ($activityReports as $row) {
                        
                        $date1 = $row['DD'];
                        if($date1 != $date2) {
                            echo '<tr class="rowChange">';
                            $date2 = $date1;
                        }else {
                            echo '<tr>';
                        }
                        echo '
                                <td>'.$row['FirstName'].' '.$row['LastName'].'</td>
                                <td>'.$row['StageName'].'</td>
                                <td>'.$row['NumOpps'].'</td>
                                <td>'.$weeklyPrefix.' '.$row['moveDateFormatted'].'</td>
                            </tr>
                        ';
                    }
                }else {
                    echo '<tr><td colspan="4">No records yet.</td></tr>';
                }
            ?>
        </table>
    </div>


    <script src="./vendor/jquery/jquery.min.js"></script>
    <script src="./dist/js/opportunityActivityReport.js"></script>
<?php } ?>
