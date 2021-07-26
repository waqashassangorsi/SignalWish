<?php 
/**
 *
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */

$selectedProducts = isset($_REQUEST['mm-product-ids'])?$_REQUEST['mm-product-ids']:"";
$selectedCoupons = isset($_REQUEST['mm-coupon-ids'])?$_REQUEST['mm-coupon-ids']:"";

?>
<div id="mm-form-container" style="background-color: #EAF2FA; padding-top:2px; padding-left:8px; padding-bottom:8px;">
	<table>
		<tr>
			<!-- LEFT COLUMN -->
			<td valign="top" style="width:380px;">
			<table cellspacing="5">
				<tr>
					<td><?php echo _mmt("From"); ?></td>
					<td>
						<input id="mm-from-date" type="text" size="25" placeholder="mm/dd/yyyy" /> 
						<a onClick="jQuery('#mm-from-date').focus();"><?php echo MM_Utils::getCalendarIcon(); ?></a>
					</td>
				</tr>
				<tr>
					<td><?php echo _mmt("To"); ?></td>
					<td>
						<input id="mm-to-date" type="text" size="25" placeholder="mm/dd/yyyy" />
						<a onClick="jQuery('#mm-to-date').focus();"><?php echo MM_Utils::getCalendarIcon(); ?></a>
					</td>
				</tr>
				<tr>
					<td><?php echo _mmt("Order #"); ?></td>
					<td><input id="mm-order-number" type="text" size="25" /></td>
				</tr>
				<tr>
					<td><?php echo _mmt("First Name"); ?></td>
					<td><input id="mm-first-name" type="text" size="25" /></td>
				</tr>
				<tr>
					<td><?php echo _mmt("Last Name"); ?></td>
					<td><input id="mm-last-name" type="text" size="25" /></td>
				</tr>
				<tr>
					<td><?php echo _mmt("Email"); ?></td>
					<td><input id="mm-email" type="text" size="25" /></td>
				</tr>
				<tr>
					<td><?php echo _mmt("Member ID"); ?></td>
					<td><input id="mm-member-id" type="text" size="25" /></td>
				</tr>
			</table>
			</td>
			
			<!-- CENTER COLUMN -->
			<td valign="top">
			<table cellspacing="5" style="width:450px">
				<tr>
					<td><?php echo _mmt("Transaction Types"); ?></td>
					<td>
						<select id="mm-transaction-types[]" multiple="multiple" style="width:300px;" size="3">
							<option value="<?php echo MM_TransactionLog::$TRANSACTION_TYPE_PAYMENT; ?>"><?php echo _mmt("Initial Payments");?></option>
							<option value="<?php echo MM_TransactionLog::$TRANSACTION_TYPE_RECURRING_PAYMENT; ?>"><?php echo _mmt("Recurring Payments");?></option>
							<option value="<?php echo MM_TransactionLog::$TRANSACTION_TYPE_REFUND; ?>"><?php echo _mmt("Refunds");?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td><?php echo _mmt("Products"); ?></td>
					<td>
						<select id="mm-product-ids[]" multiple="multiple" style="width:300px;" size="8">
						<?php echo MM_HtmlUtils::getProducts($selectedProducts); ?>
						</select>
					</td>
				</tr>
			</table>
			</td>
			
			<!-- RIGHT COLUMN -->
			<td valign="top">
			<table cellspacing="5">
				<?php
					$availableCoupons = MM_Coupon::getAllCodes(true);
					
					if(count($availableCoupons) > 0)
					{
				?>
				<tr>
					<td><?php echo _mmt("Coupons"); ?></td>
					<td>
						<select id="mm-coupon-ids[]" multiple="multiple" style="width:200px;" size="5">
						<?php echo MM_HtmlUtils::getNonFreeCouponCodes($selectedCoupons); ?>
						</select>
						<table style="width:260px;"><tr>
							<td><?php echo MM_Utils::getInfoIcon(); ?></td>
							<td style="font-size:10px; line-height:14px;"><em><?php echo _mmt("Free coupons are not included in this list as no transaction is created when a free coupon is used"); ?>.</em></td>
						</tr></table>
					</td>
				</tr>
				<?php } ?>
				
				<tr>
					<td><?php echo _mmt("Affiliate ID"); ?></td>
					<td><input id="mm-affiliate-id" type="text" size="25" /></td>
				</tr>
				<tr>
					<td><?php echo _mmt("Sub-Affiliate ID"); ?></td>
					<td><input id="mm-sub-affiliate-id" type="text" size="25" /></td>
				</tr>
				<tr>
					<td><?php echo _mmt("Service Provider"); ?></td>
					<td><input id="mm-payment-service" type="text" size="25" /></td>
				</tr>
				<!--  column 3 placeholder -->
			</table>
			</td>
		</tr>
	</table>
	
	<input type="button" class="mm-ui-button blue" value="<?php echo _mmt("Search"); ?>" onclick="mmjs.search(0);">
	<input type="button" class="mm-ui-button" value="<?php echo _mmt("Reset Form"); ?>" onclick="mmjs.resetForm();">
</div>

<script type='text/javascript'>
	jQuery(document).ready(function(){
		jQuery("#mm-from-date").datepicker({
				dateFormat: "mm/dd/yy"
		});
		jQuery("#mm-to-date").datepicker({
				dateFormat: "mm/dd/yy"
		});
		jQuery("#mm-form-container :input").keypress(function(e) {
	        if(e.which == 13) {
	            jQuery(this).blur();
	            mmjs.search(0);
	        }
	    });
				
	});
</script>