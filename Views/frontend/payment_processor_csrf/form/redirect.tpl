{extends file="parent:frontend/checkout/confirm.tpl"}
{block name='frontend_index_content_left'}{/block}
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
			function removeCSRFToken() {
				var csrfElement = jQuery('.wpwl-form input[name=__csrf_token]');
				if(csrfElement.length > 0) {
					csrfElement.remove();
				}
			}

			var wpwlOptions = {
				onReady: function(){
					document.getElementsByClassName("wpwl-form")[0].removeAttribute('target');
            		document.getElementsByClassName("wpwl-button")[0].click();
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
	<form action="{url controller=payment_processor_csrf action=result pm=$paymentMethod}" class="paymentWidgets">{$brand}</form>
	<input type="submit" value="Submit" style="display:none" />
{/block}