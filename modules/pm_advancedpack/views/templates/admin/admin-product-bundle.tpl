<div id="bundles-container" class="row mb-3">
    <div class="col-md-12">
        <h2>
            {l s='Bundles' mod='pm_advancedpack'}
        </h2>
    </div>
    <div class="col-md-12">
        <div id="bundles" class="mb-2">
            <a id="js-open-create-bundle-form" class="btn btn-outline-primary mb-3" data-toggle="collapse"
                href="#advancedpack-bundle-form" aria-expanded="false">
                <i class="material-icons">add_circle</i>
                {l s='Add bundle' mod='pm_advancedpack'}
            </a>
            {include file='./_partials/bundle/form.tpl'}
            {include file='./_partials/bundle/grid.tpl'}
        </div>
    </div>
</div>
