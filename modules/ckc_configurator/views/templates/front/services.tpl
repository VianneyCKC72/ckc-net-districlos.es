<div class="configurator-container configurator-fourth">
    <div class="d-flex align-items-center mb-4">
        <span class="mr-4 font-weight-bold subtitle">Définissez vos services</span>
        <span class="font-weight-semibold">Facultatif</span>
    </div>

    <div class="configurator-content">
        <form class="configurator-form options-form-service fadeIn" method="post">
            <div class="row row-items initial-m-p">
                {if isset($services[0]) && $services}
                    <div class="col-sm-6">
                        {* Occultation *}
                        <a href="#" class="form-title optionnal mt-0" id="checkbox_service">
                            <div class="title-img flex-column">
                                <div class="top-content w-100 d-flex justify-content-between align-items-center mb-4">
                                    Services
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
                                    Vous souhaitez une pose ? Vérifiez l'éligibilité
                                </div>
                            </div>
                        </a>
                        {foreach from=$services key=$key item=$service}
                        <div class="hide-row">
                            <div class="row row-items initial-m-p collumgap gap-1 options">
                                    <div class="col-md-12">
                                        <a href="#" data-id="{$service.dealt_service_product_id}"
                                           data-name="service_id" class="configurator-action d-flex mb-4">
                                            <div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;width: 100%;">
                                                <div class="infos-top">
                                                    <span class="name">
                                                        {$serviceTitles[$key]}
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
                                                <span style="font-size: 13px;line-height: 16px;width: 100%;">
                                                    {$service.dealt_customer_commercial_description nofilter}
                                                </span>
                                            </div>
                                        </a>
                                    </div>
                            </div>
                        </div>
                        {/foreach}
                    </div>
                {else}
                    <div class="col-sm-6">
                        {* Occultation *}
                        <a href="#" class="form-title optionnal mt-0" id="checkbox_service">
                            <div class="title-img flex-column">
                                <div class="top-content w-100 d-flex justify-content-between align-items-center mb-4">
                                    Services
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
                                    Vous souhaitez une pose ? Vérifiez l'éligibilité
                                </div>
                            </div>
                        </a>
                        <div class="hide-row">
                            <div class="row row-items initial-m-p collumgap gap-1 options">
                                <div class="col-md-12">
                                        <div class="title-img" style="display: flex;justify-content: space-between;flex-direction: column;width: 100%;">
                                            <div class="infos-top">
                                                    <span class="name">
                                                        Pas de services pour ce produit.
                                                    </span>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
            <div id="servicesPrev" style="display: none">{json_encode($serviceTitles)}</div>
            <div class="text-center" style="display: none">
                <button name="addToCart" class="btn configurator-submit btn-primary">Ajouter au panier</button>
            </div>
        </form>
    </div>

</div>
