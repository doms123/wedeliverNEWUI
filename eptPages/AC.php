<?php
function showAcSettingsPage($appName="", $apiKey="", $message="Please enter your ActiveCamapign account name and API key:", $button="Submit") 
{
?>

	<h2>Welcome to Email Power Tools</h2>
    <?php
    printf('<h3>%s</h3>', $message);
    ?>
    <br>
    <?php
    printf("<form method='POST' action='%s?op=acSetApi'>", $_SERVER['PHP_SELF']);
    ?>
	<table class='w3-table'>
        <tr>
            <th>Account Name</th>
            <td>
            <?php
            printf('<input type="text" name="acAccount" value="%s">', $appName);
            ?>
            </td>
        </tr>
	    <tr>
            <th>API Key</th>
            <td>
                <?php
                printf("<input type='text' name='acApiKey' value='%s'>", $apiKey);
                ?>
            </td>
        </tr>
    </table>
    <?php
        printf("<input type='submit' value='%s' class='w3-button w3-blue w3-round'></div>", $button);
    ?>
    </form>
<?php
}
?>