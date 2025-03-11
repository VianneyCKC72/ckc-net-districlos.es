{extends file='page.tpl'}
{block name='head_seo_title'}
    Obtenez un devis de clôture, grillage, portail avec Districlos
{/block}
{block name="page_content"}
    {capture name=path}{l s='Quotation request' mod='opartdevis'}{/capture}

    {if isset($errors)}
        {include file='_partials/form-errors.tpl' errors=$errors}
    {/if}

    {if isset($confirmation)}
        <p class="alert alert-success">{l s='Your request has been successfully sent to our team.' mod='opartdevis'}</p>
    {/if}

    <div class="loader-container">
        <div class="loaderProduct"></div>
    </div>
    <div id="iframe_devis" class="dis_flex p_btwn s_center">
        <div id="block-reassurance" class="shadow_box">
            <ul class="dis_flex flex_cln">
                <div class="block-reassurance-row dis_flex flex_row s_center">
                    <li>
                        <div class="block-reassurance-item">

                            <img src="/modules/blockreassurance/views/img/img_perso/paiement_home.png" alt="Logo paiement 100% sécurisé" class="block-reassurance-img unveil-loaded">
                            <span class="h6">Paiement 100% sécurisé </span>
                        </div>
                    </li>
                    <li>
                        <div class="block-reassurance-item">

                            <img src="/modules/blockreassurance/views/img/img_perso/livraison_home.png" alt="Logo livraison à domicile ou retrait en magasin" class="block-reassurance-img unveil-loaded">
                            <span class="h6">Livraison à domicile ou retrait en magasin </span>
                        </div>
                    </li>
                </div>
                <div class="block-reassurance-separator"></div>
                <div class="block-reassurance-row dis_flex flex_row s_center">
                    <li>

                        <div class="block-reassurance-item">

                            <img src="/modules/blockreassurance/views/img/img_perso/fab_home.png" alt="Logo fabrication européenne" class="block-reassurance-img unveil-loaded">
                            <span class="h6">Fabrication européenne </span>
                        </div>
                    </li>
                    <li>

                        <div class="block-reassurance-item">

                            <img src="/modules/blockreassurance/views/img/img_perso/garantie_home.png" alt="Logo garantie 10 ans anti-corrosion" class="block-reassurance-img unveil-loaded">
                            <span class="h6">Garantie 10 ans anti-corrosion </span>
                        </div>
                    </li>
                </div>
            </ul>
        </div>
        <div class="form_uperia_container">
            <h2 class="text-transform: uppercase">Demandez votre devis en <span style="color:#026473; font-weight: 600;">30 secondes</span></h2>
            <div id="step1">
                <div class="dis_grid grid-cols-3 gap-2 main_grid">
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture rigide">
                        <p>Rigide</p>
                        <img src="/modules/opartdevis/views/img/panneau-rigide-couv-g.jpg" alt="Clôture rigide">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture aluminium">
                        <p>Aluminium</p>
                        <img src="/modules/opartdevis/views/img/cloturealu-0m80.jpg" alt="Clôture aluminium">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture souple">
                        <p>Souple</p>
                        <img src="/modules/opartdevis/views/img/simple-torsion-24-griss.jpg" alt="Clôture souple">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture agricole">
                        <p>Agricole</p>
                        <img src="/modules/opartdevis/views/img/grillage-nouee-mouton.jpg" alt="Clôture agricole">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture barreaudée">
                        <p>Barreaudée</p>
                        <img src="/modules/opartdevis/views/img/nature.jpg" alt="Clôture barreaudée">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture piscine">
                        <p>Piscine</p>
                        <img src="/modules/opartdevis/views/img/kit-piscineg.jpg" alt="Clôture piscine">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="element dis_flex flex_cln">
                        <input type="radio" name="cloture_select" value="Clôture bois">
                        <p>Bois</p>
                        <img src="/modules/opartdevis/views/img/ganivelle-en-noisetier.jpg" alt="Clôture bois">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                </div>
                <div class="form-footer">
                    <div class="action-buttons">
                        <button class="btn btn-light btn-action previous disabled" data-step="null">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                        </button>
                        <button class="btn btn-light btn-action next disabled" data-step="2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div id="step2">
                <div class="dis_flex flex_cln gap-2 main_grid">
                    <div class="form-control">
                        <label for="email">Email *</label>
                        <input type="text" name="email" id="email">
                        <span class="error-message"></span>
                    </div>
                    <div class="form-control">
                        <label for="zip_code">Code postal *</label>
                        <input type="text" name="zip_code" id="zip_code">
                        <span class="error-message"></span>
                    </div>
                    <div class="form-control">
                        <label for="phone">Téléphone *</label>
                        <input type="text" name="phone" id="phone">
                        <span class="error-message"></span>
                    </div>
                </div>
                <div class="form-footer">
                    <div class="action-buttons">
                        <button class="btn btn-light btn-action previous" data-step="1">
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <button class="btn btn-light btn-action next" data-step="3">
                            <i class="fas fa-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="step3">
                <div class="dis_flex flex_cln gap-2 main_grid">
                    <div class="form-control">
                        <label for="name">Nom prénom *</label>
                        <input type="text" name="name" id="name">
                        <span class="error-message"></span>
                    </div>

                    <div class="form-control">
                        <label for="description">Détaillez votre demande</label>
                        <textarea name="description" id="description" cols="30" rows="10"></textarea>
                        <span class="error-message"></span>
                    </div>
                </div>
                <div class="form-footer">
                    <div class="action-buttons">
                        <button class="btn btn-light btn-action previous" data-step="2">
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <button class="btn btn-light btn-action next" data-step="null">
                            <i class="fas fa-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="step4">
                <div class="dis_flex flex_cln gap-2 main_grid">
                   <p>Merci ! Votre demande sera traitée dans les plus brefs délais.</p>
                </div>
                <div class="form-footer">
                    <div class="action-buttons">
                        <button class="btn btn-light btn-action previous disabled" data-step="null">
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <button class="btn btn-light btn-action next disabled" data-step="null">
                            <i class="fas fa-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function validateEmail(email) {
            var re = new RegExp("^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.(com|org|net|edu|gov|mil|info|gouv|fr)$");
            return re.test(email.toLowerCase());
        }

        function validateZipCode(zip) {
            var re = new RegExp("^[0-9][0-9][0-9][0-9][0-9]$");
            return re.test(zip);
        }

        function validatePhone(phone) {
            var re = new RegExp("^\\d\\d[-.\\s]?\\d\\d[-.\\s]?\\d\\d[-.\\s]?\\d\\d[-.\\s]?\\d\\d$");
            return re.test(phone);
        }

        function validateForm() {
            let isValid = true;

            const emailInput = document.getElementById('email');
            const zipInput = document.getElementById('zip_code');
            const phoneInput = document.getElementById('phone');

            if (!validateEmail(emailInput.value)) {
                emailInput.classList.add('error');
                emailInput.nextElementSibling.textContent = "Email invalide";
                emailInput.nextElementSibling.style.display = 'block';
                isValid = false;
            } else {
                emailInput.classList.remove('error');
                emailInput.nextElementSibling.style.display = 'none';
            }

            if (!validateZipCode(zipInput.value)) {
                zipInput.classList.add('error');
                zipInput.nextElementSibling.textContent = "Code postal invalide";
                zipInput.nextElementSibling.style.display = 'block';
                isValid = false;
            } else {
                zipInput.classList.remove('error');
                zipInput.nextElementSibling.style.display = 'none';
            }

            if (!validatePhone(phoneInput.value)) {
                phoneInput.classList.add('error');
                phoneInput.nextElementSibling.textContent = "Téléphone invalide";
                phoneInput.nextElementSibling.style.display = 'block';
                isValid = false;
            } else {
                phoneInput.classList.remove('error');
                phoneInput.nextElementSibling.style.display = 'none';
            }

            return isValid;
        }


        document.addEventListener('DOMContentLoaded', function() {
            localStorage.removeItem('quoteId')
            var elements = document.querySelectorAll('.element');

            elements.forEach(function(element) {
                element.addEventListener('click', function() {
                    elements.forEach(function(el) {
                        el.classList.remove('selected');
                    });

                    this.classList.add('selected');
                    this.parentNode.parentNode.querySelector('button.next').classList.remove('disabled');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });

            var nextButtons = document.querySelectorAll('.btn-action.next');
            nextButtons.forEach(function(element) {
                element.addEventListener('click', function() {
                    if (!element.classList.contains('disabled')) {
                        var step = this.dataset.step;
                        if (step === '2') {
                            document.getElementById('step1').style.display = 'none';
                            document.getElementById('step2').style.display = 'block';
                        }else if (step === '3') {
                            if (validateForm()) {
                                $('.loader-container').css('display', 'flex');
                                $('#main').css('opacity', '0.5').css('pointer-events', 'none');
                                let data = {
                                    fence_type: document.querySelector('input[name="cloture_select"]:checked').value,
                                    email: document.getElementById('email').value,
                                    zip_code: document.getElementById('zip_code').value,
                                    phone: document.getElementById('phone').value,
                                    submitFormStep2: true,
                                    ajax: 1,
                                    quoteId: localStorage.getItem('quoteId') ? localStorage.getItem('quoteId') : null
                                };

                                let options = {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'multipart/form-data'
                                    },
                                    body: JSON.stringify(data)
                                };
                                $.ajax({
                                    url: '/module/opartdevis/simplequotation',
                                    type: 'POST',
                                    data: data,
                                    success: function (data) {
                                        localStorage.setItem('quoteId', data.quoteform);
                                        $('.loader-container').css('display', 'none');
                                        $('#main').css('opacity', '1').css('pointer-events', 'unset')
                                        document.getElementById('step2').style.display = 'none';
                                        document.getElementById('step3').style.display = 'block';
                                    }
                                })
                            }
                        }else if (step === 'null') {
                            if (document.getElementById('name').value) {
                                $('.loader-container').css('display', 'flex');
                                $('#main').css('opacity', '0.5').css('pointer-events', 'none');
                                let data = {
                                    name: document.getElementById('name').value,
                                    description: document.getElementById('description').value,
                                    quoteId: localStorage.getItem('quoteId'),
                                    submitFormStep3: true,
                                    ajax: 1,
                                };

                                let options = {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'multipart/form-data'
                                    },
                                    body: JSON.stringify(data)
                                };
                                $.ajax({
                                    url: '/module/opartdevis/simplequotation',
                                    type: 'POST',
                                    data: data,
                                    success: function (data) {

                                        $('.loader-container').css('display', 'none');
                                        $('#main').css('opacity', '1').css('pointer-events', 'unset')
                                        window.location.href = '/module/successdevis/form2'
                                    }
                                })
                            }else{
                                document.getElementById('name').classList.add('error');
                                document.getElementById('name').nextElementSibling.textContent = "Votre nom et prénom sont obligatoires";
                                document.getElementById('name').nextElementSibling.style.display = 'block';
                            }
                        }
                    }
                });
            });

            var previousButtons = document.querySelectorAll('.btn-action.previous');
            previousButtons.forEach(function(element) {
                element.addEventListener('click', function() {
                    if (!element.classList.contains('disabled')) {
                        var step = this.dataset.step;
                        if (step === '2') {
                            document.getElementById('step3').style.display = 'none';
                            document.getElementById('step2').style.display = 'block';
                        }else if (step === '1') {
                            document.getElementById('step2').style.display = 'none';
                            document.getElementById('step1').style.display = 'block';
                        }
                    }
                });
            });



            document.getElementById('email').addEventListener('change', function() {
                if (!validateEmail(this.value)) {
                    this.classList.add('error');
                    this.nextElementSibling.textContent = "Email invalide";
                    this.nextElementSibling.style.display = 'block';
                } else {
                    this.classList.remove('error');
                    this.nextElementSibling.style.display = 'none';
                }
            });

            document.getElementById('zip_code').addEventListener('change', function() {
                if (!validateZipCode(this.value)) {
                    this.classList.add('error');
                    this.nextElementSibling.textContent = "Code postal invalide";
                    this.nextElementSibling.style.display = 'block';
                } else {
                    this.classList.remove('error');
                    this.nextElementSibling.style.display = 'none';
                }
            });
            document.getElementById('phone').addEventListener('change', function() {
                if (!validatePhone(this.value)) {
                    this.classList.add('error');
                    this.nextElementSibling.textContent = "Téléphone invalide";
                    this.nextElementSibling.style.display = 'block';
                } else {
                    this.classList.remove('error');
                    this.nextElementSibling.style.display = 'none';
                }
            });
        });
    </script>
{/block}
