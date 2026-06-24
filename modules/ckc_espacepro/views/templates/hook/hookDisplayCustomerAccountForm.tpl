<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<!-- Module ckc_espacepro -->
<div class="block-register-espace-pro">

    <!-- SIRET -->
    <div class="form-group row">
        <div class="col-md-12">
            <label class="form-label" for="customer-siret">{l s='Siret*' d='Shop.Theme.Customespacepro'}</label>
            <input class="form-control pad20" type="text" name="customer-siret" id="customer-siret"
                   value="{$customer->siret|default:''|escape:'htmlall':'UTF-8'}" required>
        </div>
    </div>

    {if isset($code_naf)}
        <!-- Code NAF -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="nafCodeSelect">{l s='Code NAF*' d='Shop.Theme.Customespacepro'}</label>
                <select class="form-control pad20" name="naf" id="nafCodeSelect" required>
                    <option value="" {if !isset($customer->ape) || empty($customer->ape)}selected{/if}>{l s='Choisir un code NAF' d='Shop.Theme.Customespacepro'}</option>
                    {foreach from=$code_naf item="naf"}
                        <option value="{$naf.code}"
                                {if isset($customer->ape) && $customer->ape == $naf.code}selected{/if}>
                            {$naf.code} - {$naf.description}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}

    {if $showAddressFields}
        <!-- Soumis à la TVA -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-tva">{l s='Soumis à la TVA*' d='Shop.Theme.Customespacepro'}</label>
                <select name="customer-tva" id="customer-tva" class="form-control" required>
                    <option value="0" {if isset($customer->tva) && $customer->tva == 0}selected{/if}>{l s='Non' d='Shop.Theme.Customespacepro'}</option>
                    <option value="1" {if isset($customer->tva) && $customer->tva == 1}selected{/if}>{l s='Oui' d='Shop.Theme.Customespacepro'}</option>
                </select>
            </div>
        </div>


        <!-- Numéro de TVA -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-vat_number">{l s='Numéro de TVA' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-vat_number" id="customer-vat_number"
                       value="{$customer->vat_number|default:''|escape:'htmlall':'UTF-8'}">
            </div>
        </div>

        <!-- KBIS (upload) -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="file-upload">{l s='Kbis*' d='Shop.Theme.Customespacepro'}</label>
                <div class="file-wrap">
                    <label for="file-upload" class="custom-file-upload">{l s='Choisir un fichier' d='Shop.Theme.Customespacepro'}</label>
                    <input style="opacity:0;height:0;width:0;" id="file-upload" class="file-input js-file-input" type="file"
                           name="kbis" accept=".pdf,.jpg,.png">
                    <span class="file-chosen js-file-name">{l s='Aucun fichier choisi' d='Shop.Theme.Customespacepro'}</span>
                </div>
            </div>
        </div>

        <!-- RIB (upload) -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="rib-upload">{l s='Relevé d\'identité bancaire* (RIB)' d='Shop.Theme.Customespacepro'}</label>
                <div class="file-wrap">
                    <label for="rib-upload" class="custom-file-upload">{l s='Choisir un fichier' d='Shop.Theme.Customespacepro'}</label>
                    <input style="opacity:0;height:0;width:0;" id="rib-upload" class="file-input js-file-input" type="file"
                           name="rib" accept=".pdf,.jpg,.png">
                    <span class="file-chosen js-file-name">{l s='Aucun fichier choisi' d='Shop.Theme.Customespacepro'}</span>
                </div>
            </div>
        </div>
    {/if}

    <!-- Pays -->
    <div class="form-group row">
        <div class="col-md-12">
            <label class="form-label" for="customer-pays">{l s='Pays*' d='Shop.Theme.Customespacepro'}</label>
            <select name="customer-pays" id="customer-pays" class="form-control pad20" required>
                {foreach from=$countries item='country'}
                    <option value="{$country.id_country|intval}"
                            {if isset($customer->id_country) && $customer->id_country == $country.id_country}selected{/if}>
                        {$country.name|escape}
                    </option>
                {/foreach}
            </select>
        </div>
    </div>
    {if $showAddressFields}
        <!-- Société -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-company">{l s='Société*' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-company" id="customer-company"
                       value="{$customer->company|default:''|escape:'htmlall':'UTF-8'}" required>
            </div>
        </div>
        <!-- Ville -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-ville">{l s='Ville*' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-ville" id="customer-ville"
                       value="{$customer->city|default:''|escape:'htmlall':'UTF-8'}" required>
            </div>
        </div>
        <!-- Adresse -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-adresse">{l s='Adresse*' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-adresse" id="customer-adresse"
                       value="{$customer->address1|default:''|escape:'htmlall':'UTF-8'}" required>
            </div>
        </div>
        <!-- Complément d'adresse -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-adresse2">{l s='Complément d\'adresse' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-adresse2" id="customer-adresse2"
                       value="{$customer->address2|default:''|escape:'htmlall':'UTF-8'}">
            </div>
        </div>
        <!-- Code Postal -->
        <div class="form-group row">
            <div class="col-md-12">
                <label class="form-label" for="customer-cp">{l s='Code Postal*' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-cp" id="customer-cp"
                       value="{$customer->postcode|default:''|escape:'htmlall':'UTF-8'}" required>
            </div>
        </div>
        <!-- Téléphone -->
        <div class="form-group row lastfield">
            <div class="col-md-12">
                <label class="form-label" for="customer-tel">{l s='Téléphone*' d='Shop.Theme.Customespacepro'}</label>
                <input class="form-control pad20" type="text" name="customer-tel" id="customer-tel"
                       value="{$customer->phone|default:''|escape:'htmlall':'UTF-8'}" required>
            </div>
        </div>
    {/if}
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<!-- Nom du fichier choisi -->
<script>
    (function () {
        function baseName(path) {
            return (path || '').split(/[\\/]/).pop();
        }

        $(document).on('change', '.js-file-input', function () {
            var $wrap = $(this).closest('.file-wrap');
            $wrap.find('.js-file-name').text(baseName(this.value) || 'Aucun fichier choisi');
        });
    })();
</script>
<!-- /Module ckc_espacepro -->
