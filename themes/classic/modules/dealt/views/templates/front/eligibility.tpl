<div id="dealt-service-info">
    <p>{l s='Service d\'installation disponible via notre partenaire' d='Shop.Theme.Customdealt'}</p>
    <button id="check-eligibility" class="btn btn-outline-secondary">{l s='Vérifier l\'éligibilité' d='Shop.Theme.Customdealt'}</button>
</div>

<script type="text/javascript">
    document.getElementById('check-eligibility').addEventListener('click', function () {
        fetch('{$link->getModuleLink('dealt', 'eligibility')}')
            .then(r => r.json())
            .then(data => alert('{l s='Disponible: ' d='Shop.Theme.Customdealt' js=1}' + data.available));
    });
</script>
