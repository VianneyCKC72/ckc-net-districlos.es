<section>
  <p>
	  {l s='Please call us immediately after your order confirmation to proceed to the payment. You will receive our order confirmation per email containing details and order number.' mod='ckc_paywithphone'}
	  {l s='We\'ll process the order immediately after receiving the payment.' mod='ckc_paywithphone'}
  </p>
  <div class="modal fade" id="gcpaywithphone-modal" tabindex="-1" role="dialog" aria-labelledby="Pay with Phone information" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h2>{l s='Pay with Phone' mod='ckc_paywithphone'}</h2>
        </div>
        <div class="modal-body">
          <p>{l s='Payment by phone :' mod='ckc_paywithphone'}</p>
			{include file='module:dtc_wirepayment/views/templates/hook/_partials/payment_infos.tpl'}
        </div>
      </div>
    </div>
  </div>
</section>
