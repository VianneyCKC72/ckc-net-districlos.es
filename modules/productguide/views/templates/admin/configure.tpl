<div class="panel product-tab" id="product-guide-panel">
    <h3>
        <i class="icon-book"></i> Guide sur mesure
    </h3>
    <div class="form-wrapper">
        <div class="form-group">
            <label class="control-label col-lg-3">
                <span class="label-tooltip" data-toggle="tooltip"
                      title="Active l'affichage du guide sur la page produit">
                    Activer le guide
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="guide_active_custom" id="guide_active_on"
                           value="1" {if $guide_active}checked="checked"{/if}>
                    <label for="guide_active_on">Oui</label>
                    <input type="radio" name="guide_active_custom" id="guide_active_off"
                           value="0" {if !$guide_active}checked="checked"{/if}>
                    <label for="guide_active_off">Non</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>

        <div class="form-group" id="guide-content-wrapper">
            <label class="control-label col-lg-3">
                Contenu du guide
            </label>
            <div class="col-lg-9">
                <textarea name="guide_content_custom" id="guide_content_custom"
                          class="rte autoload_rte" rows="10">{$guide_content|escape:'html':'UTF-8'}</textarea>
                <p class="help-block">
                    Rédigez votre guide avec du texte et des images.
                    Ce contenu sera affiché dans le panneau latéral sur la page produit.
                </p>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-9 col-lg-offset-3">
                <button type="button" id="save-guide-btn" class="btn btn-primary" data-id-product="{$id_product}">
                    <i class="icon-save"></i> Enregistrer le guide
                </button>
                <span id="guide-save-message" style="margin-left: 10px;"></span>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="guide_active" id="guide_active_hidden" value="{$guide_active}">
<input type="hidden" name="guide_content" id="guide_content_hidden" value="{$guide_content|escape:'html':'UTF-8'}">

<style>
    #product-guide-panel {
        margin-top: 20px;
    }
    #guide-content-wrapper {
        display: none;
    }
    #guide_active_on:checked ~ * #guide-content-wrapper {
        display: block;
    }
</style>

<script>
    $(document).ready(function() {
        // Afficher/masquer le contenu selon la checkbox
        $('input[name="guide_active_custom"]').change(function() {
            if ($('#guide_active_on').is(':checked')) {
                $('#guide-content-wrapper').slideDown();
            } else {
                $('#guide-content-wrapper').slideUp();
            }
        });

        // Initialiser l'affichage
        if ($('#guide_active_on').is(':checked')) {
            $('#guide-content-wrapper').show();
        }

        // Gestion du bouton Enregistrer
        $('#save-guide-btn').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $message = $('#guide-save-message');
            var idProduct = $btn.data('id-product');

            // Récupérer les valeurs
            var active = $('input[name="guide_active_custom"]:checked').val();
            var content = '';

            // Récupérer le contenu du TinyMCE
            if (typeof tinyMCE !== 'undefined') {
                var editor = tinyMCE.get('guide_content_custom');
                if (editor) {
                    content = editor.getContent();
                } else {
                    content = $('#guide_content_custom').val();
                }
            } else {
                content = $('#guide_content_custom').val();
            }

            // Désactiver le bouton
            $btn.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Enregistrement...');
            $message.html('');

            // Appel AJAX
            $.ajax({
                url: '{$link->getAdminLink("AdminModules", true)}&configure=productguide&ajax=1',
                type: 'POST',
                data: {
                    action: 'saveGuide',
                    id_product: idProduct,
                    guide_active: active,
                    guide_content: content
                },
                success: function(response) {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;

                    if (data.success) {
                        $message.html('<span style="color: green;"><i class="icon-check"></i> Guide enregistré avec succès !</span>');
                    } else {
                        $message.html('<span style="color: red;"><i class="icon-remove"></i> Erreur: ' + (data.message || 'Erreur inconnue') + '</span>');
                    }

                    // Réactiver le bouton
                    $btn.prop('disabled', false).html('<i class="icon-save"></i> Enregistrer le guide');

                    // Effacer le message après 3 secondes
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $(this).html('').show();
                        });
                    }, 3000);
                },
                error: function() {
                    $message.html('<span style="color: red;"><i class="icon-remove"></i> Erreur de communication avec le serveur</span>');
                    $btn.prop('disabled', false).html('<i class="icon-save"></i> Enregistrer le guide');
                }
            });
        });
    });
</script>

