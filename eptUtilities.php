<?php
function getUserDetailsByPlatform($platform)
{
    $userDetails=DB::query("select userId, platform from  tblEptUsers u where platform=%s", $platform);
    return $userDetails;
}

function getUserDetailsAll()
{
    $userDetails=DB::query("select userId, wpuser, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from  tblEptUsers u");
    return $userDetails;
}

function showCols($tblName="tblEptUsers")
{
    $sqlString = "SHOW COLUMNS FROM " . $tblName;
    return DB::query($sqlString);    
}

function getRelay()
{
    //$userDetails=DB::query("show tables from eptdb;");
   // $userDetails=DB::query(sprintf("select * from from tblEptEmailAddStatus_%s;", $GLOBALS['appName']));
  //  return $userDetails;
    
   //show tables from books;
    /*
   DB::$user = 'db';
   DB::$password = 'infusionsoft';
   DB::$dbName = 'email';
   $apiKey = DB::queryFirstField("SELECT apiKey FROM relay");
  
   $user = 'db';
   $password = 'infusionsoft';
   $dbName = 'email';   
   $emailDb = new MeekroDB($user, $password, $dbName);
   echo '<pre>';
   print_r($emailDb);
   echo '</pre>';
   $emailInfo = $emailDb->query("SELECT * FROM relay");
   */
    $user = 'db';
    $pass = 'infusionsoft';
    $dbName = 'email';   
  // $emailDb = new MeekroDB($user, $password, $dbName);
   $emailDb = new MeekroDB(null, $user, $pass, $dbName, null, null);
   $emailInfo = $emailDb->query("SELECT * FROM relay");
   echo '<pre>';
   print_r($emailDb);
   print_r($emailInfo);
   echo '</pre>';
   
}

function getUserDetails($sessionId)
{
   // $userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from  tblEptUsers u where fbIntId=%s", $fbIntId);
   $userDetails=DB::queryFirstRow("select sessionId, u.userId, wpuser, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and sessionId=%s", $sessionId); 
   return $userDetails;
}
function getUserDetailsByUID($uID)
{
    return DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from  tblEptUsers u where userId=%s", $uID);
    //return $userDetails;
    //$userDetails=DB::queryFirstRow("select userId, fbId, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where userId=41");
    //$userDetails=DB::queryFirstRow("select sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and sessionId=%s", $sessionId);
}

function getUserDetailsBySessionID($sessionId)
{
    //return DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from  tblEptUsers u where userId=%s", $uID);
    //return $userDetails;
    //$userDetails=DB::queryFirstRow("select userId, fbId, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers where userId=41");
    return DB::queryFirstRow("select sessionId, u.userId, fbId, fbIntId, fbEmail, fbFirstName, fbLastName, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptSessions s, tblEptUsers u where s.userId=u.userId and sessionId=%s", $sessionId);
}

function callMannyChat()
{   
    $fb = new Facebook\Facebook([
        //    'app_id' => '1800827109959122',
        //    'app_secret' => 'd16608ffc0e1073c9820c4cadb434e08',
            'app_id' => '177981752936708',
            'app_secret' => '2c96617ecbae0925cc5ed9f17bf7b59c',
            'default_graph_version' => 'v2.11',
        ]);	
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email']; // optional
        $loginUrl = $helper->getLoginUrl('https://wdem.wedeliver.email/jerald/login.php', $permissions); 
        
      $retString=sprintf('<div id="registerFB" style="width:200px" title="Register Facebook">
      <p><center>
      It looks like we can\'t find your details in the database. 
      <br/>Have you registered via Messenger yet? 
      <br/>If not, please <a href=\'https://m.me/wedeliver.email?ref=ept\'>go to Messenger to register</a>
      </p>
      <span style="align:center"><p><a href="%s"><img src="/images/fb-button.png" alt="Continue with Facebook"></a></p></span>
      </center>
      </div>
      ', $loginUrl);
      return $retString;   
}

function fbAuthentication()
{   
    $fb = new Facebook\Facebook([
        //    'app_id' => '1800827109959122',
        //    'app_secret' => 'd16608ffc0e1073c9820c4cadb434e08',
            'app_id' => '177981752936708',
            'app_secret' => '2c96617ecbae0925cc5ed9f17bf7b59c',
            'default_graph_version' => 'v2.11',
        ]);	
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email']; // optional
        $loginUrl = $helper->getLoginUrl('https://wdem.wedeliver.email/jerald/login.php', $permissions); 
        
      $retString=sprintf('<div id="authorizeFacebook" style="width:200px" title="Authorise Facebook">
      <center><p>
        It looks like you\'re not authorised with Facebook.<br/> Please click the button below.
      </p>
      <span style="align:center"><p><a href="%s"><img src="/images/fb-button.png" alt="Continue with Facebook"></a></p></span>
      </center>
      </div>
      ', $loginUrl);
      return $retString;   
}


function readRequest($param, $default) {
    if (isset($_REQUEST[$param])) {
            return $_REQUEST[$param];
    } else {
            return $default;
    }
}

function initializedCSS()
{
    return $retString = '
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Email Power Tools</title>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	
    <!-- bootstrap -->
    

    <!-- Bootstrap Core CSS -->
    <link href="./vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="./vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="./dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Morris Charts CSS -->
    <link href="./vendor/morrisjs/morris.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="./vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- Material Design (Tech. preview)-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.1.0/material.min.css" rel="stylesheet"> 
    <link href="https://cdn.datatables.net/1.10.16/css/dataTables.material.min.css" rel="stylesheet"> 
    

    <!-- hover
    <link href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css" rel="stylesheet"> 
    -->

    <!-- jQuery UI ThemeRoller  
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet"> 
    <link href="https://cdn.datatables.net/1.10.16/css/dataTables.jqueryui.min.css" rel="stylesheet"> 
    -->

    
    <!-- W3 CSS -->
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <!-- Custom EPT CSS -->
    <link href="https://fonts.googleapis.com/css?family=Exo+2:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <link href="./dist/css/eptCss.css" rel="stylesheet">    

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn\'t work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->   
    <style type="text/css">
        .styleBox {
            background-color: white;
            min-height: 200px;  
            border-radius: 4px 4px 4px 4px;
			-webkit-border-radius: 4px 4px 4px 4px;
			-moz-border-radius: 4px 4px 4px 4px;   
			-webkit-box-shadow: 0px 0px 5px 0px rgba(0,0,0,0.75);
			-moz-box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.75);
			box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.75);       
        }    
    </style>      
    ';
}

function initializedJavascript()
{
    $retString=sprintf('
   
    <!-- jQuery -->
    <script src="./vendor/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="./vendor/bootstrap/js/bootstrap.min.js"></script>
    
    

    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>  
    <!-- bootstrap --> 
    <script src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>  
    <script src="https://cdn.datatables.net/1.10.16/js/dataTables.jqueryui.min.js"></script> 
    
    <script src="./dist/js/kleanOnDemandScrub.js"></script>
    
    <script>
        function ShowAuth(url) {
            var newWindow = window.open(url, "name", "height=600,width=450");
            if (window.focus) {
                newWindow.focus();
            }
        }
    </script>
    %s', printScriptFunction());
    return $retString;
}

function showUserProfile($userProfile, $infusionsoft) {
    $appName = $userProfile["appName"];
    $userName = $userProfile["fbFirstName"];
    $platform = $userProfile["platform"];
    $opLink='';
    switch ($platform) {
        case "ISFT":
            $opLink = '<a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Change</a>';           
            break;
            
        case "AC":
            $opLink='<a href="?op=acSetApi">Change</a>';
            break;                        
        default:
            $opLink='<a href="?op=mcSetApi">Change</a>';
            break;
    }  
   // $gUrl="https://w3schools.com/w3images/avatar2.png";
//	$gUrl=getGravatar($email);
 //   $gravatar = sprintf('<img src="%s" alt="Gravatar Image" class="w3-circle w3-margin-right" style="width:46px">', $gUrl);
    return sprintf('
    <div style="padding-left:15px">
            <div>
                <span>Welcome, <strong>%s</strong></span><br>
                <span>App: %s
                %s
                </span><br>
            </div>
            
    </div>' , $userName, $appName,  $opLink);
    
}

function getGravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
	$url = 'https://www.gravatar.com/avatar/';
	$url .= md5( strtolower( trim( $email ) ) );
	$url .= "?s=$s&d=$d&r=$r";
	if ( $img ) {
		$url = '<img src="' . $url . '"';
		foreach ( $atts as $key => $val )
		$url .= ' ' . $key . '="' . $val . '"';
		$url .= ' />';
	}
	return $url;
}

function navigationMenu()
{
    $platform=$GLOBALS['platform'];
    $userProfile=$GLOBALS['userDetails'];
    $infusionsoft=$GLOBALS['infusionsoft'];   
    $userHtmlString = showUserProfile($userProfile, $infusionsoft);
    switch ($platform)
    {
        case 'ISFT':
            $viewIndex = 1;
            break;
        case 'AC':
            $viewIndex = 2;
            break;
        default:
            $viewIndex = 1;
            break;
    }
    //viewIndex control menu visibility according to platform
    
    ?>
    <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="ept.php">Email Power Tools</a>
    </div>
    
    <!-- /.navbar-header -->

    <ul class="nav navbar-top-links navbar-right">
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="ept.php">
                <i class="fa fa-envelope fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-messages">
                <li>
                    <a href="ept.php">
                        <div>
                            <strong>John Smith</strong>
                            <span class="pull-right text-muted">
                                <em>Yesterday</em>
                            </span>
                        </div>
                        <div>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque eleifend...</div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <strong>John Smith</strong>
                            <span class="pull-right text-muted">
                                <em>Yesterday</em>
                            </span>
                        </div>
                        <div>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque eleifend...</div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <strong>John Smith</strong>
                            <span class="pull-right text-muted">
                                <em>Yesterday</em>
                            </span>
                        </div>
                        <div>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque eleifend...</div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a class="text-center" href="ept.php">
                        <strong>Read All Messages</strong>
                        <i class="fa fa-angle-right"></i>
                    </a>
                </li>
            </ul>
            <!-- /.dropdown-messages -->
        </li>
        <!-- /.dropdown -->
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="ept.php">
                <i class="fa fa-tasks fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-tasks">
                <li>
                    <a href="ept.php">
                        <div>
                            <p>
                                <strong>Task 1</strong>
                                <span class="pull-right text-muted">40% Complete</span>
                            </p>
                            <div class="progress progress-striped active">
                                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 40%">
                                    <span class="sr-only">40% Complete (success)</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <p>
                                <strong>Task 2</strong>
                                <span class="pull-right text-muted">20% Complete</span>
                            </p>
                            <div class="progress progress-striped active">
                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
                                    <span class="sr-only">20% Complete</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <p>
                                <strong>Task 3</strong>
                                <span class="pull-right text-muted">60% Complete</span>
                            </p>
                            <div class="progress progress-striped active">
                                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                    <span class="sr-only">60% Complete (warning)</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <p>
                                <strong>Task 4</strong>
                                <span class="pull-right text-muted">80% Complete</span>
                            </p>
                            <div class="progress progress-striped active">
                                <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%">
                                    <span class="sr-only">80% Complete (danger)</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a class="text-center" href="ept.php">
                        <strong>See All Tasks</strong>
                        <i class="fa fa-angle-right"></i>
                    </a>
                </li>
            </ul>
            <!-- /.dropdown-tasks -->
        </li>
        <!-- /.dropdown -->
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="ept.php">
                <i class="fa fa-bell fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-alerts">
                <li>
                    <a href="ept.php">
                        <div>
                            <i class="fa fa-comment fa-fw"></i> New Comment
                            <span class="pull-right text-muted small">4 minutes ago</span>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <i class="fa fa-twitter fa-fw"></i> 3 New Followers
                            <span class="pull-right text-muted small">12 minutes ago</span>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <i class="fa fa-envelope fa-fw"></i> Message Sent
                            <span class="pull-right text-muted small">4 minutes ago</span>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <i class="fa fa-tasks fa-fw"></i> New Task
                            <span class="pull-right text-muted small">4 minutes ago</span>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="ept.php">
                        <div>
                            <i class="fa fa-upload fa-fw"></i> Server Rebooted
                            <span class="pull-right text-muted small">4 minutes ago</span>
                        </div>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a class="text-center" href="ept.php">
                        <strong>See All Alerts</strong>
                        <i class="fa fa-angle-right"></i>
                    </a>
                </li>
            </ul>
            <!-- /.dropdown-alerts -->
        </li>
        <!-- /.dropdown -->
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="ept.php">
                <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-user">
                <li><a href="ept.php"><i class="fa fa-user fa-fw"></i> User Profile</a>
                </li>
                <li><a href="ept.php"><i class="fa fa-gear fa-fw"></i> Settings</a>
                </li>
                <li class="divider"></li>
                <li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                </li>
            </ul>
            <!-- /.dropdown-user -->
        </li>
        <!-- /.dropdown -->
    </ul>
    <!-- /.navbar-top-links -->
    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <!--li class="sidebar-search">
                    <div class="input-group custom-search-form">
                        <input type="text" class="form-control" placeholder="Search...">
                        <span class="input-group-btn">
                        <button class="btn btn-default" type="button">
                            <i class="fa fa-search"></i>
                        </button>
                    </span>
                    </div>
                    <!-- /input-group -->
                    
                </li-->
                <?php
                    echo $userHtmlString;
                ?>
                <li class="">
                    <a href="ept.php"><i class="fa fa-fw fa-users"></i> Overview</a>
                </li>
                <?php
                if($viewIndex==1)
                {
                    $showMenu='
                    <li   class="showstatus">
                        <a href="?op=showstatus" class=""><i class="fa fa-fw fa-eye"></i> Show Status</a>
                    </li>
                    ';
                    echo $showMenu;
                }
                ?>
                <li class="emailEngagementReport">
                    <a href="?op=emailEngagementReport"><i class="fa fa-envelope-o fa-fw"></i> Email Engagement Report</a>
                </li>
                <li   class="emailOpenReport">
                    <a href="?op=emailOpenReport"><i class="fa fa-fw fa-envelope"></i> Email Open Report</a>
                </li>
                <?php
                if($viewIndex==1)
                {
                    $showMenu='
                    <li  class="lostCustomersReport">
                        <a href="?op=lostCustomersReport"><i class="fa fa-fw fa-dollar"></i> Lost Customers Report</a>
                    </li>
                    <li class="lostCLV">
                        <a href="?op=lostCLV"><i class="fa fa-fw fa-dollar"></i> Lost Customers Lifetime Value</a>
                    </li>                        
                    <li class="opportunityActivityReport">
                        <a href="ept.php?op=opportunityActivityReport"><i class="fa fa-fw fa-database"></i> Opportunity Activity Report</a>
                    </li>                       
                    <li class="updateOpportunityData">
                        <a href="ept.php?op=updateOpportunityData"><i class="fa fa-fw fa-database"></i> Update Opportunity Data</a>
                    </li> 
                    ';
                    echo $showMenu;
                }
                ?>                                                             
                <li  class="updateContactData">
                    <a href="?op=updateContactData"><i class="fa fa-fw fa-database"></i> Update Contact Data</a>
                </li>              
                <li class="acSetApi">
                    <a href="?op=acSetApi"><i class="fa fa-fw fa-gears"></i> API Settings</a>
                </li>                
                <?php
                if($viewIndex==1)
                {
                    $showMenu='
                    <li class="">
                        <a href="ept.php"><i class="fa fa-fw fa-database"></i> Update Historical Email Open Data</a>
                    </li> 
                    ';
                    echo $showMenu;
                }
                ?>         
                <li class="kleanApiConfig">
                    <a href="ept.php?op=kleanApiConfig"><i class="fa fa-fw fa-cog"></i> Klean13 Integration</a>
                </li>
                <li class="kleanOnDemandScrub">
                    <a href="ept.php?op=kleanOnDemandScrub"><i class="fa fa-fw fa-cog"></i> Klean13 On-Demand Scrub</a>
                </li> 
                <li class="adminSettings">
                    <a href="ept.php?op=adminSettings"><i class="fa fa-fw fa-gears"></i> Admin Settings</a>
                </li>
                <li class="emailAdminSettings">
                    <a href="?op=emailAdminSettings"><i class="fa fa-fw fa-gears"></i> Settings: Infusionsoft + Email Service</a>
                </li>                
                <?php
                if($viewIndex==2)
                {
                    $showMenu='
                    <li>
                        <a href="?op=listresthooks"><i class="fa fa-flash fa-fw"></i> List RESThooks</a>
                    </li>
                    ';
                    echo $showMenu;
                }
                ?>
            </ul>
        </div>
        <!-- /.sidebar-collapse -->
    </div>
    <!-- /.navbar-static-side -->
</nav>          
<?php
}
/*
$iCon = 'fa fa-comments fa-5x'
$panelColor = 'primary'
$itemCount = '0'
$itemText = 'Your Text Here!'
*/
function eptCards( $panelColor = 'primary', $iCon = 'fa fa-comments fa-5x', $itemCount = '0', $itemText = 'Your Text Here!')
{
$createCard=sprintf('
<div class="col-lg-3 col-md-6">
<div class="panel panel-%s">
    <div class="panel-heading">
        <div class="row">
            <div class="col-xs-3">
                <i class="%s"></i>
            </div>
            <div class="col-xs-9 text-right">
                <div class="huge">%s</div>
                <div>%s</div>
            </div>
        </div>
    </div>
    <a href="ept.php">
        <div class="panel-footer">
            <span class="pull-left">View Details</span>
            <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
            <div class="clearfix"></div>
        </div>
    </a>
</div>
</div>
', $panelColor, $iCon, $itemCount, $itemText);
return $createCard;
}

function oldDashboard($panelColor = 'primary', $iCon = 'fa fa-comments fa-5x', $itemCount = '0', $itemText = 'Your Text Here!')
{
    return sprintf('
    <div class="ept-dash-container">
    <div class="panel panel-primary">
    <div class="w3-container %s w3-padding-16">
    
      <div class="w3-left"><i class="%s"></i></div>
      <div class="w3-right">
			
          <h3 style="margin-top: 0px" id="numContacts">%s</h3>
		  
      </div>
      <div class="w3-clear"></div>
      <h4>%s</h4>
    </div>
    </div>
  </div>',
  $panelColor, $iCon, $itemCount, $itemText  
    );
   
}

function multipleEPTCards()
{
    $numContacts='--';
    $numOptedIn='--';
    $numOptedOut='--';
    $numComplained='--';
    $numBounced='--';   
    if ($GLOBALS['web']) {
        ##### IF AJAXCALL EXCLUDE HTML MARK UP #####
        if($GLOBALS['op'] != "ajaxcall") {    
            $appName = $GLOBALS['appName'];
            if (doesContactTableExist($appName)) {
                $numContacts=getTotalContacts($appName);
                $numOptedIn=getOptedInContacts($appName);
                $numOptedOut=getOptedOutContacts($appName);
                $numComplained=getComplainedContacts($appName);
                $numBounced=getBouncedContacts($appName);
            } 
        }
    }
        
    /*order: eptCards( $panelColor, $iCon, $itemCount, $itemText);  */ 
    $retString=sprintf('%s %s %s %s %s',   
    oldDashboard( 'w3-blue', 'fa fa-comments fa-3x', $numContacts, 'Contacts'),
    oldDashboard( 'w3-green', 'fa fa-check-square-o ept-dash-icon', $numOptedIn, 'Marketable'),  
    oldDashboard( 'w3-yellow', 'fa fa-thumbs-o-down ept-dash-icon', $numOptedOut, 'Opted Out'), 
    oldDashboard( 'w3-orange', 'fa fa-remove ept-dash-icon', $numComplained, 'Bounced'),
    oldDashboard( 'w3-red', 'fa fa-exclamation-triangle ept-dash-icon', $numBounced, 'Reported Spam')
    );   
    return $retString;
}

function printScriptFunction()
{
    return sprintf('<script>
    %s
    %s
    %s
    </script>',
    printJQueryDialog('authorizeFacebook'), 
    printJQueryDialog('registerFB'),
    dateValidationCreate()//,
    //addActiveClass()//,
    //adminEmailSelection()
    //createDatatable('tblCLV')
    );
}

function addActiveClass()
{
   return "$('li a').click(function(e) {
        $('li').removeClass('active');
        $(this).closest('li').addClass('active');
    });";

}
function kleanScrub()
{
    return '$(function() {
        $(".kleanOnDemandScrub").find("a").addClass("active");
    });';
}

function createDatatable($tblID)
{
    return sprintf('
        $(document).ready( function () {
            $(\'#%s\').DataTable();
        } );    
    ',$tblID);
}

function printJQueryDialog($divID)
{
   return sprintf('$( "#%s" ).dialog({
        modal: true,
        width:\'auto\',
        closeOnEscape: false,
        draggable: false,
        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
      });
      ', $divID);
}

function dateValidationCreate()
{
    return "
    function subscrideDateController(radButton)
    {
        
        switch (radButton) 
        { 
            case 1    : 
                    document.frmDateRange.firstClick.type  = 'hidden'; 
                    document.frmDateRange.lastClick.type  = 'hidden';  
                    document.getElementById('earliestDateLabel').style.display = 'none';
                    document.getElementById('latestDateLabel').style.display = 'none';
                    document.getElementById('firstClickValue').value='0';
                    break; 
            case 2    : 
                    document.frmDateRange.firstClick.type  = 'date'; 
                    document.frmDateRange.lastClick.type  = 'date'; 
                    document.getElementById('earliestDateLabel').style.display = 'inline';
                    document.getElementById('latestDateLabel').style.display = 'inline';
                    document.getElementById('firstClickValue').value='1';
                    break;  
            case 3    : 
                    document.frmDateRange.earliestOrder.type  = 'hidden'; 
                    document.frmDateRange.latestOrder.type  = 'hidden'; 
                    document.getElementById('latestOrderLabel').style.display = 'none';
                    document.getElementById('earliestOrderLabel').style.display = 'none'; 
                    document.getElementById('earliestClickValue').value='0';                           
                    break;
            case 4    : 
                    document.frmDateRange.earliestOrder.type  = 'date'; 
                    document.frmDateRange.latestOrder.type  = 'date';
                    document.getElementById('latestOrderLabel').style.display = 'inline';
                    document.getElementById('earliestOrderLabel').style.display = 'inline'; 
                    document.getElementById('earliestClickValue').value='1';                             
                    break;                                                   
            default    : 
                    alert('What to do?');  
        }
        
    }     
    ";

}

function printProgressBarContainer() {	   
    return '<script>
    function setProgressBar(percent) {
        var elem = document.getElementById("progressBar"); 
        elem.style.width = percent + \'\%\'; 
        var elem2 = document.getElementById("progressBar"); 
        elem2.style.width = percent + \'\%\';         
    }
    </script>
    <p id="eeProgressContainer" style="align:center">
        <span id="pleaseWait">Please Wait:</span> <span id="eeProgressTitle"><!-- empty to start with --></span>&nbsp;<span id="eeProgressDetail"><!-- empty to start with --></span>
    </p>
    <!--div class="w3-dark-grey w3-round-xlarge" style="padding:0px;visibility:hidden">
        <div id="progressBar" class="w3-container w3-blue w3-round-xlarge" style="padding:0px;height:25px;width:0%"></div>
    </div-->
    <div class="progress progress-striped active">
    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
    </div>    
    ';    
    }

    function syncXmlToMySql($appName, $tableName, $lastUpdate, $fullCriteria, $updateCriteria) {
        // 1. Save the current date and time, as we'll need that later to record when we last updated the MySql table
        $queryStartTime=new DateTime("now");       

        // 2. Ensure table exists
        createMySqlTable($appName, $tableName);
 
        // 3. Has the table been updated before? If not, populate the whole thing; otherwise just do an update
        //debugOut(sprintf("<p>%s table last updated %s</p>", $tableName, $lastUpdate));

        if ($lastUpdate == NULL) {
            printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Reading %s Table:";</script>', $tableName);
            $criteria=$fullCriteria;
        } else {
            printf('<script>var pcDiv = document.getElementById("eeProgressTitle"); pcDiv.innerHTML = "Updating %s Table:";</script>', $tableName);
            $criteria=$updateCriteria;
        }
        echo '$queryStartTime ' . 3;
        // 4. Query the count of everything in the right order to estimate the time it'll take
        $totalPages=0;
        $totalRecords=0;
        foreach ($criteria as $queryData) {
            $count=countXmlTableRows($tableName, $queryData);
            $totalPages+=ceil($count/1000)+1;
            $totalRecords+=$count;
        }
        printf("<p>Total pages: %d</p>\n", $totalPages);
        
        // 5. Query everything in the right order, update the MySQL database and update the screen status
        $cumulativePages=0;
        $cumulativeRecords=0;
        foreach ($criteria as $queryData) {
            $page=0;
            do {
                $count=getXmlDataQueryPage($appName, $tableName, $queryData, $page);
                $page++;
                $cumulativePages++;
                $cumulativeRecords+=$count;
                printf('<script>var pcDiv = document.getElementById("eeProgressDetail"); pcDiv.innerHTML = "Page %d - Fetched %d/%d records";</script>', $cumulativePages, $cumulativeRecords, $totalRecords);
                printf('<script>setProgressBar(%d);</script>' . "\n", 100*$cumulativePages/$totalPages);
            } while($count > 0);
        }
        
        // 6. Update the Date and Time that the table was last updated
        setMySqlLastUpdate($appName, $tableName, $queryStartTime);	
    }   

    function debugOut($txt) {
        global $debug;
        if ($debug) {
            printf('<script>addTextToDiv("debugDiv",\'%s\'); window.scrollTo(0, document.getElementById("debugWindow").scrollHeight);</script>%s', $txt, "\n");
        }
    }   
    function debugPrintR($var) {
        debugOut("<pre>".str_replace("\n","",nl2br(print_r($var,true)))."</pre>");
    }     
/*
$("#dia").dialog({
    autoOpen: true,
    height: 200,
    width: 300,
    modal: false,
    draggable: true,
    position: [900, 150],
    dialogClass: "foo",
    //show: {effect: 'bounce', duration: 350, times: 3}
    show: {effect: 'fade', duration: 2000}
});

$(".fbAuthfoo .ui-dialog-titlebar").css("display", "none");
$(".fbAuth .ui-dialog-title").css("font-size", "5px");
$(".fbAuth .ui-widget-header").css("display", "none");
$(".fbAuth .ui-widget-content").css("background-color", "#DD3355");
$(".fbAuth .ui-widget-content").css("font-size", "12px");
$(".ui-corner-all").css("background-color", "green");
$(".ui-resizable-n").css("background-image", "none");
*/

?>