{extends file='frontend/payment_information/information.tpl'}

{* Main content *}
{block name="frontend_index_content"}
	<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/styles/paymentform_account.css'}" media="all" />
	<div class="account--change-payment account--content register--content" data-register="true">
	    {if !empty($sError)}
		    {literal}
		    <style type="text/css">
		    	#error--message{
		    		display: block;
		    	}
		    </style>
		    {/literal}
		{else}
			{literal}
		    <style type="text/css">
		    	#error--message{
		    		display: none;
		    	}
		    </style>
		    {/literal}
	    {/if}
			<div class="alert is--error is--rounded" id="error--message">
				<div class="alert--icon">
					<i class="icon--element icon--cross"></i>
				</div>
				<div class="alert--content">
					{s name="ERROR_MC_ADD"}We are sorry. Your attempt to save your payment information was not successful, please try again.{/s}
				</div>
			</div>

		<div class="account--welcome title-center">
			<h1 class="panel--title">{s name="FRONTEND_MC_SAVE"}Save Payment Information{/s}</h1>
	    </div>
        <div class="panel">
			{literal}
			<script type="text/javascript">
				function isSessionExist()
				{
					var succesed;
					$.ajax({
				        type: "GET",
				        async: false,
				        cache: false,
				        url: '{/literal}{url controller=payment_information action=isSessionExist}{literal}',
				        success: function(result){
				        	succesed = result;
			        	},
			        	error: function(result){
				        	succesed = 'false';
			        	}
			        });

			        return succesed;
				}

				function removeCSRFToken() {
					var csrfElement = jQuery('.wpwl-form > input[name=__csrf_token]');
					if(csrfElement.length > 0) {
						csrfElement.remove();
					}
				}

				var wpwlOptions = {
					locale: "{/literal}{$lang}{literal}",
					style: "card",
					onReady: function(){
						var buttonCancel = "<a href='{/literal}{url controller=payment_information action=information}{literal}' class='wpwl-button btn_cancel'>{/literal}{s name="FRONTEND_BT_CANCEL"}Cancel{/s}{literal}</a>";
						var buttonConfirm = "{/literal}{s name="FRONTEND_BT_REGISTER"}Register{/s}{literal}";
						var ttTestMode = "<div class='testmode'>{/literal}{s name="FRONTEND_TT_TESTMODE"}THIS IS A TEST. NO REAL MONEY WILL BE TRANSFERED{/s}{literal}</div>";
						var ttRegistration = "<div class='register-tooltip'>{/literal}{s name="FRONTEND_TT_REGISTRATION"}A small amount (<1 &euro;) will be charged and instantly refunded to verify your account/card details.{/s}{literal}</div>";
			            $('form.wpwl-form').find('.wpwl-button').before(buttonCancel);
			            $('.wpwl-button-pay').html(buttonConfirm);
			            $('.wpwl-container').after(ttRegistration);
			            {/literal}{if $testMode}{literal}
				            $(".wpwl-container").wrap( "<div class='frametest'></div>");
				            $('.wpwl-container').before(ttTestMode);
			            {/literal}{/if}{literal}
					},
			    	onBeforeSubmitCard: function(event){
						removeCSRFToken();
						if (isSessionExist() == 'true') {
							return true;
						} else {
							$('#error--message').show();
							return false;
						}
			    	},
			    	onBeforeSubmitDirectDebit: function(event){
						removeCSRFToken();
			    		if (isSessionExist() == 'true') {
							return true;
						} else {
							$('#error--message').show();
							return false;
						}
			    	},
			    	onBeforeSubmitOnlineTransfer: function(event) {
						removeCSRFToken();
					},
					onBeforeSubmitVirtualAccount: function(event) {
						removeCSRFToken();
					},
					onAfterSubmit: function(event) {
						removeCSRFToken();
					}
			    }
			</script>
			{/literal}
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
			<form action="{url controller=payment_information action=result pm=$paymentMethod}" class="paymentWidgets">{$brand}</form>
		</div>
	</div>
{/block}
