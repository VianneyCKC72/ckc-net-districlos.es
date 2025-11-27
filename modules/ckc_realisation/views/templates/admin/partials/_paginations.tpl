{if $pages > 1}
    <div class="clearfix" style="margin:10px 0">
        {section name=p loop=$pages+1 start=1}
            {assign var=i value=$smarty.section.p.index}
            {assign var=url value=$base_url|cat:'&page='|cat:$i|cat:'&f_status='|cat:$f_status|cat}
            <a class="btn btn-{if $i==$page}primary{else}default{/if}" style="margin-right:5px" href="{$url|escape:'html'}">{$i}</a>
        {/section}
    </div>
{/if}
