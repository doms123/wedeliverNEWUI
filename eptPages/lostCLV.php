<?php 
function printLostCLVPage($days=0, $lostCLVDataString=''){
    
    printf('
    <div class="row">
        <div class="col-lg-12">
            <!-- for title -->
            <header class="w3-container" style="padding-top:10px">
            <h5><b><i class="fa fa-dashboard"></i> Lost Custome Lifetime Value</b></h5>
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
            <!--h2>Lost Customer Lifetime Value Report</h2--> 
            <br/>           
            %s
            <!--h2>Lost Customer Lifetime Value Report</h2-->
            <br/>  
            %s
            <div class="w3-responsive">
                                    <!--mdl-data-table-->
                <table id="tblCLV" class="mdl-data-table" style="font-size:12px">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Latest Order</th>
                        <th><span style="white-space: nowrap">Total Purchased</span></th>
                        <th>Email Status</th>
                        <th>Last Click Date</th>
                    </tr>
                    </thead>
                    <tbody>
                        %s
                    </tbody>
                </table>
            </div>
        </div>    
    </div>    
    <!-- /.row -->
    ', multipleEPTCards(), datesPickerUI(), emailSummary($days), $lostCLVDataString
);  
    
    //return multipleEPTCards();
}


function emailSummary($days = 0)
{       
    $emailLastDay= sprintf("<p>Email summary data was last updated within the last %sday%s. Please <a href='?op=updateContactData'>click here</a> to update this data now.</p>", $days > 1?$days." ":"", $days==1?"":"s");
    
    $emailSumText= '<p>By default, this report shows you up to 100 people who have purchased something from you (value $50 or more) in the last 12 months who haven\'t 
    engaged with your emails in the last 12 months. In many cases, this will be because their email address has never worked correctly, in other cases it might be because it has set to Hard Bounce in 
    Infusionsoft - even though it might still be valid. We strongly recommend that you contact each of these people individually and correct their email address and opt it back in manually if needed, 
    as these people generally still want to buy from you! Of course, if they\'ve reported you for spam or opted out, they may no longer be interested.</p>
    ';
   
   return $emailLastDay .  $emailSumText;
}


function datesPickerUI()
{
    return '
    <form name="frmDateRange" method="POST">
    <div><table width="10%">
            <tr> <td colspan="2"> <a style="font-weight:bold">Unsubscribe Date Range</a>	</td> </tr>
            <tr> 
                <td colspan=2> 
                    <input style="font-weight:bold" type="radio" name="showdata" checked="true" onclick="subscrideDateController(1);"> All Dates &nbsp;                
                    <br/>
                    <input style="font-weight:bold" type="radio" name="showdata" onclick="subscrideDateController(2);"> Specific Date Range<br>
                </td>
            </tr>            
            <tr id="firstDate"> 
                <td> 
                    <a style="display:none"  id="earliestDateLabel">Earliest Date</a><br/> 
                    <input type="hidden" name="firstClick"><br/>
                    <input type="hidden" name="firstClickValue"  id="firstClickValue" value="0">
                </td>			
                <td> 
                    <a style="display:none"  id="latestDateLabel">Latest Date</a><br/> 
                    <input type="hidden" name="lastClick">
                </td>	
            </tr>
            <tr> <td colspan="2"> <a style="font-weight:bold">Date Range for Customer Value</a>	</td> </tr>  
            <tr> 
                <td colspan="2"> 
                   <input style="font-weight:bold" type="radio" name="showdata2" checked="true" onclick="subscrideDateController(3);"> All Dates &nbsp;                
                   <br/>
                   <input style="font-weight:bold" type="radio" name="showdata2" onclick="subscrideDateController(4);"> Specific Date Range<br>
                </td>
            </tr>                     	
            <tr id="secondDate"> 
                <td>                   
                    <a style="display:none" id="earliestOrderLabel">Earliest Order</a><br/> 
                    <input type="hidden" name="earliestOrder"> 
                    <input type="hidden" name="earliestClickValue" id="earliestClickValue" value="0">                   
                </td>			
                <td>
                    <a style="display:none" id="latestOrderLabel">Latest Order</a><br/> 
                    <input type="hidden" name="latestOrder">
                </td>	
            </tr>                     				 
            <tr> 
                <td colspan="2">
                    <br/>
                    <input class="w3-bar-item w3-button w3-padding w3-blue" type="submit" name="submitLostCLV" value="Show Lost Customer Lifetime Value">		 
                </td>
            </tr>							
        </table>
    </div>
</form>	    
    ';
}

function postActionLostCLV ($appName)
{
    if(isset($_POST["submitLostCLV"])) { 

        $firstClick = $_POST["firstClick"];
        $lastClick = $_POST["lastClick"];
        $earliestOrder = $_POST["earliestOrder"];
        $latestOrder = $_POST["latestOrder"];
    
        $firstClickValue = $_POST["firstClickValue"];
        $earliestClickValue = $_POST["earliestClickValue"];
    
        $firstClick = !(empty($firstClick))?$firstClick:'1980-01-01';
        $lastClick = !(empty($lastClick))?$lastClick:'2080-12-31';
        $earliestOrder = !(empty($earliestOrder))?$earliestOrder:'1980-01-01';
        $latestOrder = !(empty($latestOrder))?$latestOrder:'2080-12-31';
    
        //reset to default is  firstClickValue and  earliestClickValue == 0
        $firstClick=$firstClickValue=="0"?'1980-01-01':$firstClick;
        $lastClick=$firstClickValue=="0"?'2080-12-31':$lastClick;
        $earliestOrder=$earliestClickValue=="0"?'1980-01-01':$earliestOrder;
        $latestOrder=$earliestClickValue=="0"?'2080-12-31':$latestOrder;	

       
        $dataToStringLostCLV='';
        $stats=getLostCustomerValueReportData($appName, $firstClick, $lastClick, $earliestOrder, $latestOrder);
        foreach($stats as $row) {
                $TotalPurchased = 0;
                $TotalPurchased = number_format($row["TotalPurchased"],2);
                //printf("<tr><td><a href='https://%s.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID=%d' target='_new'>%s %s</a></td><td>%s</td><td>%s</td><td>%s</td><td style='text-align: right'>%s</td><td>%s</td><td>%s</td></tr>\n",$appName,$row["Id"],$row["FirstName"], $row["LastName"],$row["Email"],$row["Phone1"],$row["LatestOrder"],$TotalPurchased,$optTypeTranslate[$row["Type"]], !empty($row["LastClickDate"])?$row["LastClickDate"]:"Never");
           $thisString=sprintf("
           <tr>
           <td><a href='https://%s.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID=%d' target='_new'>%s %s</a></td>
           <td>%s</td>
           <td>%s</td>
           <td>%s</td>
           <td style='text-align: right'>%s</td>
           <td>%s</td>
           <td>%s</td>
           </tr>\n",$appName,$row["Id"],$row["FirstName"], $row["LastName"],$row["Email"],$row["Phone1"],$row["LatestOrder"],$TotalPurchased,$optTypeTranslate[$row["Type"]], !empty($row["LastClickDate"])?$row["LastClickDate"]:"Never");
           $dataToStringLostCLV=$dataToStringLostCLV . $thisString;
        }

       return $dataToStringLostCLV;       
    }

}
?>
