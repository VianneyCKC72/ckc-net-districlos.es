<div id="ap5-pack-image-colors" class="panel mb-4"
     data-save-url="{$ap5_pack_image_colors_save_url|escape:'html':'UTF-8'}"
     data-id-pack="{$ap5_pack_id|intval}">
  <div class="panel-heading">
    <i class="material-icons">palette</i>
    {l s='Images par couleur (pack)' mod='pm_advancedpack'}
  </div>
  <p class="help-block">
    {l s='1) Cliquez une couleur - 2) Cliquez les miniatures à y associer - 3) Enregistrez. Les images non associées restent visibles pour toutes les couleurs.' mod='pm_advancedpack'}
  </p>

  {if !$ap5_pack_colors|@count}
    <div class="alert alert-warning">
      {l s='Aucune couleur (attribut) trouvée dans les produits du pack.' mod='pm_advancedpack'}
    </div>
  {elseif !$ap5_pack_images|@count}
    <div class="alert alert-info">
      {l s='Ajoutez d’abord des images au pack ci-dessus.' mod='pm_advancedpack'}
    </div>
  {else}
    <div class="ap5-pic-colors mb-3" role="tablist">
      {foreach from=$ap5_pack_colors key=idAttr item=color name=ap5colors}
        <button type="button"
                class="btn btn-outline-secondary ap5-pic-color-btn{if $smarty.foreach.ap5colors.first} active{/if}"
                data-id-attribute="{$idAttr|intval}"
                title="{$color.name|escape:'html':'UTF-8'}">
          {if $color.hex}
            <span class="ap5-pic-swatch" style="background:{$color.hex|escape:'html':'UTF-8'};"></span>
          {/if}
          {$color.name|escape:'html':'UTF-8'}
        </button>
      {/foreach}
    </div>

    <p class="text-muted ap5-pic-hint mb-2">
      {l s='Couleur active :' mod='pm_advancedpack'}
      <strong class="ap5-pic-active-name"></strong>
    </p>

    <div class="ap5-pic-grid">
      {foreach from=$ap5_pack_images item=img}
        {assign var=idImg value=$img.id_image|intval}
        {assign var=bound value=0}
        {if isset($ap5_pack_image_color_map[$idImg])}{assign var=bound value=$ap5_pack_image_color_map[$idImg]|intval}{/if}
        <button type="button"
                class="ap5-pic-thumb"
                data-id-image="{$idImg}"
                data-id-attribute="{$bound}"
                title="{l s='Cliquer pour associer / retirer' mod='pm_advancedpack'}">
          <img src="{$img.thumb_url|escape:'html':'UTF-8'}" alt="" />
          <span class="ap5-pic-check"><i class="material-icons">check</i></span>
          <span class="ap5-pic-bound-label"></span>
        </button>
      {/foreach}
    </div>

    <div class="mt-3">
      <button type="button" class="btn btn-primary" id="ap5-pack-image-colors-save">
        <i class="material-icons">save</i>
        {l s='Enregistrer les associations' mod='pm_advancedpack'}
      </button>
      <span id="ap5-pack-image-colors-status" class="ml-2" style="display:none;"></span>
    </div>
  {/if}
</div>

<style>
#ap5-pack-image-colors .ap5-pic-colors { display:flex; flex-wrap:wrap; gap:8px; }
#ap5-pack-image-colors .ap5-pic-color-btn {
  display:inline-flex; align-items:center; gap:8px;
  border-radius:20px; padding:6px 14px;
}
#ap5-pack-image-colors .ap5-pic-color-btn.active {
  background:#25b9d7; border-color:#25b9d7; color:#fff;
}
#ap5-pack-image-colors .ap5-pic-swatch {
  width:16px; height:16px; border-radius:50%;
  border:1px solid rgba(0,0,0,.25); display:inline-block;
}
#ap5-pack-image-colors .ap5-pic-grid {
  display:flex; flex-wrap:wrap; gap:10px;
}
#ap5-pack-image-colors .ap5-pic-thumb {
  position:relative; width:84px; height:84px; padding:0;
  border:2px solid #ddd; border-radius:8px; overflow:hidden;
  background:#f5f5f5; cursor:pointer;
}
#ap5-pack-image-colors .ap5-pic-thumb img {
  width:100%; height:100%; object-fit:cover; display:block;
}
#ap5-pack-image-colors .ap5-pic-thumb.is-selected {
  border-color:#25b9d7; box-shadow:0 0 0 2px rgba(37,185,215,.35);
}
#ap5-pack-image-colors .ap5-pic-thumb.is-selected .ap5-pic-check { display:flex; }
#ap5-pack-image-colors .ap5-pic-thumb.is-other { opacity:.45; }
#ap5-pack-image-colors .ap5-pic-check {
  display:none; position:absolute; top:4px; right:4px;
  width:22px; height:22px; border-radius:50%; background:#25b9d7; color:#fff;
  align-items:center; justify-content:center;
}
#ap5-pack-image-colors .ap5-pic-check .material-icons { font-size:16px; line-height:1; }
#ap5-pack-image-colors .ap5-pic-bound-label {
  position:absolute; left:0; right:0; bottom:0;
  background:rgba(0,0,0,.65); color:#fff; font-size:10px;
  text-align:center; padding:2px 4px; line-height:1.2;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
#ap5-pack-image-colors .ap5-pic-bound-label:empty { display:none; }
</style>

<script type="text/javascript">
(function () {
  function placeBlock() {
    var $block = $('#ap5-pack-image-colors');
    var $images = $('#product-images-container, .image-dropzone').first();
    if ($block.length && $images.length && !$block.data('placed')) {
      $block.insertAfter($images).data('placed', 1);
    }
  }

  function colorNames() {
    var map = {};
    $('#ap5-pack-image-colors .ap5-pic-color-btn').each(function () {
      map[String($(this).data('id-attribute'))] = $.trim($(this).text());
    });
    return map;
  }

  function activeColorId() {
    return parseInt($('#ap5-pack-image-colors .ap5-pic-color-btn.active').data('id-attribute'), 10) || 0;
  }

  function refreshUi() {
    var active = activeColorId();
    var names = colorNames();
    $('.ap5-pic-active-name').text(names[String(active)] || '');
    $('#ap5-pack-image-colors .ap5-pic-thumb').each(function () {
      var $t = $(this);
      var bound = parseInt($t.attr('data-id-attribute'), 10) || 0;
      $t.toggleClass('is-selected', bound === active && active > 0);
      $t.toggleClass('is-other', bound > 0 && bound !== active);
      $t.find('.ap5-pic-bound-label').text(bound && names[String(bound)] ? names[String(bound)] : '');
    });
  }

  $(document).ready(function () {
    placeBlock();
    refreshUi();
  });
  setTimeout(placeBlock, 500);

  $(document).on('click', '#ap5-pack-image-colors .ap5-pic-color-btn', function () {
    $('#ap5-pack-image-colors .ap5-pic-color-btn').removeClass('active');
    $(this).addClass('active');
    refreshUi();
  });

  $(document).on('click', '#ap5-pack-image-colors .ap5-pic-thumb', function () {
    var active = activeColorId();
    if (!active) {
      return;
    }
    var $t = $(this);
    var bound = parseInt($t.attr('data-id-attribute'), 10) || 0;
    $t.attr('data-id-attribute', bound === active ? 0 : active);
    refreshUi();
  });

  $(document).on('click', '#ap5-pack-image-colors-save', function () {
    var $wrap = $('#ap5-pack-image-colors');
    var mappings = {};
    $wrap.find('.ap5-pic-thumb').each(function () {
      mappings[$(this).data('id-image')] = parseInt($(this).attr('data-id-attribute'), 10) || 0;
    });
    var $status = $('#ap5-pack-image-colors-status').show().text('…');
    $.ajax({
      url: $wrap.data('save-url'),
      type: 'POST',
      dataType: 'json',
      data: {
        ajax: 1,
        action: 'savePackImageColors',
        id_pack: $wrap.data('id-pack'),
        mappings: mappings
      }
    }).done(function (res) {
      $status.text(res && res.success ? 'OK' : (res && res.error ? res.error : 'Erreur'));
    }).fail(function () {
      $status.text('Erreur');
    });
  });
})();
</script>
