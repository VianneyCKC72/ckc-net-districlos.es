<div class="configurator-container configurator-third">
    <div class="d-flex align-items-center mb-4">
        <span class="mr-4 font-weight-bold subtitle">Définissez vos options</span>
        <span class="font-weight-semibold">Facultatif</span>
    </div>

    <div class="configurator-content">
        <form class="configurator-form options-form fadeIn" method="post">
            <div class="row row-items initial-m-p">
                {if isset($is_rigide) && $is_rigide}
                    <div class="col-sm-6">
                        <a href="#" class="soubassement form-title optionnal mt-0" id="checkbox_soubassement">
                            <div class="title-img flex-column">
                                <div class="top-content w-100 d-flex justify-content-between align-items-center mb-4">
                                    Soubassement
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                        <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                    </svg>
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                        <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                    </svg>
                                </div>
                                <div class="description font-weight-normal">
                                    Solidifiez votre clôture, ajoutez-lui une jolie finition ou créez une clôture en escalier grâce au soubassement béton
                                </div>
                            </div>
                        </a>
                        <div class="soubassement hide-row">
                            <div class="row row-items initial-m-p gap-1 options">
                                {foreach from=$sousbassement item=soubassement_product}
                                        <div class="col-md-12">
                                            <a href="#" data-id="{$soubassement_product.id}"
                                               data-name="soubassement_id" 
                                               class="configurator-action soubassement-action d-flex mb-4">
                                                <div class="soubassement-image">
                                                    <img style="width: 100px;" src="{$soubassement_product.image_url}" class="img-responsive"
                                                         alt="{$soubassement_product.name}"/>
                                                </div>
                                                <div class="soubassement-content">
                                                    <div class="soubassement-title">
                                                        <span class="name font-weight-bold">
                                                            {$soubassement_product.short_name}
                                                        </span>
                                                        {if $soubassement_product.product_details}
                                                        <span class="soubassement-info-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="10"></circle>
                                                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                            </svg>
                                                            <span class="soubassement-info-tooltip">
                                                                {if $soubassement_product.product_details.grouped_features}
                                                                    <section class="product-features">
                                                                        <dl class="data-sheet">
                                                                            {foreach from=$soubassement_product.product_details.grouped_features item=feature}
                                                                                <dt class="name">{$feature.name}</dt>
                                                                                <dd class="value">{$feature.value|escape:'htmlall'|nl2br nofilter}</dd>
                                                                            {/foreach}
                                                                        </dl>
                                                                    </section>
                                                                {/if}
                                                            </span>
                                                        </span>
                                                        {/if}
                                                    </div>
                                                    <select name="soubassement_variation" 
                                                            data-id="{$soubassement_product.id}"
                                                            data-name="soubassement_id" 
                                                            class="form-control soubassement-select">
                                                        {foreach from=$soubassement_product.variations item=variation name=variationLoop}
                                                            <option value="{$variation.id_product_attribute}" 
                                                                    data-id-product-attribute="{$variation.id_product_attribute}"
                                                                    data-price="{$variation.price}"
                                                                    data-price-formatted="{$variation.price_formatted}"
                                                                    {if isset($variation.image_url)}data-image-url="{$variation.image_url}"{/if}
                                                                    {if $smarty.foreach.variationLoop.first}selected{/if}>
                                                                {if isset($variation.name_without_title)}{$variation.name_without_title}{else}{$variation.name}{/if}
                                                            </option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                                <div class="soubassement-price-action" style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between;">
                                                    {if $soubassement_product.variations|@count > 0}
                                                        <div class="soubassement-price" style="font-size: 16px;">
                                                            <span class="price-value">{$soubassement_product.variations[0].price_formatted}</span>
                                                        </div>
                                                    {else}
                                                        <div class="soubassement-price" style="font-size: 16px; display: none;">
                                                            <span class="price-value"></span>
                                                        </div>
                                                    {/if}
                                                    <div>
                                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                                            <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                            <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                                        </svg>
                                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                                            <rect y="0.5" width="38" height="38" rx="10" fill="#79C046"/>
                                                            <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="white" stroke="white"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                {/if}
                {if (isset($is_rigide) && $is_rigide) || (isset($is_souple) && $is_souple)}
                    {if sizeof($portillons) > 0}
                        <div class="col-sm-6">
                            <a href="#" class="portillon form-title optionnal mt-0" id="checkbox_portillon">
                                    <div class="title-img flex-column">
                                        <div class="top-content w-100 d-flex justify-content-between align-items-center mb-4">
                                            Portillon
                                            <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                                <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                            </svg>
                                            <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                                <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                                <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                            </svg>
                                        </div>
                                        <div class="description font-weight-normal">
                                            Sécurisez davantage votre extérieur et facilitez vos déplacements grâce à un portillon de jardin discret et esthétique
                                        </div>
                                    </div>
                                </a>
                            
                                <div class="portillon hide-row">
                                <div class="row row-items initial-m-p gap-1 options">
                                    {foreach from=$portillons item=portillon}
                                        {if isset($portillon.has_select) && $portillon.has_select}
                                            <div class="col-md-12">
                                                <a href="#" data-id="{$portillon.id}"
                                                   data-name="portillon_id" 
                                                   class="configurator-action portillon-jardin-action d-flex mb-4">
                                                    <div class="portillon-jardin-image">
                                                        <img style="width: 100px;" src="{$portillon.image_url}" class="img-responsive"
                                                             alt="{$portillon.name}"/>
                                                    </div>
                                                    <div class="portillon-jardin-content">
                                                        <div class="portillon-jardin-title">
                                                            <span class="name font-weight-bold">
                                                                {$portillon.short_name}
                                                            </span>
                                                            {if $portillon.product_details}
                                                            <span class="portillon-jardin-info-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <circle cx="12" cy="12" r="10"></circle>
                                                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                                </svg>
                                                                <span class="portillon-jardin-info-tooltip">
                                                                    {if $portillon.product_details.grouped_features}
                                                                        <section class="product-features">
                                                                            <dl class="data-sheet">
                                                                                {foreach from=$portillon.product_details.grouped_features item=feature}
                                                                                    <dt class="name">{$feature.name}</dt>
                                                                                    <dd class="value">{$feature.value|escape:'htmlall'|nl2br nofilter}</dd>
                                                                                {/foreach}
                                                                            </dl>
                                                                        </section>
                                                                    {/if}
                                                                </span>
                                                            </span>
                                                            {/if}
                                                        </div>
                                                        <select name="portillon_jardin_variation" 
                                                                data-id="{$portillon.id}"
                                                                data-name="portillon_id" 
                                                                class="form-control portillon-jardin-select">
                                                            {foreach from=$portillon.variations item=variation name=variationLoop}
                                                                <option value="{$variation.id_product_attribute}" 
                                                                        data-id-product-attribute="{$variation.id_product_attribute}"
                                                                        data-price="{$variation.price}"
                                                                        data-price-formatted="{$variation.price_formatted}"
                                                                        {if isset($variation.image_url)}data-image-url="{$variation.image_url}"{/if}
                                                                        {if $smarty.foreach.variationLoop.first}selected{/if}>
                                                                    {if isset($variation.name_without_title)}{$variation.name_without_title}{else}{$variation.name}{/if}
                                                                </option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                    <div class="portillon-jardin-price-action" style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between;">
                                                        {if $portillon.variations|@count > 0}
                                                            <div class="portillon-jardin-price" style="font-size: 16px;">
                                                                <span class="price-value">{$portillon.variations[0].price_formatted}</span>
                                                            </div>
                                                        {else}
                                                            <div class="portillon-jardin-price" style="font-size: 16px; display: none;">
                                                                <span class="price-value"></span>
                                                            </div>
                                                        {/if}
                                                        <div>
                                                            <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                                                <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                                <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                                            </svg>
                                                            <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                                                <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                                <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="white" stroke="white"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        {else}
                                            <div class="col-md-12">
                                                <a href="#" data-id="{$portillon.id}"
                                                   data-id-product-attribute="{$portillon.id_product_attribute}"
                                                   data-name="portillon_id" class="configurator-action d-flex mb-4">
                                                    <img style="padding:unset;margin:0;" src="{$portillon.image_url}" class="img-responsive"
                                                         alt="{$portillon.name}"/>
                                                    <div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;">
                                                        <div class="infos-top">
                                                            <span class="name">
                                                                {$portillon.short_name}
                                                            </span>
                                                            <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                                                <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                                <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                                            </svg>
                                                            <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                                                <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                                                <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                                            </svg>
                                                        </div>
                                                        <span style="font-size: 13px;line-height: 16px;">
                                                            {$portillon.description}
                                                        </span>
                                                    </div>
                                                </a>
                                            </div>
                                        {/if}
                                    {/foreach}
                                </div>
                                <input type="hidden" value="" name="portillon_id"/>
                            </div>
                        </div>
                    {/if}
                    
                    <div class="col-sm-6">
                        <a href="#" class="occultation form-title optionnal mt-0" id="checkbox_occultation">
                            <div class="title-img flex-column">
                                <div class="top-content w-100 d-flex justify-content-between align-items-center mb-4">
                                    Occultation
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                        <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                    </svg>
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                        <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                    </svg>
                                </div>
                                <div class="description font-weight-normal">
                                    Préservez votre jardin du vis-à-vis à l'aide d'un brise-vue adapté à vos besoins
                                </div>
                            </div>
                        </a>
                        <div class="occultation hide-row">
                            <div class="row row-items initial-m-p gap-1 options">
                                {foreach from=$occultations item=occultation}
                                        <div class="col-md-12">
                                            <a href="#" data-id="{$occultation.id}"
                                               data-name="occultation_id" 
                                               class="configurator-action occultation-action d-flex mb-4">
                                                <div class="occultation-image">
                                                    <img style="width: 100px;" src="{$occultation.image_url}" class="img-responsive"
                                                         alt="{$occultation.name}"/>
                                                </div>
                                                <div class="occultation-content">
                                                    <div class="occultation-title">
                                                        <span class="name font-weight-bold">
                                                            {$occultation.short_name}
                                                        </span>
                                                        {if $occultation.product_details}
                                                        <span class="occultation-info-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="10"></circle>
                                                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                            </svg>
                                                            <span class="occultation-info-tooltip">
                                                                {if $occultation.product_details.grouped_features}
                                                                    <section class="product-features">
                                                                        <dl class="data-sheet">
                                                                            {foreach from=$occultation.product_details.grouped_features item=feature}
                                                                                <dt class="name">{$feature.name}</dt>
                                                                                <dd class="value">{$feature.value|escape:'htmlall'|nl2br nofilter}</dd>
                                                                            {/foreach}
                                                                        </dl>
                                                                    </section>
                                                                {/if}
                                                            </span>
                                                        </span>
                                                        {/if}
                                                    </div>
                                                    <select name="occultation_variation" 
                                                            data-id="{$occultation.id}"
                                                            data-name="occultation_id" 
                                                            class="form-control occultation-select">
                                                        {foreach from=$occultation.variations item=variation name=variationLoop}
                                                            <option value="{$variation.id_product_attribute}" 
                                                                    data-id-product-attribute="{$variation.id_product_attribute}"
                                                                    data-price="{$variation.price}"
                                                                    data-price-formatted="{$variation.price_formatted}"
                                                                    {if isset($variation.image_url)}data-image-url="{$variation.image_url}"{/if}
                                                                    {if $smarty.foreach.variationLoop.first}selected{/if}>
                                                                {if isset($variation.name_without_title)}{$variation.name_without_title}{else}{$variation.name}{/if}
                                                            </option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                                <div class="occultation-price-action" style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between;">
                                                    {if $occultation.variations|@count > 0}
                                                        <div class="occultation-price" style="font-size: 16px;">
                                                            <span class="price-value">{$occultation.variations[0].price_formatted}</span>
                                                        </div>
                                                    {else}
                                                        <div class="occultation-price" style="font-size: 16px; display: none;">
                                                            <span class="price-value"></span>
                                                        </div>
                                                    {/if}
                                                    <div>
                                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                                            <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                            <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                                        </svg>
                                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                                            <rect y="0.5" width="38" height="38" rx="10" fill="#79C046"/>
                                                            <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="white" stroke="white"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                {/foreach}
                            </div>
                            <input type="hidden" value="" name="occultation_id"/>
                        </div>
                    </div>
                {/if}
                {if isset($isAluminium) && $isAluminium}
                    <div class="col-sm-6">
                        <a href="#" class="configurator-action mt-0" id="checkbox_panneau_deco">
                            <div class="title-img flex-column">
                                <div class="top-content w-100 d-flex justify-content-between align-items-center mb-4">
                                    Panneau
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                        <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                    </svg>
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                        <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                    </svg>
                                </div>
                                <div class="description font-weight-normal">
                                    Préservez votre jardin du vis-à-vis à l'aide d'un brise-vue adapté à vos besoins
                                </div>
                            </div>
                        </a>
                        <div class="panneau_deco hide-row">
                            <div class="row row-items initial-m-p collumgap">
                                {foreach from=$panneaux_deco item=panneau_deco}
                                    <div class="col-xs-6 pl-0">
                                        <a href="#" data-id="{$panneau_deco.id}"
                                           data-id-product-attribute="{$panneau_deco.id_product_attribute}"
                                           data-name="panneau_deco_id" class="panneau_deco form-title optionnal configurator-action">
                                            <img src="{$panneau_deco.image_url}" class="img-responsive"
                                                 alt="{$panneau_deco.name}"/>
                                            <div class="title-img">
                                                {$panneau_deco.name}
                                                <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                                    <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                                    <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                                </svg>
                                                <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                                    <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                                    <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                                </svg>
                                            </div>
                                        </a>
                                    </div>
                                {/foreach}
                            </div>
                            <input type="hidden" value="" name="occultation_id"/>
                        </div>
                    </div>
                {/if}
            </div>

            <div class="text-center" style="display: none">
                <button name="addToCart" class="btn configurator-submit btn-primary">Ajouter au panier</button>
            </div>
        </form>
    </div>

</div>
