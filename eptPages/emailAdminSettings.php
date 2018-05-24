<?php
function printEmailAdminSettings()
{
?>
<style>

.infusionsoft_api, .mandrill_api, .email_settings {    float: left;    width: 450px;	margin:10px;	border:1px solid #ddd;}
.site-content::after {    clear: both;    content: "";    display: block;}
.infusionsoft_api > h1, .mandrill_api h1, .email_settings h1 {    background: none repeat scroll 0 0 #0033A0;    color: #ffffff;    font-size: 19px;    margin: 0 0 10px;    padding: 10px;	font-weight: normal;}
.infusionsoft_api label, .mandrill_api label, .email_settings label {    display: block;    font-size: 14px;}
.infusionsoft_api form, .mandrill_api form, .email_settings form {    padding: 0 10px 5px;}
.infusionsoft_api input[type="text"], .mandrill_api input[type="text"], .email_settings input[type="text"], .email_settings select {    border: 1px solid #dddddd;    margin-bottom: 10px;    padding: 10px;    width: 100%;	font-size:14px;}
.infusionsoft_api input[type="button"], .mandrill_api input[type="button"], .email_settings input[type="button"] {    background: none repeat scroll 0 0 #0033A0;    border-bottom: 3px solid #0033A0;    border-right: 3px solid #0033A0;    font-size: 14px;    margin-bottom: 10px;}
</style>   

    <h1 class="entry-title">Settings: Infusionsoft + Email Service</h1>
    
    <label for="mandrill_api">Static User for Testing:</label>
    <div class="emailAdminSettingsPage"  data-baseurl="<?php echo '/ept.php'; ?>" data-ajaxurl="<?php echo '/newUI/process/emailAdminSetting.php'; ?>"></div>
    <select name="staticUser" id="staticUser">
        <option value="">Select User</option>
        <option value="adrian@adriansavage.co.uk">Adrian - 233</option>                         
    </select>   

    <div id="primary" class="content-area">
    <span class="loading" style="display: none;"><img src="http://wedeliver.email/admin/wp-content/plugins/infusionsoft-sdk-for-wordpress-master/loading.gif" /></span>
        <div id="content" class="site-content" role="main">                    
            <div class="infusionsoft_api">
                <h1>Infusionsoft API details</h1>
                                    
                <span class="success_msg"></span>
                
                <form name="config_infusion" method="post" action="" class="config_infusion">
                    <label for="app_name">Application Name</label>
                    <input type="text" name="config_app_name" id="config_app_name" />                    
                    <label for="api_key">API Key</label>
                    <input type="text" name="config_api_key" id="config_api_key"/>
                    <input  type="button" name="save_infusion_settings" value="Save" class="btn btn-primary" />
                </form>
            </div>					
            
            <div class="email_settings">
                <h1>Email Settings</h1>
                <span class="success_email"></span>
                <form name="config_email" method="post" action="" class="config_email">
                    <label for="email_from">Emails Will Be Sent From</label>
                    <input type="text" name="email_from" id="email_from"/>
                    
                    <label for="mandrill_api">Email Sending Service</label>
                    <select name="email_service" id="email_service">
                        <option value="">Select Service</option>
                        <option value="[email-smtp.eu-west-1.amazonaws.com]:587">Amazon SES eu-west1</option>
                        <option value="[email-smtp.us-east-1.amazonaws.com]:587">Amazon SES us-east1</option>
                        <option value="[email-smtp.us-west-2.amazonaws.com]:587">Amazon SES us-west2</option>
                        <option value="[smtp-0.emailcopilot.com]:587">Email Copilot</option>
                        <option value="[smtp.mailgun.org]:587">Mailgun</option>
                        <option value="[smtp.mandrillapp.com]:587">Mandrill</option>
                        <option value="[outlook.office365.com]:587">Microsoft Office 365</option>
                        <option value="[smtp.send13.com]:587">SEND13</option>
                        <option value="[smtp.sendgrid.net]:587">SendGrid</option>
                        <option value="[livingnews.smtp.com]:2525">SMTP.com - livingnews</option>
                        <option value="[rementor.smtp.com]:2525">SMTP.com - rementor</option>
                        <option value="[smtp.sparkpostmail.com]:587">SparkPost</option>                            
                        <option value="[mta.embhost.com]:465">Private Test Service 1</option>                            
                    </select>
                    
                    <span>Email Sending Service Username<br><b>NOTE</b> For SparkPost, this must always be <b>SMTP_Injection</b></span>
                    <input type="text" name="user_name" id="user_name"/>
                    
                    <span>Email Sending Service Password/API Key</span>
                    <input type="text" name="mandrill_apikey" id="mandrill_apikey"/> 
                    
                    <input type="button" name="save_email_settings" class="btn btn-primary" value="Save"   />                       
                </form>	
            </div>
        </div>
    </div>
    <script src="./vendor/jquery/jquery.min.js"></script>
    <script>
            /*
            getEmailUserInfo();
            function getEmailUserInfo() {

                var ajaxUrl = $('.emailAdminSettingsPage').attr('data-ajaxurl');
                var baseUrl = "https://wdem.wedeliver.email/newUI/ept.php";
                var userId = $('.emailAdminSettingsPage').attr('data-userid');  

                $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        action: 'getEmailUserInfo'
                    },
                    success: function(data) {
                        var data = JSON.parse(data);
                        var maxLoop = data.length;
                        var html = '<option>Select User</option>';
                        var cntr=0;
                        for(var x = 0; x < maxLoop; x++) {
                            var wpuser      = data[x].wpuser ? data[x].wpuser : '';
                            var sender = data[x].sender ? data[x].sender: '';
                            var host  = data[x].host ? data[x].host: '';
                            var creds     = data[x].creds ? data[x].creds: '';
                            var app     = data[x].app ? data[x].app: '';
                            var apikey     = data[x].apikey ? data[x].apikey: '';
                            var email_field     = data[x].email_field ? data[x].email_field: '';
                            //html += '<option value="?wpuser='+wpuser+'">'+sender+"|"+host+"|"+creds+"|"+app+"|"+apikey+"|"+email_field+"|"+'</option>';
                        }
                        $("#staticUser").html(html);
                    }
                });
            }   
            */
            getEptUserInfo();
            function getEptUserInfo() {

                var ajaxUrl = $('.emailAdminSettingsPage').attr('data-ajaxurl');
                var baseUrl = "https://wdem.wedeliver.email/newUI/ept.php";
                var userId = $('.emailAdminSettingsPage').attr('data-userid');  

                $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        action: 'getEptUserInfo'
                    },
                    success: function(data) {
                        var data = JSON.parse(data);
                        var maxLoop = data.length;
                        var html = '<option>Select User</option>';
                        var cntr=0;
                        for(var x = 0; x < maxLoop; x++) {
                            var userID = data[x].userId ? data[x].userId : '';
                            html += '<option value="?userID='+userID+'">'+userID+'</option>';
                        }
                        $("#staticUser").html(html);
                    }
                });
            }               
    </script>    
<?php
}
?>