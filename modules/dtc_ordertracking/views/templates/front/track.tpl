{extends file='page.tpl'}

{block name='page_title'}
  {l s='Suivi de commande' mod='dtc_ordertracking'}
{/block}

{block name='page_content'}
  <section class="dtc-ordertracking">
    <div class="dtc-ot-hero">
      <div class="container">
        <div class="dtc-ot-hero__inner">
          <div>
            <div class="dtc-ot-hero__kicker">{l s='Commande' mod='dtc_ordertracking'}</div>
            <h1 class="dtc-ot-hero__title">{$order_reference|escape:'htmlall':'UTF-8'}</h1>
            <div class="dtc-ot-hero__meta">
              <span>{l s='Passée le' mod='dtc_ordertracking'} {$order_date|escape:'htmlall':'UTF-8'}</span>
              {if $current_step_label}
                <span class="dtc-ot-hero__sep">·</span>
                <span class="dtc-ot-hero__status">{$current_step_label|escape:'htmlall':'UTF-8'}</span>
              {/if}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="dtc-ot-card">
        <h2 class="h5 mb-3">{l s='Avancement' mod='dtc_ordertracking'}</h2>

        <ol class="dtc-timeline">
          {foreach from=$timeline item=item}
            {assign var=cls value='is-todo'}
            {if $item.current}{assign var=cls value='is-current'}{/if}
            {if $item.done}{assign var=cls value='is-done'}{/if}
            <li class="dtc-timeline__item {$cls|escape:'htmlall':'UTF-8'}">
              <div class="dtc-timeline__dot" aria-hidden="true"></div>
              <div class="dtc-timeline__content">
                <div class="dtc-timeline__label">{$item.label|escape:'htmlall':'UTF-8'}</div>
                {if $item.date}
                  <div class="dtc-timeline__date">{$item.date|escape:'htmlall':'UTF-8'}</div>
                {/if}
              </div>
            </li>
          {/foreach}
        </ol>
      </div>

      <div class="dtc-ot-card dtc-ot-card--cta">
        <div class="dtc-ot-cta">
          <a class="btn btn-primary" href="{$contact_url|escape:'htmlall':'UTF-8'}">
            {l s='Contacter le service client' mod='dtc_ordertracking'}
          </a>
        </div>
      </div>
    </div>
  </section>
{/block}

