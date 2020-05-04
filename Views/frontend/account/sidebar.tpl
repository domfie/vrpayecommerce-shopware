{extends file="parent:frontend/account/sidebar.tpl"}
{block name="frontend_account_menu_link_payment" append}
	<li class="navigation--entry">
		<a href="{url controller='payment_information' action='information'}" title="{s name='FRONTEND_MC_INFO'}My Payment Information{/s}" class="navigation--link{if $sAction == 'information'} is--active{/if}">
			{s name='FRONTEND_MC_INFO'}My Payment Information{/s}
		</a>
	</li>
{/block}
