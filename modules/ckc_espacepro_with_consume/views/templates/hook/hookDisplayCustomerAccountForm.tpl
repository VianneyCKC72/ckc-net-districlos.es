<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

{*captchaVersion = {$captchaVersion}*}

{*<div class="form-group row">*}
{*    {if $captchaVersion == 2}*}
{*        <label class="col-md-3 form-control-label">{l s='Captcha' mod='eicaptcha'}</label>*}
{*        <div class="col-md-9">*}
{*            *}{***}
{*             * Le contenu du captcha est automatiquement ajouté dans le selecteur #captcha-box*}
{*             * Captcha content is automaticaly added into the selector #captcha-box*}
{*             *}
{*            <div class="g-recaptcha" data-sitekey="{$publicKey|escape:'html'}" id="captcha-box"*}
{*                 data-theme="{$captchatheme}"></div>*}
{*            <script src="https://www.google.com/recaptcha/api.js{if isset($captchaforcelang)}?hl={$captchaforcelang}{/if}"*}
{*                    async defer></script>*}
{*        </div>*}
{*    {else}*}
{*        <input type="hidden" id="captcha-box" name="g-recaptcha-response"/>*}
{*        <script src="https://www.google.com/recaptcha/api.js?render={$publicKey|escape:'html'}"></script>*}
{*        <script>*}
{*            grecaptcha.ready(function () {ldelim}*}
{*                grecaptcha.execute('{$publicKey|escape:'html'}', {ldelim}action: 'contact'{rdelim}).then(function (token) {ldelim}*}
{*                    var recaptchaResponse = document.getElementById('captcha-box');*}
{*                    recaptchaResponse.value = token;*}
{*                    {rdelim});*}
{*                {rdelim});*}
{*        </script>*}
{*    {/if}*}
{*</div>*}


<!-- Module ckc_espacepro -->
<div class="block-register-espace-pro">
    <div class="form-group row">
        <div class="col-md-12">
            <input class="form-control pad20" placeholder="Siret*" type="text" name="customer-siret" id="customer-siret"
                   required>
        </div>
    </div>

    {if ckc_form_minimal == 1}
    {if isset($code_naf)}
        <div class="form-group row">
            <div class="col-md-12">
                <select class="form-control pad20" placeholder="Code NAF*" name="naf" id="nafCodeSelect">
                    {foreach from=$code_naf item="naf"}
                        <option value="{$naf.code}">{$naf.code} - {$naf.description}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}
        <div class="form-group row">
            <div class="col-md-12 memeLigne">
                <span class="tva-label">Soumis à la TVA*</span>
                <select name="customer-tva" id="customer-tva" class="form-control" required>
                    <option value="0">Non</option>
                    <option value="1">Oui</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
                <input class="form-control pad20" placeholder="Numéro de TVA" type="text" name="customer-vat_number"
                       id="customer-vat_number">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12 memeLigne ">
                <span class="js-file-name">Kbis*</span>
                <label for="file-upload" class="custom-file-upload">
                    Choisir un fichier
                </label>
                <input style="opacity: 0; height: 0; width: 0;" id="file-upload" class="file-input js-file-input"
                       type="file" name="kbis" accept=".pdf,.jpg,.png">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12 memeLigne ">
                <span class="js-file-name js-file-name-destock">Relevé d'identité bancaire*</span>
                <span class="js-file-name js-file-name-mobile">RIB*</span>
                <label for="rib-upload" class="custom-file-upload">
                    Choisir un fichier
                </label>
                <input style="opacity: 0; height: 0; width: 0;" id="rib-upload" class="file-input js-file-input"
                       type="file" name="rib" accept=".pdf,.jpg,.png">
            </div>
        </div>
    {/if}
    <div class="form-group row">
        <div class="col-md-12 memeLigne">
            <label for="customer-pays" class="custom-customer-pays">
                Pays*
            </label>
            <select name="customer-pays" id="customer-pays" class="form-control pad20" required>
                {foreach from=$countries item='country'}
                    <option value="{$country.id_country|intval}">&nbsp;{$country.name|escape}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-12">
            <input class="form-control pad20" placeholder="Société*" type="text" name="customer-company"
                   id="customer-company" required>
        </div>
    </div>
    {if ckc_form_minimal == 1}
        <div class="form-group row">
            <div class="col-md-12">
                <input class="form-control pad20" placeholder="Ville*" type="text" name="customer-ville"
                       id="customer-ville" required>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
                <input class="form-control pad20" placeholder="Adresse*" type="text" name="customer-adresse"
                       id="customer-adresse" required>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
                <input class="form-control pad20" placeholder="Complément d'adresse" type="text" name="customer-adresse2"
                       id="customer-adresse2">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
                <input class="form-control pad20" placeholder="Code Postal*" type="text" name="customer-cp" id="customer-cp"
                       required>
            </div>
        </div>
        <div class="form-group row lastfield">
            <div class="col-md-12">
                <input class="form-control pad20" placeholder="Téléphone*" type="text" name="customer-tel" id="customer-tel"
                       required>
            </div>
        </div>
    {/if}
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<!-- Module ckc_espacepro -->