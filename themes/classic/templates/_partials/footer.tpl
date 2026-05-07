<footer id="footer" class="js-footer">
    <div class="footer-inner container">
        {include file='_partials/footer-logo-social.tpl'}
        {include file='_partials/footer-nav.tpl'}
    </div>
    <div class="container">
        {include file='_partials/footer-bottom.tpl'}
    </div>

    {hook h='displayFooter'}
    {hook h='displayFooterAfter'}
</footer>
