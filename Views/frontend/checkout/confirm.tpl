{extends file='parent:frontend/checkout/confirm.tpl'}
{block name='frontend_checkout_confirm_submit'}
    {* Submit order button *}
    {if $sPayment.embediframe || $sPayment.action}
    	<button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="true">
        	{s name='FRONTEND_EASYCREDIT_CONFIRM_BUTTON'}Weiter zu Ratenkauf by easyCredit{/s}<i class="icon--arrow-right"></i>
    	</button>
    {else}
        <button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="true">
            {s name='ConfirmActionSubmit'}{/s}<i class="icon--arrow-right"></i>
        </button>
    {/if}
{/block}
