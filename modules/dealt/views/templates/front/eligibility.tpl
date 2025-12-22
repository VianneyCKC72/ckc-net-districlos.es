<div id="dealt-service-info">
    <p>Service d'installation disponible via notre partenaire</p>
    <button id="check-eligibility" class="btn btn-outline-secondary">Vérifier l'éligibilité</button>
</div>

<script type="text/javascript">
    document.getElementById('check-eligibility').addEventListener('click', function () {
        fetch('{$link->getModuleLink('dealt', 'eligibility')}')
            .then(r => r.json())
            .then(data => alert('Disponible: ' + data.available));
    });
</script>
