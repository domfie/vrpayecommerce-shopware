{extends file="parent:frontend/checkout/confirm.tpl"}
{block name='frontend_index_content_left'}{/block}
{* Main content *}
{block name='frontend_index_content'}
	<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/styles/paymentform.css'}" media="all" />
	{literal}
	<script type="text/javascript">
		function removeCSRFToken() {
			var csrfElement = jQuery('.wpwl-form input[name=__csrf_token]');
			if(csrfElement.length > 0) {
				csrfElement.remove();
			}
		}

		var wpwlOptions = {
			locale: "{/literal}{$lang}{literal}",
			style: "card",
			onReady: function(){
				var buttonCancel = "<a href='{/literal}{url controller=checkout action=confirm}{literal}' class='wpwl-button btn_cancel'>{/literal}{s name="FRONTEND_BT_CANCEL"}Cancel{/s}{literal}</a>";
				var ttTestMode = "<div class='testmode'>{/literal}{s name="FRONTEND_TT_TESTMODE"}THIS IS A TEST. NO REAL MONEY WILL BE TRANSFERED{/s}{literal}</div>";
				var headerWidget = "<h2 style='text-align : center !important;'>{/literal}{s name="FRONTEND_RECURRING_WIDGET_HEADER2"}Use alternative payment data{/s}{literal}</h2>";
				var merchantLocation = "<div class='merchant-location-description'>{/literal}{s name="FRONTEND_MERCHANT_LOCATION_DESC"}Payee: {/s}{$merchantLocation}{literal}</div>";
	            jQuery('form.wpwl-form').find('.wpwl-button').before(buttonCancel);
	            {/literal}{if $merchantLocation && ($paymentMethod == 'vrpay_cc' || $paymentMethod == 'vrpay_ccsaved')}{literal}
	            	jQuery('.wpwl-container').after(merchantLocation);
	            {/literal}{/if}{literal}
	            jQuery(".wpwl-container").wrap( "<div class='frame'></div>");
	            {/literal}{if $testMode}{literal}
		            jQuery(".wpwl-container").wrap( "<div class='frametest'></div>");
		            jQuery('.wpwl-container').before(ttTestMode);   
	            {/literal}{/if}{literal}  
	            {/literal}{if $recurring}{literal}
	            	jQuery('#wpwl-registrations').after(headerWidget);
	            	if (jQuery('.merchant-location-description').length > 1){
		                jQuery('.merchant-location-description').eq(0).hide();
		            }
	            {/literal}{/if}{literal}
	    		
			},
			onBeforeSubmitCard: function(event) {
				removeCSRFToken();
				return true;
			},
			onBeforeSubmitDirectDebit: function(event) {
				removeCSRFToken();
			},
			onBeforeSubmitOnlineTransfer: function(event) {
				removeCSRFToken();
			},
			onBeforeSubmitVirtualAccount: function(event) {
				removeCSRFToken();
			},
			onAfterSubmit: function(event) {
				removeCSRFToken();
			},
			registrations: {
            	hideInitialPaymentForms: false,
            	requireCvv: false
            }
	    }
	</script>
	{/literal}	
	{if $recurring}
		{if $error_message!=''}
			<div class="error-box">{$error_message}</div>
		{/if}
		{if $registrations }
			<h2 style="text-align : center !important;">{s name="FRONTEND_RECURRING_WIDGET_HEADER1"}Use stored payment data{/s}</h2>
		{else}
			<h2 style="text-align : center !important;">{s name="FRONTEND_MC_PAYANDSAFE"}Pay and Save Payment Information{/s}</h2>
		{/if}
	{/if}
	{literal}
		<script>
			function createWidgetScript() {
				var jsElement = document.createElement("script");
				jsElement.type = "text/javascript";
				jsElement.src = "{/literal}{$paymentWidgetUrl}{literal}";
				document.head.appendChild(jsElement);
			}
			window.onload = createWidgetScript();
		</script>
	{/literal}
	<form action="{url controller=payment_processor action=result pm=$paymentMethod}" class="paymentWidgets">{$brand}
	</form>
{/block}
