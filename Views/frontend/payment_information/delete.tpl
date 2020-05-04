{extends file='frontend/payment_information/information.tpl'}

{* Main content *}
{block name="frontend_index_content"}
	<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/styles/paymentform_account.css'}" media="all" />
	<div class="account--change-payment account--content register--content" data-register="true">
	    {if !empty($sError)}
			<div class="alert is--error is--rounded">
				<div class="alert--icon">
					<i class="icon--element icon--cross"></i>
				</div>
				<div class="alert--content">
					{s name="ERROR_MC_DELETE"}We are sorry. Your attempt to delete your payment information was not successful, please try again.{/s}
				</div>
			</div>
		{/if}
		<div class="account--welcome">
			<h1 class="panel--title">{s name="FRONTEND_MC_DELETE"}Delete Payment Information{/s}</h1>
	    </div>
        <div class="panel">
			<p class="text-unreg">{s name="FRONTEND_MC_DELETESURE"}Are you sure to delete this payment information?{/s}</p>
			<form class="cancel_form" action="{url controller='payment_information' action='information'}" method="post">
				<input type="submit" value="{s name="FRONTEND_BT_CANCEL"}Cancel{/s}" class="btn btnCustomSubmit">
			</form>
			<form class="submit_form" action="" method="post">
				<input type="hidden" name="id" value="{$id}"/>
				<input type="hidden" name="selected_payment" value="{$selected_payment}"/>		
				<input type="submit" name="isDelete" value="{s name="FRONTEND_BT_CONFIRM"}Confirm{/s}" class="btn btnCustomSubmit">
			</form>
        </div>
{/block}
