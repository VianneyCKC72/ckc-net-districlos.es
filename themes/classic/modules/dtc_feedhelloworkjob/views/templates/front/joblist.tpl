
{extends file='page.tpl'}

{block name='head'}
    {$smarty.block.parent}
    <link rel="stylesheet" href="{$urls.base_url}modules/dtc_feedhelloworkjob/views/css/jobstyle.css?v=1.0" type="text/css" media="all" />
{/block}

{block name='javascript_bottom'}
    {$smarty.block.parent}
    <script src="{$urls.base_url}modules/dtc_feedhelloworkjob/views/js/jobsearch.js"></script>
{/block}



{block name='page_content'}
<div class="job-search">
 <form id="job-search-form" method="get" action="{$search_url}">

 <input type="submit" style="display: none;">
 <input type="text" name="city" placeholder="{l s='Ville' d='Shop.Theme.Customdtc'}" value="{if isset($smarty.get.city)}{$smarty.get.city|escape:'html':'UTF-8'}{/if}">
 <input type="text" name="job" placeholder="{l s='Intitulé du poste' d='Shop.Theme.Customdtc'}" value="{if isset($smarty.get.job)}{$smarty.get.job|escape:'html':'UTF-8'}{/if}">
  <button type="submit" title="{l s='Rechercher' d='Shop.Theme.Customdtc'}">
    <i class="fas fa-search"></i>
  </button>
 <button type="reset" id="reset-search">{l s='Réinitialiser' d='Shop.Theme.Customdtc'}</button>
 </form>
</div>

<div class="job-list">
{foreach from=$jobs item=job}

<a href="{$job.LinkPage}" target="_blank" class="cta">

  <div class="job-item">
  <h3>{$job.NameAdvertise|escape:'html':'UTF-8'}</h3>
  <p><strong>{l s='Entreprise :' d='Shop.Theme.Customdtc'}</strong> {$job.CompanyName|escape:'html':'UTF-8'}</p>
  <p><strong>{l s='Lieu :' d='Shop.Theme.Customdtc'}</strong> {$job.City|escape:'html':'UTF-8'}</p>
  <p><strong>{l s='Date :' d='Shop.Theme.Customdtc'}</strong> {$job.CreateDate|date_format:'%d/%m/%Y'}</p>
  <p>
    {$job.Description|strip_tags|replace:'&nbsp;':' '|truncate:300:'...'}
  </p> 
  <p>
    <span class="btn-primary-arrow">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-up-right">
        <path d="M13 5H19V11"></path>
        <path d="M19 5L5 19"></path>
      </svg>
    </span>
  </p>
  </div>
</a>
 {/foreach}
</div>

{if $total_pages > 1}
<div class="pagination">
 {if $current_page > 1}
 <a href="{$pagination_url}?&page={$current_page-1}">{l s='Précédent' d='Shop.Theme.Customdtc'}</a>
 {/if}
 {for $i=1 to $total_pages}
 <a href="{$pagination_url}?&page={$i}" class="{if $i == $current_page}active{/if}">{$i}</a>
 {/for}
 {if $current_page < $total_pages}
 <a href="{$pagination_url}?&page={$current_page+1}">{l s='Suivant' d='Shop.Theme.Customdtc'}</a>
 {/if}
</div>
{/if}
{/block}
