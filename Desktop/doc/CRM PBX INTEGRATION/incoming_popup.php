<?php
include 'config.inc.php';
$hostname = $dbconfig['db_server'];
$username = $dbconfig['db_username'];
$password = $dbconfig['db_password'];
$database   = $dbconfig['db_name'];
$conn = new mysqli($hostname, $username, $password, $database);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

date_default_timezone_set('Asia/Kolkata');
$current_datetime = date('Y-m-d H:i:s');
$today = date('Y-m-d');

$status = 0;

$qry_cdr = $conn->query("SELECT source_status,call_unique_id,source_number FROM ast_cdr WHERE DATE(ast_timestamp) = '$today' AND source_status = 'answer' AND call_type != 'Outgoing' ");
$row_cdr = $qry_cdr->fetch_assoc();
$cdr_num_rows = $qry_cdr->num_rows;

if($cdr_num_rows > 0){

	$source_status = $row_cdr['source_status'];
	$call_unique_id = $row_cdr['call_unique_id'];
	$source_number = $row_cdr['source_number'];

	$contact_data = getContactStatus($conn, $source_number);
	$num_contact = $contact_data['num_contact'];
	$contactid = $contact_data['contactid'];

	if($num_contact > 0){
		$view_url = $site_URL.'index.php?module=Contacts&view=Detail&record='.$contactid.'&app=MARKETING';
	}else{
		$view_url = $site_URL.'index.php?module=Contacts&view=Edit&app=MARKETING';
	}

	$qry_dupli = $conn->query("SELECT duplicate_id FROM incoming_duplicate WHERE DATE(date_time) = '$today' AND unique_id = '$call_unique_id' ");
	$num_dupli = $qry_dupli->num_rows;

	if($num_dupli == 0){
		$insert_uniqid = "INSERT INTO incoming_duplicate SET unique_id = '$call_unique_id', date_time = '$current_datetime' ";
		$insert_status = $conn->query($insert_uniqid);
		if($insert_status === TRUE){
			$status = 1;
		}
	}
}

function getContactStatus($conn, $source_number){

	$qry_contact = $conn->query("SELECT a.contactid FROM vtiger_contactdetails a LEFT JOIN vtiger_crmentity b ON a.contactid = b.crmid WHERE a.mobile LIKE '%". $source_number ."' AND b.deleted = 0 ");
	$row_contact = $qry_contact->fetch_assoc();
	$num_contact = $qry_contact->num_rows;
	$contactid = $row_contact['contactid'];

	$contact_data = array('num_contact' => $num_contact, 'contactid' => $contactid);
	return $contact_data;
}

$responseData = array(
	'status' => $status, 
	'source_number' => $source_number, 
	'view_url' => $view_url, 
);
echo json_encode($responseData);
?>