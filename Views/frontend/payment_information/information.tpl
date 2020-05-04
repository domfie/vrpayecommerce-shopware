{extends file='frontend/account/index.tpl'}
{* Breadcrumb *}
{block name='frontend_index_start' append}
	{$sBreadcrumb[0] = ['name'=>"{s name='FRONTEND_MC_INFO'}My Payment Information{/s}", 'link'=>{url action='information'}]}
{/block}

{block name="frontend_index_left_categories_headline"}
{/block}

{block name='frontend_index_left_categories_inner'}
{/block}

{* Main content *}
{block name="frontend_index_content"}
{if $isRecurringActive}
	<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/styles/paymentform_account.css'}" media="all" />
	<div class="account--change-payment account--content register--content" data-register="true">
	    {if !empty($sSuccess)}
			<div class="alert is--success is--rounded">
				<div class="alert--icon">
					<i class="icon--element icon--check"></i>
				</div>
				<div class="alert--content is--strong">
					{if $sSuccess == 'register'}
						{s name="SUCCESS_MC_ADD"}Congratulations, your payment information were successfully saved.{/s}
					{/if}
					{if $sSuccess == 'change' }
						{s name="SUCCESS_MC_UPDATE"}Congratulations, your payment information were successfully updated.{/s}
					{/if}
					{if $sSuccess == 'delete' }
						{s name="SUCCESS_MC_DELETE"}Congratulations, your payment information were successfully deleted.{/s}
					{/if}
				</div>
			</div>
		{/if}
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
				{s name="ERROR_MC_UPDATE"}We are sorry. Your attempt to update your payment information was not successful, please try again.{/s}
			</div>
		</div>
		<div class="account--welcome">
			<h1 class="panel--title">{s name="FRONTEND_MC_INFO"}My Payment Information{/s}</h1>
	    </div>
        <div class="panel">
			{if $isCardsSavedActive == 'Yes'}
				<div class="group group-top"><h3>{s name="FRONTEND_MC_CC"}Credit Cards{/s}</h3></div>
				{foreach from=$customerDataCC item=list}
					<div class="group-list">
						<div class="group-img">
							<img src="{link file='frontend/_resources/images/'}{$list.brand|lower}.png" class="card_logo" alt="{$list.brand}">
						</div>
						<span class="card_info">{s name="FRONTEND_MC_ENDING"}ending in:{/s} {$list.last4digits}; {s name="FRONTEND_MC_VALIDITY"}expires on:{/s} {$list.expiry_month}/{$list.expiry_year|substr:2}</span>
						<div class="group-button">
						{if $list.payment_default}
							<button class="btn btnDefault">{s name="FRONTEND_MC_BT_DEFAULT"}Default{/s}</button>
						{else}
							<form action="{url}" method="post">
								<input type="hidden" name="id" value="{$list.id}"/>
								<input type="hidden" name="selected_payment" value="vrpay_ccsaved"/>
								<input type="hidden" name="set_default" value="1"/>
								<button class="btn btnDefault" type="submit" value="submit">{s name="FRONTEND_MC_BT_SETDEFAULT"}Set as Default{/s}</button>
							</form>
						{/if}
						<form class = "btnChange" action="{url action='change'}" method="post">
							<input type="hidden" name="id" value="{$list.id}"/>
							<input type="hidden" name="selected_payment" value="vrpay_ccsaved"/>
							<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_CHANGE"}Change{/s}</button>
						</form>
						<form class ="btnDelete" action="{url action='delete'}" method="post">
							<input type="hidden" name="id" value="{$list.id}"/>
							<input type="hidden" name="selected_payment" value="vrpay_ccsaved"/>
							<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_DELETE"}Delete{/s}</button>
						</form>
						</div>
						<div style="clear:both"></div>
					</div>
				{/foreach}
				<div class="group-add">
					<form class = "btnAdd" action="{url action='register'}" method="post">
						<input type="hidden" name="selected_payment" value="vrpay_ccsaved"/>
						<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_ADD"}Add{/s}</button>
					</form>
				</div>
				<div class="group-separator"></div>
			{/if}

			{if $isDDSavedActive == 'Yes'}
				<div class="group"><h3>{s name="FRONTEND_MC_DD"}Direct Debit{/s}</h3></div>
				{foreach from=$customerDataDD item=list}
					<div class="group-list">
						<div class="group-img">
							<img src="{link file='frontend/_resources/images/'}sepa.png" class="card_logo" alt="sepa">
						</div>
						<span class="dd_info">{s name="FRONTEND_MC_ACCOUNT"}Account: ****{/s} {$list.last4digits}</span>
						<div class="group-button">
						{if $list.payment_default}
							<button class="btn btnDefault">{s name="FRONTEND_MC_BT_DEFAULT"}Default{/s}</button>
						{else}
							<form action="{url}" method="post">
								<input type="hidden" name="id" value="{$list.id}"/>
								<input type="hidden" name="selected_payment" value="vrpay_ddsaved"/>
								<input type="hidden" name="set_default" value="1"/>
								<button class="btn btnDefault" type="submit" value="submit">{s name="FRONTEND_MC_BT_SETDEFAULT"}Set as Default{/s}</button>
							</form>
						{/if}
						<form class = "btnChange" action="{url action='change'}" method="post">
							<input type="hidden" name="id" value="{$list.id}"/>
							<input type="hidden" name="selected_payment" value="vrpay_ddsaved"/>
							<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_CHANGE"}Change{/s}</button>
						</form>
						<form class ="btnDelete" action="{url action='delete'}" method="post">
							<input type="hidden" name="id" value="{$list.id}"/>
							<input type="hidden" name="selected_payment" value="vrpay_ddsaved"/>
							<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_DELETE"}Delete{/s}</button>
						</form>
						</div>
						<div style="clear:both"></div>
					</div>
				{/foreach}
				<div class="group-add">
					<form class = "btnAdd" action="{url action='register'}" method="post">
						<input type="hidden" name="selected_payment" value="vrpay_ddsaved"/>
						<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_ADD"}Add{/s}</button>
					</form>
				</div>
				<div class="group-separator"></div>
			{/if}

			{if $isPayPalSavedActive == 'Yes'}
				<div class="group"><h3>{s name="FRONTEND_MC_PAYPAL"}PayPal{/s}</h3></div>
				{foreach from=$customerDataPAYPAL item=list}
					<div class="group-list">
						<div class="group-img">
							<img src="{link file='frontend/_resources/images/'}paypal.png" class="card_logo" alt="paypal">
						</div>
						<span class="paypal_info">{s name="FRONTEND_MC_EMAIL"}Email:{/s} {$list.email}</span>
						<div class="group-button">
						{if $list.payment_default}
							<button class="btn btnDefault">{s name="FRONTEND_MC_BT_DEFAULT"}Default{/s}</button>
						{else}
							<form action="{url}" method="post">
								<input type="hidden" name="id" value="{$list.id}"/>
								<input type="hidden" name="selected_payment" value="vrpay_paypalsaved"/>
								<input type="hidden" name="set_default" value="1"/>
								<button class="btn btnDefault" type="submit" value="submit">{s name="FRONTEND_MC_BT_SETDEFAULT"}Set as Default{/s}</button>
							</form>
						{/if}
						<form class = "btnChange" action="{url action='change'}" method="post">
							<input type="hidden" name="id" value="{$list.id}"/>
							<input type="hidden" name="selected_payment" value="vrpay_paypalsaved"/>
							<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_CHANGE"}Change{/s}</button>
						</form>
						<form class ="btnDelete" action="{url action='delete'}" method="post">
							<input type="hidden" name="id" value="{$list.id}"/>
							<input type="hidden" name="selected_payment" value="vrpay_paypalsaved"/>
							<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_DELETE"}Delete{/s}</button>
						</form>
						</div>
						<div style="clear:both"></div>
					</div>
				{/foreach}
				<div class="group-add">
					<form class = "btnAdd" action="{url action='register'}" method="post">
						<input type="hidden" name="selected_payment" value="vrpay_paypalsaved"/>
						<button class="btn" type="submit" value="submit">{s name="FRONTEND_MC_BT_ADD"}Add{/s}</button>
					</form>
				</div>
				<div class="group-separator"></div>
			{/if}
			<div style="clear:both"></div>
        </div>
    </div>
{/if}
{/block}
