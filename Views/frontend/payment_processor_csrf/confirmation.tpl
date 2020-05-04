{extends file="frontend/index/index.tpl"}

{* Hide sidebar left *}
{block name='frontend_index_content_left'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}{/block}

{* Hide shop navigation *}
{block name='frontend_index_shop_navigation'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Step box *}
{block name='frontend_index_navigation_categories_top'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Hide top bar *}
{block name='frontend_index_top_bar_container'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Footer *}
{block name='frontend_index_footer'}
    {if !$theme.checkoutFooter}
        {$smarty.block.parent}
    {else}
        {block name='frontend_index_checkout_finish_footer'}
            {include file="frontend/index/footer_minimal.tpl"}
        {/block}
    {/if}
{/block}

{* Back to the shop button *}
{block name='frontend_index_logo_trusted_shops' append}
    {if $theme.checkoutHeader}
        <a href="{url controller='index'}"
           class="btn is--small btn--back-top-shop is--icon-left"
           title="{"{s name='FinishButtonBackToShop' namespace='frontend/checkout/finish'}{/s}"|escape}"
           xmlns="http://www.w3.org/1999/html">
            <i class="icon--arrow-left"></i>
            {s name="FinishButtonBackToShop" namespace="frontend/checkout/finish"}{/s}
        </a>
    {/if}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <form id="confirm--form" method="post" action="{url controller=payment_processor_csrf action='capture' pm=$paymentShortName}">
        <link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/styles/payment_confirmation.css'}" media="all" />
        <div class="content checkout--content finish--content">
            {block name='frontend_checkout_finish_information_wrapper'}
                <div class="panel--group block-group information--panel-wrapper finish--info" data-panel-auto-resizer="true">
                    {* Separate Billing & Shipping *}
                    {block name='frontend_checkout_finish_information_addresses_billing'}
                        <div class="information--panel-item">
                            {block name='frontend_checkout_finish_information_addresses_billing_panel'}
                                <div class="panel has--border block information--panel billing--panel finish--billing easycredit--billing">
                                     {* Headline *}
                                    {block name='frontend_checkout_confirm_information_addresses_billing_panel_title'}
                                        <div class="panel--title is--underline">
                                            {s name="SHOPWARE_BILLING_ADDRESS"}Billing address{/s}
                                        </div>
                                    {/block}
                                    {block name='frontend_checkout_finish_information_addresses_billing_panel_body'}
                                        <div class="panel--body is--wide">
                                            {if $billingAddress.company}
                                                <span class="address--company is--bold">{$billingAddress.company}</span>{if $billingAddress.department}<br /><span class="address--department is--bold">{$billingAddress.department}</span>{/if}
                                                <br />
                                            {/if}

                                            <span class="address--salutation">{$billingAddress.salutation}</span>
                                            <span class="address--firstname">{$billingAddress.firstname}</span> <span class="address--lastname">{$billingAddress.lastname}</span><br />
                                            <span class="address--street">{$billingAddress.street}</span><br />
                                            <span class="address--zipcode">{$billingAddress.zipcode}</span> <span class="address--city">{$billingAddress.city}</span><br />
                                            <span class="address--countryname">{$countryBillingAddress}</span>
                                        </div>
                                    {/block}
                                </div>
                            {/block}
                        </div>
                    {/block}
                    {block name='frontend_checkout_finish_information_addresses_shipping'}
                        <div class="information--panel-item">
                            {block name='frontend_checkout_finish_information_addresses_shipping_panel'}
                                <div class="panel has--border block information--panel shipping--panel finish--shipping easycredit--shipping">

                                    {* Headline *}
                                    {block name='frontend_checkout_finish_information_addresses_shipping_panel_title'}
                                        <div class="panel--title is--underline">
                                            {s name="SHOPWARE_SHIPPING_ADDRESS"}Shipping address{/s}
                                        </div>
                                    {/block}

                                    {block name='frontend_checkout_finish_information_addresses_shipping_panel_body'}
                                        <div class="panel--body is--wide">
                                            {if $shippingAddress.company}
                                                <span class="address--company is--bold">{$shippingAddress.company}</span>{if $shippingAddress.department}<br /><span class="address--department is--bold">{$shippingAddress.department}</span>{/if}
                                                <br />
                                            {/if}

                                            <span class="address--salutation">{$shippingAddress.salutation}</span>
                                            <span class="address--firstname">{$shippingAddress.firstname}</span> <span class="address--lastname">{$shippingAddress.lastname}</span><br />
                                            <span class="address--street">{$shippingAddress.street}</span><br />
                                            <span class="address--zipcode">{$shippingAddress.zipcode}</span> <span class="address--city">{$shippingAddress.city}</span><br />
                                            <span class="address--countryname">{$countryShippingAddress}</span>
                                        </div>
                                    {/block}
                                </div>
                            {/block}
                        </div>
                    {/block}
                    {* Payment method *}
                    {block name='frontend_checkout_finish_information_payment'}
                        <div class="information--panel-item">
                            {block name='frontend_checkout_finish_payment_method_panel'}
                                <div class="panel has--border block information--panel payment--panel finish--details easycredit--payment">

                                    {block name='frontend_checkout_finish_left_payment_method_headline'}
                                        <div class="panel--title is--underline payment--title">
                                            {s name="SHOPWARE_PAYMENT_DISPATCH"}Payment and dispatch{/s}
                                        </div>
                                    {/block}

                                    {block name='frontend_checkout_finish_left_payment_content'}
                                        <div class="panel--body is--wide payment--content">

                                            {* Payment method *}
                                            {block name='frontend_checkout_finish_payment_method'}
                                                <strong>{$paymentMethod}</strong><br />
                                            {/block}

                                            {block name='frontend_checkout_finish_dispatch_method'}
                                                {if isset($paymentResponse.resultDetails.tilgungsplanText)}
                                                    {$paymentResponse.resultDetails.tilgungsplanText}<br />
                                                {/if}
                                            {/block}

                                            {block name='frontend_checkout_confirmation_vorvertraglicheInformationen'}
                                                {if isset($paymentResponse.resultDetails.vorvertraglicheInformationen)}
                                                    <strong><a href="{$paymentResponse.resultDetails.vorvertraglicheInformationen}">{s name="FRONTEND_EASYCREDIT_LINK"}Vorvertragliche Informationen zum Ratenkauf hier abrufen{/s}</a></strong>
                                                {/if}
                                            {/block}

                                        </div>
                                    {/block}
                                </div>
                            {/block}
                        </div>
                    {/block}
                </div>
            {/block}
            {block name='frontend_checkout_finish_items'}
                <div class="finish--table product--table easycredit--product">
                    <div class="panel has--border">
                        <div class="panel--body is--rounded">

                            {* Table header *}
                            {block name='frontend_checkout_finish_table_header'}
                                {include file="frontend/checkout/finish_header.tpl"}
                            {/block}

                            {* Article items *}
                            {foreach $sBasket.content as $key => $sBasketItem}
                                {block name='frontend_checkout_finish_item'}
                                    {include file='frontend/checkout/finish_item.tpl' isLast=$sBasketItem@last}
                                {/block}
                            {/foreach}

                            {* Table footer *}
                            {block name='frontend_checkout_finish_table_footer'}
                                {include file="frontend/checkout/finish_footer.tpl"}
                                <div class="basket--footer">
                                    <div class="table--aggregation">
                                    </div>
                                    <ul class="aggregation--list">
                                        {block name='frontend_checkout_cart_footer_field_labels_sum_of_interest_entry'}
                                            <li class="list--entry block-group entry--sum-of-interest">

                                                {block name='frontend_checkout_cart_footer_field_labels_sum_of_interest_label'}
                                                    <div class="entry--label block">
                                                        {s name="FRONTEND_EASYCREDIT_INTEREST"}Sum of Interest{/s}
                                                    </div>
                                                {/block}

                                                {block name='frontend_checkout_cart_footer_field_labels_sum_of_interest_value'}
                                                    <div class="entry--value block is--no-star">
                                                        {if isset($sumOfInterest)}
                                                            {$sumOfInterest|currency}
                                                        {/if}
                                                    </div>
                                                {/block}

                                            </li>
                                        {/block}

                                        {block name='frontend_checkout_cart_footer_field_labels_order_total_entry'}
                                            <li class="list--entry block-group entry--order-total">
                                                {block name='frontend_checkout_cart_footer_field_labels_order_total_label'}
                                                    <div class="entry--label block">
                                                        <strong>
                                                            {s name="FRONTEND_EASYCREDIT_TOTAL"}Order Total{/s}
                                                        </strong>
                                                    </div>
                                                {/block}

                                                {block name='frontend_checkout_cart_footer_field_labels_order_total_value'}
                                                    <div class="entry--value block is--no-star">
                                                        <strong>
                                                            {if isset($orderTotal)}
                                                                {$orderTotal|currency}
                                                            {/if}
                                                        </strong>
                                                    </div>
                                                {/block}
                                            </li>
                                        {/block}
                                    </ul>
                                </div>
                            {/block}
                        </div>
                    </div>
                </div>
            {/block}
            {* Table actions *}
            {block name='frontend_checkout_confirm_confirm_table_actions'}
                <div class="table--actions actions--bottom easycredit--button">
                    <div class="main--actions">
                        {block name='frontend_checkout_confirm_submit'}
                            <button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="true">
                                {s name="SHOPWARE_PAYMENT_BUTTON"}Complete payment{/s}<i class="icon--arrow-right"></i>
                            </button>
                        {/block}
                    </div>
                </div>
            {/block}
        </div>
    </form>
{/block}