<?php
/**
 *
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */

$error = "";
if(isset($_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT]))
{
	if(!preg_match("/[0-9]+/", $_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT]) || intval($_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT]) <= 0)
	{
		$error = "1-click purchase confirmation dialog height must be greater than 0.";
	}
	
	if(empty($error))
	{
		MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT, $_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT]);
	}
}

if(isset($_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH]))
{
	if(!preg_match("/[0-9]+/", $_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH]) || intval($_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH]) <= 0)
	{
		$error = "1-click purchase confirmation dialog width must be greater than 0.";
	}

	if(empty($error))
	{
		MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH, $_POST[MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH]);
	}
}

$width = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH);
$height = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT);

?>

<div style="width: 600px; margin-top: 8px;" class="mm-divider"></div> 

<div class="mm-wrap">
	<p class="mm-header-text"><?php echo _mmt("1-Click Purchase Confirmation Settings");?> <span style="font-size:12px;"><a href="http://support.membermouse.com/support/solutions/articles/9000020386-adjust-the-size-of-the-1-click-purchase-confirmation-dialog" target="_blank"><?php echo _mmt("Learn More");?></a></span></p>
	
	<div style="margin-top:10px;">
		<p><?php echo _mmt("Set the width and height for the 1-click purchase confirmation dialog below"); ?>:</p>
		<p>
			<?php echo _mmt("Width"); ?>: <input type='text' style='width: 60px;' name='<?php echo MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_WIDTH; ?>' value='<?php echo $width; ?>' />
			<span style="margin-left:10px;"><?php echo _mmt("Height"); ?>: <input type='text' style='width: 60px;' name='<?php echo MM_OptionUtils::$OPTION_KEY_PURCHASE_CONFIRMATION_DIALOG_HEIGHT; ?>' value='<?php echo $height; ?>' /></span>
		</p>
	</div>
</div>