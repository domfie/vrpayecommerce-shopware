{extends file='frontend/payment_information/information.tpl'}

{* Main content *}
{block name='frontend_index_content'}
	{literal}
		<style>
			body {
				display: none;
				background: white;
			}
		</style>
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
				var csrfElement = jQuery('.wpwl-form input[name=__csrf_token]');
				if(csrfElement.length > 0) {
					csrfElement.remove();
				}
			}

			var wpwlOptions = {
				onReady: function(){
					document.getElementsByClassName('wpwl-form')[0].submit();
				},
				onBeforeSubmitVirtualAccount: function(event){
					removeCSRFToken();
					if (isSessionExist() == 'true') {
						return true;
					} else {
						$('#error--message').show();
						return false;
					}
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
				onAfterSubmit: function(event) {
					removeCSRFToken();
				}
		    }
		</script>
	{/literal}
	<input type="submit" value="Submit" style="display:none" />
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
	<form action="{url controller=payment_information action=result pm=$paymentMethod}?recurring_id={$id}" class="paymentWidgets">{$brand}</form>
{/block}
