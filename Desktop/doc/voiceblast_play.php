#!/usr/bin/php -q
<?php
GLOBAL $agi;
GLOBAL $callerid;
GLOBAL $uniqueid;
require('/var/lib/asterisk/agi-bin/phpagi/phpagi.php');//needed for ivr creation
$agi = new AGI();//creation of class
$agi->Answer();
$callerid = $agi->request['agi_callerid'];
$uniqueid = $agi->request['agi_uniqueid'];
//$agi->verbose("calleridddddd".$callerid);
$task_id = $argv[1];

$hostname ="localhost";
$username ="VBDemoUsR";
$password ="VBPaSs1234";
$database="VBDemoDB";
$conn = new mysqli($hostname, $username, $password, $database);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

global $root_directory;
$dtmf = '--';

date_default_timezone_set('Asia/Kolkata');
$datetime = date('Y-m-d H:i:s');
// $agi->verbose("@@@@@@@@@@@@@task_id@@@@@@@@".$task_id);

$qry_taskid = $conn->query("SELECT add_action, voice_file FROM vtiger_voiceblasttask WHERE voiceblasttaskid = '$task_id' ");
$result_task = $qry_taskid->fetch_assoc();

$voice_file_arr = explode('.', $result_task['voice_file']);
$voiceFile = $voice_file_arr[0];

// $agi->stream_file($voiceFile);
$agi->verbose("************voicefilewithpath*******".$voiceFile);
$callType = $result_task['add_action'];
$agi->verbose("************call type*******".$callType);


if($callType == 1)
{
	$res1=$agi->get_data($voiceFile,10000,1);
	$dtmf =$res1["result"];
	$agi->verbose("************DTMF*******".$dtmf);
	$agi->set_variable("dtmfcapture", $dtmf);
}
else
{
	$agi->stream_file($voiceFile, '#'); 
}

//$agi->verbose("********valueeeeeee***".$dtmf);

//$update_query = "UPDATE vtiger_voiceblasttask SET status = '2', call_end = '$datetime', output = '$dtmf' WHERE voiceblasttaskid = '$task_id' ";
//$update_task = $conn->query($update_query);
//$agi->verbose("********valueeeeeee***".$update_query);


$conn->close(); 
?>
