<?php 
if(isset($_POST["mm_captcha_public_key"]))
{
	require_once MM_PLUGIN_ABSPATH . '/lib/recaptcha/autoload.php';
	
	$publicKey = $_POST["mm_captcha_public_key"];
	$privateKey = $_POST["mm_captcha_private_key"];
	
	// validate the public key
	if(empty($publicKey))
	{
		MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_CAPTCHA_KEY, $publicKey);
	}
	else 
	{
		$contents = MM_Utils::sendRequest('http://www.google.com/recaptcha/api/challenge?k=' . $publicKey,"",0);
		
		if(preg_match("/(invalid)|(input error)/", strtolower($contents)))
		{
			$error = "The Captcha site key provided is invalid. Please try again.";
		}
		else 
		{
			MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_CAPTCHA_KEY, $publicKey);
		}
	}
	    
	MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_CAPTCHA_PRIVATE_KEY, $privateKey);
}

$siteKey = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_CAPTCHA_KEY);
$privateKey = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_CAPTCHA_PRIVATE_KEY);
?>
<div class="mm-wrap">
    <p class="mm-header-text"><?php echo _mmt("Captcha Settings"); ?> <span style="font-size:12px;"><a href="http://support.membermouse.com/support/solutions/articles/9000020327-add-captcha-validation-to-the-checkout-page" target="_blank"><?php echo _mmt("Learn more"); ?></a></span></p>
	<div style="margin-bottom:10px;">
		<img src="https://membermouse.com/assets/plugin_images/logos/recaptcha_v2.png" style="vertical-align:middle; margin-right:10px;" />
		<a target="_blank" href="https://www.google.com/recaptcha/" class="mm-ui-button green"><?php echo _mmt("Create a Free Account"); ?></a> 
		<a target="_blank" href="https://www.google.com/recaptcha/admin" class="mm-ui-button"><?php echo _mmt("Access Existing Account"); ?></a>
	</div>
	
	<table>
		<tr>
			<td width='70'><?php echo _mmt("Site Key"); ?> </td>
			<td>
				<span style="font-family: courier; font-size: 11px;">
				<input type='text' id='mm_captcha_public_key' name='mm_captcha_public_key' value='<?php echo $siteKey; ?>' size="45" />
				</span>
			</td>
		</tr>
		<tr>
			<td width='70'><?php echo _mmt("Secret Key"); ?></td>
			<td>
				<span style="font-family: courier; font-size: 11px;">
				<input type='text' id='mm_captcha_private_key' name='mm_captcha_private_key' value='<?php echo $privateKey; ?>' size="45" />
				</span>
			</td>
		</tr>
	</table>
</div>
