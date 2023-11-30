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

$loggedin_user = $_POST['loggedin_user'];
$is_admin = getIsAdmin($loggedin_user,$conn);

$qry_amount = $conn->query("SELECT amount FROM vtiger_users WHERE id = '$loggedin_user' ");
$res_amount = $qry_amount->fetch_assoc();
$amount = $res_amount['amount'];

function getIsAdmin($loggedin_user,$conn){
	$qry_isadmin = $conn->query("SELECT id FROM vtiger_users WHERE id = '$loggedin_user' AND is_admin = 'on' AND status = 'Active' ");
	$is_admin = $qry_isadmin->num_rows;
	return $is_admin;
}

$campData = array('is_admin' => $is_admin, 'amount' => $amount);
echo json_encode($campData);
?>