<?php
/**
 * 
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */

$view = new MM_BundlesView();
$dataGrid = new MM_DataGrid($_REQUEST, "id", "desc", 10);
$data = $view->getViewData($dataGrid);
$dataGrid->setTotalRecords($data);
$dataGrid->recordName = "bundle";

$rows = array();

foreach($data as $key => $item)
{
    $tag = new MM_Bundle($item->id, false);
    
	// Type / Products
	if($item->is_free != "1")
	{  	
	    $products = array();
	    $productIds = array();
	    
	    if(!empty($item->products)) 
	    {
		    foreach($item->products as $product)
	   		{
	   			$products[] = "<a href='".MM_ModuleUtils::getUrl(MM_MODULE_PRODUCT_SETTINGS, MM_MODULE_PRODUCTS)."&autoload=".$product->id."'>".$product->name."</a>";
	   			$productIds[] = $product->id;
	   		}
	    }
	    
	    $bundleType = MM_Utils::getIcon('dollar', 'green', '1.3em', '2px', _mmt('Paid Bundle'));
		$productAssociations = MM_Utils::getIcon('shopping-cart', 'blue', '1.3em', '1px', _mmt('Products'), 'margin-right:5px;').join(', ' , $products);
		$purchaseLinks = '<a title="'._mmt("Get purchase links").'" onclick="mmjs.showPurchaseLinks('.$item->id.',\''.htmlentities(addslashes($item->name), ENT_QUOTES, "UTF-8").'\', \''.join(',' , $productIds).'\')" class="mm-ui-button" style="margin:0px;">'.MM_Utils::getIcon('money', '', '1.3em', '1px', '', 'margin-right:0px;').'</a>';
	}
	else 
	{
		 $bundleType = MM_Utils::getIcon('dollar', 'red', '1.3em', '2px', _mmt('Free Bundle'));
		 $productAssociations = MM_NO_DATA;
		 $purchaseLinks = MM_NO_DATA;
	}  
	
    // Name / Subscribers		    
    if(!empty($item->member_count))
    {
   		$item->name .= '<p>'.MM_Utils::getIcon('users', 'blue', '1.2em', '1px', '', 'margin-right:2px;').' <a href="'.MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_MEMBERS, MM_MODULE_BROWSE_MEMBERS).'&bundleId='.$item->id.'">'.$item->member_count.' Subscribers</a></p>';
   	}
   	else
   	{
   		$item->name .= '<p>'.MM_Utils::getIcon('users', 'grey', '1.2em', '1px', '', 'margin-right:2px;').' <i>'._mmt('No Subscribers').'</i></p>';
   	}

    // Actions
   	$editActionUrl = 'onclick="mmjs.edit(\'mm-bundles-dialog\', \''.$item->id.'\')"';
   	$deleteActionUrl = 'onclick="mmjs.remove(\''.$item->id.'\')"';
   	$actions = MM_Utils::getEditIcon(_mmt("Edit Bundle"), '', $editActionUrl);
   	
    if(!$tag->hasAssociations() && intval($item->member_count) <= 0)
    {
  	 	$actions .= MM_Utils::getDeleteIcon(_mmt("Delete Bundle"), 'margin-left:5px;', $deleteActionUrl);
    }
    else 
    {
   		$actions .= MM_Utils::getDeleteIcon(_mmt("This bundle is currently being used and cannot be deleted"), 'margin-left:5px;', '', true);
    }

	$rows[] = array
    (
        array( 'content' => $item->id),
    	array( 'content' => "<span title='ID [".$item->id."]'>".$item->name."</span>"),
    	array( 'content' => $bundleType),
    	array( 'content' => $productAssociations),
    	array( 'content' => $purchaseLinks),
    	array( 'content' => MM_Utils::getStatusImage($item->status)),
    	array( 'content' => $actions),
    );
}

$headers = array
(
    'id'            => array('content' => '<a onclick="mmjs.sort(\'id\');" href="#">'._mmt("ID").'</a>'),
	'name'			=> array('content' => '<a onclick="mmjs.sort(\'name\');" href="#">'._mmt('Name / Subscribers').'</a>'),
	'is_free'		=> array('content' => '<a onclick="mmjs.sort(\'is_free\');" href="#">'._mmt('Type').'</a>'),
	'products'		=> array('content' => 'Products', 'attr'=>'style="width:500px;"'),
   	'purchaselinks'	=> array('content' => _mmt('Purchase Links')),
	'status'		=> array('content' => '<a onclick="mmjs.sort(\'status\');" href="#">'._mmt('Status').'</a>'),
	'actions'		=> array('content' => _mmt('Actions'))
);

$dataGrid->setHeaders($headers);
$dataGrid->setRows($rows);

$dgHtml = $dataGrid->generateHtml();

if($dgHtml == "") {
	$dgHtml = "<p><i>"._mmt("No bundles").".</i></p>";
}
?>
<div class="mm-wrap">
	
	<?php if(MM_MemberMouseService::hasPermission(MM_MemberMouseService::$FEATURE_BUNDLES)) { ?>
		<div class="mm-button-container">
			<a onclick="mmjs.create('mm-bundles-dialog')" class="mm-ui-button green"><?php echo MM_Utils::getIcon('plus-circle', '', '1.2em', '1px'); ?> <?php echo _mmt("Create Bundle");?></a>
		</div>
	
		<div class="clear"></div>
		
		<div style="width:98%">
		<?php echo $dgHtml; ?>
		</div>
	<?php } else { ?>
		<?php echo MM_Utils::getIcon('lock', 'yellow', '1.3em', '2px'); ?> 
		<?php echo _mmt("This feature is not available on your current plan");?>. <?php echo sprintf(_mmt("To get access, %s upgrade your plan now %s"), '<a href="'.MM_MemberMouseService::getUpgradeUrl(MM_MemberMouseService::$FEATURE_BUNDLES).'" target="_blank">','</a>'); ?>
	<?php } ?>
</div>

<?php if(isset($_REQUEST["autoload"])) { ?>
<script type='text/javascript'>
jQuery(document).ready(function() {
	<?php
	if($_REQUEST["autoload"] == "new")
	{
		 echo 'mmjs.create(\'mm-bundles-dialog\');';
	}
	else
	{
		echo 'mmjs.edit(\'mm-bundles-dialog\', \''.$_REQUEST["autoload"].'\');';
	}
	?>
});
</script>
<?php } ?>