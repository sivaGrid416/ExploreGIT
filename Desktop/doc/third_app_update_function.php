<?php

function sendDatatoAPP($entity) 
{
	require_once('modules/Users/models/Record.php');
	global $adb, $log;
	$id = $entity->get('id');
	$log->info("id::".$entity->get('id'));
	$con_id=explode("x",$id);	
    
    $userid=$con_id[1];
    
	// $sql_user="select user_name from vtiger_users where id ='".$usr_id."'";
	// $result=$adb->pquery($sql_user);
	// $username=$adb->query_result($result,0,'user_name');
	// $log->info("Username::".$username);

//Fetching Result
$lead="SELECT  vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, vtiger_contactdetails.mobile,vtiger_contactsubdetails.homephone,vtiger_contactdetails.email,vtiger_troubletickets.ticket_no,vtiger_troubletickets.ticketid,vtiger_troubletickets.title,vtiger_troubletickets.status, vtiger_ticketcf.cf_930,vtiger_ticketcf.cf_932,vtiger_ticketcf.cf_906,vtiger_ticketcf.cf_946,vtiger_ticketcf.cf_898, vtiger_ticketcf.cf_1063, vtiger_contactaddress.mailingcity,vtiger_contactaddress.mailingstreet,vtiger_contactaddress.mailingstate, vtiger_contactaddress.mailingcountry,vtiger_contactaddress.mailingpobox,vtiger_contactaddress.mailingzip,vtiger_crmentity.description, vtiger_users.first_name, vtiger_users.last_name, vtiger_users.email1 FROM vtiger_contactdetails INNER JOIN vtiger_troubletickets ON vtiger_contactdetails.contactid = vtiger_troubletickets.contact_id INNER JOIN vtiger_ticketcf ON vtiger_ticketcf.ticketid =vtiger_troubletickets.ticketid INNER JOIN vtiger_contactaddress ON  vtiger_contactaddress.contactaddressid=vtiger_troubletickets.contact_id INNER JOIN vtiger_contactsubdetails ON  vtiger_contactsubdetails.contactsubscriptionid=vtiger_troubletickets.contact_id INNER JOIN vtiger_crmentity ON  vtiger_crmentity.crmid=vtiger_troubletickets.ticketid INNER JOIN vtiger_users ON  vtiger_crmentity.smownerid=vtiger_users.id where vtiger_troubletickets.ticketid='$userid'";
			$result =  $adb->pquery($lead);
			
            $fname=$adb->query_result($result,0,'firstname');
            $lname=$adb->query_result($result,0,'lastname');
            $mobile=$adb->query_result($result,0,'mobile');
            $homephone=$adb->query_result($result,0,'homephone');
            $email=$adb->query_result($result,0,'email');
            $ticket_no=$adb->query_result($result,0,'ticket_no');
            $ticketid=$adb->query_result($result,0,'ticketid');
            $cmplnt_date=$adb->query_result($result,0,'cf_930');
            $sfmodel=$adb->query_result($result,0,'cf_946');	
            $sfqty=$adb->query_result($result,0,'cf_898');
            $warranty = $adb->query_result($result,0,'cf_932');	
            $description = $adb->query_result($result,0,'description');
            $assignedFName = $adb->query_result($result,0,'first_name');
            $assignedLName=$adb->query_result($result,0,'last_name');
            $email1 = $adb->query_result($result,0,'email1');
            $tcktStatus = $adb->query_result($result,0,'status');
            $ticketTitle = $adb->query_result($result,0,'title');
            $serviceType = $adb->query_result($result,0,'cf_906');
            $engineer = $adb->query_result($result,0,'cf_1063');

            $cmplntdate = date("d/m/Y H:i:s", strtotime($cmplnt_date));

				if($warranty == 1){
					$warranty = "Yes";
				}else{
					$warranty = "No";
				}

                $name=$fname.' '.$lname;
                $assignedName=$assignedFName.' '.$assignedLName;
			
				$mobile = preg_replace('/\D+/', '', $mobile);
				$mobile = substr($mobile, -10);
				if($homephone !=''){
                $homephone = preg_replace('/\D+/', '', $homephone);
				$homephone = substr($homephone, -10);
                }

                $mailingstreet=$adb->query_result($result,0,'mailingstreet');
                $mailingcity=$adb->query_result($result,0,'mailingcity');
                $mailingstate=$adb->query_result($result,0,'mailingstate');
                $mailingpobox=$adb->query_result($result,0,'mailingpobox');
                $mailingcountry=$adb->query_result($result,0,'mailingcountry');
                $mailingzip=$adb->query_result($result,0,'mailingzip');

                $address=$mailingstreet.' '.$mailingpobox.' '.$mailingcountry;	


                $inside_array = array(
                    "TicketNo"=>$ticket_no,
                    "CustomerName"=>$name,
                    "CustomerPhone"=> $mobile, 
                    "TicketStatus"=> $tcktStatus, 
                    "TicketSubject"=> $ticketTitle, 
                    "CustomerEmail"=> $email, 
                    "District"=> 'None', 
                    "City"=> $mailingcity, 
                    "State"=> $mailingstate, 
                    "Engineer"=> $engineer, 
                    "EngineerEmail"=> "", 
                    "Address"=> $address, 
                    "CustomerPhone2"=> $homephone, 
                    "Pincode"=> $mailingzip, 
                    "Issue_Desc_ByCustomer"=> $description, 
                    "Product"=> 'Super Fan', 
                    "Model"=>$sfmodel, 
                    "TypeOfService"=>$serviceType, 
                    "TicketReceivedDate"=>$cmplntdate,
                    "Issue_Desc_ByEnginner"=>'Empty Field',
                    "Quantity"=>$sfqty
                    );
    
               //"WarrantyStatus"=>$warranty,
                    $json_arr = array("mode"=>null,"CallcenterCRMList"=>[$inside_array]);
    
                    $json_array=json_encode($json_arr);

                    // $sql_user="insert into apicheck values (null,'$json_array', $userid)";
	                // $result=$adb->pquery($sql_user);

                    // $url = "http://113.193.25.21:717/API/CallCenterBulkImport?";
                    $url = "http://superfanapi.quikallot.com/API/CallCenterBulkImport?";
                
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                
                $headers = array(
                   "Content-Type: application/json",
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                
                
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_array);
                
                //for debug only!
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                
                $resp = curl_exec($curl);

                $sql_user_resp="insert into apicheck values (null,'$resp', $userid)";
                $result_resp=$adb->pquery($sql_user_resp);

                curl_close($curl);
}
?>
