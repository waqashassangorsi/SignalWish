<?php
/**
 *
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */

$view = new MM_ClickBankIPNLogView();

if(!empty($_REQUEST["sortby"]))
{
	$dataGrid = new MM_DataGrid($_REQUEST, $_REQUEST["sortby"], "desc", 20);
}
else
{
	$dataGrid = new MM_DataGrid($_REQUEST, "date_received", "desc", 20);
}
$data = $view->getViewData($_REQUEST, $dataGrid);

$dataGrid->setTotalRecords($data);
$dataGrid->recordName = "IPN";

$rows = array();
$headers = array();

foreach($data as $key=>$item)
{	
	// member link
	$user = new MM_User($item->user_id);
	
	$memberLink = MM_NO_DATA;
	
	if($user->isValid())
	{
		$memberLink = $user->getUsername();
		$memberLink = "<a href='?page=".MM_MODULE_MANAGE_MEMBERS."&module=details_general&user_id=".$item->user_id."'>".$user->getUsername()."</a>";
	}
	
	// order link
	$orderLink = MM_NO_DATA;
	
	if(!empty($item->order_number))
	{
		$orderLink = "<span style='font-family:courier;'><a href='".MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_MEMBERS, MM_MODULE_MEMBER_DETAILS_TRANSACTION_HISTORY)."&user_id={$item->user_id}'>{$item->order_number}</a></span>";
	}
	
	// IPN Details
	$ipnDetails = MM_NO_DATA;
	
	?>
		<div id="mm-view-info-<?php echo $item->id; ?>" style="display:none;" title="IPN Details" style="font-size:11px;">
			<table style="width:100%">
			<?php 
				$ipnContent = $item;
				$altRow = false;
				
				foreach($ipnContent as $key=>$value)
				{
					$bkgdColor = "#f9f9f9";
					
					if($altRow)
					{
						$bkgdColor = "#fff";
					}
					
					echo "<tr style='background-color:{$bkgdColor}'><td style='padding:2px; padding-left:5px; font-size:10px; color:#666'>";
					echo "<strong>{$key}</strong>";
					echo "</td><td style='padding:2px; padding-left:5px;'>";
					echo "<span style='font-family:courier;'>{$value}</span>";
					echo "</td></tr>";
					
					$altRow = !$altRow;
				}
			?>
			</table>
		</div>
	<?php

	// txn ID
	$receipt = MM_NO_DATA;
	
	if(!empty($item->ctransreceipt))
	{
		$receipt = "<span style='font-family:courier;'>{$item->ctransreceipt}</span>";
	}
	
	// txn type
	$type = MM_NO_DATA;
	
	if(!empty($item->ctransaction))
	{
		$type = "<span style='font-family:courier;'>{$item->ctransaction}</span>";
	}

	
	$ipnDetails = "<a href='javascript:viewInfo({$item->id})'>View IPN Details</a>";
	
	$row = array();
	$row[] = array('content' => $memberLink);
	$row[] = array('content' => $orderLink);
	$row[] = array('content' => $receipt);
	$row[] = array('content' => $type);
	$row[] = array('content' => $ipnDetails);
	$row[] = array('content' => MM_Utils::dateToLocal($item->date_received));
	
	$rows[] = $row;
}

$headers['user_id'] = array('content' => '<a onclick="mmjs.sort(\'user_id\');" href="#">Member</a>', "attr" => "style='width:250px;'");
$headers['order_id'] = array('content' => '<a onclick="mmjs.sort(\'order_id\');" href="#">Order#</a>', "attr" => "style='width:80px;'");
$headers['receipt'] = array('content' => '<a onclick="mmjs.sort(\'txn_id\');" href="#">Receipt</a>', "attr" => "style='width:150px;'");
$headers['type'] = array('content' => '<a onclick="mmjs.sort(\'txn_type\');" href="#">Transaction Type</a>', "attr" => "style='width:150px;'");
$headers['ipn_content'] = array('content' => 'IPN Details', "attr" => "style='width:150px;'");
$headers['received'] = array('content' => '<a onclick="mmjs.sort(\'received\');" href="#">Date</a>', "attr" => "style='width:150px;'");

$dataGrid->setHeaders($headers);
$dataGrid->setRows($rows);

$dgHtml = $dataGrid->generateHtml();

if($dgHtml == "") 
{
	$dgHtml = "<p><i>No IPNs found.</i></p>";
}

echo $dgHtml;
?>