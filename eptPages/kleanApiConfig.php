<?php 
function printKleanApiConfig($appName, $sessionId, $op) { ?>

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
    <div class="row kleanApiConfigPage" data-baseurl="<?php echo $baseUrl; ?>">
        <h2>Klean13 API Configuration</h2>
        <div class="pageLoader">
            <span class="loader"></span> Loading, please wait...
        </div>
        <div class="apiConfig kleanHidden">
            <p>
                <label class="switch" title="Click to switch on">
                    <input type="checkbox" class="toggleConfig">
                    <span class="slider round"></span>
                </label>
                <span class="switchLabel">Enable real-time scrubbing<span class="switchStatus kleanHidden">OFF</span></span>
            </p>
            <div class="apiForm">
                <label>API Key</label>
                <input type="hidden" value="<?php echo $userId; ?>" class="userId">
                <input type="hidden" class="tableName" value="<?php $tableName; ?>">
                <input type="text" class="txtApiKey" placeholder="Enter API Key" value="<?php echo $kleanApiKey; ?>">                  
            </div>

            <div class="addTagWrap">
                <h4>Add Tag</h4>
                <div class="tagGroup">
                    <label for="tagPrefix">Tag Prefix</label>
                    <input type="text" id="tagPrefix" class="tagPrefix" value="K13 - ">
                </div>
                <div class="tagGroup hiddenAC">
                    <label for="tagCategory">Category</label>
                    <select id="tagCategory" class="tagCategory">
                        <option>Loading...</option>
                    </select>
                </div>
                <div class="tagGroup hiddenAC">
                    <label class="otherTagLabel">Other Tag</label>
                    <input type="text" class="otherTagCat">
                    <input type="hidden" class="otherTagId">
                    <label for="otherTag" class="left">(Other)</label>
                </div>
                <button class="buttonSaveApiKey btn btn-primary">Save settings</button>
            </div>
            <div class="loaderWrap"></div>
            <div class="resMsgWrap"> </div>
        </div>
        <p class="appName kleanHidden"><?php echo $userDetails["appName"]; ?></p>
        <p class="authhash kleanHidden"><?php echo $userDetails["authhash"]; ?></p>
    </div>


    <script src="./vendor/jquery/jquery.min.js"></script>
    <script>
        $(function() {
            $(".kleanApiConfig").find("a").addClass("active");
        });
    </script>

    <?php if($platform == 'ISFT') { ?>
        <script src="./dist/js/kleanApiConfigISFT.js"></script>
    <?php }else if($platform == 'AC') { ?>
        <script src="./dist/js/kleanApiConfigAC.js"></script>
    <?php } ?>
<?php } ?>
