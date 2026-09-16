{* Remplace {hook h='CETemplate' id=14} — structure + IDs CE pour conserver 14010000.css *}
{assign var='img_cms' value='/img/cms/'}
{capture assign='ce_arrow_svg'}
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right" aria-hidden="true">
  <path d="M13 5H19V11"></path>
  <path d="M19 5L5 19"></path>
</svg>
{/capture}

<link rel="stylesheet" href="{$urls.base_url|escape:'html':'UTF-8'}modules/creativeelements/views/css/ce/14010000.css" media="all">

{* Pas de data-elementor-id : sinon frontend-edit.js injecte le bouton "modifier avec CE" *}
<div class="elementor elementor-14010000">
  <div class="elementor-section-wrap">

    <section class="elementor-element elementor-element-63690c3 elementor-section-boxed elementor-section-height-default elementor-section-height-default elementor-section elementor-top-section" data-id="63690c3" data-element_type="section">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">
          <div class="elementor-element elementor-element-b62da3d ce-widgets-space--gap elementor-column elementor-col-100 elementor-top-column" data-id="b62da3d" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">
                <div class="elementor-element elementor-element-9b5fc45 elementor-widget elementor-widget-text-editor" data-id="9b5fc45" data-element_type="widget" data-widget_type="text-editor.default">
                  <div class="elementor-widget-container">
                    <div class="elementor-text-editor elementor-clearfix">
                      <p>{l s='Parcourir d\'autres types de produits' d='Shop.Theme.Custommodules'}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="elementor-element elementor-element-0e4f281 elementor-section-full_width elementor-section-height-default elementor-section-height-default elementor-section elementor-top-section" data-id="0e4f281" data-element_type="section" id="liste-category-home">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">
          {* Noms + URLs issus de category_lang (langue courante) *}
          {assign var='ce_vignettes' value=[
            ['id' => 3, 'img' => 'grillage_rigide.png', 'col' => '9d3c240', 'cta' => 'f9daf8d'],
            ['id' => 2, 'img' => 'grillage_souple.png', 'col' => '80cfd1c', 'cta' => '4cffc06'],
            ['id' => 4, 'img' => 'districlos_portails.png', 'col' => '4e380f0', 'cta' => 'b0a5656'],
            ['id' => 6, 'img' => 'brise%20vue.png', 'col' => '8cd8aae', 'cta' => 'a02eb73', 'br' => true],
            ['id' => 216, 'img' => 'Aluminium.png', 'col' => 'dd7327e', 'cta' => '021ed0f'],
            ['id' => 130, 'img' => 'Portillions.png', 'col' => '95cafa9', 'cta' => '3c9b0b5']
          ]}
          {assign var='ce_cat_infos' value=Category::getCategoryInformation([3, 2, 4, 6, 216, 130], $language.id)}

          {foreach from=$ce_vignettes item=ce_v}
            {assign var='ce_cat_id' value=$ce_v.id}
            {if isset($ce_cat_infos[$ce_cat_id])}
              {assign var='ce_cat' value=$ce_cat_infos[$ce_cat_id]}
              {assign var='ce_cat_name' value=$ce_cat.name}
              {assign var='ce_cat_url' value=$link->getCategoryLink($ce_cat_id, $ce_cat.link_rewrite, $language.id)}
            {else}
              {assign var='ce_cat_name' value=''}
              {assign var='ce_cat_url' value=$link->getCategoryLink($ce_cat_id, null, $language.id)}
            {/if}

            <div class="elementor-element elementor-element-{$ce_v.col|escape:'html':'UTF-8'} ce-widgets-space--gap elementor-column elementor-col-16 elementor-top-column" data-id="{$ce_v.col|escape:'html':'UTF-8'}" data-element_type="column">
              <div class="elementor-column-wrap elementor-element-populated">
                <div class="elementor-widget-wrap">
                  <div class="elementor-element elementor-element-{$ce_v.cta|escape:'html':'UTF-8'} elementor-cta--skin-cover elementor-cta--valign-top bloc_vignette_category elementor-animated-content elementor-widget elementor-widget-call-to-action" data-id="{$ce_v.cta|escape:'html':'UTF-8'}" data-element_type="widget" data-widget_type="call-to-action.default">
                    <div class="elementor-widget-container">
                      <a class="elementor-cta" href="{$ce_cat_url|escape:'html':'UTF-8'}">
                        <div class="elementor-cta-bg-wrapper">
                          <img src="{$img_cms|escape:'html':'UTF-8'}{$ce_v.img|escape:'html':'UTF-8'}" loading="lazy" width="233" height="300" class="elementor-cta-bg elementor-bg" alt="{$ce_cat_name|escape:'html':'UTF-8'}">
                          <div class="elementor-cta-bg-overlay"></div>
                        </div>
                        <div class="elementor-cta-content">
                          <span class="elementor-cta-title elementor-content-item elementor-animated-item--grow">
                            {if !empty($ce_v.br)}
                              {$ce_cat_name|regex_replace:'/\s+/':'<br>' nofilter}
                            {else}
                              {$ce_cat_name|escape:'html':'UTF-8'}
                            {/if}
                          </span>
                          <span class="btn-primary-arrow">{$ce_arrow_svg nofilter}</span>
                        </div>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          {/foreach}

        </div>
      </div>
    </section>

    <section class="elementor-element elementor-element-5e3e935 elementor-section-full_width elementor-section-height-default elementor-section-height-default elementor-section elementor-top-section" data-id="5e3e935" data-element_type="section">
      <div class="elementor-container elementor-column-gap-no">
        <div class="elementor-row">
          <div class="elementor-element elementor-element-7a7cbb3 ce-widgets-space--gap elementor-column elementor-col-100 elementor-top-column" data-id="7a7cbb3" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">
                <div class="elementor-element elementor-element-3e351e3 banner-services stack-row__cards elementor-widget__width-inherit elementor-widget elementor-widget-ps-widget-module" data-id="3e351e3" data-element_type="widget" data-widget_type="ps-widget-module.default">
                  <div class="elementor-widget-container">
                    {widget name='blockreassurance'}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="elementor-element elementor-element-bd433b3 elementor-section-boxed elementor-section-height-default elementor-section-height-default elementor-section elementor-top-section" data-id="bd433b3" data-element_type="section">
      <div class="elementor-container elementor-column-gap-default">
        <div class="elementor-row">

          <div class="elementor-element elementor-element-c19f568 ce-widgets-space--gap elementor-column elementor-col-50 elementor-top-column" data-id="c19f568" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">
                <div class="elementor-element elementor-element-cfd2d99 elementor-widget elementor-widget-html" data-id="cfd2d99" data-element_type="widget" data-widget_type="html.default">
                  <div class="elementor-widget-container">
                    <a href="{$link->getCategoryLink(93, null, $language.id)|escape:'html':'UTF-8'}" class="card__bg-image">
                      <div class="content">
                        <h2 class="h2">{l s='Ces produits existent aussi en kit !' d='Shop.Theme.Custommodules'}</h2>
                        <div class="block-kit-category">
                          {l s='Choisissez votre kit idéal en fonction du type de pose souhaité. Ils contiennent tout le nécessaire pour une clôture réussie !' d='Shop.Theme.Custommodules'}
                        </div>
                        <p></p>
                        <p class="link-arrow">
                          {l s='Voir les kits' d='Shop.Theme.Custommodules'}
                          <img src="{$img_cms|escape:'html':'UTF-8'}espace-pro-arrow-home.svg" width="38" height="38" alt="">
                        </p>
                      </div>
                      <div class="thumbnail">
                        <img loading="lazy" src="{$img_cms|escape:'html':'UTF-8'}a-propos-ContactCard-ambitions-2.jpg" alt="{l s='Ces produits existent aussi en kit !' d='Shop.Theme.Custommodules'}">
                      </div>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="elementor-element elementor-element-c08e077 ce-widgets-space--gap elementor-column elementor-col-50 elementor-top-column" data-id="c08e077" data-element_type="column">
            <div class="elementor-column-wrap elementor-element-populated">
              <div class="elementor-widget-wrap">
                <div class="elementor-element elementor-element-012cbfa elementor-widget elementor-widget-html" data-id="012cbfa" data-element_type="widget" data-widget_type="html.default">
                  <div class="elementor-widget-container">
                    <a href="{$link->getModuleLink('ckcconfigurateurdistriclos', 'configurator', [], true, $language.id)|escape:'html':'UTF-8'}" class="card__bg-image">
                      <div class="content">
                        <h2 class="h2">{l s='Configurez votre clôture' d='Shop.Theme.Custommodules'}</h2>
                        <div class="block-kit-category">
                          {l s='Vous préférez du sur-mesure ? Composez votre clôture et choisissez ses caractéristiques uniques.' d='Shop.Theme.Custommodules'}
                        </div>
                        <p></p>
                        <p class="link-arrow">
                          {l s='Configurer votre clôture' d='Shop.Theme.Custommodules'}
                          <img src="{$img_cms|escape:'html':'UTF-8'}espace-pro-arrow-home.svg" width="38" height="38" alt="">
                        </p>
                      </div>
                      <div class="thumbnail">
                        <img loading="lazy" src="{$img_cms|escape:'html':'UTF-8'}image-6-a-propos.jpg" alt="{l s='Configurez votre clôture' d='Shop.Theme.Custommodules'}">
                      </div>
                    </a>
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
