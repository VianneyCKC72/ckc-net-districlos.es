{extends file='page.tpl'}

{block name='page_title'}
  {l s='Suivre une commande' mod='dtc_ordertracking'}
{/block}

{block name='page_content'}
  <section class="dtc-ordertracking-form">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h2 class="h4 mb-3">{l s='Suivre une commande' mod='dtc_ordertracking'}</h2>

              <form action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
                <div class="form-group">
                  <label for="dtc-ot-code">{l s='Référence de commande' mod='dtc_ordertracking'}</label>
                  <input id="dtc-ot-code" class="form-control" type="text" name="code"
                         value="{$code_value|escape:'htmlall':'UTF-8'}" required>
                </div>
                {if empty($allow_ref_only)}
                  <div class="form-group">
                    <label for="dtc-ot-email">{l s='Email' mod='dtc_ordertracking'}</label>
                    <input id="dtc-ot-email" class="form-control" type="email" name="email"
                           value="{$email_value|escape:'htmlall':'UTF-8'}" required>
                    <small class="form-text text-muted">
                      {l s='(Ou utilisez le lien de suivi reçu par email)' mod='dtc_ordertracking'}
                    </small>
                  </div>
                {/if}

                <button class="btn btn-primary" type="submit">
                  {l s='Afficher le suivi' mod='dtc_ordertracking'}
                </button>

                <p class="mt-3 mb-0">
                  <a href="{$login_url|escape:'htmlall':'UTF-8'}">
                    {l s='Vous avez un compte ? Connectez-vous' mod='dtc_ordertracking'}
                  </a>
                </p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
{/block}

