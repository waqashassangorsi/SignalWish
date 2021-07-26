stripe = Stripe(stripeElementsInfo.stripePublishableKey);

var MM_StripeElements = Class.extend({ 
	
	pageObject: {},
	stripe: {},
	elements: {}, 
	card: {}, 
	
	init: function()
	{       
	},
	
	createElementDiv: function(el, wrapperID) {  
		if ((el != null) && (document.getElementById(wrapperID) == null))
		{
			var wrapper = document.createElement('div');
			wrapper.id = wrapperID;
		    el.parentNode.insertBefore(wrapper, el);
		    wrapper.appendChild(el);
		}
	}, 
	  
	parseCss: function(text) {
		let tokenizer = /([\s\S]+?)\{([\s\S]*?)\}/gi,
			rules = [],
			rule, token;
		text = text.replace(/\/\*[\s\S]*?\*\//g, '');
		while ( (token=tokenizer.exec(text)) ) {
			style = mmStripeElements.parseRule( token[2].trim() );
			style.cssText = mmStripeElements.stringifyRule(style);
			rule = {
				selectorText : token[1].trim().replace(/\s*\,\s*/, ', '),
				style : style
			};
			rule.cssText = rule.selectorText + ' { ' + rule.style.cssText + ' }';
			rules.push(rule);
		}
		return rules;
	},


	parseRule: function(css) {
		let tokenizer = /\s*([a-z\-]+)\s*:\s*((?:[^;]*url\(.*?\)[^;]*|[^;]*)*)\s*(?:;|$)/gi,
			obj = {},
			token;
		while ( (token=tokenizer.exec(css)) ) {
			obj[token[1]] = token[2];
		}
		return obj;
	},

	stringifyRule: function(style) {
		let text = '',
			keys = Object.keys(style).sort();
		for (let i=0; i<keys.length; i++) {
			text += ' ' + keys[i] + ': ' + style[keys[i]] + ';';
		}
		return text.substring(1);
	},
	
	parseStyleRulesIntoElements: function(rules, matchingStr, myMMReferenceVar)
	{
		if(myMMReferenceVar == null)
		{
			myMMReferenceVar = {};
		}

		if(rules!=undefined && rules != null)
		{
			if(rules.selectorText == matchingStr)
			{   
				for(var eachvar in rules.style)
				{
					if(eachvar!="cssText")
					{   
						if(myMMReferenceVar[eachvar] == undefined)
						{ 
							myMMReferenceVar[eachvar] = rules.style[eachvar]; 
						}
					} 
				} 
			}
		}
	}, 
	
	/*
	 * Replace css elements from valid 
	 */
	translateCssForStripe: function(css)
	{
		/*
		 * Stripe style elements:
		 * fontFamily, fontSize, fontSmoothing, fontStyle, fontVariant, fontWeight, iconColor, 
		 * lineHeight, letterSpacing, textAlign, textDecoration, textShadow, and textTransform
		 */ 
		var replaceArr = [];
		replaceArr["font-size"] = "fontSize";
		replaceArr["font-family"] = "fontFamily";
		replaceArr["font-smoothing"] = "fontSmoothing";
		replaceArr["font-style"] = "fontStyle";
		replaceArr["font-variant"] = "fontVariant";
		replaceArr["font-weight"] = "fontWeight";
		replaceArr["icon-color"] = "iconColor";
		replaceArr["line-height"] = "lineHeight";
		replaceArr["letter-spacing"] = "letterSpacing";
		replaceArr["text-align"] = "textAlign";
		replaceArr["text-decoration"] = "textDecoration";
		replaceArr["text-shadow"] = "textShadow";
		replaceArr["text-transform"] = "textTransform"; 
		for(var eachvar in replaceArr)
		{
    		css = css.replace(eachvar, replaceArr[eachvar]);
		}	
		return css;
	},
	
	getCustomStyleAndPlaceholder: function(divName)
	{  
		var divStr = "#"+divName+" .mm-stripe-elements-container"; 
		var divBaseStr = "#"+divName+" .mm-stripe-elements-container .base"; 
		var divInvalidStr = "#"+divName+" .mm-stripe-elements-container .invalid"; 
		var divEmptyStr = "#"+divName+" .mm-stripe-elements-container .empty"; 
		var divCompleteStr = "#"+divName+" .mm-stripe-elements-container .complete"; 

		var mmStripeElementsBaseStyle = {placeholder:''};
		var mmStripeElementsInvalidStyle = {};
		var mmStripeElementsEmptyStyle = {};
		var mmStripeElementsCompleteStyle = {};

		let rules = [];  
		var cssText = "";
		var foundStyleSheet = false;
		jQuery.each(document.styleSheets, function(sheetIndex, sheet) {
			try{
				if(sheet.cssRules!=undefined && sheet.cssRules!=null)
				{ 
				    jQuery.each(sheet.cssRules || sheet.rules, function(ruleIndex, rule) {
				        var css = rule.cssText;
				        if(css != undefined && css != null && css.length>0)
				        {
				        	if(css.includes(divStr) || 
				        			css.includes(divBaseStr) || 
				        			css.includes(divInvalidStr) || 
				        			css.includes(divEmptyStr) || 
				        			css.includes(divCompleteStr)
				        	)
				        	{
				        		css = mmStripeElements.translateCssForStripe(css); 
				        		cssText += css;
				        	}
				        }
				    });
				}
			}
			catch(ex)
			{
				console.log("MemberMouse :: Loading style sheet exception :: "+ex.message); 
			}
		});
		
		if(cssText.length>0)
		{
			rules = mmStripeElements.parseCss(cssText);
			foundStyleSheet = true;
		}
		
		if(!foundStyleSheet)
		{
			if(jQuery('style[id="mm-stripe-elements-definitions"]').length)
			{ 	 
				rules = mmStripeElements.parseCss(jQuery('style').text());
			}
		}

		for(i=0; i<rules.length; i++)
		{    
			// generic style definition for given div
			mmStripeElements.parseStyleRulesIntoElements(rules[i], divStr, mmStripeElementsBaseStyle); 
			
			// more specific base definition
			mmStripeElements.parseStyleRulesIntoElements(rules[i], divBaseStr, mmStripeElementsBaseStyle); 

			// specific invalid definition
			mmStripeElements.parseStyleRulesIntoElements(rules[i], divInvalidStr, mmStripeElementsInvalidStyle); 

			// specific empty definition
			mmStripeElements.parseStyleRulesIntoElements(rules[i], divEmptyStr, mmStripeElementsEmptyStyle);
			
			// specific complete definition
			mmStripeElements.parseStyleRulesIntoElements(rules[i], divCompleteStr, mmStripeElementsCompleteStyle);			 
		}   
		
		var placeholder = "";
		if(mmStripeElementsBaseStyle["content"]!=undefined && mmStripeElementsBaseStyle["content"]!=null)
		{
			placeholder = mmStripeElementsBaseStyle["content"].replace(/\"/g,"");
		}
		
		delete mmStripeElementsBaseStyle["content"];
		var styleForDiv = {
				"placeholder": placeholder,
			    "style": {
			        "base": mmStripeElementsBaseStyle 
			    }
			};
		if(mmStripeElementsInvalidStyle!=null)
		{
			styleForDiv["style"]["invalid"] = mmStripeElementsInvalidStyle;
		}
		if(mmStripeElementsEmptyStyle!=null)
		{
			styleForDiv["style"]["empty"] = mmStripeElementsEmptyStyle;
		} 
		if(mmStripeElementsCompleteStyle!=null)
		{
			styleForDiv["style"]["complete"] = mmStripeElementsCompleteStyle;
		} 
		return styleForDiv;   
	},
	  
	load: function()
	{
		console.log("Building... loading form elements !");
		this.stripe = Stripe(stripeElementsInfo.stripePublishableKey); 
		
		this.elements = this.stripe.elements();  
		
		this.card = this.elements.create('cardNumber', mmStripeElements.getCustomStyleAndPlaceholder("mm_field_cc_number_div"));  
		this.card.mount('#mm_field_cc_number_div');
		
		var cardExpiry = this.elements.create('cardExpiry', mmStripeElements.getCustomStyleAndPlaceholder("mm_field_cc_exp_div"));
		cardExpiry.mount('#mm_field_cc_exp_div');
		 
		var cardCvc = this.elements.create('cardCvc', mmStripeElements.getCustomStyleAndPlaceholder("mm_field_cc_cvv_div"));
		cardCvc.mount('#mm_field_cc_cvv_div');   
		
		// Handle real-time validation errors from the card Element.
		 this.card.addEventListener('change', function(event) {
		  var displayError = document.getElementById('card-errors');
		  if(displayError!=undefined && displayError!=null){ 
			  if (event.error) {
			    displayError.textContent = event.error.message;
			  } else {
			    displayError.textContent = '';
			  }
		  }
		  else{
			  if (event.error) {
				  console.log("Error with Stripe Elements: ");
				  console.log(event.error);
				  mmStripeElements.errorHandler(event.error.message);
			  }
		  }
		});
	},
	
	buildForm: function()
	{   
		mmStripeElements.load();
	},
	
	errorHandler: function(errorMessage)
	{ 
		alert(errorMessage);
	}, 
	
	doTokenExchange: function()
	{     
		mmStripeElements.pageObject.usePaymentTokenField = true;  

		var firstName = "";
		var lastName = "";
		var cardholderName = "";
		if (!mmStripeElements.myAccount)
	    { 
			if(jQuery("#mm_field_first_name").length)
			{
				firstName = document.getElementById('mm_field_first_name').value;
			}
			
			if(jQuery("#mm_field_last_name").length)
			{
				lastName = document.getElementById('mm_field_last_name').value;
			}  
	    }
		else{
			if(jQuery("#mm-data-first-name").length)
			{
				firstName = jQuery("#mm-data-first-name").text().trim();
			}
			
			if(jQuery("#mm-data-last-name").length)
			{
				lastName = jQuery("#mm-data-last-name").text().trim();
			}
		}
		cardholderName = firstName+" "+lastName;
		
		var cardButton = document.getElementById('card-button'); 

		if(typeof stripeSecret === 'undefined' || stripeSecret==null || stripeSecret.length<=0)
		{
			if(typeof stripeSecretError !== 'undefined' && stripeSecretError!==null && stripeSecretError.length>0)
			{
				mmStripeElements.errorHandler(stripeSecretError);
			}
			else
			{
				mmStripeElements.errorHandler("Invalid gateway configuration.");
			}
			return false;
		}
		
		var paymentMethodData = {};
		cardholderName = cardholderName.trim();
		if(cardholderName.length>0)
		{  
			paymentMethodData = {
		        billing_details: {name: cardholderName}
		    };
		}
		
		mmStripeElements.stripe.handleCardSetup(
				  stripeSecret, mmStripeElements.card, {
					  payment_method_data: paymentMethodData
				  }
	  	).then(function(result) {
	  	  console.log(result); 
		  if (result.error) {  
			var errorMessage = result.error.message; 
			mmStripeElements.errorHandler(errorMessage);
		  } else {
			  	console.log(result);
				if (mmStripeElements.myAccount)
			    { 
			    	mmStripeElements.pageObject.addPaymentTokenToForm(result.setupIntent.payment_method);  
		    		mmStripeElements.pageObject.doSubscriptionBillingUpdate(); 
			    }
		    	else{ 
			    	mmStripeElements.pageObject.addPaymentTokenToForm(result.setupIntent.payment_method);  
			    	mmStripeElements.pageObject.submitCheckoutForm(false); 
		    	}
		    	return true;
		    }
		}); 
		  
		return false; 
	}
}); 

var mmStripeElements = new MM_StripeElements();  
if ((typeof myaccount_js !== 'undefined') && (myaccount_js instanceof MM_MyAccountView))
{
	//my account page
	mmStripeElements.myAccount = true;
	mmStripeElements.pageObject = myaccount_js;   
	mmStripeElements.pageObject.usePaymentTokenField = true;
	myaccount_js.addPreUpdateCallback('onsite',mmStripeElements.doTokenExchange);  

	jQuery( ".mm-update-subscription-button" ).on( "form:loaded", function( event ) { 
		mmStripeElements.buildForm(); 
    });
}
else
{
	//checkout page 
	mmStripeElements.pageObject = mmjs;      
	mmStripeElements.pageObject.usePaymentTokenField = true;
	mmjs.addPrecheckoutCallback('onsite',mmStripeElements.doTokenExchange);

	jQuery(document).ready(function() {
		mmStripeElements.pageObject = mmjs;      
		mmStripeElements.pageObject.usePaymentTokenField = true;
		mmjs.addPrecheckoutCallback('onsite',mmStripeElements.doTokenExchange);
		mmStripeElements.buildForm();
	}); 
} 
