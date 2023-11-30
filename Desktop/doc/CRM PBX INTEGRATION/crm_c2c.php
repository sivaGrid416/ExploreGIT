<?php
// Copyright 2018 astTECS
// Requires Asterisk, Apache, and PHP.  Place this file
// onto your apache server and call the URL like so
// http://yourapacheserver/AddCall.php?exten=12132222&phoneno=10042425050

//require_once('/var/www/html/agc/dbconnect.php');
header('Content-Type: application/json');
$log_file = "./clientCallLogs.log"; 
$inputPost = file_get_contents("php://input");
$jsonValues = json_decode($inputPost, true);
//$exten = $jsonValues['exten'];
//$number = $jsonValues['phoneno'];
// $source = substr($jsonValues['source'],-10);                  //phoneno
// $destination = substr($jsonValues['destination'],-10);   //exten

if(isset($_GET['type'])){
	$type = $_GET['type'];
	$record = $_GET['record'];
}
$source = $_GET['exten'];                 //phoneno
$destination = $_GET['phoneno'];   //exten

date_default_timezone_set("Asia/Kolkata");
$time =microtime(true);
$micro_time=sprintf("%06d",($time - floor($time)) * 1000000);
$date=new DateTime( date('Y-m-d H:i:s.'.$micro_time,$time) );
$calldate=$date->format("Y-m-d H:i:s");

$logString = "CallDate - $calldate, Source - $source, Destination - $destination\r\n";
error_log($logString, 3, $log_file);

$ucid=$date->format("Ymd.His.u");
//echo $ucid;

if($destination == '' || $source == ''){
echo "Extension& Phoneno Parameter is missing";exit;
}

$callConnectionCheck = "Fail";

$strHost = "127.0.0.1";
$strUser = "admin";
$strSecret = "astCRMDial2PBX";
//$strChannel = "SIP/GSMGW/$exten";
//$strChannel = "local/4545$source@AGNT_Call";
$strChannel = "local/4512$source@from-crm";
//$strContext = "AGNT_Call";
$strContext = "from-crm";
$strWaitTime = "30";
$strPriority = "1";
$strMaxRetry = "2";
$errno=0 ;
$errstr=0 ;
$strCallerId = "$source <$source>";
$oSocket = fsockopen ("127.0.0.1", 5038, $errno, $errstr, 20);
if (!$oSocket) {
//echo "$errstr ($errno)<br>\n";
} else {
$callConnectionCheck = "Success";
fputs($oSocket, "Action: login\r\n");
fputs($oSocket, "Events: on\r\n");
fputs($oSocket, "Username: $strUser\r\n");
fputs($oSocket, "Secret: $strSecret\r\n\r\n");
fputs($oSocket, "Variable:srcnum=$source\r\n");
fputs($oSocket, "Variable:dstnum=$destination\r\n");
fputs($oSocket, "Action: Originate\r\n");
fputs($oSocket, "Context: $strContext\r\n");
fputs($oSocket, "Channel: $strChannel\r\n");
fputs($oSocket, "WaitTime: $strWaitTime\r\n");
fputs($oSocket, "CallerId: $strCallerId\r\n");
fputs($oSocket, "Variable:UCID=$ucid\r\n");
fputs($oSocket, "Exten: 4521$destination\r\n");
fputs($oSocket, "Priority: $strPriority\r\n\r\n");
fputs($oSocket, "Action: Logoff\r\n\r\n");
sleep(2);
fclose($oSocket);

	if($type == 'list'){
		//header("location: http://43.254.43.140/crmdemo/index.php?module=Contacts&view=List&viewname=7&app=MARKETING");
		// header("location: http://43.254.43.140/crmdemo/index.php?module=Contacts&view=Edit&record=".$record."&app=MARKETING");
		header("location: http://192.168.10.94/crmpbx/index.php?module=Contacts&view=List&popupenable=1&record=".$record."&viewname=7&app=MARKETING");
	}else{
		
		//header("location: http://43.254.43.140/crmdemo/index.php?module=Contacts&view=Detail&record=".$record."&app=MARKETING");
		// header("location: http://43.254.43.140/crmdemo/index.php?module=Contacts&view=Edit&record=".$record."&app=MARKETING");
		header("location: http://192.168.10.94/crmpbx/index.php?module=Contacts&view=List&popupenable=1&record=".$record."&viewname=7&app=MARKETING");
	}

 }

//echo $callConnectionCheck;

echo json_encode(array("result"=>$callConnectionCheck));

?>
