<?php 
function printKleanOnDemandScrub($appName, $sessionId, $userDetails, $op) { ?>

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
    <div class="row kleanOnDemandScrubPage" data-baseurl="<?php echo $baseUrl; ?>"  data-userid="<?php echo $userDetails['userId']; ?>">
        <h2>Klean13 On-Demand Scrub</h2>
        <div class="onDemandScrub">
            <div>
                <input type="checkbox" class="checkEverything" id="checkEverything"/>
                <label for="checkEverything">Select this checkbox to scrub ALL contacts in your database (use with caution)</label>
            </div>
            <div class="tagGroup">
                <label for="tagCategory" class="tagCategoryLabel">All contacts with this tag will be scrubbed:</label>
                <select id="tagCategory" class="tagCategory">
                    <option>Loading...</option>
                </select>
            </div>
            <button class="scrubButton" disabled>Start Scrub</button>

            <p class="cbMessage"></p>
            <label class="outputLabel">Result:</label><br>
            <div class="printOutput"></div>
            <div class="autoUpdateWrap">
                <input type="checkbox" id="autoUpdate" class="autoUpdate" checked>
                <label for="autoUpdate">automatically update status of list scrub</label>
            </div>
        </div>

        <div class="modal fade scrubHidden" id="myModal" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Klean13 On-Demand Scrub</h4>
                    </div>
                    <div class="modal-body">
                        <p>This will use XXX credits from your Klean13 account - click <strong>YES</strong> to continue or <strong>NO</strong> to cancel</p>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary startScrub">Yes</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    </div>
              </div>
            </div>
        </div>

        <p class="appName scrubHidden"><?php $userDetails["appName"]; ?></p>
        <p class="authhash scrubHidden"><?php $userDetails["authhash"]; ?></p>
    </div>


    <!--script src="./vendor/jquery/jquery.min.js"></script>
    <script src="./vendor/bootstrap/js/bootstrap.min.js"></script>
    <script>
        $(function() {
            $(".kleanOnDemandScrub").find("a").addClass("active");
        });
    </script>

    <script src="./dist/js/kleanOnDemandScrub.js"></script-->
<?php } ?>
