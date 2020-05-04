{extends file="frontend/index/index.tpl"}

{* Include the necessary stylesheets. We need inline styles here due to the fact that the colors are configuratable. *}
{block name="frontend_index_header_css_screen" append}
	<style type="text/css">
		#confirm .table, #confirm .country-notice {
			background: {config name=baskettablecolor};
		}
		#confirm .table .table_head {
			color: {config name=basketheaderfontcolor};
			background: {config name=basketheadercolor};
		}
	</style>
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}{/block}

{* Hide sidebar left *}
{block name='frontend_index_content_left'}{/block}

{block name='frontend_index_content'}
	<div style="margin-top:45px; width: 100%">
        <div style="width: 100%; margin: 0 auto; padding-bottom:10px; border: 1px solid #dadae5; border-radius:3px ">
            <div style="text-align: center;">
                <p><h2 style="color:#d9400b !important">{s name="SHOPWARE_FAILPAYMENTTITLE"}Payment cannot be completed{/s}</h2></p>
				<p>{$errorMessage}</p>
                <p><a href="{url controller=checkout action=confirm}" class="btn is--primary"><strong>{s name="SHOPWARE_TOCONTINUETITLE"}Continue{/s}</strong></a></p>
            </div>
        </div>
	</div>
{/block}
