{extends file='page.tpl'}
{block name='head_seo_title'}
    {$meta_title}
{/block}
{block name='page_content'}
    <div id="configurator-loader">
        <div>Chargement</div>
    </div>

    <div class="add_to_cart_configurator_bottom" style="display: none;">
        <div class="container">
            <div class="product-add-to-cart clearfix js-product-add-to-cart dis_flex row">
                <div class="product-quantity justify-content-between w-100" style="">
                    <div class="price product_header_title js-product-prices">
                        <div class="product-prices">
                            <div class="h1">
                                <div class="current-price">
                                    <div>
                                        <div class="price preview-distri-prices" id="second_price" content="">102.78 €</div>
                                        <div class="taxe">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="position-relative">
                            <button class="btn btn-primary" id="voir_config" type="button">
                                Voir ma configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="error-bg">
        <div class="error-modal">
            <div class="error-title">Attention !</div>
            <div class="error-text"></div>
            <div class="error-buttons">
                <button name="changeType" class="btn configurator-change-type configurator-submit revert btn-primary">Continuer et changer de gamme</button>
                <button name="keepType" class="btn configurator-change-type configurator-submit revert btn-primary">Conserver ces attributs / gammes</button>
            </div>
        </div>
    </div>

    <div class="row mobile_row_configurator" style="display: flex">
        <div class="col-xl-8 mobile_configurator">
            <nav data-depth="5" class="breadcrumb">
                <ol>
                    <li>
                        <a href="/">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                        </a>
                    </li>

                    <li>
                        <span>Configurez votre clôture</span>
                    </li>
                </ol>
            </nav>
            <h1 class="h2">Configurez votre clôture</h1>
            <p class="font-weight-semibold mb-0">Bienvenue sur notre outil de création de kit de clôture.</p>
            <p>Suivez les étapes en cliquant ci-dessous pour configurer votre clôture idéale et personnalisée !</p>
            <div class="step first" data-step="first">
                <div class="configurator-container configurator-first">
                    <div class="d-flex align-items-center mb-4">
                        <span class="mr-4 font-weight-bold subtitle">Type de clôture</span>
                        <span class="font-weight-semibold">Obligatoire</span>
                    </div>
                    <div class="configurator-content">
                        <div class="row row-items initial-m-p">
                            {foreach from=$categories key=i item=category}
                                <div class="mt-1 col-xs-12 col-sm-4" data-wow-duration="1s" {if $i == 1}data-wow-delay="0.5s"{elseif $i == 2}data-wow-delay="1s"{/if}>
                                    <a href="#" data-id="{$category.id_category}" class="configurator-action">
                                        <img src="{$link->getCatImageLink($category.link_rewrite, $category.id_image)|escape:'html':'UTF-8'}" class="img-responsive" alt="{$category.name}" />
                                        <div class="title-img">
                                            {$category.name}
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
                    </div>
                </div>
            </div>

            <div class="step second disabled" data-step="second" style="margin: 2rem auto;">
            </div>

            <div class="step third disabled" data-step="third" style="margin-bottom: 2rem;display:none;">
            </div>

            <div class="step fourth disabled" data-step="fourth" style="margin-bottom: 2rem;display:none;">
            </div>
        </div>

        <div class="col-xl-4 mobil_session">
            <div class="configurator-session sticky-column" style="display: none">
                <div class="font-weight-semibold subtitle mb-2">Ma clôture</div>

                <div class="preview" style="margin-bottom: 1rem;">
                    <div class="configurator-container configurator-preview">

                        <div class="configurator-content">
                            <div class="configurator-preview-content">
                                <div class="configurator-preview-base">
                                </div>
                                <div class="configurator-preview-panneau">
                                </div>
                                <div class="configurator-preview-poteau">
                                </div>
                                <div class="configurator-preview-jambe">
                                </div>
                                <div class="configurator-preview-occultation">
                                </div>
                                <div class="configurator-preview-portillon">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="font-weight-semibold">Récapitulatif</div>
                <span id="deconseil">(Nous déconseillons la pose sur platine et/ou avec brise-vue pour cette hauteur)</span>
                <div class="configurator-session-list">
                    <div class="justify-content-between"><span>Type de grillage</span><strong><span id="grillage_type"></span></strong></div>
                    <div class="onlyAlu displayEspacement justify-content-between"><span>Espacement des lames</span><strong><span id="grillage_espacement"></span>cm</strong></div>
                    <div class="justify-content-between"><span>Longueur</span><strong><span id="grillage_length"></span>m</strong></div>
                    <div class="justify-content-between"><span>Type de pose</span><strong><span id="grillage_installation"></span></strong></div>
                    <div class="justify-content-between"><span>Hauteur</span><strong><span id="grillage_height"></span></strong></div>
                    <div class="justify-content-between"><span>Couleur</span><strong><span id="grillage_color"></span></strong></div>
                    <div class="onlyAlu displayDepartMur justify-content-between"><span>Départ du mur</span><strong><span id="grillage_depart_mur"></span></strong></div>
                    <div class="onlyAlu displayDepartMur justify-content-between"><span>Nombre d'angle</span><strong><span id="grillage_nombre_angle"></span></strong></div>
                </div>

                <div class="configurator-products-list">
                    <div class="font-weight-semibold">Produits</div>
                    <div class="list" style="gap: 10px;display: flex;flex-direction: column;">

                    </div>
                </div>
                <div class="configurator-price d-flex justify-content-between">
                    <div class="font-weight-semibold subtitle">Total TTC</div>
                    <strong id="price"><span id="price_value"></span> &euro;</strong>
                </div>

                <button id="addToCartAlias" name="addToCartAlias" class="btn configurator-submit revert btn-primary w-100">
                    <svg width="27" height="20" viewBox="0 0 27 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.89581 7.50056e-05C0.800988 -0.00133052 0.706845 0.0170303 0.618852 0.0540904C0.530859 0.0911506 0.450771 0.146171 0.383242 0.215954C0.315714 0.285737 0.262092 0.368891 0.225492 0.460584C0.188892 0.552276 0.170044 0.650678 0.170044 0.750072C0.170044 0.849465 0.188892 0.947867 0.225492 1.03956C0.262092 1.13125 0.315714 1.21441 0.383242 1.28419C0.450771 1.35397 0.530859 1.40899 0.618852 1.44605C0.706845 1.48311 0.800988 1.50147 0.89581 1.50007H2.21049C2.55775 1.50007 2.84722 1.75153 2.91302 2.10944L3.05558 2.89069L4.59387 11.3584C4.87205 12.8862 6.1563 14 7.64066 14H16.5723C18.0566 14 19.3414 12.8866 19.6191 11.3584L21.1574 2.89069C21.1771 2.78227 21.1738 2.67062 21.1476 2.56372C21.1214 2.45681 21.0731 2.35727 21.006 2.2722C20.9389 2.18714 20.8547 2.11864 20.7594 2.0716C20.6641 2.02456 20.5601 2.00013 20.4548 2.00007H4.34976L4.31901 1.82917C4.1267 0.772838 3.23668 7.50056e-05 2.21049 7.50056e-05H0.89581ZM4.62276 3.50006H19.5902L18.2131 11.0782C18.0623 11.908 17.3787 12.5 16.5723 12.5H7.64066C6.83451 12.5 6.15136 11.9079 5.99987 11.0782V11.0772L4.62276 3.50006ZM8.76713 15C8.38756 15 8.02354 15.158 7.75515 15.4394C7.48676 15.7207 7.33598 16.1022 7.33598 16.5C7.33598 16.8978 7.48676 17.2794 7.75515 17.5607C8.02354 17.842 8.38756 18 8.76713 18C9.14669 18 9.51071 17.842 9.7791 17.5607C10.0475 17.2794 10.1983 16.8978 10.1983 16.5C10.1983 16.1022 10.0475 15.7207 9.7791 15.4394C9.51071 15.158 9.14669 15 8.76713 15ZM15.4458 15C15.0663 15 14.7022 15.158 14.4338 15.4394C14.1655 15.7207 14.0147 16.1022 14.0147 16.5C14.0147 16.8978 14.1655 17.2794 14.4338 17.5607C14.7022 17.842 15.0663 18 15.4458 18C15.8254 18 16.1894 17.842 16.4578 17.5607C16.7262 17.2794 16.877 16.8978 16.877 16.5C16.877 16.1022 16.7262 15.7207 16.4578 15.4394C16.1894 15.158 15.8254 15 15.4458 15Z" fill="white"/>
                        <rect x="12.83" y="5.66211" width="13" height="13" rx="6.5" fill="white"/>
                        <rect x="12.83" y="5.66211" width="13" height="13" rx="6.5" stroke="#7CBA49" stroke-width="2"/>
                        <path d="M21.8799 12.7713H19.9899V14.7113H18.6699V12.7713H16.7799V11.5513H18.6699V9.61133H19.9899V11.5513H21.8799V12.7713Z" fill="#7CBA49"/>
                    </svg>
                    Ajouter au panier</button>

            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js"></script>
{/block}
