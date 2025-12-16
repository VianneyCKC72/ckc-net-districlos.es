{if $pro_login_error}
    {if $pro_login_error == 1}
        {assign var=errMsg value="Veuillez saisir votre e-mail et votre mot de passe."}
    {elseif $pro_login_error == 2}
        {assign var=errMsg value="L’adresse e-mail n’est pas valide."}
    {elseif $pro_login_error == 3}
        {assign var=errMsg value="E-mail ou mot de passe incorrect."}
    {elseif $pro_login_error == 4}
        {assign var=errMsg value="Votre compte n’est pas encore activé."}
    {else}
        {assign var=errMsg value="Impossible de vous connecter. Veuillez réessayer."}
    {/if}
    <div class="alert alert-danger" role="alert">{$errMsg}</div>
{/if}


<div class="pro-auth">
    <ul class="breadcrumb">
        <li>
            <svg role="navigation" aria-label="Accueil" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round" class="lucide lucide-house">
                <title>Accueil</title>
                <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path>
                <path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            </svg>
        </li>

        <li>
            <span>Connectez-vous à votre compte</span>
        </li>
    </ul>

    <div class="pro-auth__intro">
        <p>
            Bienvenue dans votre Espace Professionnel dédié aux experts du bâtiment, aux jardiniers et aux paysagistes
            passionnés.
            Chez Districlos, nous comprenons l’importance d’un partenariat solide pour mener à bien vos projets. C’est
            pourquoi
            nous avons créé cet espace exclusif, où vous bénéficiez de tarifs avantageux et de remises toute l’année sur
            nos clôtures,
            grillages et portails de qualité supérieure.
        </p>
    </div>

    <div class="pro-auth__grid">
        <section class="pro-auth__form">
            <h3 class="pro-auth__title">Je me connecte à mon espace</h3>
            {if isset($login_error) && $login_error}
                <div class="alert alert-danger" role="alert">
                    {$login_error}
                </div>
            {/if}

            <form id="pro-home-login-form" method="post" action="{$login_action}" novalidate>
                <input type="hidden" name="back" value="{$back}">

                <div class="pro-field">
                    <label for="pro-login-email">Votre e-mail</label>
                    <input class="pro-input" type="email" name="email" id="pro-login-email"
                           value="{if isset($login_email)}{$login_email|escape:'html'}{/if}"
                           required autocomplete="username">
                </div>

                <div class="pro-field">
                    <label for="pro-login-pass">Votre mot de passe</label>
                    <div class="pro-input-wrap">
                        <input class="pro-input" type="password" name="password" id="pro-login-pass" required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" aria-label="Afficher/Masquer le mot de passe">
                            <!-- Icône œil -->
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696
               10.75 10.75 0 0 1 19.876 0
               1 1 0 0 1 0 .696
               10.75 10.75 0 0 1-19.876 0"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>

                            <!-- Icône œil barré -->
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575
               1 1 0 0 1 0 .696
               10.747 10.747 0 0 1-1.444 2.49"></path>
                                <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path>
                                <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151
               1 1 0 0 1 0-.696
               10.75 10.75 0 0 1 4.446-5.143"></path>
                                <path d="m2 2 20 20"></path>
                            </svg>
                        </button>
                        {*<button type="button" class="pro-eye" aria-label="Afficher / masquer le mot de passe"></button>*}
                    </div>
                </div>

                <div class="pro-auth__links">
                    <a class="pro-link" href="{$urls.pages.password}">Mot de passe oublié&nbsp;?</a>
                </div>

                <div class="pro-auth__actions">
                    <button class="pro-btn pro-btn--ghost" type="submit" name="submitLogin">Se connecter</button>

                    <div class="pro-auth__cta">
                        <span class="pro-badge-text">100€ offert&nbsp;!</span>
                        <a class="pro-btn pro-btn--primary"
                           href="inscription?ep=1">
                            Créer un compte
                        </a>
                    </div>

                </div>
            </form>
        </section>

        <aside class="pro-auth__visual">
            <figure class="pro-figure">
                <picture>
                    <source srcset="/img/cms/auth/img-espace-pro3.webp" type="image/webp">
                    <img src="/img/cms/auth/img-espace-pro3.jpeg" alt="Districlos sur salon professionnel"
                         loading="lazy">
                </picture>

                {*<figcaption class="pro-quote">
                    <p>«&nbsp;Mon expérience avec Districlos a été géniale, et je suis convaincu que toute collaboration
                        sera synonyme de succès et de satisfaction&nbsp;»</p>
                    <small>Thomas — Paysagiste</small>
                </figcaption>

                <div class="pro-ribbon">
                    <strong>100€ offerts dès 1&nbsp;000€ d’achat HT</strong>
                    <span>pour toute première commande en tant que professionnel&nbsp;!</span>
                </div>*}
            </figure>
        </aside>
    </div>

    <div class="pro-steps">
        <div class="pro-step"><span class="pro-step__num">1</span><span
                    class="pro-step__txt">Je remplis le formulaire<br>d’inscription</span>
        </div>
        <div class="pro-step"><span class="pro-step__num">2</span><span class="pro-step__txt">Mon compte est validé<br>par les équipes Districlos</span>
        </div>
        <div class="pro-step"><span class="pro-step__num">3</span><span class="pro-step__txt">Je bénéficie de <strong>100€ offerts<br>dès 1&nbsp;000€ d’achat</strong></span>
        </div>
    </div>
</div>
