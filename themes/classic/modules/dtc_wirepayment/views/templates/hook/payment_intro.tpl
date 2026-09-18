<section>
  <p><span><b>{l s='Vous recevrez les coordonnées bancaires du magasin par email.' d='Shop.Theme.Custommodules'}<br/>
    </b></span><i>{l s='Pensez à vérifier votre dossier courrier indésirable.' d='Shop.Theme.Custommodules'}</i><br/></p>
  <p><span>{capture assign="dtc_wire_ref"}{l s='[1]Indiquez bien la référence de votre commande dans le libellé[/1] du virement bancaire.' sprintf=['[1]' => '<b>', '[/1]' => '</b>'] d='Shop.Theme.Custommodules'}{/capture}{$dtc_wire_ref nofilter}</span>
    <br/><i>{l s='Les délais de traitement seront plus rapides.' d='Shop.Theme.Custommodules'}</i><br/></p>
  <p><br/>{l s='Pour tout complément d\'information :' d='Shop.Theme.Custommodules'}
    <br/><br/><i class="fa fa-phone"></i> <span class="contact-info">04 22 53 10 34</span>
    <br/><i class="fa fa-envelope"></i> <span class="contact-info">commercial@districlos.com</span><br/>
  </p>

  <p>
    {l s='Veuillez virer le montant de la facture sur notre compte bancaire. Vous recevrez la confirmation de commande par e-mail avec les coordonnées bancaires et le numéro de commande.' d='Modules.Dtcwirepayment.Shop'}
    {if $bankwireReservationDays}
      {if $bankwireReservationDays > 1}
        {l s='Les articles seront réservés %s jours et la commande sera traitée dès réception du paiement.' sprintf=[$bankwireReservationDays] d='Modules.Dtcwirepayment.Shop'}
      {else}
        {l s='Les articles seront réservés %s jour et la commande sera traitée dès réception du paiement.' sprintf=[$bankwireReservationDays] d='Modules.Dtcwirepayment.Shop'}
      {/if}
    {/if}
    {if $bankwireCustomText}
        <a data-toggle="modal" data-target="#bankwire-modal">{l s='Plus d\'informations' d='Modules.Dtcwirepayment.Shop'}</a>
    {/if}
  </p>

  <div class="modal fade" id="bankwire-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h2>{l s='Virement bancaire' d='Modules.Dtcwirepayment.Shop'}</h2>
        </div>
        <div class="modal-body">
          <p>{l s='Le paiement s\'effectue par virement sur le compte suivant :' d='Modules.Dtcwirepayment.Shop'}</p>
          {include file='module:dtc_wirepayment/views/templates/hook/_partials/payment_infos.tpl'}
          {$bankwireCustomText nofilter}
        </div>
      </div>
    </div>
  </div>
</section>
