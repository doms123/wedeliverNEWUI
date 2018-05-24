
<?php 
//include 'cardTemplate.php'; 
function printReset(){
?>

    <!--div class='w3-container'>
        <h2>Welcome to Email Power Tools</h2>
        <h3>Before we start, you need to select your CRM platform:</h3>
        <p><a href='?op=choose&platform=AC' class='w3-button w3-blue w3-round'>ActiveCampaign</a></p>
        <p><a href='?op=choose&platform=ISFT' class='w3-button w3-blue w3-round'>Infusionsoft</a></p>
        <p><a href='?op=choose&platform=MC' class='w3-button w3-blue w3-round"'>MailChimp</a></p
    </div-->
    <div class="container">
  	<center>
  		<div class="col-lg-12">
	  		<h2>Welcome to Email Power Tools</h2>
	  		<h3>Before we start, you need to select your CRM platform:</h3><br/>
	    	<div class="styleBox col-lg-offset-1 col-lg-3">
	    	<br/>
	    		<a href="?op=choose&platform=AC"><img width="150" height="150" href="?op=choose&platform=AC" src="https://ffb2efd5105ff0aedbc9-9cdacdeebf0faa19b665bf427f0c8092.ssl.cf1.rackcdn.com/img/integrations-screenshots/ActiveCampaign/dcb15b0e7e7f4acc7575f4f8bfe33ff6.128x128.png"></img></a>
	    		
	    		<p><a style="margin:20px" href="?op=choose&platform=AC" class="w3-button w3-blue w3-round">ActiveCampaign</a></p>
	    	</div>
	    	
	    	<div class="styleBox col-lg-offset-1 col-lg-3" style="background-color:grey">
	    		<br/>
	    		<a href="?op=choose&platform=ISFT"><img width="150" height="150" href="?op=choose&platform=AC" src="https://accounts.infusionsoft.com/img/is_cornerstone.svg?b=1.0.88"></img></a>
	    		<p><a style="margin:20px"  href="?op=choose&platform=ISFT" class="w3-button w3-blue w3-round">Infusionsoft</a></p>
	    	</div>

	    	<div class="styleBox col-lg-offset-1 col-lg-3">
	    		<br/>
	    		<a href="?op=choose&platform=MC"><img width="150" height="150" href="?op=choose&platform=AC" src="http://pluspng.com/img-png/mailchimp-png-mailchimp-710.png"></img></a>
	    		<p><a style="margin:20px"  href="?op=choose&platform=MC" class="w3-button w3-blue w3-round">MailChimp</a></p>
    		</div>
    	</div>
    </center>
  </div>  
<?php 
}
?>