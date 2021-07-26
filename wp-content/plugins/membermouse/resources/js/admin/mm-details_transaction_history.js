/*!
 * 
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */
var MM_TransactionHistoryViewJS = MM_Core.extend({
	issueRefund: function(transactionId, transactionAmt){
		mmjs.closeDialog();
		var isOk = confirm("Are you sure you want to refund $"+transactionAmt+" to this customer?");
		if(isOk){
		    var values = {
		        transaction_id:transactionId,
		        transaction_amount: transactionAmt,
		        mm_action: "refundTransaction"
		    };
		    var ajax = new MM_Ajax(false, this.module, this.action, this.method);
		    ajax.send(values, false, 'mmjs', "refundTransactionCallback");
		}
	},
	
	refundTransactionCallback: function(response)
	{
		if(response.type == "success")
		{
			alert(response.message);
			this.search();
		}
		else
		{
			jQuery("#mm-issue-refund-dialog").html("<div style='margin-top:10px;'><center>" + response.message + "</center></div>");
			jQuery("#mm-issue-refund-dialog").show();
			jQuery("#mm-issue-refund-dialog").dialog({autoOpen: true, width: "600", height: "120"});
		}
		
	},
	
	editTransaction: function(orderId, orderItemId, transactionId)
	{
		var dialogId = "mm-edit-transaction-dialog";
		jQuery("#"+dialogId).dialog({autoOpen: false});
	 
		var values = {};
		values.order_id = orderId;
		values.order_item_id = orderItemId;
		values.transaction_id = transactionId;
		values.mm_action = "showEditDialog";
		mmdialog_js.method = 'showEditTransactionDialog';
		mmdialog_js.showDialog(dialogId, this.module, 400, 250, "Edit Transaction", values, "performAction");
	},
	
	saveTransaction: function()
	{
		var form_obj = new MM_Form('mm-edit-transaction-div');
	    var values = form_obj.getFields();
		values.mm_action = "saveTransaction";
	    
	    var ajax = new MM_Ajax(false, this.module, this.action, this.method);
	    ajax.send(values, false, 'mmjs', "saveTransactionCallback"); 
	},
	
	saveTransactionCallback: function(response)
	{
		mmjs.closeDialog();
		alert(response.message);
		document.location.href = document.location.href;
	},
	
	resendAffiliateCommission: function(userId, orderId, orderNumber, orderItemId, transactionId, affiliateId)
	{
		var values = {};
		values.user_id = userId;
		values.order_id = orderId;
		values.order_item_id = orderItemId;
		values.transaction_id = transactionId;
		values.mm_action = "resendAffiliateCommission";
	    
		msg = "Are you sure you want to send this commission request? This may result in duplicate tracking.\n\n";
		msg += "Before continuing you may want to confirm that this commission hasn't already been reported:\n";
		msg += "Affiliate ID: " + affiliateId + "\n";
		msg += "Order #: " + orderNumber + "-" + transactionId + "\n\n";
		msg += "Click OK to continue sending the commission request.";
		
		var doContinue = confirm(msg);
		if(doContinue)
		{
			var ajax = new MM_Ajax(false, this.module, this.action, this.method);
			ajax.send(values, false, 'mmjs', "resendAffiliateCommissionCallback"); 
		}
	},
	
	resendAffiliateCommissionCallback: function(response)
	{
		alert(response.message);
		document.location.href = document.location.href;
	},
	
	sort: function(columnName) 
	  {
		  var newSortDir = "asc";
		  
		  if(columnName == this.sortBy)
		  {
			  if(this.sortDir=="asc") {
				  newSortDir = "desc";
			  }
		  }
		  
		  this.sortBy = columnName;
		  this.sortDir = newSortDir;
		  
		  this.search();
	  },
	
	dgPreviousPage: function(crntPage)
	  {	
		  if(parseInt(crntPage) != 0) {
			  this.crntPage = parseInt(crntPage) - 1;
			  this.search();
		  }
	  },
	  
	  dgNextPage: function(crntPage, totalPages)
	  {
		  if(crntPage != (parseInt(totalPages) - 1)) {
			  this.crntPage = parseInt(crntPage) + 1;
			  this.search();
		  }
	  },
	
	dgSetResultSize: function(pageControl){
		  if(jQuery(pageControl).val() != undefined)
		  {
			  this.crntPage = 0;
			  this.resultSize = jQuery(pageControl).val();
			  this.search();
		  }
	  },
	  
	  search: function(crntPage) 
	  {
		  var form_obj = new MM_Form('mm-form-container');
		  var values = form_obj.getFormContents();
		  
		  if(crntPage != undefined) {
			  this.crntPage = crntPage;
		  }
      
		  values.sortBy = this.sortBy;
		  values.sortDir = this.sortDir;
		  values.crntPage = this.crntPage;
		  values.resultSize = this.resultSize;
		  values.mm_action = "refreshView";
		  var ajax = new MM_Ajax(false, this.module, this.action, this.method);
		  ajax.send(values, false, "mmjs", "refreshViewCallback"); 
	  },
	
});

var mmjs = new MM_TransactionHistoryViewJS("MM_TransactionHistoryView", "Transaction History");