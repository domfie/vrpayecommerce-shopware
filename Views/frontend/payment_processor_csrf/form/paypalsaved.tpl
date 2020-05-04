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
					var buttonCancel = "<div class='btn-box'><a href='{/literal}{url controller=checkout action=confirm}{literal}' class='wpwl-button btn_cancel'>{/literal}{s name="FRONTEND_BT_CANCEL"}Cancel{/s}{literal}</a></div>";
					var ttTestMode = "<div class='testmode'>{/literal}{s name="FRONTEND_TT_TESTMODE"}THIS IS A TEST. NO REAL MONEY WILL BE TRANSFERED{/s}{literal}</div>";
					jQuery('form.wpwl-form').find('.wpwl-button').after(buttonCancel);
					var clearFloat = "<div style='clear:both'></div>";
					var btnPayNow = "<button type='submit' name='pay' class='wpwl-button wpwl-button-pay'>{/literal}{s name="FRONTEND_BT_PAYNOW"}Pay now{/s}{literal}</button>";
					var headerWidget = "<h2 style='text-align : center !important;'>{/literal}{s name="FRONTEND_RECURRING_WIDGET_HEADER2"}Use alternative payment data{/s}{literal}</h2>";
					jQuery('form.wpwl-form-virtualAccount-PAYPAL').find('.wpwl-button-brand').wrap( "<div class='payment-brand'></div>");
	           		jQuery('form.wpwl-form-virtualAccount-PAYPAL').find('.btn_cancel').after(btnPayNow);
	           		jQuery('form.wpwl-form-virtualAccount-PAYPAL').find('.wpwl-button-pay').after(clearFloat);
	           		jQuery(".wpwl-container").wrap( "<div class='frame'></div>");
		            {/literal}{if $testMode}{literal}
			            jQuery(".wpwl-container").wrap( "<div class='frametest'></div>");
			            jQuery('.wpwl-container').before(ttTestMode);
		            {/literal}{/if}{literal}
		            {/literal}{if $recurring}{literal}
		            	jQuery('#wpwl-registrations').after(headerWidget);
		            {/literal}{/if}{literal}
				},
				onBeforeSubmitCard: function(event) {
					removeCSRFToken();
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
	{if $error_message!=''}
		<div class="error-box">{$error_message}</div>
	{/if}
	{if !empty($registrations)}
		<h2 style="text-align : center !important;">{s name="FRONTEND_RECURRING_WIDGET_HEADER1"}Use stored payment data{/s}</h2>
		<div id="wpwl-registrations">
			<div class="wpwl-container wpwl-container-registration wpwl-clearfix" style="display: block;">
					<form class="wpwl-form wpwl-form-registrations wpwl-form-has-inputs wpwl-clearfix" action="{url controller=payment_processor_csrf action=result pm=$paymentMethod}" method="POST" lang="en" accept-charset="UTF-8" data-action="submit-registration">
						{foreach from=$registrations key=k item=v}
							{if $v.payment_default == 1}
								<div class="regid{$v.ref_id} wpwl-group wpwl-group-registration wpwl-clearfix wpwl-selected ">
							{else}
								<div class="regid{$v.ref_id} wpwl-group wpwl-group-registration wpwl-clearfix ">
							{/if}
								<label class="wpwl-registration">
									<div class="wpwl-wrapper-registration wpwl-wrapper-registration-registrationId">
										{if $v.payment_default == 1}
											<input type="radio" name="registrationId" value="{$v.ref_id}" checked="checked" data-action="change-registration">
										{else}
											<input type="radio" name="registrationId" value="{$v.ref_id}" data-action="change-registration">
										{/if}
									</div>
									<div class="wpwl-wrapper-registration wpwl-wrapper-registration-details">
										<div class="wpwl-wrapper-registration wpwl-wrapper-registration-email">{$v.email}</div>
										<div class="wpwl-wrapper-registration wpwl-wrapper-registration-holder">{$v.holder}</div>
									</div>
									<div class="wpwl-wrapper-registration wpwl-wrapper-registration-cvv"></div>
								</label>
							</div>
						{/foreach}
						<div class="wpwl-group wpwl-group-submit wpwl-clearfix">
							<div class="wpwl-wrapper wpwl-wrapper-submit">
								<button type="submit" name="pay" class="wpwl-button wpwl-button-pay">{s name="FRONTEND_BT_PAYNOW"}Pay Now{/s}</button>
							</div>
						</div>
					</form>
				<iframe name="registrations-target" class="wpwl-target" src="about:blank" frameborder="0"></iframe>
			</div>
		</div>
	{else}
		<h2 style="text-align : center !important;">{s name="FRONTEND_MC_PAYANDSAFE"}Pay and Save Payment Information{/s}</h2>
	{/if}
	{literal}
		<script>
			function createWidgetScript() {
				var jsElement = document.createElement("script");
				jsElement.type = "text/javascript";
				jsElement.src = "{/literal}{$paymentWidgetUrl}{literal}";
				document.head.appendChild(jsElement);
				jQuery( "input[type=radio][name=registrationId]" ).on( "click", function() {
					jQuery(".wpwl-group-registration").removeClass("wpwl-selected");
					jQuery(".regid"+this.value).addClass("wpwl-selected");
				});
			}
			window.onload = createWidgetScript();
		</script>
	{/literal}
	<form action="{url controller=payment_processor_csrf action=result pm=$paymentMethod}" class="paymentWidgets">{$brand}</form>
{/block}
