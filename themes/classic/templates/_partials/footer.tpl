<div class="footer-container">
    <div class="container">
        <div class="footer-top-grid">
            {include file='_partials/footer-logo-social.tpl'}
            {include file='_partials/footer-nav.tpl'}
        </div>
    </div>

    {include file='_partials/footer-bottom.tpl'}

    {* Hooks modules (newsletter, reassurance, etc.) *}
    {hook h='displayFooter'}
    {hook h='displayFooterAfter'}
</div>
