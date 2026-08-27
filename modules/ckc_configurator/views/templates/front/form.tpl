<div id="debug-container"></div>
<div class="configurator-container configurator-second">
    <div class="d-flex align-items-center">
        <span class="mr-4 font-weight-bold subtitle">{l s='Définissez vos besoins' d='Shop.Theme.Customconfigurator'}</span>
        <span class="font-weight-semibold">{l s='Obligatoire' d='Shop.Theme.Customconfigurator'}</span>
    </div>

    <div class="configurator-content">

        <form class="configurator-form core-form">
            <div class="form-title">{l s='Type de grillage' d='Shop.Theme.Customconfigurator'}</div>
            <div class="row row-items initial-m-p">
                {if isset($types)}
                    {foreach from=$types item=type}
                        <div class="col-xs-4">
                            <a href="#" data-id="{$type.id_category}" data-name="type"
                               class="{if isset($session->id_type) && $session->id_type == $type.id_category}selected {/if}configurator-action">
                                <img src="//{$link->getCatImageLink($type.link_rewrite, $type.id_image)|escape:'html':'UTF-8'}"
                                     class="img-responsive" alt="{$type.name}"/>
                                <div class="title-img">
                                    {$type.name}
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                        <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                    </svg>
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                        <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    {/foreach}
                {/if}
                <input type="hidden" name="type" value="{if isset($session->id_type)}{$session->id_type}{/if}"
                       class="form-control"/>
            </div>

            <div class="viewEspacement" style="display:none">
                <div class="form-title">{l s='Espacement des lames (entre 1cm et 3cm)' d='Shop.Theme.Customconfigurator'}
                </div>
                <input type="number" id="espacement"
                       min="1" max="3"
                       name="espacement" {if isset($session->espacement)} value="{$session->espacement}" {/if}
                />
            </div>

            <div class="form-title" style="margin-bottom: 15px;">{l s='Longueur (en mètres)' d='Shop.Theme.Customconfigurator'}</div>
            <div class="longueur-container input-group-configurator-text">
                <input type="text" id="longueur" class="form-control"
                       name="longueur" {if isset($session->length)} value="{$session->length}" {/if}
                       placeholder="{l s='Ex: 1.8, 2.0, 2.5' d='Shop.Theme.Customconfigurator'}"/>
            </div>

            <div class="form-title">{l s='Type de pose' d='Shop.Theme.Customconfigurator'}</div>
            <div class="row row-items initial-m-p">
                {if isset($installations)}
                    {foreach from=$installations item=installation}
                        <div class="col-md-4 col-xs-6">
                            <a href="#" data-id="{$installation.id}" data-name="pose"
                               class="pose-item {if isset($session->installation) && $session->installation == $installation.id}selected {/if}configurator-action">
                                <img src="/modules/ckc_configurator/views/images/installations/{$session->id_category}/{$installation.id}.png"
                                     class="img-responsive" alt="{$installation.name}"/>
                                <div class="title-img">
                                    {$installation.name}
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                        <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                    </svg>
                                    <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                        <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                        <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    {/foreach}
                {/if}
                <input type="hidden" name="pose" value="{if isset($session->installation)}{$session->installation}{/if}"
                       class="form-control"/>
            </div>

            <div id="heights">
                <div class="form-title">
                    {l s='Hauteur' d='Shop.Theme.Customconfigurator'}
                </div>
                <div class="row row-items initial-m-p height-mobile">
                    {if isset($heights)}
                        {foreach from=$heights item=height}
                            <div class="col-xs-6 col-xs-6 " style="margin-bottom: 40px">
                                <a href="#" data-id="{$height.value}" data-name="hauteur"
                                   class="item-height {if $height.checked == true}selected {/if}configurator-action configurator-action-xs">
                                    <img src="/modules/ckc_configurator/views/images/hauteurs/{$session->id_category}/{$height.name}.png"
                                         class="img-responsive" alt="{$height.name}"/>
                                    <div class="title-img">
                                        {$height.name}
                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                            <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                            <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                        </svg>
                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                            <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                            <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        {/foreach}
                    {/if}
                </div>
                <input type="hidden" name="hauteur" value="{if isset($session->height)}{$session->height}{/if}"
                       class="form-control"/>
            </div>

            {if isset($session->id_type) && $session->id_type == 177}
                <div id="poteaux">
                    <div class="form-title">
                        {l s='Type de poteau' d='Shop.Theme.Customconfigurator'}
                    </div>
                    <div class="row row-items initial-m-p">
                        {foreach from=$poteaux item=poteau}
                            <div class="col-xs-6" style="margin-bottom: 40px">
                                <a href="#" data-id="{$poteau.value}" data-name="poteaux"
                                   class="item-height {if $poteau.checked == true}selected {/if}configurator-action configurator-action-xs">
                                    <img src="/modules/ckc_configurator/views/images/type_poteaux/{$poteau.name}.jpg"
                                         class="img-responsive" alt="{$poteau.name}"/>
                                    <div class="title-img">
                                        {$poteau.name}
                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                            <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                            <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                        </svg>
                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                            <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                            <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        {/foreach}
                    </div>
                    <input type="hidden" name="poteaux" value="{if isset($session->poteaux)}{$session->poteaux}{/if}"
                           class="form-control"/>
                </div>
            {/if}

            <div id="colors">
                <div class="form-title">
                    {l s='Couleur' d='Shop.Theme.Customconfigurator'}
                </div>
                <div class="row row-items initial-m-p">
                    {if isset($colors)}
                        {foreach from=$colors item=color}
                            <div class="col-xs-6 col-xs-6 " style="margin-bottom: 40px">
                                <a href="#" data-id="{$color.value}" data-name="couleur"
                                   class="{if $color.checked == true}selected {/if}configurator-action configurator-action-xs">
                                    <img src="/modules/ckc_configurator/views/images/couleurs/{$color.name}.png"
                                         class="img-responsive" alt="{$color.name}"/>
                                    <div class="title-img">
                                        {$color.name|ucfirst}
                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-not-selected">
                                            <rect y="0.5" width="38" height="38" rx="10" fill="#0B798A"/>
                                            <path d="M24.8 21.125H20.075V25.975H16.775V21.125H12.05V18.075H16.775V13.225H20.075V18.075H24.8V21.125Z" fill="white"/>
                                        </svg>
                                        <svg width="38" height="39" viewBox="0 0 38 39" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-selected">
                                            <rect y="0.5" width="38" height="38" rx="10" fill="white"/>
                                            <path d="M25.5761 12.5007L25.576 12.5007C25.1976 12.5135 24.8533 12.6922 24.61 12.9746L15.9182 22.832L13.3948 19.9703C13.2719 19.8262 13.1217 19.7071 12.9508 19.6234C12.778 19.5387 12.5891 19.4926 12.3959 19.4904C12.2026 19.4882 12.0127 19.5298 11.838 19.6107C11.6636 19.6915 11.5096 19.8089 11.3831 19.9523C11.2567 20.0957 11.1596 20.2628 11.0949 20.4426C11.0302 20.6223 10.9984 20.8131 11.0001 21.0043C11.0018 21.1955 11.0371 21.3857 11.1051 21.5642C11.1725 21.741 11.2714 21.9047 11.3989 22.0446L14.9202 26.0381L14.9203 26.0382C15.173 26.3247 15.5302 26.5 15.9182 26.5C16.3061 26.5 16.6633 26.3247 16.9161 26.0382L16.9162 26.0381L26.606 15.049C26.7987 14.8354 26.9242 14.5683 26.9747 14.2853C27.0254 14.001 26.9997 13.7054 26.8988 13.435C26.7978 13.1645 26.6234 12.9246 26.3889 12.754C26.1533 12.5825 25.8698 12.4909 25.5761 12.5007Z" fill="#7CBA49" stroke="#7CBA49"/>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        {/foreach}
                    {/if}
                </div>
                <input type="hidden" name="couleur" value="{if isset($session->color)}{$session->color}{/if}"
                       class="form-control"/>
            </div>

            <div class="viewDepartMur">
                <div class="form-title">
                    {l s='Départ(s) du mur' d='Shop.Theme.Customconfigurator'}
                </div>
                <em><p style="font-size: 0.8rem">
                        {l s='Le nombre de fois où votre clôture commence ou termine sur un mur' d='Shop.Theme.Customconfigurator'}
                    </p></em>
                <div class="input-group-configurator-text">
                    <input type="number" id="depart_mur" class="form-control"
                           name="depart_mur" {if isset($session->depart_mur)} value="{$session->depart_mur}" {/if}
                           min="0"/>
                </div>

                <br>

            </div>
            <div class="viewNombreAngle">
                <div class="form-title">
                    {l s='Nombre d\'angle(s)' d='Shop.Theme.Customconfigurator'}
                </div>
                <div class="input-group-configurator-text">
                    <input type="number" id="nombre_angle" class="form-control"
                           name="nombre_angle" {if isset($session->nombre_angle)} value="{$session->nombre_angle}" {/if}
                           min="0"/>
                </div>
            </div>
            <br>
        </form>
    </div>
</div>
