<div class="panel">
  <div class="panel-heading">
    <i class="icon-truck"></i> {l s='DTC Suivi de commande' mod='dtc_ordertracking'}
  </div>
  <form method="post" action="{$currentIndex|escape:'htmlall':'UTF-8'}&configure={$module_name|escape:'htmlall':'UTF-8'}&token={$token_admin_modules|escape:'htmlall':'UTF-8'}">
    <div class="panel-body">
      <div class="form-group">
        <label>
          <input type="checkbox" name="DTC_ORDERTRACKING_ALLOW_REF_ONLY" value="1" {if $allow_ref_only}checked{/if}>
          {l s='Autoriser la recherche par référence seule (moins sécurisé)' mod='dtc_ordertracking'}
        </label>
        <p class="help-block">
          {l s='Si activé, n’importe qui connaissant une référence peut afficher la page de suivi (rate-limit appliqué).' mod='dtc_ordertracking'}
        </p>
      </div>

      <hr>

      <h3 class="h4">{l s='Libellés des étapes (1 à 5)' mod='dtc_ordertracking'}</h3>
      <div class="row">
        {for $i=1 to 5}
          <div class="col-lg-6">
            <div class="form-group">
              <label for="dtc_step_{$i|intval}">{l s='Étape %d' sprintf=[$i] mod='dtc_ordertracking'}</label>
              <input id="dtc_step_{$i|intval}" class="form-control" type="text"
                     name="DTC_ORDERTRACKING_STEP_LABEL_{$i|intval}"
                     value="{$labels[$i]|default:''|escape:'htmlall':'UTF-8'}" required>
            </div>
          </div>
        {/for}
      </div>

      <hr>

      <h3 class="h4">{l s='Mapping statuts → étapes' mod='dtc_ordertracking'}</h3>
      <p class="help-block">
        {l s='Associez chaque statut de commande à une étape (ou laissez sur “—”).' mod='dtc_ordertracking'}
      </p>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>{l s='ID' mod='dtc_ordertracking'}</th>
              <th>{l s='Nom' mod='dtc_ordertracking'}</th>
              <th>{l s='Couleur' mod='dtc_ordertracking'}</th>
              <th>{l s='Étape' mod='dtc_ordertracking'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$states item=s}
              <tr>
                <td>{$s.id_order_state|intval}</td>
                <td>{$s.name|escape:'htmlall':'UTF-8'}</td>
                <td>
                  <span class="label" style="background-color:{$s.color|escape:'htmlall':'UTF-8'};color:#fff;">
                    {$s.color|escape:'htmlall':'UTF-8'}
                  </span>
                </td>
                <td>
                  {assign var=val value=$mapping[$s.id_order_state]|default:0}
                  <select class="form-control" name="mapping[{$s.id_order_state|intval}]">
                    <option value="0" {if $val|intval === 0}selected{/if}>—</option>
                    {for $i=1 to 5}
                      <option value="{$i|intval}" {if $val|intval === $i}selected{/if}>
                        {l s='Étape %d' sprintf=[$i] mod='dtc_ordertracking'}
                      </option>
                    {/for}
                  </select>
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel-footer">
      <button type="submit" name="submitDtcOrderTracking" class="btn btn-default pull-right">
        <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_ordertracking'}
      </button>
    </div>
  </form>
</div>

