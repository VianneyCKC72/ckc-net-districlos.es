{extends file='customer/page.tpl'}

{block name='page_title'}
    <h1>{l s='Mes réalisations' mod=$module_name}</h1>
{/block}

{block name='page_content'}
    <div class="small_container">
        <div class="row">

            {include file='customer/sidebar-account.tpl'}

            <div class="col-md-9">
                <div class="content_right ckc-real">

                    {*
                      Bandeau de messages (fallback non-AJAX)
                    *}
                    {if isset($smarty.get.ok)}
                        <div class="alert alert-success">
                            {l s='Photo ajoutée, en attente de validation.' mod=$module_name}
                        </div>
                    {/if}
                    {if isset($smarty.get.del)}
                        <div class="alert alert-success">
                            {l s='Photo supprimée.' mod=$module_name}
                        </div>
                    {/if}
                    {if isset($smarty.get.err)}
                        <div class="alert alert-danger">
                            {l s='Une erreur est survenue.' mod=$module_name}
                            {if $smarty.get.err} ({$smarty.get.err|escape:'html'}) {/if}
                        </div>
                    {/if}

                    {*
                      Zone de message pour l’AJAX (affiché/masqué par JS)
                    *}
                    <div id="real-msg" class="alert" style="display:none"></div>

                    {*
                      Upload
                    *}
                    <h2 class="ckc-h2">{l s='Ajouter une photo' mod=$module_name}</h2>
                    <div class="ckc-card real-upload ckc-upload mb-3">
                        <form id="real-upload-form"
                              method="post"
                              action="{$add_link|escape:'html'}"
                              enctype="multipart/form-data">
                            <input type="hidden" name="token" value="{$token|escape:'html'}"/>
                            <input type="hidden" name="submitReal" value="1"/>
                            <div class="form-group">
                                <label class="ckc-label">{l s='Photo (JPEG/PNG ≤ 5MB)' mod=$module_name}</label>
                                <input class="ckc-file form-control" type="file" name="file" accept="image/*" required/>
                            </div>
                            <button class="ckc-btn-primary" type="submit" id="real-btn">
                                {l s='Ajouter' mod=$module_name}
                            </button>
                        </form>
                    </div>

                    {*
                      Galerie
                    *}
                    <h2 class="ckc-h2">{l s='Mes photos' mod=$module_name}</h2>

                    {if isset($rows) && $rows}
                        <div id="real-grid" class="real-grid">
                            {foreach from=$rows item=r}
                                <article class="real-card" id="real-item-{$r.id_realisation|intval}">
                                    <img class="real-thumb"
                                         src="{$file_base_url|escape:'html'}thumb_{$r.filename|escape:'url'}"
                                         alt=""/>

                                    <div class="real-actions">
                                        <form method="post"
                                              action="{$del_link|escape:'html'}"
                                              class="js-real-del"
                                              data-id="{$r.id_realisation|intval}">
                                            <input type="hidden" name="token" value="{$token|escape:'html'}"/>
                                            <input type="hidden" name="id_realisation"
                                                   value="{$r.id_realisation|intval}"/>
                                            <button class="btn btn-outline-danger btn-sm" type="submit"
                                                    title="{l s='Supprimer' mod=$module_name}">
                                                {* ton SVG poubelle *}
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                     fill="none" stroke="currentColor" stroke-width="2"
                                                     stroke-linecap="round" stroke-linejoin="round"
                                                     class="lucide lucide-trash-2">
                                                    <path d="M10 11v6"/>
                                                    <path d="M14 11v6"/>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                                    <path d="M3 6h18"/>
                                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                            </button>
                                        </form>

                                    </div>

                                    <div class="real-meta">
                    <span class="real-badge
                      {if $r.status=='approved'}real-badge--approved
                      {elseif $r.status=='rejected'}real-badge--rejected
                      {else}real-badge--pending{/if}">
                      {if $r.status == 'approved'}
                          Mise en ligne
                      {elseif $r.status=='rejected'}
                          Rejetée
                      {elseif $r.status=='pending'}
                          En attente de validation
                      {/if}
                    </span>
                                    </div>
                                </article>
                            {/foreach}
                        </div>
                    {else}
                        <div id="real-grid" class="real-grid"></div>
                        <div class="alert alert-info">
                            {l s='Vous n’avez pas encore ajouté de photo.' mod=$module_name}
                        </div>
                    {/if}

                </div>
            </div>
        </div>
    </div>
{literal}
    <script type="text/javascript">

        /*        document.addEventListener('DOMContentLoaded', function () {
                    var form = document.getElementById('real-upload-form');
                    var btn = document.getElementById('real-btn');
                    var msg = document.getElementById('real-msg');
                    var grid = document.getElementById('real-grid');

                    function showMsg(text, type) {
                        // type: 'success' | 'danger' | 'info'
                        if (!msg) return;
                        msg.classList.remove('alert-success', 'alert-danger', 'alert-info');
                        msg.classList.add('alert-' + (type || 'info'));
                        msg.textContent = text;
                        msg.style.display = 'block';

                        // petit fade-out simple
                        setTimeout(function () {
                            msg.style.transition = 'opacity .3s ease';
                            msg.style.opacity = '0';
                            setTimeout(function () {
                                msg.style.display = 'none';
                                msg.style.opacity = '1';
                                msg.style.transition = '';
                            }, 300);
                        }, 3000);
                    }

                    if (!form) return;

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();

                        var fd = new FormData(form);
                        fd.append('ajax', '1'); // dit au contrôleur de répondre en JSON

                        if (btn) btn.disabled = true;

                        fetch(form.getAttribute('action'), {
                            method: 'POST',
                            body: fd,
                            headers: {'X-Requested-With': 'XMLHttpRequest'}
                        })
                            .then(function (r) {
                                return r.json();
                            })
                            .then(function (res) {
                                if (res && res.ok) {
                                    if (grid) {
                                        var wrapper = document.createElement('div');
                                        wrapper.className = 'col-md-3 col-sm-4 col-xs-6 mb-3';
                                        wrapper.id = 'real-item-' + res.item.id;

                                        wrapper.innerHTML =
                                            '<div class="card">' +
                                            '<img class="img-fluid" src="' + res.item.thumb + '" alt="">' +
                                            '<div class="card-body small"><strong>' + res.item.status + '</strong></div>' +
                                            '</div>';

                                        // prepend
                                        if (grid.firstChild) {
                                            grid.insertBefore(wrapper, grid.firstChild);
                                        } else {
                                            grid.appendChild(wrapper);
                                        }
                                    }
                                    form.reset();
                                    showMsg(res.msg || 'Photo ajoutée.', 'success');
                                } else {
                                    showMsg((res && res.msg) ? res.msg : 'Échec de l’envoi.', 'danger');
                                }
                            })
                            .catch(function () {
                                showMsg('Erreur réseau pendant l’envoi.', 'danger');
                            })
                            .finally(function () {
                                if (btn) btn.disabled = false;
                            });
                    });
                });*/

        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('real-upload-form');
            var btn = document.getElementById('real-btn');
            var msg = document.getElementById('real-msg');
            var grid = document.getElementById('real-grid');

            function showMsg(text, type) {
                if (!msg) return;
                msg.classList.remove('alert-success', 'alert-danger', 'alert-info');
                msg.classList.add('alert', 'alert-' + (type || 'info'));
                msg.textContent = text;
                msg.style.display = '';
                clearTimeout(msg._t);
                msg._t = setTimeout(function () {
                    msg.style.display = 'none';
                }, 2500);
            }

            // -------- Upload AJAX --------
            if (form && btn) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var fd = new FormData(form);
                    fd.append('ajax', '1');

                    btn.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        body: fd,
                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    })
                        .then(function (r) {
                            return r.json();
                        })
                        .then(function (res) {
                            if (res && res.ok && res.item && grid) {
                                // Construire une carte et l’injecter en tête
                                var delUrl = grid.getAttribute('data-del-url') || '';
                                var token = grid.getAttribute('data-token') || '';

                                var wrapper = document.createElement('article');
                                wrapper.className = 'real-card';
                                wrapper.id = 'real-item-' + res.item.id;

                                if(res.item.status == "pending") {
                                    var statusFr = 'En attente de validation'
                                } else {
                                    var statusFr = res.item.status
                                }

                                wrapper.innerHTML =
                                    '<img class="real-thumb" src="' + res.item.thumb + '" alt="">'+
                                    '<div class="real-actions">'+
                                    '<form method="post" action="' + delUrl + '" class="js-real-del" data-id="' + res.item.id + '">'+
                                    '<input type="hidden" name="token" value="' + token + '">'+
                                    '<input type="hidden" name="id_realisation" value="' + res.item.id + '">'+
                                    '<button class="btn btn-outline-danger btn-sm" type="submit" title="Supprimer">'+
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2">'+
                                    '<path d="M10 11v6"></path>'+
                                    '<path d="M14 11v6"></path>'+
                                    '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>'+
                                    '<path d="M3 6h18"></path>'+
                                    '<path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>'+
                                    '</svg>'+
                                    '</button>'+
                                    '</form>'+
                                    '</div>'+
                                    '<div class="real-meta">'+
                                    '<span class="real-badge real-badge--pending">' + (statusFr || 'En attente de validation') + '</span>'+
                                    '</div>';

                                // Enlève le message "aucune photo" s'il est présent
                                var info = grid.nextElementSibling;
                                if (info && info.classList.contains('alert')) info.remove();

                                grid.prepend(wrapper);
                                form.reset();
                                showMsg(res.msg || 'Photo ajoutée.', 'success');
                            } else {
                                showMsg((res && res.msg) ? res.msg : 'Échec de l’envoi.', 'danger');
                            }
                        })
                        /*                        .catch(function (e) {
                                                    showMsg('Erreur réseau pendant l’envoi.', 'danger');
                                                })*/
                        .finally(function () {
                            btn.disabled = false;
                        });
                });
            }

            // -------- Suppression AJAX (délégation) --------
            document.addEventListener('submit', function (e) {
                var target = e.target;
                if (!target.classList.contains('real-del-form')) return;

                e.preventDefault();

                var idInput = target.querySelector('input[name="id_realisation"]');
                if (!idInput) {
                    showMsg('Identifiant manquant.', 'danger');
                    return;
                }
                var id = idInput.value;

                var fd = new FormData(target);
                fd.append('ajax', '1');
                fd.append('xhr', '1');

                fetch(target.action, {
                    method: 'POST',
                    body: fd,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (res) {
                        if (res && res.ok) {
                            var item = document.getElementById('real-item-' + id);
                            if (item) {
                                item.style.transition = 'opacity .15s ease';
                                item.style.opacity = '0';
                                setTimeout(function () {
                                    if (item.parentNode) item.parentNode.removeChild(item);
                                    // Si plus d’éléments, afficher une info
                                    if (grid && grid.children.length === 0) {
                                        var info = document.createElement('div');
                                        info.className = 'alert alert-info';
                                        info.textContent = 'Vous n’avez pas encore ajouté de photo.';
                                        grid.after(info);
                                    }
                                }, 160);
                            }
                            showMsg('Photo supprimée.', 'success');
                        } else {
                            showMsg((res && res.msg) ? res.msg : 'Suppression impossible.', 'danger');
                        }
                    })
                    .catch(function () {
                        showMsg('Erreur réseau pendant la suppression.', 'danger');
                    });
            });
        });

    </script>
{/literal}
{/block}
