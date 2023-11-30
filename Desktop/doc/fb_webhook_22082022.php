<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include_once 'include/Webservices/Utils.php';
include_once 'modules/Users/Users.php';
include_once 'include/Webservices/Create.php';
include_once 'includes/main/WebUI.php';

$facebook_config_details = Settings_FBMessenger_Module_Model::getFacebookConfigurationDetails();
$facebook_access_token = $facebook_config_details['access_token'];
$facebook_verify_token = $facebook_config_details['verify_token'];
$facebook_handler = $facebook_config_details['fb_handler'];

if (isset($_GET['hub_mode']) && isset($_GET['hub_verify_token']) && isset($_GET['hub_challenge'])) {

    if ($_GET['hub_verify_token'] == $facebook_verify_token) {
        echo $_GET['hub_challenge'];
    }
}

$adb = PearDatabase::getInstance();
global $adb;

$response = file_get_contents("php://input");
$myfile = fopen("fb_log.txt", "w");
fwrite($myfile, $response);
fclose($myfile);
$response_msg = json_decode($response, true);

$message = $response_msg['entry'][0]['messaging'][0]['message']['text'];

$access_token = $facebook_access_token;

if ($message) {
    $recipient_id = $response_msg['entry'][0]['messaging'][0]['sender']['id'];
    //$recipient_id = '7076192399119939';//manual testing
    $reply_message = 'Thanks for messaging us. We try to be as responsive as possible. Please drop your Email id & Contact number for further discussion. We will get back to you soon.';
    //send_reply_msg($recipient_id, $access_token, $reply_message);
    $senderinfo = getsenderDetails($recipient_id, $access_token);
    $sender = (array) $senderinfo;
    
    $facebook_config_details = Settings_FBMessenger_Module_Model::getFacebookConfigurationDetails();
    $fb_handler = $facebook_config_details['fb_handler'];
    $fb_handler_assignedto = PrepareAgentAssignedToValue($fb_handler);

    $sender_name = $sender['name'];
    $first_name = $sender['first_name'];
    $last_name = $sender['last_name'] ? $sender['last_name'] : $sender['name'];
    $email = $sender['email'];
    $contact_ws_id = '';
    $contact_id = getContactID($recipient_id);
    if ($contact_id) {
        $contact_ws_id = vtws_getWebserviceEntityId('Contacts', $contact_id);
    } else {
        $contact_data = array(
            'lastname' => $first_name . ' ' . $last_name,
            'email' => $email,
            'leadsource' => 'Facebook Messenger',
            'facebook_id' => $recipient_id,
            'cf_1054' => 1,
            'assigned_user_id' => $fb_handler_assignedto,
        );
        if($contact_data['facebook_id'] != '165746000143957'){
        $contact_id = CreateContactRecord($contact_data);
        $contact_ws_id = vtws_getWebserviceEntityId('Contacts', $contact_id);
       }
    }
    $fb_messenger_record_id = '';
    $is_fb_id_exists = IsFbIdExists($recipient_id);
    if ($is_fb_id_exists > 0) {
        $fb_messenger_record_id = $is_fb_id_exists;
    } else {
        $fb_messenger_record_id = 0;
    }
    
    $fb_msg_data = array(
        'sender_id' => $recipient_id,
        'sender_name' => $sender_name,
        'conatctid' => $contact_ws_id,
        'message' => $message,
        'fb_direction' => 'Incoming',
        'assigned_user_id' => $fb_handler_assignedto, // 19=Users Module ID, 1=First user Entity ID
    );

    if ($sender_name != '' && $contact_id > 0 && $fb_messenger_record_id == '') {
        $fb_msg_data['assigned_user_id'] = getContactAssignedToValue($contact_id, $fb_handler);
        $fb_messenger_id = createMessengerRecord($fb_msg_data);
    } elseif ($sender_name != '' && $contact_id > 0 && $fb_messenger_record_id > 0) {
        //$fb_msg_data['assigned_user_id'] = getAssignedtoOfExistingFBMessenger($fb_messenger_record_id);
        $fb_msg_data['assigned_user_id'] = getContactAssignedToValue($contact_id, $fb_handler);
        $fb_messenger_id = createMessengerRecord($fb_msg_data);
    } else {
        /*$fb_messenger_id = $fb_messenger_record_id;
        $fbmessenger_ws_id = vtws_getWebserviceEntityId('FBMessenger', $fb_messenger_id);
        $fb_recordModel = Vtiger_Record_Model::getInstanceById($fb_messenger_id);
        $fb_assignedTo = $fb_recordModel->get('assigned_user_id');
        $is_user_id_status = IsUserId($fb_assignedTo);
        $is_group_id_status = IsGroupId($fb_assignedTo);
        if ($is_user_id_status == 1) {
            $User_ws_id = vtws_getWebserviceEntityId('Users', $fb_assignedTo);
        } elseif ($is_group_id_status == 1) {
            $User_ws_id = vtws_getWebserviceEntityId('Groups', $fb_assignedTo);
        } else {
            $User_ws_id = '19x1';
        }
        $mod_comment_data = array(
            'related_to' => $fbmessenger_ws_id,
            'commentcontent' => 'Temp Customer : ' . $message,
            'assigned_user_id' => $User_ws_id,
        );
        $comment_entry = CreateCommentToFBMessenger($mod_comment_data);*/
    }


    /*$fb_user_handler_list = getFBUsersHandlersList($fb_handler, $recipient_id);
    foreach ($fb_user_handler_list as $key => $userid) {
        $notification_entry = createMessengerRecord_notification($fb_msg_data, $userid, $fb_messenger_id);
    }*/
    $notification_users = getNotificationUsers($recipient_id, $facebook_handler);
    foreach ($notification_users as $key => $userid) {
        $notification_entry = createMessengerRecord_notification($fb_msg_data, $userid, $fb_messenger_id);
    }
}

function getNotificationUsers($facebook_id, $fb_handler) {
    global $adb;
    $contactid = getContactID($facebook_id);
    if ($contactid) {
        $contact_record_model = Vtiger_Record_Model::getInstanceById($contactid);
        $contact_assignedto = $contact_record_model->get('assigned_user_id');
        if ($contact_assignedto == '23') {
            $fb_handler = $fb_handler;
        } else {
            $fb_handler = $contact_assignedto;
        }
    }
    $userslist = array();
    $is_user_id_status = IsUserId($fb_handler);
    $is_group_id_status = IsGroupId($fb_handler);
    if ($is_user_id_status == 1) {
        $userslist[] = $fb_handler;
    } elseif ($is_group_id_status == 1) {
        $userslist = getAstGroupUsersList($fb_handler);
    }

    return $userslist;
}

function getContactAssignedToValue($contactid, $fb_handler) {
    global $adb;
    $sql = "select smownerid from vtiger_crmentity where crmid=?";
    $result = $adb->pquery($sql, array($contactid));
    $smownerid = $adb->query_result($result, 0, 'smownerid');
    if ($smownerid == '23') { //23 is None Group
        $contact_assignedto = PrepareAgentAssignedToValue($fb_handler);
    } else {
        $contact_assignedto = PrepareAgentAssignedToValue($smownerid);
    }
    return $contact_assignedto;
}

function PrepareAgentAssignedToValue($input_userid) {
    $is_user_id_status = IsUserId($input_userid);
    $is_group_id_status = IsGroupId($input_userid);
    if ($is_user_id_status == 1) {
        $Agent_ws_id = vtws_getWebserviceEntityId('Users', $input_userid);
    } elseif ($is_group_id_status == 1) {
        $Agent_ws_id = vtws_getWebserviceEntityId('Groups', $input_userid);
    } else {
        $Agent_ws_id = '19x1';
    }
    return $Agent_ws_id;
}

function getAssignedtoOfExistingFBMessenger($fb_messenger_id) {
    $fb_recordModel = Vtiger_Record_Model::getInstanceById($fb_messenger_id);
    $fb_assignedTo = $fb_recordModel->get('assigned_user_id');
    $is_user_id_status = IsUserId($fb_assignedTo);
    $is_group_id_status = IsGroupId($fb_assignedTo);
    if ($is_user_id_status == 1) {
        $User_ws_id = vtws_getWebserviceEntityId('Users', $fb_assignedTo);
    } elseif ($is_group_id_status == 1) {
        $User_ws_id = vtws_getWebserviceEntityId('Groups', $fb_assignedTo);
    } else {
        $User_ws_id = '19x1';
    }
    return $User_ws_id;
}

function CreateCommentToFBMessenger($data) {
    include_once 'include/Webservices/Utils.php';
    include_once 'modules/Users/Users.php';
    include_once 'includes/main/WebUI.php';
    include_once 'include/Webservices/Create.php';
    try {
        $user = new Users();
        $current_user = $user->retrieveCurrentUserInfoFromFile(Users::getActiveAdminId());
        $modcomments = vtws_create('ModComments', $data, $current_user);
    } catch (WebServiceException $ex) {
        echo $ex->getMessage();
    }
}

function IsFbIdExists($recipient_id) {
    global $adb;
    $sql = "SELECT MAX( fbmessengerid ) AS messengerid, sender_id
FROM `vtiger_fbmessenger` AS a
JOIN vtiger_crmentity AS b ON ( b.crmid = a.fbmessengerid )
WHERE sender_id = ?
AND b.deleted =0 ";
    $result = $adb->pquery($sql, array($recipient_id));
    $no_of_rows = $adb->num_rows($result);
    if ($no_of_rows > 0) {
        $fb_messenger_id = $adb->query_result($result, 0, 'messengerid');
    } else {
        $fb_messenger_id = 0;
    }
    return $fb_messenger_id;
}

function getFBUsersHandlersList($fb_handler, $facebook_id) {
    global $adb;
    $is_fb_id_exists = IsFbIdExists($facebook_id);
    //check is contact exists
    $userslist = array();
    if ($is_fb_id_exists > 0) {
        $fb_messenger_record_id = $is_fb_id_exists;
        $fb_recordModel = Vtiger_Record_Model::getInstanceById($fb_messenger_record_id);
        $fb_assignedTo = $fb_recordModel->get('assigned_user_id');
        $is_user_id_status = IsUserId($fb_assignedTo);
        $is_group_id_status = IsGroupId($fb_assignedTo);
        if ($is_user_id_status == 1) {
            $userslist[] = $fb_assignedTo;
        } elseif ($is_group_id_status == 1) {
            $userslist = getAstGroupUsersList($fb_assignedTo);
        }
    } else {
        $is_user_id_status = IsUserId($fb_handler);
        $is_group_id_status = IsGroupId($fb_handler);
        if ($is_user_id_status == 1) {
            $userslist[] = $fb_handler;
        } elseif ($is_group_id_status == 1) {
            $userslist = getAstGroupUsersList($fb_handler);
        }
    }
    return $userslist;
}

function getAstGroupUsersList($groupid) {
    global $adb;
    $sql = "SELECT * FROM `vtiger_users2group` WHERE groupid =?";
    $result = $adb->pquery($sql, array($groupid));
    $no_of_rows = $adb->num_rows($result);
    $userlist = array();
    for ($i = 0; $i < $no_of_rows; $i++) {
        $userlist[] = $adb->query_result($result, $i, 'userid');
    }
    return $userlist;
}

function IsUserId($id) {
    global $adb;
    $sql = "select * from vtiger_users where id=?";
    $result = $adb->pquery($sql, array($id));
    $no_of_rows = $adb->num_rows($result);
    if ($no_of_rows > 0) {
        $is_user_id_status = 1;
    } else {
        $is_user_id_status = 0;
    }
    return $is_user_id_status;
}

function IsGroupId($id) {
    global $adb;
    $sql = "select * from vtiger_groups where groupid=?";
    $result = $adb->pquery($sql, array($id));
    $no_of_rows = $adb->num_rows($result);
    if ($no_of_rows > 0) {
        $status = 1;
    } else {
        $status = 0;
    }
    return $status;
}

function send_reply_msg($recipient_id, $access_token, $reply_message) {
    $data = array(
        'messaging_type' => 'RESPONSE',
        'recipient' =>
        array(
            'id' => $recipient_id,
        ),
        'message' =>
        array(
            'text' => $reply_message,
        ),
    );
    $postdata = json_encode($data);
    $myfile = fopen("fb_log4.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $postdata);
    fclose($myfile);
    $url = 'https://graph.facebook.com/v12.0/me/messages?access_token=' . $access_token . '';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function getsenderDetails($senderid, $access_token) {
    $url = 'https://graph.facebook.com/' . $senderid . '?fields=first_name,last_name,name,email&access_token=' . $access_token . '';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result);
}

function createMessengerRecord_notification($data, $userid, $fb_messenger_id) {

    global $adb;

    date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
    $today = date('Y-m-d H:i:s');
    $sender_id = $data['sender_id'];
    $sender_name = $data['sender_name'];
    $conatctid = $data['conatctid'];
    $conatctid_arr = explode('x', $conatctid);
    $conatctid = $conatctid_arr[1];
    $message = $data['message'];
    $userid = $userid;
    $recordid = $fb_messenger_id;
    $source = 'Messanger';

    if($sender_id != '165746000143957'){
    $insert_notification = "INSERT into api_social_notificatons (sender_id,contact_id,message,sender_name,date_time,source,userid,recordid)VALUES('" . $sender_id . "','" . $conatctid . "' ,'" . $message . "' ,'" . $sender_name . "' ,'" . $today . "' ,'" . $source . "','" . $userid . "','" . $recordid . "' )";
    $adb->pquery($insert_notification);
    }
}

function createMessengerRecord($data) {
    $senderid = $data['sender_id'];
    $customername = $data['sender_name'];
    $source = 'FBMessenger';
    $last_chat_info = getLastChatInfo($senderid, $source);
    $ticket_id = $last_chat_info['ticket_id'];
    $chat_no = $last_chat_info['chat_no'];
    $agentid_info = $data['assigned_user_id'];
    $agentname = getAgentName($agentid_info);
    $agent_id_info = explode('x', $agentid_info);
    $agent_id = $agent_id_info[1];
    if ($ticket_id == 'new') {
        $ticket_assigned_user_id = $data['assigned_user_id'];
        $ticket_data = PrepareTempTicketData($source, $ticket_assigned_user_id);
        $ticket_id = CreateTicketRecord($ticket_data);
        $ticket_relatedto = $data['conatctid'];
        Push2ticketsqueue($ticket_id, $ticket_assigned_user_id, $ticket_relatedto, $source);
        $conversation_status = 'Start';
    } else {
        $conversation_status = 'Continue';
    }
    CreateOmniChatEntry($customername, $senderid, $source, $agentname, $agent_id, $chat_no, $ticket_id);
    $ticket_ws_id = vtws_getWebserviceEntityId('HelpDesk', $ticket_id);
    $data['ticket_id'] = $ticket_ws_id;
    try {
        $user = new Users();
        $current_user = $user->retrieveCurrentUserInfoFromFile(Users::getActiveAdminId());
        $fb_messenger = vtws_create('FBMessenger', $data, $current_user);
        $fb_msgr_data = explode('x', $fb_messenger['id']);
        $fb_msgr_id = $fb_msgr_data[1];
        UpdateConversationStatus($conversation_status, $fb_msgr_id);
    } catch (WebServiceException $ex) {
        echo $ex->getMessage();
    }

    return $fb_msgr_id;
}

function Push2ticketsqueue($ticket_id, $ticket_assigned_user_id, $ticket_relatedto, $source) {
    global $adb;
    $ticket_record_Model = Vtiger_Record_Model::getInstanceById($ticket_id);
    $ticket_no = $ticket_record_Model->get('ticket_no');
    $ticket_assignedto_params = explode('x', $ticket_assigned_user_id);
    $ticket_assigned_to = $ticket_assignedto_params[1];
    $ticket_relatedto_params = explode('x', $ticket_relatedto);
    $ticket_relatedto = $ticket_relatedto_params[1];
    $sql = "INSERT INTO `ast_tickets_queue`(`ticketid`, `ticketno`, `assignedto`, `contactid`, `source`) VALUES (?,?,?,?,?)";
    $result = $adb->pquery($sql, array($ticket_id, $ticket_no, $ticket_assigned_to, $ticket_relatedto, $source));
}

function UpdateConversationStatus($conversation_status, $fb_msgr_id) {
    global $adb;
    $sql = "UPDATE vtiger_fbmessenger SET conversation_status=? where fbmessengerid=?";
    $result = $adb->pquery($sql, array($conversation_status, $fb_msgr_id));
}

function getAgentName($agentid_info) {
    global $adb;
    $agent_id_info = explode('x', $agentid_info);
    $agent_id = $agent_id_info[1];
    $is_user_id_status = IsUserId($agent_id);
    if ($is_user_id_status == 1) {
        $agent_name = getUserFullName($agent_id);
    }
    $is_group_id_status = IsGroupId($agent_id);
    if ($is_group_id_status == 1) {
        $agent_name = getGroupName($agent_id);
        $agent_name = $agent_name[0];
    }
    return $agent_name;
}

function CreateOmniChatEntry($customername, $senderid, $source, $agentname, $agent_id, $chat_no, $ticket_id) {
    global $adb;
    $startdatetime = date('Y-m-d H:i:s');
    $sql = "INSERT INTO `ast_omni_chats`( `customername`, `senderid`, `mobilenumber`, "
            . " `source`, `startdatetime`, `agentname`, `agentid`, "
            . " `chat_no`, `dispo`, `ticketid`, `enddatetime`, `chat_direction`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
    $result = $adb->pquery($sql, array($customername, $senderid, '', $source, $startdatetime, $agentname, $agent_id, $chat_no, '', $ticket_id, '', 'Incoming'));
    //echo $adb->convert2Sql($sql, array($customername, $senderid, '', $source, $startdatetime, $agentname, $chat_no, '', $ticket_id, ''));
}

function CreateTicketRecord($data) {
    include_once 'include/Webservices/Utils.php';
    include_once 'modules/Users/Users.php';
    include_once 'includes/main/WebUI.php';
    include_once 'include/Webservices/Create.php';
    try {
        $user = new Users();
        $current_user = $user->retrieveCurrentUserInfoFromFile(Users::getActiveAdminId());
        $ticketinfo = vtws_create('HelpDesk', $data, $current_user);
        $ticket_data = explode('x', $ticketinfo['id']);
        $ticket_id = $ticket_data[1];
    } catch (WebServiceException $ex) {
        echo $ex->getMessage();
    }
    return $ticket_id;
}

function PrepareTempTicketData($ticket_source, $ticket_assigned_user_id) {
    $startdatetime = date('d-m-Y H:i:s');
    $ticket_data = array(
        'ticket_title' => 'Temp-Ticket',
        'ticketpriorities' => 'Normal',
        'ticket_source' => $ticket_source,
        'ticketstatus' => 'Temp',
        'chat_starttime' => $startdatetime,
        'assigned_user_id' => $ticket_assigned_user_id,
    );
    return $ticket_data;
}

function getLastChatInfo($senderid, $source) {
    global $adb;
    $sql1 = "select 1 from ast_omni_chats";
    $result1 = $adb->pquery($sql1, array());
    $no_of_rows1 = $adb->num_rows($result1);
    if ($no_of_rows1 > 0) {
        $sql = "select max(sino) as sino,enddatetime,ticketid,chat_no from  ast_omni_chats where senderid=? and source=?"
                . "  and sino = (select max(sino) from ast_omni_chats where senderid=? and source=?)";
        $result = $adb->pquery($sql, array($senderid, $source, $senderid, $source));
        $no_of_rows = $adb->num_rows($result);
        if ($no_of_rows > 0) {
            $enddatetime = $adb->query_result($result, 0, 'enddatetime');
            if ($enddatetime == '0000-00-00 00:00:00') {
                $ticketid = $adb->query_result($result, 0, 'ticketid');
                $chat_no = $adb->query_result($result, 0, 'chat_no');
            } else {
                $prev_chat_no = $adb->query_result($result, 0, 'chat_no');
                $ticketid = 'new';
                $chat_no = $prev_chat_no + 1;
            }
            $data = array(
                'chat_no' => $chat_no,
                'ticket_id' => $ticketid,
            );
        } else {
            $data = array(
                'chat_no' => 1,
                'ticket_id' => 'new',
            );
        }
    } else {
        $data = array(
            'chat_no' => 1,
            'ticket_id' => 'new',
        );
    }



    return $data;
}

function getContactID($facebook_id) {
    global $adb;
    $sql = "select contactid from vtiger_contactdetails as a join vtiger_crmentity as b"
            . " on (b.crmid=a.contactid) where facebook_id=? and b.deleted=0";
    $result = $adb->pquery($sql, array($facebook_id));
    $no_of_rows = $adb->num_rows($result);

    if ($no_of_rows > 0) {
        $contactid = $adb->query_result($result, 0, 'contactid');
    } else {
        $contactid = 0;
    }
    return $contactid;
}

function CreateContactRecord($data) {
    try {
        $user = new Users();
        $current_user = $user->retrieveCurrentUserInfoFromFile(Users::getActiveAdminId());
        $contact = vtws_create('Contacts', $data, $current_user);
        $contactinfo = explode('x',$contact['id']);
        $contactid = $contactinfo[1];
    } catch (WebServiceException $ex) {
        echo $ex->getMessage();
    }

    return $contactid;
}

?>
