<?php
/**
 *
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */
 
$useCustomField = (isset($_REQUEST["mm_member_custom_field"])) ? true : false;
$useCustomField2 = (isset($_REQUEST["mm_member_custom_field2"])) ? true : false;
$doGenerateCsv = (isset($_REQUEST["csv"])) ? true : false;

// get data based on search criteria and datagrid settings
$view = new MM_MembersView();
$searchByDate = "user_registered";

if(!empty($_REQUEST["mm_member_search_by_date"]))
{
	$searchByDate = $_REQUEST["mm_member_search_by_date"];
}

$dataGrid = new MM_DataGrid($_REQUEST, "", "desc");
$data = $view->search($_REQUEST, $dataGrid, $doGenerateCsv);

$dataGrid->setTotalRecords($data);
$dataGrid->recordName = "member";


// define datagrid headers
$headers = array
(	    
   	'last_name'				=> array('content' => '<a onclick="mmjs.sort(\'last_name\');" href="#">'._mmt('Name') .'</a>'),
   	'user_email'			=> array('content' => '<a onclick="mmjs.sort(\'user_email\');" href="#">'._mmt('Email') .'</a>'),
   	'phone'					=> array('content' => '<a onclick="mmjs.sort(\'phone\');" href="#">'._mmt('Phone') .'</a>'),
   	'membership_level_id'	=> array('content' => '<a onclick="mmjs.sort(\'membership_level_id\');" href="#">'._mmt('Membership Level') .'</a>'),
   	'bundles'				=> array('content' => _mmt('Bundles'))
);

if($useCustomField)
{
	$field = new MM_CustomField($_REQUEST["mm_member_custom_field"]);
	if($field->isValid())
	{
		$headers["mm_custom_field"] = array('content' => $field->getDisplayName());
	}
	else
	{
		$useCustomField = false;
	}
}

if($useCustomField2)
{
	if($_REQUEST["mm_member_custom_field2"] != $_REQUEST["mm_member_custom_field"])
	{
		$field = new MM_CustomField($_REQUEST["mm_member_custom_field2"]);
		if($field->isValid())
		{
			$headers["mm_custom_field2"] = array('content' => $field->getDisplayName());
		}
		else
		{
			$useCustomField2 = false;
		}
	}
	else
	{
		$useCustomField2 = false;
	}
}

switch($searchByDate)
{
	case "user_registered":
		$headers["user_registered"] = array('content' => '<a onclick="mmjs.sort(\'user_registered\');" href="#">'._mmt('Registered').'</a>');
		break;

	case "status_updated":
		$headers["status_updated"] = array('content' => '<a onclick="mmjs.sort(\'status_updated\');" href="#">'._mmt('Status Changed').'</a>');
		break;
}

$headers["last_login_date"] = array('content' => '<a onclick="mmjs.sort(\'last_login_date\');" href="#">'._mmt('Engagement').'</a>');
$headers["status"] = array('content' => '<a onclick="mmjs.sort(\'status\');" href="#">'._mmt('Status').'</a>');
$headers['actions'] = array('content' => _mmt('Actions'));

$datagridRows = array();


// define CSV headers
if($doGenerateCsv)
{
	$csvHeaders = array
	(
		'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Membership Level', 'Bundles', 'Bundle Expirations', 'Membership Expiration', 'Registered', 'Status Changed', 'Status',
		'Billing Address', 'Billing City', 'Billing State', 'Billing Zip', 'Billing Country',
		'Shipping Address', 'Shipping City', 'Shipping State', 'Shipping Zip', 'Shipping Country', 'Notes'
	);

	$fields = MM_CustomField::getCustomFieldsList();
	foreach($fields as $id=>$val)
	{
		$customField = new MM_CustomField($id);
		if($customField->isValid())
		{
			$csvHeaders[] = $customField->getDisplayName();
		}
	}
	
	$csvRows = array($csvHeaders);
}


// process data
$bundleNames = array();
foreach($data as $key=>$item)
{
	$user = new MM_User();
	$user->setId($item->id);
	$user->setFirstName($item->first_name);
	$user->setLastName($item->last_name);
	$user->setEmail($item->user_email);
	$user->setPhone($item->phone);
	$user->setRegistrationDate($item->user_registered);
	$user->setLastLoginDate($item->last_login_date);
	$user->setMembershipId($item->membership_level_id);
	$user->setStatus($item->status);
	
	if($doGenerateCsv)
	{
		$user->setBillingAddress($item->billing_address1);
		$user->setBillingCity($item->billing_city);
		$user->setBillingState($item->billing_state);
		$user->setBillingZipCode($item->billing_postal_code);
		$user->setBillingCountry($item->billing_country);
		$user->setShippingAddress($item->shipping_address1);
		$user->setShippingCity($item->shipping_city);
		$user->setShippingState($item->shipping_state);
		$user->setShippingZipCode($item->shipping_postal_code);
		$user->setShippingCountry($item->shipping_country);
		$user->setNotes($item->notes);
	}
	
	$name = $user->getFullName(true);
	
	if(empty($name)) 
	{
		$name = MM_NO_DATA;
	}
	
	$phone = $user->getPhone();
	
	if(empty($phone)) 
	{
		$phone = MM_NO_DATA;
	}
	
	// status
	$status = MM_Status::getImage($user->getStatus());
	
    // actions
   $editActionUrl = "href='".MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_MEMBERS, MM_MODULE_MEMBER_DETAILS_GENERAL)."&user_id=".$user->getId()."'";
   $deleteActionUrl = 'onclick="mmjs.remove(\''.$user->getId().'\', \''.$user->getEmail().'\')"';
   $actions = MM_Utils::getEditIcon("Edit Member", '', $editActionUrl);
    
	if(($user->getStatus() == MM_Status::$ERROR) || ($user->getStatus() == MM_Status::$PENDING_ACTIVATION) || ($user->getStatus() == MM_Status::$PENDING_CANCELLATION))
    {  	
    	$actions .= MM_Utils::getDeleteIcon("Delete Member", 'margin-left:5px;', $deleteActionUrl);
    } 
    else if(!$user->hasActiveSubscriptions()) 
    {
    	$actions .= MM_Utils::getDeleteIcon("Delete Member", 'margin-left:5px;', $deleteActionUrl);
    }
    else 
    {
    	$actions .= MM_Utils::getDeleteIcon("This member has an active paid membership or bundle which must be canceled before they can be deleted", 'margin-left:5px;', '', true);
    } 
	
    // membership level
	$membershipStr = $user->getMembershipName();
	
	if(($user->getStatus() == MM_Status::$PENDING_ACTIVATION) || ($user->getStatus() == MM_Status::$ERROR))
	{
		$membershipStr = "<em>".$user->getMembershipName()."</em>";
	}
	
	// bundles	
	if(!empty($item->bundles))
	{
		$bundleExpirations = array();
		$bundles = explode(",", $item->bundles);
		
		// iterate over array of bundle IDs, lookup bundle ID name 
		// and replace the ID with the bundle name
		for($i = 0; $i < count($bundles); $i++)
		{
			$bundleId = $bundles[$i]; 
			if(isset($bundleNames[$bundleId]))
			{   
				$memberAppliedBundle = MM_AppliedBundle::getAppliedBundle($user->getId(), $bundleId); 
				$date = $memberAppliedBundle->getExpirationDate();
				if(!is_null($date) && !empty($date))
				{
					$bundleExpirations[$bundleId] = $date;
				}
			}
			else
			{
				$bundle = new MM_Bundle($bundleId);
				
				if($bundle->isValid())
				{  
					// cache bundle name for future use while processing remaining rows
					$bundleNames[$bundleId] = $bundle->getName();
					$memberAppliedBundle = MM_AppliedBundle::getAppliedBundle($user->getId(), $bundle->getId());
					
					$date = $memberAppliedBundle->getExpirationDate();
					if(!is_null($date) && !empty($date))
					{
						$bundleExpirations[$bundleId] = $date;
					}
				}
				else 
				{ 
					$bundleNames[$bundleId] = MM_NO_DATA;
				}
			}
			
			$bundles[$i] = $bundleNames[$bundleId];
		}
		
		$bundles = implode(", ", $bundles);
		if (!is_array($bundleExpirations))
		{
			error_log("Offending content = ".print_r($bundleExpirations,true));
		}
		$bundleExpirations = implode(",", $bundleExpirations); 
	}
	else
	{
		$bundleExpirations = MM_NO_DATA;
		$bundles = MM_NO_DATA;
	}
	
	// last login date
	$userEngagement = MM_NO_DATA;
	$lastLoginDate = $user->getLastLoginDate();
	if(!empty($lastLoginDate))
	{
		$userEngagement = MM_Utils::getIcon('calendar-o', 'purple', '1.2em', '2px', "Last logged in {$user->getLastLoginDate(true)}", "margin-right:8px;");
	}
	else 
	{
		$userEngagement = MM_Utils::getIcon('calendar-o', 'purple', '1.2em', '2px', "Member hasn't logged in yet", "margin-right:8px;");
	}
	
	$userEngagement .= MM_Utils::getIcon('key', 'yellow', '1.2em', '2px', "Logged in {$user->getLoginCount()} times");
	$userEngagement .= " <span style='font-family:courier; font-size:12px; position:relative; top:1px; margin-right:8px;'>{$user->getLoginCount()}</span>";
	$userEngagement .= MM_Utils::getIcon('file-o', 'turq', '1.2em', '2px', "Accessed {$user->getPageAccessCount()} pages");
	$userEngagement .= " <span style='font-family:courier; font-size:12px; position:relative; top:1px;'>{$user->getPageAccessCount()}</span>";
	
	// build datagrid row
	$row = array();
	$row[] = array('content' => "<span title='ID [".$user->getId()."]' style='line-height:20px;'>".$name."</span>");
	$row[] = array('content' => "<a href='".MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_MEMBERS, MM_MODULE_MEMBER_DETAILS_GENERAL)."&user_id={$user->getId()}'>".MM_Utils::abbrevString($user->getEmail())."</a>");
	$row[] = array('content' => $phone);
	$row[] = array('content' => $membershipStr);
	$row[] = array('content' => MM_Utils::abbrevString($bundles, 30));
	
    if($useCustomField)
    {
    	if($item->custom_field_value == MM_CustomField::$CHECKBOX_ON)
    	{
    		$customFieldContent = MM_Utils::getCheckIcon();
    	}
    	else if($item->custom_field_value == MM_CustomField::$CHECKBOX_OFF)
    	{
    		$customFieldContent = MM_Utils::getCrossIcon();
    	}
    	else 
    	{
	   		$customFieldContent = $item->custom_field_value;	
    	}
    	
    	$row[] = array('content' => $customFieldContent);
    }
    
    if($useCustomField2)
    {
    	if($item->custom_field_value2 == MM_CustomField::$CHECKBOX_ON)
    	{
    		$customFieldContent = MM_Utils::getCheckIcon();
    	}
    	else if($item->custom_field_value2 == MM_CustomField::$CHECKBOX_OFF)
    	{
    		$customFieldContent = MM_Utils::getCrossIcon();
    	}
    	else
    	{
    		$customFieldContent = $item->custom_field_value2;
    	}
    	 
    	$row[] = array('content' => $customFieldContent);
    }
    
    switch($searchByDate)
    {
    	case "user_registered":
    		$row[] = array('content' => $user->getRegistrationDate(true));
    		break;
    
    	case "status_updated":
    		$row[] = array('content' => MM_Utils::dateToLocal($item->status_updated));
    		break;
    }
    
    $row[] = array('content' => $userEngagement);
    $row[] = array('content' => $status);
    $row[] = array('content' => $actions);
    
	$datagridRows[] = $row;
		
	// build CSV row
	if($doGenerateCsv)
	{
		$membershipRegistrationDate= "";
		$membershipLevel = $user->getMembershipLevel(); 
        if($membershipLevel instanceof MM_MembershipLevel)
        {
            if($membershipLevel->doesExpire())
            {
                $date = $user->getExpirationDate(true);
                if(isset($item->expiration_date) &&
                   !is_null($item->expiration_date) &&
                   !empty($item->expiration_date))
                {
                    $membershipRegistrationDate = $item->expiration_date;
                }
                else
                {
                    $membershipRegistrationDate = $membershipLevel->getExpirationDate($user->getRegistrationDate());
                }
            }
            else 
            {
                $membershipRegistrationDate = "N/A";
            }
        }
		
		$csvRow = array();
			
		$csvRow[] = $user->getId();
		$csvRow[] = $user->getFirstName();
		$csvRow[] = $user->getLastName();
		$csvRow[] = $user->getEmail();
		$csvRow[] = $user->getPhone();
		$csvRow[] = $user->getMembershipName(); 
		$csvRow[] = ($bundles == MM_NO_DATA) ? "" : $bundles;
		$csvRow[] = ($bundles == MM_NO_DATA) ? "" : $bundleExpirations;
		$csvRow[] = $membershipRegistrationDate;
		$csvRow[] = $user->getRegistrationDate(true);
		$csvRow[] = MM_Utils::dateToLocal($item->status_updated);
		$csvRow[] = $user->getStatusName();
		$csvRow[] = $user->getBillingAddress();
		$csvRow[] = $user->getBillingCity();
		$csvRow[] = $user->getBillingState();
		$csvRow[] = $user->getBillingZipCode();
		$csvRow[] = $user->getBillingCountryName();
		$csvRow[] = $user->getShippingAddress();
		$csvRow[] = $user->getShippingCity();
		$csvRow[] = $user->getShippingState();
		$csvRow[] = $user->getShippingZipCode();
		$csvRow[] = $user->getShippingCountryName();
		$csvRow[] = $user->getNotes();
		
		$fields = MM_CustomField::getCustomFieldsList();
		foreach($fields as $id=>$val)
		{
			$customField = new MM_CustomField($id);
			if($customField->isValid())
			{
				$csvRow[] = stripslashes($user->getCustomDataByFieldId($customField->getId())->getValue());
	 		}
		}
		
		$csvRows[] = $csvRow;
	}
}

// store CSV in session
if($doGenerateCsv)
{
	$csv = "";
	foreach($csvRows as $row)
	{
		$csvRow = "";
		foreach($row as $elem)
		{
			$csvRow .= "\"".preg_replace("/[\"]+/", "", $elem)."\",";
		}
		$csv .= preg_replace("/(\,)$/", "", $csvRow)."\n";
	}  
	
	MM_Session::value(MM_Session::$KEY_CSV, $csv);  
	
}
$dataGrid->setHeaders($headers);
$dataGrid->setRows($datagridRows);

$dgHtml = $dataGrid->generateHtml();

if(empty($dgHtml)) 
{
	$dgHtml = "<p><i>"._mmt("No members found.") ."</i></p>";
}

echo $dgHtml;
?>
