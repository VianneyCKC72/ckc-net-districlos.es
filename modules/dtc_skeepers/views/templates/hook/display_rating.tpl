{**
 * Module dtc_skeepers
 * Template pour affichage de la note client
 *
 * @author    Agence CKC
 * @copyright 2024
 *}

{if isset($dtc_skeepers_note) && $dtc_skeepers_note}
    {if isset($dtc_skeepers_certificate_link) && $dtc_skeepers_certificate_link}
        <a href="{$dtc_skeepers_certificate_link}" target="_blank" rel="noopener noreferrer" class="avis-link" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 6px;">
            {$dtc_skeepers_text} : <strong>{$dtc_skeepers_note}/5</strong><img class="ico-stars" src="/img/cms/ico-stars.png" alt="ico-stars.png" />
        </a>
    {else}
        {$dtc_skeepers_text} : <strong>{$dtc_skeepers_note}/5</strong><img class="ico-stars" src="/img/cms/ico-stars.png" alt="ico-stars.png" />
    {/if}
{else}
    {$dtc_skeepers_text} : <strong>{$dtc_skeepers_fallback_note}</strong><img class="ico-stars" src="/img/cms/ico-stars.png" alt="ico-stars.png" />
{/if}
