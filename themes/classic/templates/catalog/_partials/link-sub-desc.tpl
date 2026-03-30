{assign var="dtc_wizard_content" value=""}

{if isset($category.id)}
  {* Capture le rendu du wizard (le module ne renvoie rien si la catégorie n'est pas active) *}
  {capture assign="dtc_wizard_content"}{hook h='displayHeaderCategory' category=$category}{/capture}
{/if}

{if $dtc_wizard_content|trim ne ''}
  {$dtc_wizard_content nofilter}
{elseif !empty($subcategoryLinks)}
  <div class="category_inline description_souscat">
    {foreach $subcategoryLinks as $link}
      <a href="{$link.url}" class="item description_souscatconf {$link.class}">
        <h2>
          <span class="title">
            {$link.title nofilter}
          </span>
        </h2>
        <span class="btn-primary-arrow">
          <svg
            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-move-up-right">
            <path d="M13 5H19V11"></path>
            <path d="M19 5L5 19"></path>
          </svg>
        </span>
      </a>
    {/foreach}
  </div>
{/if}