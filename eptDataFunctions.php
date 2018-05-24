<?php

$optTypeTranslate=array(
	"SingleOptIn"            => "Unconfirmed",
	"UnengagedMarketable"    => "Unengaged Marketable",
	"DoubleOptin"            => "Confirmed",
	"Confirmed"              => "Confirmed",
	"UnengagedNonMarketable" => "Unengaged Non-Marketable",
	"NonMarketable"          => "Non-Marketable",
	"Lockdown"               => "Lockdown",
	"Bounce"                 => "Bounce (too many times)",
	"HardBounce"             => "Hard Bounce",
	"Manual"                 => "Opted Out",
	"Admin"                  => "Admin Opted Out",
	"System"                 => "System Opted Out",
	"ListUnsubscribe"        => "List Unsubscribe",
	"Feedback"               => "ISP Spam Complaint",
	"Spam"                   => "Spam Complaint",
	"Invalid"                => "Invalid Address"
);

function createEmailSentSummaryTable($appName) {
	//printf("<p>Creating EmailSentSummary Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`MailBatchId` varchar(255) NOT NULL,
		`DateSent` DateTime,
		`#Sent` bigInt(20),
		`#Opened` bigInt(20),
		`#Clicked` bigInt(20),
		`#OptOut` bigInt(20),
		`#Bounce` bigInt(20),
		`#ISPSpamComplaints` bigInt(20),
		`#InternalSpamComplaints` bigInt(20),
		PRIMARY KEY (`MailBatchId`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptEmailSentSummary_%s",$appName));
}

function createInvoiceTable($appName) {
	//printf("<p>Creating Invoice Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` bigInt(20) NOT NULL,
		`ContactId` bigInt(20) NOT NULL,
		`InvoiceTotal` Double,
		`TotalDue` Double,
		`TotalPaid` Double,
		`DateCreated` DateTime,
		`LastUpdated` DateTime,
		PRIMARY KEY (`id`),
		KEY (`ContactId`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptInvoice_%s",$appName));
}

function createContactTable($appName) {
	//printf("<p>Creating Contact Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` varchar(255) NOT NULL,
		`FirstName` varchar(255),
		`LastName` varchar(255),
		`Email` varchar(255),
		`EmailAddress2` varchar(255),
		`EmailAddress3` varchar(255),
		`Phone1` varchar(255),
		`LastUpdated` DateTime,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`email`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptContact_%s",$appName));
}

function createEmailAddStatusTable($appName) {
	//printf("<p>Creating EmailAddStatus Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` varchar(255) NOT NULL,
		`Email` varchar(255),
		`LastOpenDate` DateTime,
		`LastClickDate` DateTime,
		`LastSentDate` DateTime,
		`Type` varchar(255) NOT NULL DEFAULT %s,
		PRIMARY KEY (`id`),
		UNIQUE KEY (`email`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptEmailAddStatus_%s",$appName), "Not Set");
}

function createStageTable($appName) {
	//printf("<p>Creating EmailAddStatus Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` varchar(255) NOT NULL,
		`StageName` varchar(255),
		`TargetNumDays` bigInt(20),
		`StageOrder` bigInt(20),
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptStage_%s",$appName), "Not Set");
}

function createStageMoveTable($appName) {
	//printf("<p>Creating EmailAddStatus Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` bigInt(20) NOT NULL,
		`OpportunityId` bigInt(20) NOT NULL,
		`UserId` bigInt(20) NOT NULL,
		`DateCreated` DateTime,
		`CreatedBy` bigInt(20) NOT NULL,
		`PrevStageMoveDate` DateTime,
		`MoveFromStage` bigInt(20) NOT NULL,
		`MoveToStage` bigInt(20) NOT NULL,
		`MoveDate` DateTime,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptStageMove_%s",$appName), "Not Set");
}

function createLeadTable($appName) {
	//printf("<p>Creating EmailAddStatus Table</p>");
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` bigInt(20) NOT NULL,
		`UserId` bigInt(20) NOT NULL,
		`StageID` bigInt(20) NOT NULL,
		`DateCreated` DateTime,
		`LastUpdated` DateTime,
		`Leadsource` varchar(255),
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptLead_%s",$appName), "Not Set");
}

function createUsersTable($appName) {
	DB::query("CREATE TABLE IF NOT EXISTS %l (
		`Id` bigInt(20) NOT NULL,
		`FirstName` varchar(255) NOT NULL,
		`LastName` varchar(255) NOT NULL,
		`DateCreated` DateTime,
		`LastUpdated` DateTime,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptUser_%s",$appName), "Not Set");
}
	
function createMySqlTable($appName, $tableName) {
	if ($tableName == "Contact") {
		createContactTable($appName);
	} elseif ($tableName == "Invoice") {
		createInvoiceTable($appName);
	} elseif ($tableName == "EmailAddStatus") {
		createEmailAddStatusTable($appName);
	} elseif ($tableName == "EmailSentSummary") {
		createEmailSentSummaryTable($appName);
	} elseif ($tableName == "Lead") {
		createLeadTable($appName);
	} elseif ($tableName == "StageMove") {
		createStageMoveTable($appName);
	} elseif ($tableName == "Stage") {
		createStageTable($appName);
	} elseif ($tableName == "User") {
		createUsersTable($appName);
	}
}

function getAllMySqlLastUpdate($appName) {
	$status=DB::query("select tableName, lastComplete, DATEDIFF(CURDATE(),lastComplete) daysAgo from tblEptAppStatus where appName=%s", $appName);
	return $status;		
}

function getOpportunityActivityReport($appName, $type) {
	$sql = "SELECT ";
			
			if($type == 'daily') {
				$sql .= "DATE_FORMAT(sm.MoveDate, '%a %e %M %Y') moveDateFormatted, ";
			}else if($type == 'weekly') {
				$sql .= "DATE_FORMAT(DATE_ADD(sm.MoveDate, INTERVAL(-WEEKDAY(sm.MoveDate)) DAY), '%a %e %M %Y') moveDateFormatted, ";
			}else if($type == 'monthly') {
				$sql .= "DATE_FORMAT(sm.MoveDate, '%M %Y') moveDateFormatted, ";
			}
	$sql .= "
			date(sm.MoveDate) DD, 
			sm.UserId, 
			sm.MoveToStage, 
			count(sm.UserId) NumOpps,
			s.StageName,
			s.ID,
			u.FirstName,
			u.LastName
			FROM eptdb.tblEptStageMove_".$appName." sm
			INNER JOIN eptdb.tblEptStage_".$appName." s
			ON s.Id = sm.MoveToStage
			INNER JOIN eptdb.tblEptUser_".$appName." u
			ON u.Id = sm.UserId
			WHERE sm.MoveFromStage != sm.MoveToStage ";

			if($type == 'daily') {
				$sql .= "GROUP BY date(sm.MoveDate), ";
			}else if($type == 'weekly') {
				$sql .= "GROUP BY YEAR(sm.MoveDate), WEEKOFYEAR(sm.MoveDate), ";
			}else if($type == 'monthly') {
				$sql .= "GROUP BY YEAR(sm.MoveDate), MONTH(sm.MoveDate), ";
			}

	$sql .= "
			sm.UserId, sm.MoveToStage 
			ORDER BY DD DESC";
	$status=DB::query($sql);

	return $status;		
}

function getOpportunityActivityReportSummary($appName, $days) {	

	// $engagement=DB::queryFirstRow("select count(Sent) Sent, Sum(Opened) Opened, Sum(Clicked) Clicked from (select Id, Max(lastSentDate) Sent, if (Max(lastOpenDate) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, 0)) Opened, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, 0) Clicked from ( select C.Id, 'Email1', lastSentDate, lastOpenDate, lastClickDate from %l C, %l S where C.Email=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE() UNION select C.Id, 'Email2', lastSentDate, lastOpenDate, lastClickDate from %l C, %l S where C.EmailAddress2=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE()) AA group by Id) AB", $days, $days, $days, sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days, sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);

	// $allNotSent=DB::queryFirstField("select count(lastSentDate) from %l, %l where %l.Email=%l.Email and lastSentDate < CURDATE() - INTERVAL %d DAY", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);

	// $marketableNotSent=DB::queryFirstField("select count(lastSentDate) from %l, %l where %l.Email=%l.Email and lastSentDate < CURDATE() - INTERVAL %d DAY and Type In ('Confirmed', 'DoubleOptin', 'SingleOptIn', 'UnengagedMarketable')", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);

	// // printf("<p>Last %d Days:</p>", $days);
	// // printf("<p>Sent: %d -- ", $engagement["Sent"]);
	// // printf("Opened: %d (%3.1f%%) -- ", $engagement["Opened"], 100*$engagement["Opened"]/$engagement["Sent"]);
	// // printf("Clicked: %d (%3.1f%%)</p>", $engagement["Clicked"], 100*$engagement["Clicked"]/$engagement["Sent"]);
	// // printf("<p>Not Sent Anything: %d</p>", $allNotSent);
	// // printf("<p>Marketable and Not Sent Anything: %d</p>", $marketableNotSent);
	
	// return array("Sent"=>$engagement["Sent"], "Opened"=>$engagement["Opened"], "Clicked"=>$engagement["Clicked"], "NotSent"=>$allNotSent, "OptInNotSent"=>$marketableNotSent);


	$query=DB::query("SELECT 
					date(sm.MoveDate) DD, 
					sm.UserId, 
					sm.MoveToStage, 
					count(sm.UserId) NumOpps,
					s.StageName,
					s.ID,
					u.FirstName,
					u.LastName
					FROM eptdb.tblEptStageMove_".$appName." sm
					INNER JOIN eptdb.tblEptStage_".$appName." s
					ON s.Id = sm.MoveToStage
					INNER JOIN eptdb.tblEptUser_".$appName." u
					ON u.Id = sm.UserId
					WHERE sm.MoveFromStage != sm.MoveToStage AND
					sm.MoveDate > NOW() - INTERVAL %d DAY
					GROUP BY date(sm.MoveDate), sm.UserId, sm.MoveToStage 
					ORDER BY DD DESC", $days);

	$totalNumOpps = 0;
	$stage = array();
	$numOpps = array();
	$totalWon = 0;
	$totalLost = 0;
	foreach($query as $row) {
		$totalNumOpps += $row['NumOpps'];
		array_push($stage, $row['StageName']);
		array_push($numOpps, $row['NumOpps']);

		if(strpos($row['StageName'], 'Won') !== false) {
		    $totalWon += $row['NumOpps'];
		}else if(strpos($row['StageName'], 'Lost') !== false) {
		    $totalLost += $row['NumOpps'];
		}
	}

	return array(
		"days" => $days,
		"totalNumOpps" => $totalNumOpps,
		"stage" => $stage,
		"numOpps" => $numOpps,
		"totalWon" => $totalWon,
		"totalLost" => $totalLost
	);
}

function getMySqlLastUpdate($appName, $tableName) {
	$lastUpdate=DB::queryFirstField("select lastComplete from tblEptAppStatus where appName=%s and tableName=%s", $appName, $tableName);
	return $lastUpdate;		
}

function setMySqlLastUpdate($appName, $tableName, $lastUpdate) {
	$lastUpdate->setTimezone(new DateTimeZone('America/New_York'));
    DB::insertUpdate("tblEptAppStatus", array("appName"=>$appName, "tableName"=>$tableName, "lastComplete"=>$lastUpdate));	
}

function countXmlTableRows($table, $queryData) {
	global $infusionsoft;
	$count=$infusionsoft->data()->count($table, $queryData);
	return $count;
}

function getXmlDataQueryPage($appName, $tableName, $queryData, $page, $maxRows=1000) {
	global $infusionsoft;

	if ($tableName == "EmailAddStatus") {
		$columns=array("Id","Email","LastOpenDate","LastClickDate","LastSentDate","Type");		
	} elseif ($tableName == "Contact") {
		$columns=array("Id","FirstName","LastName","Email","EmailAddress2","EmailAddress3","Phone1","LastUpdated");
	} elseif ($tableName == "Invoice") {
		$columns=array("Id","ContactId","DateCreated","LastUpdated","InvoiceTotal","TotalDue","TotalPaid");	
	} elseif ($tableName == "Lead") {
		$columns=["Id", "UserId", "StageID", "DateCreated", "LastUpdated", "Leadsource"];
	} elseif ($tableName == "StageMove") {
		$columns=["Id","OpportunityId","UserId","DateCreated","CreatedBy","PrevStageMoveDate","MoveFromStage","MoveToStage","MoveDate"];
	} elseif ($tableName == "Stage") {
		$columns=["Id", "StageName", "TargetNumDays", "StageOrder"];
	}elseif ($tableName == "User") {
	$columns=array("Id","FirstName","LastName");	
	}
 	$orderBy="Id";
	$ascending=true;
	
	$count=0;
	
	$done=0;
	$results=array();
	for ($attempt = 1; $attempt <= 3 && !$done; $attempt++) {
		try {
				$results=$infusionsoft->data()->query($tableName, $maxRows, $page, $queryData, $columns, $orderBy, $ascending);
			$done = true;
		} catch(Exception $e) {
			debugOut(sprintf("<p>Caught exception: page %d<p>", $page));
//			print("<pre style="font-size:8px">".str_replace("\n","",nl2br(print_r($e,true)))."</pre>");
			sleep($attempt);
		}
	}
	if (!$done) {
		debugOut(sprintf("<p>Page %d timed out<p>", $page));
		if ($maxRows == 1) {
			printf("<p>Caught exception: page %d<p>\n", $page);
			print("<pre style='font-size:8px'>".str_replace("\n","",nl2br(htmlspecialchars(print_r($e->getMessage(),true))))."</pre>");
			return 1;
		}
		// Let's try it going at one-tenth of the speed...
		$totCount=0;
		$newPage=$page*10;
		$maxPage=$newPage+10;
		$newMaxRows=$maxRows/10;
		do {
			debugOut(sprintf("<p>getXmlDataQueryPage(%s, %s, %s, %s)</p>", $appName, $tableName, $newPage, $newMaxRows));
			$thisCount=getXmlDataQueryPage($appName, $tableName, $queryData, $newPage, $newMaxRows);
			$count+=$thisCount;
			$newPage++;
		} while(($thisCount > 0) && ($newPage < $maxPage));
		return $count;
	}
		
	foreach ($results as $result) {
		DB::insertUpdate(sprintf("tblEpt%s_%s",$tableName,$appName), $result);
		$count++;
	}
		
	return $count;
}

function getSavedSearchPage($appName, $tableName, $reportId, $searchUserId, $page) {
	global $infusionsoft;

	if ($tableName == "EmailSentSummary") {
		$columns=array('MailBatchId', 'DateSent', '#Sent', '#Opened', '#Clicked', '#OptOut', '#Bounce', '#ISPSpamComplaints', '#InternalSpamComplaints');
	}
	
	$count=0;
	
	$done=0;
	$results=array();
	for ($attempt = 1; $attempt <= 3 && !$done; $attempt++) {
		try {
			$results=$infusionsoft->search()->getSavedSearchResults($reportId, $searchUserId, $page, $columns);
			$done = true;
		} catch(Exception $e) {
			debugOut(sprintf("<p>Caught exception: page %d<p>", $page));
			print("<pre style='font-size:8px'>".str_replace("\n","",nl2br(print_r($e->getMessage(),true)))."</pre>");
			sleep($attempt);
		}
	}
	if (!$done) {
		debugOut(sprintf("<p>Page %d timed out<p>", $page));
		return 1;
	}
		
	foreach ($results as $result) {
		DB::insertUpdate(sprintf("tblEpt%s_%s",$tableName,$appName), $result);
		$count++;
	}
		
	return $count;
}

function doesContactTableExist($appName) {
	$result = DB::query("SHOW TABLES LIKE %s", sprintf("tblEptContact_%s",$appName));
	return count($result) > 0;
}

function doesTableExist($appName, $tableName) {
	$result = DB::query("SHOW TABLES LIKE %s", sprintf("tblEpt%s_%s",$tableName,$appName));
	return count($result) > 0;
}

// THIS NEEDS TO BE MADE CONTACT CENTRIC NEXT
function getTotalContacts($appName) {
	$numOptedIn=DB::queryFirstField("select count(Id) OptedIn from %l", sprintf("tblEptContact_%s",$appName));
	return $numOptedIn;	
}


// ADDED "Subscribed" for ActiveCampaign
function getOptedInContacts($appName) {
	$numOptedIn=DB::queryFirstField("select count(type) OptedIn from %l, %l where %l.Email=%l.Email and type in ('UnengagedMarketable','SingleOptIn','Confirmed','DoubleOptin','Subscribed')", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName));
	return $numOptedIn;	
}

// ADDED "Opted Out" for ActiveCampaign
function getOptedOutContacts($appName) {
	$numOptedOut=DB::queryFirstField("select count(type) OptedOut from %l, %l where %l.Email=%l.Email and type in ('Manual','Admin','ListUnsubscribe','Opted Out')", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName));
	return $numOptedOut;	
}

function getBouncedContacts($appName) {
	$numBounced=DB::queryFirstField("select count(type) Bounced from %l, %l where %l.Email=%l.Email and type in ('Bounce','HardBounce')", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName));
	return $numBounced;	
}

// ADDED "Spam Complaint" for ActiveCampaign
function getComplainedContacts($appName) {
	$numComplaints=DB::queryFirstField("select count(type) Complained from %l, %l where %l.Email=%l.Email and type in ('Spam','Feedback','Spam Complaint')", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName));
	return $numComplaints;	
}

function getLatestOpenRates($appName, $days) {
	debugOut("<p>getLatestOpenRates($days)</p>");
	
	$stats=DB::queryFirstRow("select sum(`#Sent`) Sent,
		sum(`#Opened`) Opened, 
		100*sum(`#Opened`)/sum(`#Sent`) `Open%`, 
		sum(`#Clicked`) Clicked, 
		100*sum(`#Clicked`)/sum(`#Sent`) `Click%`, 
		sum(`#OptOut`) OptedOut, 
		100*sum(`#OptOut`)/sum(`#Sent`) `OptOut%`, 
		sum(`#Bounce`) Bounced, 
		100*sum(`#Bounce`)/sum(`#Sent`) `Bounce%`, 
		sum(`#ISPSpamComplaints`)+sum(`#InternalSpamComplaints`) Complaints, 
		100*(sum(`#ISPSpamComplaints`)+sum(`#InternalSpamComplaints`))/sum(`#Sent`) `Complaint%`
		 from %l where DateSent BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE()", sprintf("tblEptEmailSentSummary_%s",$appName), $days);
		 
	 return $stats;
}

function getHistoricalOpenRates($appName) {
	debugOut("<p>getHistoricalOpenRates</p>");
	
	$stats=DB::query("select month(DateSent) M, year(DateSent) Y, 
		sum(`#Sent`) Sent,
		sum(`#Opened`) Opened, 
		100*sum(`#Opened`)/sum(`#Sent`) `Open%`, 
		sum(`#Clicked`) Clicked, 
		100*sum(`#Clicked`)/sum(`#Sent`) `Click%`, 
		sum(`#OptOut`) OptedOut, 
		100*sum(`#OptOut`)/sum(`#Sent`) `OptOut%`, 
		sum(`#Bounce`) Bounced, 
		100*sum(`#Bounce`)/sum(`#Sent`) `Bounce%`, 
		sum(`#ISPSpamComplaints`)+sum(`#InternalSpamComplaints`) Complaints, 
		100*(sum(`#ISPSpamComplaints`)+sum(`#InternalSpamComplaints`))/sum(`#Sent`) `Complaint%`
		 from %l group by Y, M order by Y, M", sprintf("tblEptEmailSentSummary_%s",$appName));
		 
	 return $stats;
}

function getEngagementStats($appName, $days) {	
	// ENGAGEMENT QUERIES USING Contact and EmailAddStatus TABLES

	// WITHIN THE LAST __ DAYS (e.g. 30)
	// - People who have been sent anything
	// - People who have been sent anything AND OPENED
	// - People who have been sent anything AND CLICKED
	// - All People who have not been sent anything
	// - Opted In People who have not been sent anything

	debugOut("<p>getEngagementStats($days)</p>");

	// Number of people sent within 30 days and of those, number of people who opened something

	// Email1 only
	// $engagement=DB::queryFirstRow("select count(lastSentDate) Sent, sum(if(lastOpenDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, 0)) Opened, sum(if(lastClickDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, 0)) Clicked from %l, %l where %l.Email=%l.Email and lastSentDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE()", $days, $days, sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);
	
	// Email1 and Email2
	$engagement=DB::queryFirstRow("select count(Sent) Sent, Sum(Opened) Opened, Sum(Clicked) Clicked from (select Id, Max(lastSentDate) Sent, if (Max(lastOpenDate) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, 0)) Opened, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE(), 1, 0) Clicked from ( select C.Id, 'Email1', lastSentDate, lastOpenDate, lastClickDate from %l C, %l S where C.Email=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE() UNION select C.Id, 'Email2', lastSentDate, lastOpenDate, lastClickDate from %l C, %l S where C.EmailAddress2=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL %d DAY AND CURDATE()) AA group by Id) AB", $days, $days, $days, sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days, sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);
	

	$allNotSent=DB::queryFirstField("select count(lastSentDate) from %l, %l where %l.Email=%l.Email and lastSentDate < CURDATE() - INTERVAL %d DAY", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);

	$marketableNotSent=DB::queryFirstField("select count(lastSentDate) from %l, %l where %l.Email=%l.Email and lastSentDate < CURDATE() - INTERVAL %d DAY and Type In ('Confirmed', 'DoubleOptin', 'SingleOptIn', 'UnengagedMarketable')", sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $days);

	// printf("<p>Last %d Days:</p>", $days);
	// printf("<p>Sent: %d -- ", $engagement["Sent"]);
	// printf("Opened: %d (%3.1f%%) -- ", $engagement["Opened"], 100*$engagement["Opened"]/$engagement["Sent"]);
	// printf("Clicked: %d (%3.1f%%)</p>", $engagement["Clicked"], 100*$engagement["Clicked"]/$engagement["Sent"]);
	// printf("<p>Not Sent Anything: %d</p>", $allNotSent);
	// printf("<p>Marketable and Not Sent Anything: %d</p>", $marketableNotSent);
	
	return array("Sent"=>$engagement["Sent"], "Opened"=>$engagement["Opened"], "Clicked"=>$engagement["Clicked"], "NotSent"=>$allNotSent, "OptInNotSent"=>$marketableNotSent);
}

function maintAddColumn($table, $alterQuery) {
	$appList=DB::query("select appName from tblEptAppStatus where tableName=%s", $table);
	
	//ignore errors (BAD IDEA)
	DB::$error_handler = false;
	
	foreach ($appList as $app) {
		$appName=$app["appName"];
		printf("App: %s\n", $appName);
		$result=DB::query($alterQuery, sprintf("tblEpt%s_%s", $table, $appName));
		print_r($result);
	}
}

function getLostCustomerData($appName) {
	$optTypeCriteria="and Type Not In ('SingleOptIn', 'DoubleOptin', 'Confirmed')";
	$orderCriteria="and LatestOrder > CURDATE() - INTERVAL 12 MONTH";
	$lastOpenCriteria="and (Type = 'HardBounce' or LastOpenDate < CURDATE() - INTERVAL 12 MONTH or LastOpenDate IS NULL)";
	
	$data=DB::query("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalValue, Type, LastSentDate, LastOpenDate
	from %l i, %l c, %l s
	where i.ContactId=c.Id and c.Email=s.Email %l
	group by i.ContactId
	having TotalValue > 50 %l %l
	order by TotalValue DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria,
	$orderCriteria, $lastOpenCriteria); 
	
	
	$dbgQuery=sprintf("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalValue, Type, LastSentDate, LastOpenDate
	from %s i, %s c, %s s
	where i.ContactId=c.Id and c.Email=s.Email %s
	group by i.ContactId
	having TotalValue > 50 %s %s
	order by TotalValue DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria,
	$orderCriteria, $lastOpenCriteria);
		
	printf("QUERY: %s\n", $dbgQuery);
	debugOut($dbgQuery);
	
	return $data;
}
//function getLostCustomerValueReportData($appName, $firstClick='1980-01-01', $lastClick='2080-12-31', $earliestOrder='1980-01-01', $latestOrder='2080-12-31') //original format
function getLostCustomerValueReportData($appName, $firstClick, $lastClick, $earliestOrder, $latestOrder) {	
	$optTypeCriteria="and Type Not In ('SingleOptIn', 'DoubleOptin', 'Confirmed', 'UnengagedMarketable', 'UnengagedNonmarketable')";
	
	$data=DB::query("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalPurchased, Sum(TotalPaid) TotalPaid, Type, LastClickDate, LastSentDate, LastOpenDate
	from %l i, %l c, %l s
	where i.ContactId=c.Id and c.Email=s.Email %l
	and i.DateCreated >= %t and i.DateCreated <= %t
	and s.LastClickDate >= %t and s.LastClickDate <= %t
	group by i.ContactId
	order by TotalPurchased DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria, $earliestOrder, $latestOrder, $firstClick, $lastClick); 
	
	$dbgQuery=sprintf("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalPurchased, Sum(TotalPaid) TotalPaid, Type, LastClickDate, LastSentDate, LastOpenDate
	from %s i, %s c, %s s
	where i.ContactId=c.Id and c.Email=s.Email %s
	and i.DateCreated >= %s and i.DateCreated <= %s
	and s.LastClickDate >= %s and s.LastClickDate <= %s
	group by i.ContactId
	order by TotalPurchased DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria, $earliestOrder, $latestOrder, $firstClick, $lastClick); 
	
	printf("QUERY: %s\n", $dbgQuery);
	
	
	return $data;	
}
function showGetLostCustomerValueReportData2($appName, $firstClick='1980-01-01', $lastClick='2080-12-31', $earliestOrder='1980-01-01', $latestOrder='2080-12-31') {
	
	$optTypeCriteria="and Type Not In ('SingleOptIn', 'DoubleOptin', 'Confirmed', 'UnengagedMarketable', 'UnengagedNonmarketable')";
	
	$data=DB::query("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalPurchased, Sum(TotalPaid) TotalPaid, Type, LastClickDate, LastSentDate, LastOpenDate
	from %l i, %l c, %l s
	where i.ContactId=c.Id and c.Email=s.Email %l
	and i.DateCreated >= %t and i.DateCreated <= %t
	group by i.ContactId
	order by TotalPurchased DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria, $earliestOrder, $latestOrder); 
	
	$dbgQuery=sprintf("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalPurchased, Sum(TotalPaid) TotalPaid, Type, LastClickDate, LastSentDate, LastOpenDate
	from %s i, %s c, %s s
	where i.ContactId=c.Id and c.Email=s.Email %s
	and i.DateCreated >= %s and i.DateCreated <= %s
	and s.LastClickDate >= %s and s.LastClickDate <= %s
	group by i.ContactId
	order by TotalPurchased DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria, $earliestOrder, $latestOrder, $firstClick, $lastClick); 
	
	printf("QUERY: %s\n", $dbgQuery);	
	/*
	$optTypeCriteria="and Type Not In ('SingleOptIn', 'DoubleOptin', 'Confirmed', 'UnengagedMarketable', 'UnengagedNonmarketable')";
	
	$data=DB::query("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalPurchased, Sum(TotalPaid) TotalPaid, Type, COALESCE(LastClickDate,'2080-12-31') AS modLastClickDate  , LastSentDate, LastOpenDate
	from %l i, %l c, %l s
	where i.ContactId=c.Id and c.Email=s.Email %l
	and i.DateCreated >= %t and i.DateCreated <= %t
	and s.modLastClickDate >= %t and s.modLastClickDate <= %t
	group by i.ContactId
	order by TotalPurchased DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria, $earliestOrder, $latestOrder, $firstClick, $lastClick); 
	
	$dbgQuery=sprintf("select c.Id Id, FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalPurchased, Sum(TotalPaid) TotalPaid, Type, COALESCE(LastClickDate,'2080-12-31') AS modLastClickDate, LastSentDate, LastOpenDate
	from %s i, %s c, %s s
	where i.ContactId=c.Id and c.Email=s.Email %s
	and i.DateCreated >= %s and i.DateCreated <= %s
	and s.modLastClickDate >= %s and s.modLastClickDate <= %s
	group by i.ContactId
	order by TotalPurchased DESC
	LIMIT 100", sprintf("tblEptInvoice_%s",$appName), sprintf("tblEptContact_%s",$appName), sprintf("tblEptEmailAddStatus_%s",$appName), $optTypeCriteria, $earliestOrder, $latestOrder, $firstClick, $lastClick); 
	
	printf("QUERY: %s\n", $dbgQuery);
	
	
	return $data;	
	*/
	return $data;	
}

//end testing
/**

CREATE TABLE `tblEptAppStatus` (
	`appId` bigInt(20) NOT NULL AUTO_INCREMENT,
	`appName` varchar(60),
	`tableName` varchar(255),
	`lastComplete` DateTime,
	PRIMARY KEY (`appId`),
	UNIQUE KEY (`appName`, `tableName`),
	KEY (`appName`),
	KEY (`tableName`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `tblEptAppStatus` ADD `emailSentSummaryUpdateLastComplete` DateTime;

	`emailAddStatusUpdateLastComplete` DateTime,
	`contactUpdateLastComplete` DateTime,
	`emailSentSummaryUpdateLastComplete` DateTime,


DB::query("CREATE TABLE IF NOT EXISTS %l (
	`id` bigInt(20) NOT NULL,
	`email` varchar(255),
	`dateCreated` DateTime,
	`lastOpenDate` DateTime,
	`lastClickDate` DateTime,
	`lastSentDate` DateTime,
	`Type` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8", sprintf("tblEptEmailAddStatus_%s",$table));

CREATE TABLE IF NOT EXISTS `tblEptEmailAddStatus_APPNAME` (
	`id` bigInt(20) NOT NULL,
	`email` varchar(255) NOT NULL,
	`dateCreated` DateTime,
	`lastOpenDate` DateTime,
	`lastClickDate` DateTime,
	`lastSentDate` DateTime,
	`Type` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblEptUsers` (
  `userId` bigint(20) NOT NULL AUTO_INCREMENT,
  `client` varchar(255) DEFAULT 'ept',
  `fbId` varchar(60),
  `fbFirstName` varchar(120),
  `fbLastName` varchar(120),
  `isContactId` bigint(11),
  `isEmail` varchar(255),
  `appName` varchar(60),
  `appDomainName` varchar(120),
  `accessToken` varchar(120),
  `refreshToken` varchar(120),
  `expiresAt` bigint(11),
  `tokenType` varchar(255),
  `scope` varchar(255),
  `authhash` varchar(20),
  `oneTimeHash` varchar(20),
  `oneTimeHashExpires` bigint(11),
  PRIMARY KEY (`userId`),
  UNIQUE KEY `fbId` (`fbId`),
  UNIQUE KEY `isContactId` (`isContactId`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `tblEptSessions` (
  `sessionId` varchar(255) NOT NULL,
  `userId` bigint(20) NOT NULL,
  PRIMARY KEY (`sessionId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8


LOOKING UP EMAIL STATUS PER CONTACT

select tblEptContact_inspwr.Email Email, Type from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email limit 50;

select Type, Count(Type) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email group by Type;



OPEN RATE QUERIES USING EmailSentSummary TABLE

Open Rate: Last 30 Days

select sum(`#Sent`) Sent,
sum(`#Opened`) Opened, 
100*sum(`#Opened`)/sum(`#Sent`) `Open%`, 
sum(`#Clicked`) Clicked, 
100*sum(`#Clicked`)/sum(`#Sent`) `Click%`, 
sum(`#OptOut`) OptedOut, 
100*sum(`#OptOut`)/sum(`#Sent`) `OptOut%`, 
sum(`#Bounce`) Bounced, 
100*sum(`#Bounce`)/sum(`#Sent`) `Bounce%`, 
sum(`#ISPSpamComplaints`)+sum(`#InternalSpamComplaints`) Complaints, 
100*(sum(`#ISPSpamComplaints`)+sum(`#InternalSpamComplaints`))/sum(`#Sent`) `Complaint%`
 from tblEptEmailSentSummary_inspwr where DateSent BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE();


#Clicked                
#OptOut                 
#Bounce                 
#ISPSpamComplaints      
#InternalSpamComplaints 

Open Rate By Month
select month(DateSent) M, year(DateSent) Y, sum(`#Sent`), sum(`#Opened`) Opened, 100*sum(`#Opened`)/sum(`#Sent`) `Open%` from tblEptEmailSentSummary_inspwr group by Y, M order by Y, M;

Open Rate By Week, Limit to Big Broadcasts
select week(DateSent) M, year(DateSent) Y, sum(`#Sent`), sum(`#Opened`) Opened, 100*sum(`#Opened`)/sum(`#Sent`) `Open%` from tblEptEmailSentSummary_inspwr where `#Sent` > 50000 group by Y, M order by Y, M;


ENGAGEMENT QUERIES USING Contact and EmailAddStatus TABLES

WITHIN THE LAST __ DAYS (e.g. 30)
- People who have been sent anything
- People who have been sent anything AND OPENED
- People who have been sent anything AND CLICKED
- All People who have not been sent anything
- Opted In People who have not been sent anything


Number of people sent within 30 days who opened something

select  count(lastSentDate), sum(if(lastOpenDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, 0)) Opened from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE();

TO INCLUDE EMAIL1 AND EMAIL2

select count(lastSentDate), sum(if(lastOpenDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, 0)) Opened 
	from tblEptContact_inspwr C, tblEptEmailAddStatus_inspwr S1, tblEptEmailAddStatus_inspwr S2
	where C.Email=S1.Email and C.EmailAddress2=S2.Email
	and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE();

SELECT 
   ID, 
   (SELECT MAX(LastUpdateDate)
      FROM (VALUES (UpdateByApp1Date),(UpdateByApp2Date),(UpdateByApp3Date)) AS UpdateDate(LastUpdateDate)) 
   AS LastUpdateDate
FROM ##TestTable

select Id, Max(lastSentDate) lastSent, Max(lastOpenDate) lastOpen from ( select C.Id, "Email1", lastSentDate, lastOpenDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S where C.Email=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() UNION select C.Id, "Email2", lastSentDate, lastOpenDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S where C.EmailAddress2=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() ) AA group by Id order by Id limit 40;


select Id, Max(lastSentDate) lastSent, Max(lastOpenDate) lastOpen, if (Max(lastOpenDate) BETWEEN CURDATE() - INTERVAL 750 DAY AND CURDATE(), 1, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL 750 DAY AND CURDATE(), 1, 0)) Opened, Max(lastClickDate) lastClick, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL 750 DAY AND CURDATE(), 1, 0) Clicked from ( select C.Id, "Email1", lastSentDate, lastOpenDate, lastClickDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S where C.Email=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 750 DAY AND CURDATE() UNION select C.Id, "Email2", lastSentDate, lastOpenDate, lastClickDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S where C.EmailAddress2=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 750 DAY AND CURDATE() ) AA group by Id order by Id limit 40;

select count(Sent) Sent, Sum(Opened) Opened, Sum(Clicked) Clicked from (select Id, Max(lastSentDate) Sent, if (Max(lastOpenDate) BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, 0)) Opened, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, 0) Clicked from ( select C.Id, "Email1", lastSentDate, lastOpenDate, lastClickDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S where C.Email=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() UNION select C.Id, "Email2", lastSentDate, lastOpenDate, lastClickDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S where C.EmailAddress2=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()) AA group by Id) AB;

select count(Sent) Sent, Sum(Opened) Opened, Sum(Clicked) Clicked from (select Id, Max(lastSentDate) Sent, if (Max(lastOpenDate) BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, 0)) Opened, if (Max(lastClickDate) BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE(), 1, 0) Clicked from ( select C.Id, "Email1", lastSentDate, lastOpenDate, lastClickDate from tblEptContact_inspwr C, tblEptEmailAddStatus_inspwr S where C.Email=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() UNION select C.Id, "Email2", lastSentDate, lastOpenDate, lastClickDate from tblEptContact_inspwr C, tblEptEmailAddStatus_inspwr S where C.EmailAddress2=S.Email and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()) AA group by Id) AB;


select * from
(
select C.Id, "Email1", lastSentDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S
	where C.Email=S.Email
	and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()
UNION
select C.Id, "Email2", lastSentDate from tblEptContact_uir93022 C, tblEptEmailAddStatus_uir93022 S
	where C.EmailAddress2=S.Email
	and lastSentDate BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()
) AA order by Id limit 10;



Number of people not sent anything in the last 30 days

	select count(lastSentDate) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate < CURDATE() - INTERVAL 30 DAY;

	Broken down by Type
  
	select Type, count(lastSentDate) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate < CURDATE() - INTERVAL 30 DAY group by Type;

	Only Opted In People

	select count(lastSentDate) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate < CURDATE() - INTERVAL 30 DAY and Type In ('Confirmed', 'DoubleOptin', 'SingleOptIn', 'UnengagedMarketable');

	Only Opted In People broken down by Opt Type

	select Type, count(lastSentDate) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate < CURDATE() - INTERVAL 30 DAY and Type In ('Confirmed', 'DoubleOptin', 'SingleOptIn', 'UnengagedMarketable') group by Type;


Number of people who have NEVER been sent anything

select count(tblEptContact_inspwr.Email) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate is NULL;

	Number of opted in people who have NEVER been sent anything

	select Type, count(tblEptContact_inspwr.Email) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate is NULL and Type In ('Confirmed', 'DoubleOptin', 'SingleOptIn', 'UnengagedMarketable') group by Type;

	Number of people who have NEVER been sent anything broken down by opt type
	
	select Type, count(tblEptContact_inspwr.Email) from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email and lastSentDate is NULL group by Type;


select tblEptContact_inspwr.Email Email, Type, lastSentDate from tblEptContact_inspwr, tblEptEmailAddStatus_inspwr where tblEptContact_inspwr.Email=tblEptEmailAddStatus_inspwr.Email limit 50;




People who have spent lots of money and no longer marketable

select FirstName, LastName, c.Email, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalValue, Type, LastSentDate 
from tblEptInvoice_inspwr i, tblEptContact_inspwr c, tblEptEmailAddStatus_inspwr s 
where i.ContactId=c.Id and c.Email=s.Email and Type Not In ('SingleOptIn', 'DoubleOptin', 'Confirmed') 
group by i.ContactId 
order by TotalValue DESC limit 25;

Same but Hard Bounce only - where order is within the last 12 months

select FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalValue, Type, LastSentDate, LastOpenDate
from tblEptInvoice_inspwr i, tblEptContact_inspwr c, tblEptEmailAddStatus_inspwr s
where i.ContactId=c.Id and c.Email=s.Email and Type = 'HardBounce' 
group by i.ContactId
having TotalValue > 50 and LatestOrder > CURDATE() - INTERVAL 12 MONTH
order by TotalValue DESC;

Same but any email status where either email never opened, or not opened anything for the last 12 months

select FirstName, LastName, c.Email, c.Phone1, Max(i.DateCreated) LatestOrder, Sum(TotalDue) TotalValue, Type, LastSentDate, LastOpenDate
from tblEptInvoice_inspwr i, tblEptContact_inspwr c, tblEptEmailAddStatus_inspwr s
where i.ContactId=c.Id and c.Email=s.Email 
group by i.ContactId
having TotalValue > 50 and LatestOrder > CURDATE() - INTERVAL 12 MONTH and (LastOpenDate < CURDATE() - INTERVAL 12 MONTH or LastOpenDate IS NULL)
order by TotalValue DESC
LIMIT 100;



**/
?> 
