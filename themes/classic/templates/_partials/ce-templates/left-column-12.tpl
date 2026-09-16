{* Remplace {hook h='CETemplate' id=12} — colonne gauche listing (shop particulier) *}
{assign var='img_cms' value='/img/cms/'}
{capture assign='ce_arrow_svg'}
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right" aria-hidden="true"><path d="M13 5H19V11"></path><path d="M19 5L5 19"></path></svg>
{/capture}
{capture assign='ce_arrow_svg_green'}
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7CBA49" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 5H19V11"></path><path d="M19 5L5 19"></path></svg>
{/capture}

<link rel="stylesheet" href="{$urls.base_url|escape:'html':'UTF-8'}modules/creativeelements/views/css/ce/12010000.css" media="all">

{* Pas de data-elementor-id : évite le bouton d'édition CE *}
<div class="elementor elementor-12010000">
  <div class="elementor-section-wrap">

    <section class="elementor-element elementor-element-f5682c2 elementor-section-full_width elementor-section-height-default elementor-section elementor-top-section" data-id="f5682c2" data-element_type="section">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">
          <div class="elementor-element elementor-element-cfe14d2 ce-widgets-space--gap elementor-column elementor-col-100 elementor-top-column" data-id="cfe14d2" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">
                <div class="elementor-element elementor-element-78fde48 elementor-widget__width-inherit elementor-widget elementor-widget-ps-widget-module" data-id="78fde48" data-element_type="widget">
                  <div class="elementor-widget-container">
                    {hook h='displayLeftColumn' mod='pm_advancedsearch4'}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="elementor-element elementor-element-6cb0601 elementor-section-full_width elementor-section-height-default elementor-section elementor-top-section" data-id="6cb0601" data-element_type="section" id="block-moins-chers">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">
          <div class="elementor-element elementor-element-2aa5f62 ce-widgets-space--gap elementor-column elementor-col-100 elementor-top-column" data-id="2aa5f62" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">

                <div class="elementor-element elementor-element-f61a0ff elementor-widget elementor-widget-text-editor" data-id="f61a0ff" data-element_type="widget">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p><img src="{$img_cms|escape:'html':'UTF-8'}logo_garantie_prix.png" alt="" width="38" height="38"></p>
                      <p>
                        {if $language.iso_code == 'en'}
                          Guaranteed products<br>
                          <strong><span class="h3 block-titre">at the lowest price online</span></strong>
                        {else}
                          Produits garantis<br>
                          <strong><span class="h3 block-titre">au meilleur prix en ligne</span></strong>
                        {/if}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="elementor-element elementor-element-682edaf elementor-widget elementor-widget-text-editor" data-id="682edaf" data-element_type="widget">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p>{l s='Vous trouvez moins cher ailleurs ? Nous vous remboursons la différence.' d='Shop.Theme.Custommodules'}</p>
                    </div>
                  </div>
                </div>

                <div class="elementor-element elementor-element-4ca4fc8 elementor-widget elementor-widget-text-editor" data-id="4ca4fc8" data-element_type="widget">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p>{if $language.iso_code == 'en'}Valid for identical products from the same source{else}Valable pour des produits identiques de même provenance{/if}</p>
                    </div>
                  </div>
                </div>

                <div class="elementor-element elementor-element-9e4d9f6 elementor-widget elementor-widget-text-editor" data-id="9e4d9f6" data-element_type="widget">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p style="text-align: center;">
                        <a style="display: inline-flex; align-items: center; justify-content: center;" href="{$link->getCMSLink(650, null, $language.id)|escape:'html':'UTF-8'}" class="link-arrow blue-light">
                          {l s='En savoir plus' d='Shop.Theme.Customcatalog'}&nbsp;<span class="btn-primary-arrow">{$ce_arrow_svg nofilter}</span>
                        </a>
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="elementor-element elementor-element-8e5dd50 elementor-section-boxed elementor-section-height-default elementor-section elementor-top-section" data-id="8e5dd50" data-element_type="section" id="demander-un-devis">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">
          <div class="elementor-element elementor-element-9af60c3 ce-widgets-space--gap elementor-column elementor-col-100 elementor-top-column" data-id="9af60c3" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">

                <div class="elementor-element elementor-element-336030b elementor-widget elementor-widget-text-editor" data-id="336030b" data-element_type="widget">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p>
                        {if $language.iso_code == 'en'}Specific requirements?{else}Besoin spécifique ?{/if}<br>
                        <strong><span class="h3 block-titre">{l s='Demandez votre devis en 30 secondes' d='Shop.Theme.Custommodules'}</span></strong>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="elementor-element elementor-element-ea0c2d0 elementor-widget elementor-widget-text-editor" data-id="ea0c2d0" data-element_type="widget">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p>
                        <a href="{$link->getModuleLink('opartdevis', 'simplequotation', [], true, $language.id)|escape:'html':'UTF-8'}" class="link-arrow" style="display: inline-flex; align-items: center; justify-content: center;" title="{l s='Demandez votre devis en 30 secondes' d='Shop.Theme.Custommodules'}">
                          <span class="btn-primary-arrow" style="background: white;">{$ce_arrow_svg_green nofilter}</span>
                        </a>
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="elementor-element elementor-element-4d6d522 elementor-section-full_width elementor-section-height-default elementor-section elementor-top-section" data-id="4d6d522" data-element_type="section">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">
          <div class="elementor-element elementor-element-c78b3c5 ce-widgets-space--gap elementor-column elementor-col-100 elementor-top-column" data-id="c78b3c5" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">
                <div class="elementor-element elementor-element-1e41ed2 elementor-widget elementor-widget-ps-widget-module" data-id="1e41ed2" data-element_type="widget">
                  <div class="elementor-widget-container">
                    {hook h='displayLeftColumn' mod='tm_sidebestsellers'}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>
</div>
