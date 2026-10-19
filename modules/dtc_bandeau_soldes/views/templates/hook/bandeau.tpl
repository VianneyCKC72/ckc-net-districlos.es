<div class="bandeau-soldes"
     style="background-color: {$dtc_bandeau.color_bg|escape:'html':'UTF-8'};"
     role="region"
     aria-label="{$dtc_bandeau.badge_label|escape:'html':'UTF-8'}">
    <div class="bandeau-soldes__inner">
        <a href="{$dtc_bandeau.link|escape:'html':'UTF-8'}"
           class="bandeau-soldes__link"
           style="color: {$dtc_bandeau.color_text|escape:'html':'UTF-8'};">
            <span class="bandeau-soldes__badge"
                  style="background-color: {$dtc_bandeau.badge_color_bg|escape:'html':'UTF-8'}; color: {$dtc_bandeau.badge_color_text|escape:'html':'UTF-8'};">{$dtc_bandeau.badge nofilter}</span>
            <span class="bandeau-soldes__text"
                  style="color: {$dtc_bandeau.color_text|escape:'html':'UTF-8'};">{$dtc_bandeau.text nofilter}</span>
        </a>
    </div>
</div>
