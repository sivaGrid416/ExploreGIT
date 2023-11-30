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

$task_id = $argv[1];
$dialStatus = $argv[2];
$duration = $argv[3];
$dtmf = $argv[4];
$bill_sec = $argv[5];
// if(!is_numeric($dtmf))
// {
//         $dtmf = '--';
// }

//DB connection
$hostname ="localhost";
$username ="VBDemoUsR";
$password ="VBPaSs1234";
$database="VBDemoDB";
$conn = new mysqli($hostname, $username, $password, $database);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}


date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

$currentStatus = "No value";
$dialed_count = 0;
$sql = "SELECT `status`, `dialed_count` FROM `vtiger_voiceblasttask` WHERE `voiceblasttaskid` = '$task_id'";
$agi->verbose("status**** ".$sql);
$result = $conn->query($sql);
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
	$currentStatus = $row['status'];
	$dialed_count = $row['dialed_count'];
  }
}
$dialed_count++;

// updateContactStatus($conn, $task_id, $dialStatus);

// if($dtmf != 11)
if(strcmp($currentStatus, "ANSWER") != 0)
{

	$update_query = "UPDATE vtiger_voiceblasttask SET duration = '$duration', talktime = '$bill_sec', status='$dialStatus', dialed_count='$dialed_count', call_date='$date' WHERE voiceblasttaskid = '$task_id' ";
	$agi->verbose("voiceblasttaskid update status**** ".$update_query);
	$update_task = $conn->query($update_query);

        if($currentStatus=='PROGRESS' && $dialStatus==""){
                $update_qry = "UPDATE vtiger_voiceblasttask SET output='$dtmf' WHERE voiceblasttaskid = '$task_id' and add_action='Yes'";
                $agi->verbose("voiceblasttaskid update status**** ".$update_qry);
                $updt_task = $conn->query($update_qry);
        }

        //get Contactdetails
        $sql_ctid = "SELECT A.contactid as contactid, B.campaign_name as campaign_name FROM `vtiger_contactdetails` as A JOIN `vtiger_voiceblasttask` as B on A.mobile=B.destination WHERE B.`voiceblasttaskid`=$task_id";
        $agi->verbose("Contact details**** ".$sql_ctid);
        $result_ctid = $conn->query($sql_ctid);
        if ($result_ctid->num_rows > 0) {
        while($row_ctid = $result_ctid->fetch_assoc()) {
                $contact_id = $row_ctid['contactid'];
                $campaign_name_ctid = $row_ctid['campaign_name'];

                $update_query_ctid = "UPDATE vtiger_campaigncontrel SET callStatus = '$dialStatus' WHERE campaignid = '$campaign_name_ctid' AND contactid='$contact_id'";
                $agi->verbose("voiceblasttaskid update status**** ".$update_query_ctid);
                $update_task_ctid = $conn->query($update_query_ctid);
        }
        }
        if(strcmp($dialStatus, "ANSWER") == 0)
        {
            updateBilling($conn, $task_id, $agi);
        }
}


function updateBilling($conn, $taskid, $agi)
{
	$source = "";
        $destination = "";
        $campaign_name = "";
        $duration = "";
        $call_start = "";
        $call_end = "";
        $ownerid = "";
        $rate = 0;
        $pulse = 0;
        $assignedid = 0;

        //get call details
        $sql = "SELECT `source`, `destination`, `campaign_name`, `duration`,`call_start`, `call_end` FROM `vtiger_voiceblasttask` WHERE `voiceblasttaskid`=$taskid";
        $agi->verbose("call details**** ".$sql);
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
                        $source = $row['source'];
                        $destination = $row['destination'];
                        $campaign_name = $row['campaign_name'];
                        $duration = $row['duration'];
                        $call_start = $row['call_start'];
                        $call_end = $row['call_end'];
                }
        }
        $source = "12345";
        //get owner id
        $sql = "SELECT smownerid FROM vtiger_crmentity WHERE crmid = '$taskid'";
        $qry_assigned = $conn->query($sql);
        $agi->verbose("ownerID **** ".$sql);
        $row_assigned = $qry_assigned->fetch_assoc();
        $ownerid = $row_assigned['smownerid'];
        //get rate and pulse
        $sql = "SELECT `crmid` FROM `vtiger_crmentity` WHERE `smownerid`=$ownerid and `setype`='UserRate'";
        $agi->verbose("CRM ID **** ".$sql);
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
                        $crmid = $row['crmid'];
                        $sql1 = "SELECT `userrateid`, `activity`, `rate`, `pulse` FROM `vtiger_userrate` WHERE `userrateid`=$crmid";
                        $agi->verbose("call type **** ".$sql1);
                        $result1 = $conn->query($sql1);
                        if ($result1->num_rows > 0) {
                                while($row1 = $result1->fetch_assoc()) {
                                        if(strcmp(strtolower($row1['activity']), "call") == 0)
                                        {
                                                $rate = ($row1['rate']/100);
                                                $pulse = $row1['pulse'];
                                        }
                                }
                        }
                }
        }
        //bill calculation
        $agi->verbose("rate**** ".$rate."***pulse***.$pulse");
        $pulseCount = (int)($duration/$pulse);          //round up
        if($duration%$pulse > 0)
        {
                $pulseCount = $pulseCount + 1;
        }
        $totalAmount = $pulseCount*$rate;
        $agi->verbose("pulseCount**** ".$pulseCount."total*****".$totalAmount);
        //insertion into billing table
        $datetime = date('Y-m-d H:i:s');
        $crmid = fetchCrmID();
        $agi->verbose("CRMid**** ".$crmid);
        $voiceBlastCRM = "INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,modifiedby,setype,description,createdtime,modifiedtime,viewedtime,status,version,presence,deleted,label) values('" . $crmid . "','" . $ownerid . "','" . $ownerid . "','" . $ownerid . "','VBAccounts','','" . $datetime . "','" . $datetime . "','','','','1','0','')";
        $agi->verbose("CRM entity insert**** ".$voiceBlastCRM);
        $crmCheck = $conn->query($voiceBlastCRM);

        if($crmCheck === TRUE)
        {
                $agi->verbose("LOOP CONDITON ENTRY**** ");
                updateCrmID($agi, $crmid);
                $voiceBlastmodule = "INSERT INTO `vtiger_vbaccounts`(`vbaccountsid`, `campaign`, `user`, `activity`, `source`, `destination`, `duration`, `pulse`, `rate`, `amount`) VALUES ($crmid,$campaign_name,$ownerid,'call','$source','$destination','$duration',$pulseCount,$rate,$totalAmount)";
                $agi->verbose("VB accounts insert**** ".$voiceBlastmodule);
                $voiceBlastCheck = $conn->query($voiceBlastmodule);
                
                if($voiceBlastCheck === TRUE)
                {
                        $agi->verbose("INSERTED $crmid");
                }
                $currentDate = date('Y-m-d');
                $currentTime = date('H:i:s');
                $voiceBlastmodule = "INSERT INTO `vtiger_vbaccountscf`(`vbaccountsid`, `cf_946`, `cf_948`, `cf_950`, `cf_952`) VALUES ($crmid,'$currentDate','$currentDate','$currentTime','$currentTime')";
                $agi->verbose("VB accounts insert**** ".$voiceBlastmodule);
                $voiceBlastCheck = $conn->query($voiceBlastmodule);
                if($voiceBlastCheck === TRUE)
                {
                        $agi->verbose("*****Old crm ID**** ".$crmid);
                        //updateCrmID($agi, $crmid);
                }

        }
        updateTotalBilling($agi, $conn, $ownerid, $totalAmount);


}

function updateTotalBilling($agi, $conn, $ownerid, $totalAmount)
{
        $currentAmount = 0;
        $sql = "SELECT `amount` FROM `vtiger_users` WHERE `id`=$ownerid";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                        $currentAmount = $row['amount'];
                }
        }
        $remainingAmount = $currentAmount - $totalAmount;
        $update_query = "UPDATE `vtiger_users` SET `amount`=$remainingAmount WHERE `id`=$ownerid";
        $update_task = $conn->query($update_query);
        $agi->verbose("CRM update**** ".$update_query);
}

function updateContactStatus($conn, $task_id, $dialStatus){

    $qry_contactid = $conn->query("SELECT destination FROM vtiger_voiceblasttask WHERE voiceblasttaskid = '$task_id' ");
    $row_contactid = $qry_contactid->fetch_assoc();
    $destination = $row_contactid['destination'];

    $update_call_status = "UPDATE vtiger_contactdetails SET last_call_status = '$dialStatus' WHERE mobile = '$destination' ";
    $conn->query($update_call_status);
}

function fetchCrmID()
{
        global $conn;
        $res1 = $conn->query("SELECT id FROM vtiger_crmentity_seq");
        $crm_ids = $res1->fetch_assoc();
        $crm_id = $crm_ids['id'] + 1;
        return $crm_id;
}


function updateCrmID($agi, $crm_id)
{
        global $conn;
        $agi->verbose("Updated crm ID**** ".$crm_id);
        $updateCrmID = "UPDATE vtiger_crmentity_seq SET id = '$crm_id' ";
        $agi->verbose("Updated crm query**** ".$updateCrmID);
        $conn->query($updateCrmID);
}
$conn->close();
?>

