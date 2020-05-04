{extends file="parent:frontend/checkout/confirm.tpl"}
{block name='frontend_index_content_left'}{/block}
{* Main content *}
{block name='frontend_index_content'}
	{literal}
	<style>
	    body {
	        display:none;
	        background: white !important;
	    }
	</style>
	{/literal}

	<form action="{$redirectUrl}" id="paymentWidgets" method="post">
		{foreach from=$redirectParameters key=k item=v}
			<input type="hidden" name="{$v.name}" value="{$v.value}">
		{/foreach}
	</form>

	{literal}
	<script type="text/javascript">
		var redirectUrl = "{/literal}{$redirectUrl}{literal}";
		if(redirectUrl != "") {
			document.getElementById('paymentWidgets').submit();
		}
		else {
			var errorMessageEasycreditBeforePayment = "{/literal}{s name='ERROR_MESSAGE_EASYCREDIT_BEFORE_PAYMENT'}Please make sure your payment method is correct!{/s}{literal}";
			alert(errorMessageEasycreditBeforePayment);
			window.location.href = "{/literal}{$failedUrl}{literal}";
		}
	</script>
	{/literal}
{/block}