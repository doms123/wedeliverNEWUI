<?php 
//include 'cardTemplate.php'; 
function printDashboardPage(){
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
        <h2>Welcome to Email Power Tools</h2>
        <p>Email Power Tools is provided totally free by Adrian Savage.</p>
        <p>We\'ll be adding additional reports and features as time, resources and ideas allow... please get in touch if you have any feedback, questions or suggestions.</p>
        <p><b>Please Note:</b> This is a free product! That means that very limited support will be available via the Facebook group!</p>
        <p>If you haven\'t already done so, <a href="?op=updateContactData" class="btn btn-primary">Click Here to Update Your Contact Data</a></p>
        </div>
    </div>    
    <!-- /.row -->
    ', multipleEPTCards());  
}
?>
