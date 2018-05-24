<?php
function printAdminSettings($appName, $sessionId, $userDetails) { ?>
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
    <div class="row adminSettingsPage" data-userid="<?php echo $userDetails['userId']; ?>" data-baseurl="<?php echo '/ept.php'; ?>" data-ajaxurl="<?php echo '/newUI/process/adminSettings.php'; ?>">
        <h2>Dynamic Tools Admin</h2>

        <div class="sectionBlock01">
            <label>Account Access</label>
            <select class="accountAccess">
                <option>Loading data...</option>
            </select>
        </div>
        <div class="sectionBlock03">
            <a class="w3-button w3-blue listRestHookBtn">List Resthooks</a>
            <div class="row restHookArea" style="display: none;">
                <div class="restHookListBlock col-lg-8">
                    <div class="panel panel-default ">
                        <div class="panel-heading">
                            RESThook Details
                        </div>
                        <div class="panel-body">
                            <div class="panel-group" id="accordion">
                               <p>Loading, please wait...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <script src="./vendor/jquery/jquery.min.js"></script>
    <script>
        $(function() {
            var ajaxUrl = $('.adminSettingsPage').attr('data-ajaxurl');
            var baseUrl = "https://wdem.wedeliver.email/newUI/ept.php";
            var userId = $('.adminSettingsPage').attr('data-userid');
            $('.adminSettings').find('a').addClass('active');

            loadAccountAccess();
            function loadAccountAccess() {
                $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        action: 'loadAccountAccess',
                        userId: userId
                    },
                    success: function(data) {
                        var data = JSON.parse(data);
                        var maxLoop = data.length;
                        var html = '<option>Choose to Switch Account</option>';
            
                        for(var x = 0; x < maxLoop; x++) {
                            var userId      = data[x].userId ? data[x].userId : '';
                            var fbFirstName = data[x].fbFirstName ? data[x].fbFirstName: '';
                            var fbLastName  = data[x].fbLastName ? data[x].fbLastName: '';
                            var appName     = data[x].appName ? data[x].appName: '';
                            var fbEmail     = data[x].fbEmail ? data[x].fbEmail: '';
                            html += '<option value="?uid='+userId+'">'+fbFirstName+"-"+fbLastName+"-"+appName+"-"+fbEmail+'</option>';
                        }

                        $(".accountAccess").html(html);
                    }
                });
            }
            
            $('.accountAccess').on('change', function(){
                var val = $(this).val();
                window.location.href = baseUrl+val;
            });

            listResthook();
            function listResthook() {
                $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        action: 'listResthook',
                        userId: userId
                    },
                    success: function(data) {
                        var data = JSON.parse(data).result;
                        var maxLoop = data.length;
                        var html = "";
                        console.log('data', data)
                        for(var x = 0; x < maxLoop; x++) {
                            html += '<div class="panel panel-default">';
                                html += '<div class="panel-heading">';
                                    html += '<h4 class="panel-title">';
                                        html += '<a data-toggle="collapse" data-parent="#accordion" href="#" aria-expanded="false" class="collapsed">RESThook - '+data[x].key+'</a>';
                                    html += '</h4>';
                                html += '</div>';
                                html += '<div id=" class="panel-collapse collapse in" aria-expanded="false">';
                                    html += '<div class="panel-body">';
                                        html += '<ul>';
                                            html += '<li><strong>Event Key: </strong>'+data[x].eventKey+'</li>';
                                            html += '<li><strong>Hook URL: </strong>'+data[x].hookUrl+'</li>';
                                            html += '<li><strong>Status: </strong>'+data[x].status+'</li>';
                                        html += '</ul>';
                                    html += '</div>';
                                html += '</div>';
                            html += '</div>';
                        }

                        $("#accordion").html(html);
                    }
                });
            }

            var isActive = 0;
            $(".listRestHookBtn").click(function() {
                if(isActive == 0) {
                    $(".restHookArea").show();
                    $(this).text('Hide Resthooks');
                }else {
                    $(".restHookArea").hide(); 
                    $(this).text('List Resthooks');
                }

                isActive = !isActive;
                console.log(isActive)
            });
        });
    </script>
<?php } ?>
