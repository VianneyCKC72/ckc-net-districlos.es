{**
 * Category Filter Wizard — Back-office
 * 1) Liste des filtres par catégorie (lignes cliquables "Filtre catégorie - id-slug")
 * 2) Sélection d'une catégorie pour créer un nouveau filtre
 * 3) Au clic sur une ligne → formulaire de configuration du filtre
 **}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-magic"></i>
    {l s='Category Filter Wizard' mod='dtc_categoryfilterwizard'}
  </div>

  {* ---------- Étape 1 : Liste + Ajout d'un filtre ---------- *}
  <div class="panel-body">
    <h4>{l s='Filtres par catégorie' mod='dtc_categoryfilterwizard'}</h4>

    {* Créer un nouveau filtre : sélection catégorie *}
    <form method="post" action="{$wizard_form_action|escape:'htmlall':'UTF-8'}" class="form-inline well" style="margin-bottom:20px">
      <input type="hidden" name="add_dtc_wizard" value="1">
      <label class="control-label" style="margin-right:8px">{l s='Ajouter un filtre pour la catégorie :' mod='dtc_categoryfilterwizard'}</label>

      {* Recherche autocomplétée (évite un select gigantesque) *}
      <input type="hidden" name="id_category" id="dtc-add-cat-id" value="">
      <input
        type="text"
        id="dtc-add-cat-search"
        class="form-control fixed-width-xxl"
        placeholder="{l s='Rechercher… (2+ caractères)' mod='dtc_categoryfilterwizard'}"
        style="margin-right:10px"
        autocomplete="off"
        required
      >
      <div id="dtc-add-cat-results"
           class="list-group"
           style="position:fixed;z-index:1000;max-width:520px;display:none;max-height:240px;overflow:auto">
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="icon-plus"></i> {l s='Créer le filtre' mod='dtc_categoryfilterwizard'}
      </button>
    </form>

    {* Liste des configs : lignes cliquables "Filtre catégorie - id-slug" *}
    {if $wizard_configs}
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>{l s='Filtre catégorie' mod='dtc_categoryfilterwizard'}</th>
            <th style="width:80px">{l s='Étapes' mod='dtc_categoryfilterwizard'}</th>
            <th style="width:80px">{l s='Active' mod='dtc_categoryfilterwizard'}</th>
            <th style="width:120px">{l s='Actions' mod='dtc_categoryfilterwizard'}</th>
          </tr>
        </thead>
        <tbody>
          {foreach $wizard_configs as $cfg}
            {assign var="cfg_slug" value=$cfg.cat_link_rewrite|default:''}
            <tr class="dtc-config-row" data-href="{$wizard_list_url|escape:'htmlall':'UTF-8'}&amp;edit_id_category={$cfg.id_category|intval}">
              <td>
                <a href="{$wizard_list_url|escape:'htmlall':'UTF-8'}&amp;edit_id_category={$cfg.id_category|intval}" class="dtc-config-link">
                  <i class="icon-filter"></i> {l s='Filtre catégorie' mod='dtc_categoryfilterwizard'} — {$cfg.id_category|intval}{if $cfg_slug}-{$cfg_slug|escape:'htmlall':'UTF-8'}{/if}
                </a>
              </td>
              <td>{$cfg.steps|count}</td>
              <td>
                {if $cfg.active}
                  <span class="label label-success">{l s='Oui' mod='dtc_categoryfilterwizard'}</span>
                {else}
                  <span class="label label-default">{l s='Non' mod='dtc_categoryfilterwizard'}</span>
                {/if}
              </td>
              <td>
                <form method="post" action="{$wizard_list_url|escape:'htmlall':'UTF-8'}" onsubmit="return confirm('{l s='Supprimer ce filtre ?' mod='dtc_categoryfilterwizard'}');">
                  <input type="hidden" name="delete_id_category" value="{$cfg.id_category|intval}">
                  <button
                    type="submit"
                    name="delete_dtc_wizard"
                    value="1"
                    class="btn btn-danger btn-sm"
                    onclick="event.stopPropagation();"
                    title="{l s='Supprimer' mod='dtc_categoryfilterwizard'}">
                    <i class="icon-trash"></i> {l s='Supprimer' mod='dtc_categoryfilterwizard'}
                  </button>
                </form>
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    {else}
      <p class="help-block">{l s='Aucun filtre configuré. Sélectionnez une catégorie ci-dessus et cliquez sur "Créer le filtre".' mod='dtc_categoryfilterwizard'}</p>
    {/if}
  </div>
</div>

{* ---------- Étape 2 : Formulaire de configuration (affiché quand on édite une catégorie) ---------- *}
{if $wizard_edit_id_cat && $wizard_edit_config}
  {assign var="edit_cat" value=$wizard_edit_config}
  <div class="panel panel-default" id="dtc-edit-panel">
    <div class="panel-heading">
      <a href="{$wizard_list_url|escape:'htmlall':'UTF-8'}" class="btn btn-default btn-sm pull-right">
        <i class="icon-arrow-left"></i> {l s='Retour à la liste' mod='dtc_categoryfilterwizard'}
      </a>
      <i class="icon-pencil"></i>
      {l s='Configuration du filtre' mod='dtc_categoryfilterwizard'} — {$edit_cat.id_category|intval}-{$edit_cat.cat_link_rewrite|escape:'htmlall':'UTF-8'}
    </div>
    <div class="panel-body">
      <form method="post" action="{$wizard_form_action|escape:'htmlall':'UTF-8'}&amp;edit_id_category={$wizard_edit_id_cat|intval}" id="dtc-wizard-form">
        <input type="hidden" name="submit_dtc_wizard" value="1">
        <input type="hidden" name="id_category" value="{$wizard_edit_id_cat|intval}">

        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Wizard actif' mod='dtc_categoryfilterwizard'}</label>
          <div class="col-lg-6">
            <span class="switch prestashop-switch fixed-width-lg">
              <input type="radio" name="active" id="active_on" value="1" {if $edit_cat.active}checked{/if}>
              <label for="active_on">{l s='Oui' mod='dtc_categoryfilterwizard'}</label>
              <input type="radio" name="active" id="active_off" value="0" {if !$edit_cat.active}checked{/if}>
              <label for="active_off">{l s='Non' mod='dtc_categoryfilterwizard'}</label>
              <a class="slide-button btn"></a>
            </span>
          </div>
        </div>

        <hr>
        <h5>{l s='Étapes du wizard' mod='dtc_categoryfilterwizard'}</h5>
        <p class="help-block">{l s='Chaque étape pose une question basée sur une caractéristique ou un groupe d\'attributs.' mod='dtc_categoryfilterwizard'}</p>

        <div id="dtc-steps-container">
          {if $edit_cat.steps}
            {foreach $edit_cat.steps as $step}
              <div class="dtc-step-row row" style="margin-bottom:10px">
                <div class="col-lg-1">
                  <input type="number" name="step_position[]" value="{$step.position|intval}" class="form-control" style="width:60px" min="0">
                </div>
                <div class="col-lg-10">
                  <input type="text" name="step_label[]" class="form-control" value="{$step.label|escape:'htmlall':'UTF-8'}"
                         placeholder="{l s='Question (ex: Quel matériau ?)' mod='dtc_categoryfilterwizard'}">
                  <textarea
                    name="step_custom_options_json[]"
                    class="form-control dtc-step-options-json"
                    style="display:none">{if isset($step.custom_options_json)}{$step.custom_options_json|escape:'htmlall':'UTF-8'}{/if}</textarea>

                  <div class="dtc-step-options-editor" style="margin-top:10px">
                    <div class="dtc-step-options-list"></div>
                    <button type="button" class="btn btn-default btn-xs dtc-add-step-option" style="margin-top:8px">
                      <i class="icon-plus"></i> {l s='Ajouter une option' mod='dtc_categoryfilterwizard'}
                    </button>
                  </div>
                </div>
                <div class="col-lg-1">
                  <button type="button" class="btn btn-danger btn-sm dtc-remove-step"><i class="icon-trash"></i></button>
                </div>
              </div>
            {/foreach}
          {else}
            <div class="dtc-step-row row" style="margin-bottom:10px">
              <div class="col-lg-1">
                <input type="number" name="step_position[]" value="0" class="form-control" style="width:60px" min="0">
              </div>
              <div class="col-lg-10">
                <input type="text" name="step_label[]" class="form-control"
                       placeholder="{l s='Question (ex: Quel matériau ?)' mod='dtc_categoryfilterwizard'}">
                <textarea
                  name="step_custom_options_json[]"
                  class="form-control dtc-step-options-json"
                  style="display:none"></textarea>

                <div class="dtc-step-options-editor" style="margin-top:10px">
                  <div class="dtc-step-options-list"></div>
                  <button type="button" class="btn btn-default btn-xs dtc-add-step-option" style="margin-top:8px">
                    <i class="icon-plus"></i> {l s='Ajouter une option' mod='dtc_categoryfilterwizard'}
                  </button>
                </div>
              </div>
              <div class="col-lg-1">
                <button type="button" class="btn btn-danger btn-sm dtc-remove-step"><i class="icon-trash"></i></button>
              </div>
            </div>
          {/if}
        </div>

        <button type="button" id="dtc-add-step" class="btn btn-default btn-sm" style="margin-top:10px">
          <i class="icon-plus"></i> {l s='Ajouter une étape' mod='dtc_categoryfilterwizard'}
        </button>

        <hr>
        <div class="panel-footer">
          <button type="submit" class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Enregistrer' mod='dtc_categoryfilterwizard'}
          </button>
        </div>
      </form>
    </div>
  </div>

  {* Template de ligne d'étape pour le JS (clone) *}
  <script type="text/template" id="dtc-step-row-tpl">
    <div class="dtc-step-row row" style="margin-bottom:10px">
      <div class="col-lg-1">
        <input type="number" name="step_position[]" value="0" class="form-control" style="width:60px" min="0">
      </div>
      <div class="col-lg-10">
        <input type="text" name="step_label[]" class="form-control" placeholder="{l s='Question (ex: Quel matériau ?)' mod='dtc_categoryfilterwizard'}">
        <textarea
          name="step_custom_options_json[]"
          class="form-control dtc-step-options-json"
          style="display:none"></textarea>

        <div class="dtc-step-options-editor" style="margin-top:10px">
          <div class="dtc-step-options-list"></div>
          <button type="button" class="btn btn-default btn-xs dtc-add-step-option" style="margin-top:8px">
            <i class="icon-plus"></i> {l s='Ajouter une option' mod='dtc_categoryfilterwizard'}
          </button>
        </div>
      </div>
      <div class="col-lg-1">
        <button type="button" class="btn btn-danger btn-sm dtc-remove-step"><i class="icon-trash"></i></button>
      </div>
    </div>
  </script>

  <script>
  (function() {
    var container = document.getElementById('dtc-steps-container');
    var tpl = document.getElementById('dtc-step-row-tpl');
    if (!container || !tpl) return;

    document.getElementById('dtc-add-step').addEventListener('click', function() {
      var row = tpl.textContent ? tpl.textContent : tpl.innerText;
      var div = document.createElement('div');
      div.innerHTML = row.trim();
      var newRow = div.firstElementChild;
      var idx = container.querySelectorAll('.dtc-step-row').length;
      newRow.querySelector('input[name="step_position[]"]').value = idx;
      container.appendChild(newRow);
      if (window.dtcInitStepOptionsEditor) {
        window.dtcInitStepOptionsEditor(newRow);
      }
    });

    container.addEventListener('click', function(e) {
      var btn = e.target.closest('.dtc-remove-step');
      if (!btn) return;
      var row = btn.closest('.dtc-step-row');
      if (container.querySelectorAll('.dtc-step-row').length > 1) row.remove();
    });
  })();
  </script>

  <script>
    (function () {
      var stepsContainer = document.getElementById('dtc-steps-container');
      if (!stepsContainer) return;

      function parseCustomOptions(raw) {
        var v = (raw || '').toString().trim();
        if (!v) return [];
        try {
          var decoded = JSON.parse(v);
          return Array.isArray(decoded) ? decoded : [];
        } catch (e) {
          return [];
        }
      }

      function parseProductIds(raw) {
        var v = (raw || '').toString().replace(/;/g, ',');
        var parts = v.split(',');
        var out = [];
        for (var i = 0; i < parts.length; i++) {
          var n = parseInt(parts[i].trim(), 10);
          if (n > 0) out.push(n);
        }
        // unique
        return Array.from(new Set(out));
      }

      function buildOptionRowHtml() {
        return '' +
          '<div class="dtc-step-option-row row" style="margin-top:8px">' +
            '<div class="col-lg-4">' +
              '<input type="text" class="form-control dtc-option-title" placeholder="Titre de l option" />' +
            '</div>' +
            '<div class="col-lg-4">' +
              '<input type="file" class="form-control dtc-option-file" accept="image/*" />' +
              '<input type="text" class="form-control dtc-option-image" placeholder="URL auto après upload" readonly style="margin-top:6px" />' +
            '</div>' +
            '<div class="col-lg-3">' +
              '<input type="text" class="form-control dtc-option-product-ids" placeholder="IDs produits (1,2,3)" />' +
            '</div>' +
            '<div class="col-lg-1">' +
              '<button type="button" class="btn btn-danger btn-xs dtc-remove-step-option" title="Supprimer">' +
                '<i class="icon-trash"></i>' +
              '</button>' +
            '</div>' +
          '</div>';
      }

      function rebuildJson(stepRow) {
        var textarea = stepRow.querySelector('textarea.dtc-step-options-json');
        var list = stepRow.querySelector('.dtc-step-options-list');
        if (!textarea || !list) return;

        var options = [];
        list.querySelectorAll('.dtc-step-option-row').forEach(function (optRow) {
          var title = (optRow.querySelector('.dtc-option-title') || {}).value;
          title = (title || '').toString().trim();
          if (!title) return;

          var imageUrl = (optRow.querySelector('.dtc-option-image') || {}).value;
          imageUrl = (imageUrl || '').toString().trim();

          var productIdsRaw = (optRow.querySelector('.dtc-option-product-ids') || {}).value;
          var productIds = parseProductIds(productIdsRaw);

          options.push({
            title: title,
            image_url: imageUrl,
            product_ids: productIds,
          });
        });

        textarea.value = options.length ? JSON.stringify(options) : '';
      }

      function renderOptions(stepRow, options) {
        var list = stepRow.querySelector('.dtc-step-options-list');
        if (!list) return;

        list.innerHTML = '';
        if (!options || !options.length) return;

        options.forEach(function (opt) {
          var row = document.createElement('div');
          row.innerHTML = buildOptionRowHtml();
          var optRow = row.firstElementChild;

          if (opt && typeof opt === 'object') {
            var titleInput = optRow.querySelector('.dtc-option-title');
            var imgInput = optRow.querySelector('.dtc-option-image');
            var idsInput = optRow.querySelector('.dtc-option-product-ids');

            if (titleInput && opt.title !== undefined) titleInput.value = String(opt.title);
            if (imgInput && opt.image_url !== undefined) imgInput.value = String(opt.image_url);
            if (idsInput && Array.isArray(opt.product_ids)) idsInput.value = opt.product_ids.join(',');
          }

          list.appendChild(optRow);
        });
      }

      window.dtcInitStepOptionsEditor = function (stepRow) {
        if (!stepRow) return;
        if (stepRow.dataset.dtcOptionsInited === '1') return;
        stepRow.dataset.dtcOptionsInited = '1';

        var textarea = stepRow.querySelector('textarea.dtc-step-options-json');
        if (!textarea) return;

        var options = parseCustomOptions(textarea.value);
        renderOptions(stepRow, options);
        rebuildJson(stepRow);
      };

      // init existing step rows
      stepsContainer.querySelectorAll('.dtc-step-row').forEach(function (stepRow) {
        window.dtcInitStepOptionsEditor(stepRow);
      });

      // Delegation for click: add/remove option
      stepsContainer.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.dtc-add-step-option');
        if (addBtn) {
          var stepRow = addBtn.closest('.dtc-step-row');
          if (!stepRow) return;
          var list = stepRow.querySelector('.dtc-step-options-list');
          if (!list) return;

          var wrapper = document.createElement('div');
          wrapper.innerHTML = buildOptionRowHtml();
          list.appendChild(wrapper.firstElementChild);

          rebuildJson(stepRow);
          return;
        }

        var remBtn = e.target.closest('.dtc-remove-step-option');
        if (remBtn) {
          var stepRow2 = remBtn.closest('.dtc-step-row');
          var optRow = remBtn.closest('.dtc-step-option-row');
          if (optRow && stepRow2) optRow.remove();
          if (stepRow2) rebuildJson(stepRow2);
        }
      });

      // Upload image per option (back-office)
      var uploadUrl = {$wizard_upload_image_url|json_encode};
      var uploadToken = {$wizard_upload_image_token|json_encode};
      if (uploadUrl) {
        stepsContainer.addEventListener('change', function (e) {
          var fileInput = e.target.closest('.dtc-option-file');
          if (!fileInput) return;

          var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
          if (!file) return;

          var optionRow = fileInput.closest('.dtc-step-option-row');
          var imageInput = optionRow ? optionRow.querySelector('.dtc-option-image') : null;
          if (!imageInput) return;

          var formData = new FormData();
          formData.append('dtc_token', uploadToken);
          formData.append('image', file, file.name);

          // Reset avant upload (pour forcer un changement visuel)
          imageInput.value = '';

          fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data || !data.ok || !data.url) {
                alert((data && data.error) ? data.error : 'Upload image failed');
                return;
              }
              imageInput.value = String(data.url);
              rebuildJson(optionRow.closest('.dtc-step-row'));
            })
            .catch(function () {
              alert('Upload image failed');
            });
        });
      }

      // Rebuild JSON on any input inside options
      stepsContainer.addEventListener('input', function (e) {
        var stepRow = e.target.closest('.dtc-step-row');
        if (!stepRow) return;
        if (!e.target.closest('.dtc-step-options-editor')) return;

        if (stepRow._dtcRebuildTimer) clearTimeout(stepRow._dtcRebuildTimer);
        stepRow._dtcRebuildTimer = setTimeout(function () {
          rebuildJson(stepRow);
        }, 150);
      });
    })();
  </script>
{/if}

<style>
.dtc-config-row .dtc-config-link { font-weight: 500; text-decoration: none; }
.dtc-config-row { cursor: pointer; }
.dtc-config-row:hover { background-color: #f5f5f5; }
#dtc-edit-panel .panel-footer { background: #fff; border-top: 1px solid #ddd; }
</style>
<script>
document.querySelectorAll('.dtc-config-row').forEach(function(row) {
  var href = row.getAttribute('data-href');
  if (href) row.addEventListener('click', function(e) { if (!e.target.closest('a')) window.location = href; });
});
</script>

<script>
(function () {
  var searchInput  = document.getElementById('dtc-add-cat-search');
  var hiddenId     = document.getElementById('dtc-add-cat-id');
  var resultsBox   = document.getElementById('dtc-add-cat-results');
  // JSON-encode pour éviter que `&` devienne `&amp;` (sinon fetch ne marche pas)
  var ajaxUrl      = {$wizard_category_search_url|json_encode};
  var dtcToken     = {$wizard_category_search_token|json_encode};

  if (!searchInput || !hiddenId || !resultsBox || !ajaxUrl) return;

  var timer = null;

  function hideResults() {
    resultsBox.style.display = 'none';
    resultsBox.innerHTML = '';
  }

  function renderResults(items) {
    resultsBox.innerHTML = '';
    items.forEach(function (it) {
      var a = document.createElement('a');
      a.href = '#';
      a.className = 'list-group-item';
      a.textContent = it.label;
      a.addEventListener('click', function (e) {
        e.preventDefault();
        hiddenId.value = String(it.id_category);
        searchInput.value = it.label;
        hideResults();
      });
      resultsBox.appendChild(a);
    });
    resultsBox.style.display = items && items.length ? 'block' : 'none';
  }

  function positionResults() {
    // Aligne la liste sous l'input.
    // `position: fixed` + coordonnées viewport => robuste dans l'admin (modales/panels).
    var rect = searchInput.getBoundingClientRect();
    resultsBox.style.position = 'fixed';
    resultsBox.style.left = rect.left + 'px';
    resultsBox.style.top = rect.bottom + 'px';
    resultsBox.style.width = rect.width + 'px';
  }

  searchInput.addEventListener('input', function () {
    hiddenId.value = '';
    var q = searchInput.value || '';
    if (timer) clearTimeout(timer);
    hideResults();

    if (q.length < 2) return;

    timer = setTimeout(function () {
      positionResults();

      var join = ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
      var url = ajaxUrl + join
        + 'q=' + encodeURIComponent(q)
        + '&limit=15'
        + '&dtc_token=' + encodeURIComponent(dtcToken);
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data) {
            hideResults();
            return;
          }
          if (data.error) {
            console.error('[dtc_categoryfilterwizard] categorysearch error:', data.error);
            hideResults();
            return;
          }
          renderResults(data.results || []);
        })
        .catch(function () {
          hideResults();
        });
    }, 250);
  });

  document.addEventListener('click', function (e) {
    if (e.target !== searchInput && !resultsBox.contains(e.target)) {
      hideResults();
    }
  });

  // Empêche la soumission si aucun id n'a été choisi
  var form = searchInput.closest('form');
  if (form) {
    form.addEventListener('submit', function () {
      if (!hiddenId.value) {
        e.preventDefault();
        alert('Veuillez sélectionner une catégorie dans la liste.');
      }
    });
  }
})();
</script>
