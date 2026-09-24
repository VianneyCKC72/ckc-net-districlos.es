<div class="dealt-cart-tva">
    <h3>{l s='TVA réduite à 10%' d='Shop.Theme.Customdealt'}</h3>
    <div class="dealt-cart-tva-box">
        <p>{l s='Profitez d’un taux de TVA réduit à 10% sur la pose si vous remplissez les conditions suivantes :' d='Shop.Theme.Customdealt'}</p>
        <ul>
            <li>{l s='Votre habitation est achevée depuis plus de 2 ans' d='Shop.Theme.Customdealt'}</li>
            <li>{l s='Vous êtes propriétaire, locataire ou occupant à titre gratuit' d='Shop.Theme.Customdealt'}</li>
            <li>{l s='Les travaux sont réalisés par un professionnel' d='Shop.Theme.Customdealt'}</li>
        </ul>
        <p>{l s='[1]Un formulaire TVA simplifié sera à fournir.[/1]' sprintf=['[1]'=>'<strong>','[/1]'=>'</strong>'] d='Shop.Theme.Customdealt'}</p>
        <a href="{$link->getCMSLink(21)|escape:'html':'UTF-8'}" target="_blank" class="btn btn-secondary btn-sm mt-2">
            {l s='Télécharger le formulaire' d='Shop.Theme.Customdealt'}
        </a>
    </div>
</div>
