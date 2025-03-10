{if !empty($subcategoryLinks)}
    <div class="category_inline description_souscat">
        {foreach $subcategoryLinks as $link}
            <a href="{$link.url}" class="item description_souscatconf {$link.class}" style="max-width: 295px; padding: 0 15px;">
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