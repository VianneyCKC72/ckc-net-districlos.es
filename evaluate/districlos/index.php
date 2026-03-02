<?php

// PHPMailer pour envoi des documents en PJ
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de Satisfaction</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- En-tête -->
    <header class="site-header">
        <h1>Votre avis compte !</h1>
        <p class="header-subtitle">En 30 secondes, aidez-nous à améliorer votre expérience</p>
    </header>

    <!-- Contenu principal -->
    <main class="main-content">

        <!-- Carte entreprise -->
        <section class="company-section">
            <div class="company-card">

                <!-- Infos entreprise (gauche) -->
                <div class="company-info">
                    <div class="company-logo-wrapper">
                        <img src="../img/logo_districlos2021_web.svg" class="logo" alt="Logo Districlos">
                    </div>
                    <div class="company-details">
                        <div class="verified-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Profil vérifié
                        </div>
                        <h2 class="company-name">Districlos</h2>
                        <div class="company-rating">
                            <span class="rating-count">42 avis</span>
                            <div class="stars-display">
                                <span class="star filled">★</span>
                                <span class="star filled">★</span>
                                <span class="star filled">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                        <p class="company-tagline">Entreprise spécialisée dans l'aménagement extérieur</p>
                        <a href="https://www.cloture-discount.fr" target="_blank" class="btn-visit">Visiter le site web</a>
                    </div>
                </div>

                <!-- Séparateur -->
                <div class="company-divider"></div>

                <!-- À propos (droite) -->
                <div class="company-about">
                    <h3 class="about-title">A propos de l'entreprise</h3>
                    <p class="about-description">Cloture Discount : boutique en ligne de cloture et grillage à prix discount, panneaux de cloture rigides et autres clotures.</p>
                    <p class="about-location">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Localisation : France
                    </p>
                    <a href="https://www.cloture-discount.com" target="_blank" class="about-website">www.cloture-discount.com</a>
                </div>

            </div>
        </section>

        <div class="card-divider"></div>

        <!-- Formulaire multi-étapes -->
        <section class="form-section">
            <form action="index.php" method="POST" enctype="multipart/form-data" id="satisfaction-form">

                <!-- Indicateur d'étapes -->
                <div class="steps-indicator">
                    <div class="step active" data-step="1">1</div>
                    <div class="step-line"></div>
                    <div class="step" data-step="2">2</div>
                    <div class="step-line"></div>
                    <div class="step" data-step="3">3</div>
                </div>

                <!-- Étape 1 : Note globale -->
                <div class="form-step active" id="step-1">
                    <p class="step-question">Quelle note donneriez-vous à votre expérience ?</p>

                    <div class="star-rating-wrapper">
                        <div class="star-rating" id="star-rating">
                            <input type="radio" name="rating" id="star5" value="5">
                            <label for="star5" title="Très satisfait">★</label>

                            <input type="radio" name="rating" id="star4" value="4">
                            <label for="star4" title="Satisfait">★</label>

                            <input type="radio" name="rating" id="star3" value="3">
                            <label for="star3" title="Neutre">★</label>

                            <input type="radio" name="rating" id="star2" value="2">
                            <label for="star2" title="Déçu">★</label>

                            <input type="radio" name="rating" id="star1" value="1">
                            <label for="star1" title="Très déçu">★</label>
                        </div>
                        <div class="star-labels">
                            <span>Très déçu</span>
                            <span>Très satisfait</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-next" onclick="nextStep(1)">Suivant</button>
                    </div>
                </div>

                <!-- Étape 2 : Commentaire -->
                <div class="form-step" id="step-2">
                    <p class="step-question">Décrivez-nous votre expérience</p>

                    <textarea name="comment" class="form-textarea" placeholder="Merci ! Donnez-nous vos retours constructifs concernant la livraison, le service après-vente, le site internet, etc..." rows="5"></textarea>

                    <div class="form-actions">
                        <button type="button" class="btn-prev" onclick="prevStep(2)">Précédent</button>
                        <button type="button" class="btn-next" onclick="nextStep(2)">Suivant</button>
                    </div>
                </div>

                <!-- Étape 3 : Date & Contact -->
                <div class="form-step" id="step-3">

                    <div class="step3-inner">

                        <!-- Date de l'expérience -->
                        <div class="form-group-full">
                            <label for="experience_date" class="label-centered">Date de l'expérience</label>
                            <input type="date" name="experience_date" id="experience_date" class="form-input input-date">
                        </div>

                        <!-- Question contact -->
                        <div class="contact-question">
                            <p class="step-question">Souhaitez-vous être contacté par le service client ?</p>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="contact" value="oui" id="contact-oui" onchange="toggleContact(this)" checked>
                                    <span class="radio-custom"></span>
                                    Oui
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="contact" value="non" id="contact-non" onchange="toggleContact(this)">
                                    <span class="radio-custom"></span>
                                    Non
                                </label>
                            </div>
                        </div>

                        <!-- Champs de contact (actifs par défaut car "Oui" est coché) -->
                        <div class="contact-fields" id="contact-fields">
                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="lastname">Nom</label>
                                    <input type="text" name="lastname" id="lastname" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="firstname">Prénom</label>
                                    <input type="text" name="firstname" id="firstname" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="email">Votre adresse mail</label>
                                    <input type="email" name="email" id="email" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Votre numéro de téléphone</label>
                                    <input type="tel" name="phone" id="phone" class="form-input">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-prev" onclick="prevStep(3)">Précédent</button>
                        <button type="submit" class="btn-submit">Terminer</button>
                    </div>
                </div>

            </form>
        </section>

    </main>

    <script>
        function nextStep(current) {
            const currentStep = document.getElementById('step-' + current);
            const nextStep = document.getElementById('step-' + (current + 1));
            if (!nextStep) return;

            currentStep.classList.remove('active');
            nextStep.classList.add('active');

            document.querySelector('[data-step="' + current + '"]').classList.remove('active');
            document.querySelector('[data-step="' + current + '"]').classList.add('completed');
            document.querySelector('[data-step="' + (current + 1) + '"]').classList.add('active');

            const lines = document.querySelectorAll('.step-line');
            if (current <= lines.length) {
                lines[current - 1].classList.add('completed');
            }
        }

        function prevStep(current) {
            const currentStep = document.getElementById('step-' + current);
            const prevStep = document.getElementById('step-' + (current - 1));
            if (!prevStep) return;

            currentStep.classList.remove('active');
            prevStep.classList.add('active');

            document.querySelector('[data-step="' + current + '"]').classList.remove('active');
            document.querySelector('[data-step="' + (current - 1) + '"]').classList.remove('completed');
            document.querySelector('[data-step="' + (current - 1) + '"]').classList.add('active');

            const lines = document.querySelectorAll('.step-line');
            if (current - 2 >= 0) {
                lines[current - 2].classList.remove('completed');
            }
        }

        function toggleContact(radio) {
            const fields = document.getElementById('contact-fields');
            const inputs = fields.querySelectorAll('input');

            if (radio.value === 'oui') {
                fields.classList.remove('disabled');
                inputs.forEach(input => input.disabled = false);
            } else {
                fields.classList.add('disabled');
                inputs.forEach(input => {
                    input.disabled = true;
                    input.value = '';
                });
            }
        }
    </script>

</body>
</html>
