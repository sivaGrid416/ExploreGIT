<?php
// Copyright 2020 astTECS
// Requires Asterisk, Apache, and PHP.  Place this file
// onto your apache server and call the URL like so
// http://yourapacheserver/AddCall.php?exten=12132222&phoneno=100426666

//require_once('/var/www/html/agc/dbconnect.php');

date_default_timezone_set("Asia/Dubai");
$time =microtime(true);
$micro_time=sprintf("%06d",($time - floor($time)) * 1000000);
$date=new DateTime( date('Y-m-d H:i:s.'.$micro_time,$time) );

$calldate=$date->format("Y-m-d H:i:s");
$ucid=$date->format("Ymd.His.u");
//echo $ucid;

$exten = $_REQUEST['party_a'];
$number = $_REQUEST['party_b'];
$crmid  = $_REQUEST['callerid'];
$key = $_REQUEST['key'];
$de_key = base64_decode($key);

$str = 'hFAZWutyvDXUkVJq';
// $key1 = base64_encode($str);

if($de_key != $str)
{
echo "Key does not match"; exit;
}

if($exten == '' || $number == '')
{
	echo "Extension & Phoneno Parameter is missing";exit;
}
if($de_key == $str)
{
$strHost = "127.0.0.1";
$strUser = "astcronTECS";
$strSecret = "astZENTECS2020";
//$strChannel = "SIP/GSMGW/$exten";
$strChannel = "local/$exten@Click2Call";
$strContext = "Click2Call";
$strWaitTime = "30";
$strPriority = "1";
$strMaxRetry = "2";
$errno=0 ;
$errstr=0 ;
$strCallerId = "$exten <$exten>";
$oSocket = fsockopen ("127.0.0.1", 5038, $errno, $errstr, 20);
if (!$oSocket) {
echo "$errstr ($errno)<br>\n";
} else {
fputs($oSocket, "Action: login\r\n");
fputs($oSocket, "Events: on\r\n");
fputs($oSocket, "Username: $strUser\r\n");
fputs($oSocket, "Secret: $strSecret\r\n\r\n");
fputs($oSocket, "Action: originate\r\n");
fputs($oSocket, "Context: $strContext\r\n");
fputs($oSocket, "Channel: $strChannel\r\n");
fputs($oSocket, "WaitTime: $strWaitTime\r\n");
fputs($oSocket, "CallerId: $strCallerId\r\n");
fputs($oSocket, "Variable:UCID=$ucid\r\n");
fputs($oSocket, "Variable:CCID=$crmid\r\n");
fputs($oSocket, "Exten: $number\r\n");
fputs($oSocket, "Priority: $strPriority\r\n\r\n");
fputs($oSocket, "Action: Logoff\r\n\r\n");
sleep(2);
fclose($oSocket);
$data = array("Result"=>"SUCCESS","A-Party"=>$exten,"B-Party"=>$number,"Caller ID"=>$crmid,"Unique ID"=>$ucid);
echo json_encode($data);

//echo "SUCCESS";
//echo "A-Party ".$exten." B-Party ".$number." Caller ID " .$crmid;
 }
mysql_connect("localhost","root","astTECS@astCC9001");
mysql_select_db("clicktocall");
$ins_c2c="insert into c2c_trig_log(id,calldate,a_party,b_party,callerid,uniqueid) values('','$calldate','$exten','$number','$crmid','$ucid')";
$re=mysql_query($ins_c2c);
//echo "Call_UCID:".$ucid;echo "Call_date:".$calldate;
//$data_op=array("Call_UCID"=>"$ucid","Agent_Phone_Number"=>"$exten","Customer_Phone_Number"=>"$number","Caller_ID"=>"$crmid");
//echo $data_op1=json_encode($data_op);
}



