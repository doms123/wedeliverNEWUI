<?php 
function Report_printLostCLVPage($days=0, $lostCLVDataString=''){
 // echo 'days ' . $days;
  //  echo 'lostCLVDataString ' . $lostCLVDataString;
    printf('
    <div class="row">
        <div class="col-lg-12">
            <!-- for title -->
            <header class="w3-container" style="padding-top:10px">
            <h5><b><i class="fa fa-dashboard"></i> Lost Customers Report</b></h5>
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
        <div class="w3-container">
            <!--h2>Lost Customers Report</h2--> 
            <br/>           
            %s
            <!--h2>Lost Customer Lifetime Value Report</h2-->
            <br/>  
            <div class="w3-responsive">
                                    <!--mdl-data-table-->
                <table id="tblCLV" class="mdl-data-table" style="font-size:12px">
                    <thead>
                    <tr><th>Name<th>Email<th>Phone<th>Latest Order<th><span style=\'white-space: nowrap\'>Total Value</span><th>Email Status<th>Last Opened Something</th></tr>
                    </thead>
                    <tbody>
                        %s
                    </tbody>
                </table>
            </div>
        </div>    
    </div>    
    <!-- /.row -->
    ', multipleEPTCards(), Report_emailSummary($days), $lostCLVDataString 
);  
 //multipleEPTCards(), Report_emailSummary($days), $lostCLVDataString   
    //return multipleEPTCards();
}


function Report_emailSummary($days = 0)
{       
    $emailLastDay= sprintf('<p>Email summary data was last updated within the last %sday%s. Please <a href="?op=updateContactData">click here</a> to update this data now.</p>', $days > 1?$days." ":"", $days==1?"":"s");
    
    $emailSumText= '<p>By default, this report shows you up to 100 people who have purchased something from you (value $50 or more) in the last 12 months who haven\'t 
    engaged with your emails in the last 12 months. In many cases, this will be because their email address has never worked correctly, in other cases it might be because it has set to Hard Bounce in 
    Infusionsoft - even though it might still be valid. We strongly recommend that you contact each of these people individually and correct their email address and opt it back in manually if needed, 
    as these people generally still want to buy from you! Of course, if they\'ve reported you for spam or opted out, they may no longer be interested.</p>
    ';
   
   return $emailLastDay .  $emailSumText;
}

function Report_postActionLostCLV ($appName, $optTypeTranslate)
{
       
        $dataToStringLostCLV='';
        $stats=getLostCustomerData($appName);
        if($stats)
        {
            foreach($stats as $row) {
                $thisString=sprintf("<tr><td><a href='https://%s.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID=%d' target='_new'>%s %s</a><td>%s<td>%s<td>%s<td style='text-align: right'>%s<td>%s<td>%s</td></tr>\n", $appName, $row["Id"], $row["FirstName"], $row["LastName"], $row["Email"], $row["Phone1"], $row["LatestOrder"], number_format($row["TotalValue"],2), $optTypeTranslate[$row["Type"]], $row["LastOpenDate"]?$row["LastOpenDate"]:"Never");
               // echo '$row["Type"] ' . $row["Type"] . '<br/>';
                $dataToStringLostCLV=$dataToStringLostCLV . $thisString;
            }
        }
        else
        {
            $dataToStringLostCLV='<tr><td colspan="8">no record</td></tr>';
        }
        //echo '$dataToStringLostCLV:>> ' . $dataToStringLostCLV;
       return $dataToStringLostCLV;       
    

}
?>
