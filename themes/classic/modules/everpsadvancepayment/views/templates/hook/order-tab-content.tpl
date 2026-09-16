{*
* Project : everpsadvancepayment
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

<div class="tab-pane" id="invoiceadvancepayment">
    <input type="hidden" name="id_order" value="{$id_order|escape:'htmlall':'UTF-8'}">
    <div class="form-horizontal clearfix">
        <div class="col-md-9">
            <a href="{$pdf_link|escape:'htmlall':'UTF-8'}" class="btn btn-default">{l s='Advance Invoice' mod='everpsadvancepayment'}</a>
        </div>
    </div>
</div>
