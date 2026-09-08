<div id="block-reassurance-custom">
    <h2 class="picto_rond_title pouce">{l s='Les avantages d\'être pro chez Districlos' d='Shop.Theme.Customcustomer'}</h2>
    <div class="row">
        <div class="inside">
            <ul class="cont-inside">
                <li class="item">
                    <picture><source srcset="/img/cms/auth/livraison_home.webp" type="image/webp"><img width="60" height="60" src="/img/cms/auth/livraison_home.png" loading="lazy"/></picture>
                    <div class="title">{l s='Livraison offerte' d='Shop.Theme.Customcustomer'}<br>{l s='dès 450 €' d='Shop.Theme.Customcustomer'}</div>
                    <p>{capture assign="ckc_ep_livraison"}{l s='Directement sur votre [1]chantier[/1] ou dépôt !%br%La livraison s\'effectue avec [2]notre propre flotte de camions[/2] pour des [3]délais[/3]%br%et des [4]coûts réduits.[/4]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>', '[2]' => '<strong>', '[/2]' => '</strong>', '[3]' => '<strong>', '[/3]' => '</strong>', '[4]' => '<strong>', '[/4]' => '</strong>', '%br%' => '<br>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_ep_livraison nofilter}</p>
                </li>
                <li class="item">
                    <picture><source srcset="/img/cms/auth/remise_rond2.webp" type="image/webp"><img width="60" height="60" src="/img/cms/auth/remise_rond2.png" loading="lazy"/></picture>
                    <div class="title">{l s='Mêmes produits' d='Shop.Theme.Customcustomer'}<br>{l s='au meilleur prix' d='Shop.Theme.Customcustomer'}</div>
                    <p>{capture assign="ckc_ep_tarifs"}{l s='Obtenez des [1]tarifs exclusifs[/1]%br%professionnels sur la totalité de notre catalogue de produits !' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>', '%br%' => '<br>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_ep_tarifs nofilter}</p>
                </li>
                <li class="item">
                    <picture><source srcset="/img/cms/auth/remise_rond.webp" type="image/webp"><img width="60" height="60" src="/img/cms/auth/remise_rond.png" loading="lazy"/></picture>
                    <div class="title">{l s='100€ offert dès 1000€' d='Shop.Theme.Customcustomer'}</div>
                    <p>{capture assign="ckc_ep_remises"}{l s='Pour tout nouveau professionnel ! De plus, vous pouvez bénéficier de [1]remises exceptionnelles[/1] sur l\'ensemble de notre catalogue Districlos.' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_ep_remises nofilter}</p>
                </li>
                <li class="item">
                    <picture><source srcset="/img/cms/auth/carton_rond.webp" type="image/webp"><img width="60" height="60" src="/img/cms/auth/carton_rond.png" loading="lazy"/></picture>
                    <div class="title">{l s='Reprise de marchandise' d='Shop.Theme.Customcustomer'}</div>
                    <p>{capture assign="ckc_ep_reprise"}{l s='En bon état et non rayé (hors soubassement). La récupération des produits s\'effectue [1]sous 3 mois maximum.[/1]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>'] d='Shop.Theme.Customcustomer'}{/capture}{$ckc_ep_reprise nofilter}</p>
                </li>
            </ul>
        </div>
    </div>
</div>
