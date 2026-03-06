{**
 * Wizard de filtre interactif — Catégorie 215 (Lames de clôture)
 *
 * 4 étapes :
 *   1. Style de lame       (Lames pleines / Lames semi-ajourées)
 *   2. Lames décoratives   (Oui / Non)
 *   3. Hauteur             (placeholder — valeurs à confirmer)
 *   4. Pose de clôture     (placeholder — options à confirmer)
 *
 * Une fois les IDs produits reçus, renseigner la table `productMap` dans la section <script>.
 *
 * Images : remplacer les blocs "Image à venir" par :
 *   <img src="{$urls.theme_assets}img/nom-image.jpg" alt="...">
 *}

{literal}
<style>
/* ================================================================
   Wizard de filtre — Catégorie 215
   ================================================================ */
.filter-wizard {
    background: #F6F2EB;
    padding: 1.5rem 2rem;
}

/* Barre de progression */
.filter-wizard__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.filter-wizard__btn-reset-all {
    margin-left: auto;
    background: none;
    border: 1px solid #999;
    border-radius: 50px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    color: #555;
    cursor: pointer;
    transition: border-color 0.2s, color 0.2s;
}
.filter-wizard__btn-reset-all:hover {
    border-color: #666;
    color: #333;
}

.filter-wizard__dots {
    display: flex;
    gap: 6px;
}

.filter-wizard__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #d9d9d9;
    transition: background 0.3s;
}
.filter-wizard__dot.is-active { background: #6abf50; }
.filter-wizard__dot.is-done   { background: #a8d98f; }

.filter-wizard__step-count {
    font-size: 0.8rem;
    color: #888;
}

/* Étapes */
.filter-wizard__step { display: none; }
.filter-wizard__step.is-active { display: block; }

.filter-wizard__question {
    font-weight: bold;
    font-size: 18px;
    color: black;
    margin-bottom: 1.25rem;
}

/* Options */
.filter-wizard__options {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}
.filter-wizard__options-hauteur-pleines,
.filter-wizard__options-hauteur-ajourees {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
}

.filter-wizard__option {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 0.75rem;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    user-select: none;
}
.filter-wizard__option:hover     { border-color: #6abf50; }
.filter-wizard__option.is-selected {
    border-color: #6abf50;
    box-shadow: 0 0 0 3px rgba(106, 191, 80, 0.15);
}

/* Placeholder image */
.filter-wizard__option-img {
    width: 140px;
    height: 100px;
    border-radius: 8px;
    background: #e8e8e8;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #bbb;
    font-size: 0.72rem;
    text-align: center;
    margin-bottom: 0.6rem;
    overflow: hidden;
    flex-shrink: 0;
}
.filter-wizard__option-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
    display: block;
}

/* Option texte seule (hauteurs) */
.filter-wizard__option--text {
    min-width: 80px;
    padding: 0.75rem 1.25rem;
}
.filter-wizard__option--text .filter-wizard__option-label {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

.filter-wizard__option-label {
    font-size: 0.85rem;
    color: #333;
    text-align: center;
}

/* Boutons */
.filter-wizard__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.filter-wizard__btn-validate {
    background: #6abf50;
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 0.55rem 1.8rem;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.2s, opacity 0.2s;
    font-weight: 500;
}
.filter-wizard__btn-validate:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.filter-wizard__btn-validate:not(:disabled):hover { background: #58a640; }

.filter-wizard__btn-back {
    background: none;
    border: 1px solid #ccc;
    border-radius: 50px;
    padding: 0.4rem 1.2rem;
    font-size: 0.85rem;
    cursor: pointer;
    color: #555;
    transition: border-color 0.2s;
}
.filter-wizard__btn-back:hover { border-color: #888; }

/* Résultat */
.filter-wizard__result { display: none; }

.filter-wizard__result-placeholder {
    background: #ebebeb;
    border-radius: 10px;
    padding: 1.5rem;
    color: #999;
    font-style: italic;
    margin: 1rem 0;
    text-align: center;
}
</style>
{/literal}

<div class="filter-wizard" id="filter-wizard-215">

    {* ── En-tête : indicateur de progression ── *}
    <div class="filter-wizard__header">
        <div class="filter-wizard__dots">
            <span class="filter-wizard__dot is-active" data-step="1"></span>
            <span class="filter-wizard__dot" data-step="2"></span>
            <span class="filter-wizard__dot" data-step="3"></span>
            <span class="filter-wizard__dot" data-step="4"></span>
            <span class="filter-wizard__dot" data-step="5"></span>
        </div>
        <span class="filter-wizard__step-count">
            Étape <strong id="fw215-current">1</strong> / 5
        </span>
        <button type="button" class="filter-wizard__btn-reset-all filter-wizard__btn-reset" aria-label="Réinitialiser tous les filtres">Réinitialiser tous les filtres</button>
    </div>

    {* ── Étape 1 : Style de lame ── *}
    <div class="filter-wizard__step is-active" data-step="1">
        <p class="filter-wizard__question">Quel style de lame préférez-vous ?</p>
        <div class="filter-wizard__options">
            <label class="filter-wizard__option" data-name="style" data-value="pleines">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/lamespleines.png" alt="Lames pleines" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">Lames pleines</span>
            </label>
            <label class="filter-wizard__option" data-name="style" data-value="semi-ajourees">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/lamesajourees.png" alt="Lames semi-ajourées" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">Lames persiennes</span>
            </label>
            <div class="filter-wizard__actions">
                <button class="filter-wizard__btn-validate" data-next="2" disabled>Valider</button>
            </div>
        </div>
    </div>

    {* ── Étape 2 : Lames décoratives ── *}
    <div class="filter-wizard__step" data-step="2">
        <p class="filter-wizard__question">Souhaitez-vous des lames décoratives ?</p>
        <div class="filter-wizard__options">
            <label class="filter-wizard__option" data-name="decoratives" data-value="oui">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/lamesdecorativesoui.png" alt="Oui" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">Oui</span>
            </label>
            <label class="filter-wizard__option" data-name="decoratives" data-value="non">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/lamesdecorativesnon.png" alt="Non" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">Non</span>
            </label>
            <div class="filter-wizard__actions">
                <button class="filter-wizard__btn-validate" data-next="3" disabled>Valider</button>
                <button class="filter-wizard__btn-back" data-prev="1">Retour</button>
            </div>
        </div>
    </div>

    {* ── Étape 3 : Hauteur (options selon style : pleines vs ajourées) ── *}
    <div class="filter-wizard__step" data-step="3">
        <p class="filter-wizard__question">Quelle hauteur convient à votre projet ?</p>
        <div class="filter-wizard__options">
            <div class="filter-wizard__options-hauteur-pleines" data-style="pleines">
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="0m515"><span class="filter-wizard__option-label">0,515 m</span></label>
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="0m960"><span class="filter-wizard__option-label">0,96 m</span></label>
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="0m90"><span class="filter-wizard__option-label">0,90 m</span></label>
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="1m35"><span class="filter-wizard__option-label">1,35 m</span></label>
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="1m41"><span class="filter-wizard__option-label">1,41 m</span></label>
            </div>
            <div class="filter-wizard__options-hauteur-ajourees" data-style="semi-ajourees" style="display:none;">
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="0m403"><span class="filter-wizard__option-label">0,403 m</span></label>
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="0m766"><span class="filter-wizard__option-label">0,766 m</span></label>
                <label class="filter-wizard__option filter-wizard__option--text" data-name="hauteur" data-value="1m129"><span class="filter-wizard__option-label">1,129 m</span></label>
            </div>
            <div class="filter-wizard__actions">
                <button class="filter-wizard__btn-validate" data-next="4" disabled>Valider</button>
                <button class="filter-wizard__btn-back" data-prev="2">Retour</button>
            </div>
        </div>
    </div>

    {* ── Étape 4 : Pose de clôture ── *}
    {* Options placeholder — à confirmer avec les types de pose réels *}
    <div class="filter-wizard__step" data-step="4">
        <p class="filter-wizard__question">Quelle pose de clôture vous convient ?</p>
        <div class="filter-wizard__options">
            <label class="filter-wizard__option" data-name="pose" data-value="sceller">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/asceller.png" alt="A sceller" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">A sceller</span>
            </label>
            <label class="filter-wizard__option" data-name="pose" data-value="platines">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/surplatines.png" alt="Sur platines" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">Sur platines</span>
            </label>
            <div class="filter-wizard__actions">
                <button class="filter-wizard__btn-validate" data-next="5" disabled>Valider</button>
                <button class="filter-wizard__btn-back" data-prev="3">Retour</button>
            </div>
        </div>
    </div>

    {* ── Étape 5 : Conditionnement ── *}
    <div class="filter-wizard__step" data-step="5">
        <p class="filter-wizard__question">Quel conditionnement désirez-vous ?</p>
        <div class="filter-wizard__options">
            <label class="filter-wizard__option" data-name="conditionnement" data-value="kit">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/enkit.png" alt="En kit" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">En kit</span>
            </label>
            <label class="filter-wizard__option" data-name="conditionnement" data-value="unite">
                <span class="filter-wizard__option-img">
                    <img src="{$urls.theme_assets}img/sub-category/alunite.png" alt="À l'unité" loading="lazy">
                </span>
                <span class="filter-wizard__option-label">À l'unité</span>
            </label>
            <div class="filter-wizard__actions">
                <button class="filter-wizard__btn-validate filter-wizard__btn-submit" disabled>Voir les produits</button>
                <button class="filter-wizard__btn-back" data-prev="4">Retour</button>
            </div>
        </div>
    </div>

    {* ── Résultat : le bouton "Réinitialiser tous les filtres" est dans l'en-tête (visible sur toutes les étapes) ── *}
    <div class="filter-wizard__result" id="fw215-result">
        <p class="filter-wizard__question">Produits correspondant à vos critères</p>
        <div class="filter-wizard__result-placeholder" id="fw215-products">
            Placeholder — les produits filtrés s'afficheront ici une fois les IDs renseignés.
        </div>
    </div>

</div>

<script>
{literal}
(function () {
    var wizard = document.getElementById('filter-wizard-215');
    if (!wizard) return;

    var answers = {};

    /**
     * Table de correspondance : combinaison de réponses → IDs produits PrestaShop.
     *
     * À renseigner une fois les IDs fournis.
     *
     * La clé est construite à partir des réponses dans l'ordre suivant :
     *   style | decoratives | hauteur | pose
     *
     * Les étapes "passées" (non répondues) sont omises de la clé.
     *
     * Exemple de clé : "style:pleines|decoratives:non|hauteur:1.5m|pose:avec-poteaux"
     *
     * Vous pouvez aussi définir des clés partielles pour des filtrages plus larges,
     * en ajoutant une logique de fallback dans resolveProducts().
     *
     * @type Object.<string, number[]>
     */
    var productMap = {
        /* Style seul (fallback si étapes suivantes non répondues) */
        'style:pleines': [
            1324, 1936, 1938, 1939, 1940, 1941, 1942,
            2272, 2274, 2276, 2279, 2280, 2281,
            2273, 2275, 2277, 2282, 2283, 2284,
            1325, 1327, 1326, 1333, 1331, 1328, 1329
        ],
        'style:semi-ajourees': [
            1945, 1944, 1946, 1947, 1948, 1949, 1323,
            1325, 1327, 1326, 1333, 1331, 1328, 1329
        ],
        /* Style + Lames décoratives */
        'style:pleines|decoratives:oui': [
            2272, 2274, 2276, 2279, 2280, 2281,
            2273, 2275, 2277, 2282, 2283, 2284,
            1325, 1327, 1326, 1333, 1331, 1328, 1329
        ],
        'style:pleines|decoratives:non': [
            1324, 1936, 1938, 1939, 1940, 1941, 1942,
            1333, 1331, 1328, 1329
        ],
        'style:semi-ajourees|decoratives:oui': [
            1325, 1327, 1326, 1333, 1331, 1328, 1329
        ],
        'style:semi-ajourees|decoratives:non': [
            1945, 1944, 1946, 1947, 1948, 1949, 1323,
            1333, 1331, 1328, 1329
        ],
        /* Style + Décoratives + Hauteur (1324 toujours si pleines ; 1323 toujours si ajourées ; 1333, 1331, 1328, 1329) */
        'style:pleines|decoratives:oui|hauteur:0m515': [1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:oui|hauteur:0m960': [1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:oui|hauteur:0m90': [2272, 2274, 2276, 2279, 2280, 2281, 1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:oui|hauteur:1m35': [2273, 2275, 2277, 2282, 2283, 2284, 1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:oui|hauteur:1m41': [1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:non|hauteur:0m515': [1936, 1940, 1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:non|hauteur:0m960': [1938, 1941, 1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:non|hauteur:0m90': [1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:non|hauteur:1m35': [1324, 1333, 1331, 1328, 1329],
        'style:pleines|decoratives:non|hauteur:1m41': [1939, 1942, 1324, 1333, 1331, 1328, 1329],
        'style:semi-ajourees|decoratives:oui|hauteur:0m403': [1323, 1333, 1331, 1328, 1329],
        'style:semi-ajourees|decoratives:oui|hauteur:0m766': [1323, 1333, 1331, 1328, 1329],
        'style:semi-ajourees|decoratives:oui|hauteur:1m129': [1323, 1333, 1331, 1328, 1329],
        'style:semi-ajourees|decoratives:non|hauteur:0m403': [1944, 1947, 1323, 1333, 1331, 1328, 1329],
        'style:semi-ajourees|decoratives:non|hauteur:0m766': [1945, 1948, 1323, 1333, 1331, 1328, 1329],
        'style:semi-ajourees|decoratives:non|hauteur:1m129': [1946, 1949, 1323, 1333, 1331, 1328, 1329],
        /* Pose (intersection avec style|decoratives|hauteur ; 1324, 1323, 1333, 1331, 1328, 1329 conservés) */
        'pose:sceller': [1936, 1938, 1939, 1944, 1945, 1946, 1324, 1323, 1333, 1331, 1328, 1329],
        'pose:platines': [1940, 1941, 1942, 1947, 1944, 1949, 1324, 1323, 1333, 1331, 1328, 1329],
        /* Conditionnement (intersection avec le résultat précédent) */
        'conditionnement:kit': [1936, 1938, 1939, 1940, 1941, 1942, 1944, 1945, 1946, 1947, 1948, 1949, 2272, 2274, 2276, 2279, 2280, 2281, 2273, 2275, 2277, 2282, 2283, 2284],
        'conditionnement:unite': [1324, 1323, 1333, 1331, 1328, 1329]
    };

    /* ── Helpers ── */

    function buildKey() {
        var order = ['style', 'decoratives', 'hauteur', 'pose', 'conditionnement'];
        return order
            .filter(function (k) { return answers[k] !== undefined; })
            .map(function (k) { return k + ':' + answers[k]; })
            .join('|');
    }

    function intersectArrays(a, b) {
        var set = {};
        a.forEach(function (id) { set[String(id)] = true; });
        return b.filter(function (id) { return set[String(id)]; });
    }

    /**
     * Résout une clé donnée (sans conditionnement) pour obtenir la liste d'IDs.
     * Gère l'intersection avec la pose si la clé contient |pose:
     */
    function resolveKey(keyStr) {
        if (productMap[keyStr]) return productMap[keyStr];
        if (keyStr.indexOf('|pose:') !== -1) {
            var posePart = keyStr.split('|pose:')[1];
            var poseKey = 'pose:' + (posePart ? posePart.split('|')[0] : '');
            var baseKey = keyStr.replace(/\|pose:[^|]+$/, '');
            var baseList = productMap[baseKey];
            var parts = baseKey.split('|');
            while (!baseList && parts.length > 0) {
                parts.pop();
                baseList = productMap[parts.join('|')];
            }
            var poseList = productMap[poseKey];
            if (baseList && poseList) return intersectArrays(baseList, poseList);
            return baseList || [];
        }
        var parts = keyStr.split('|');
        while (parts.length > 0) {
            parts.pop();
            var partial = parts.join('|');
            if (productMap[partial]) return productMap[partial];
        }
        return [];
    }

    /**
     * Résout les IDs produits à partir des réponses.
     * Gère pose (intersection) et conditionnement (intersection).
     * Si "style" n'est pas choisi (ex. étape 1 passée), retourne null = afficher tous les produits.
     *
     * @returns number[]|null null = pas de filtre (afficher tout)
     */
    function resolveProducts() {
        var key = buildKey();
        if (!key || key.indexOf('style:') === -1) return null;
        if (productMap[key]) return productMap[key];

        /* Clé avec conditionnement : intersection entre base (sans conditionnement) et liste conditionnement */
        if (key.indexOf('|conditionnement:') !== -1) {
            var condPart = key.split('|conditionnement:')[1];
            var condKey = 'conditionnement:' + (condPart ? condPart.split('|')[0] : '');
            var baseKey = key.replace(/\|conditionnement:[^|]+$/, '');
            var baseList = resolveKey(baseKey);
            var condList = productMap[condKey];
            if (baseList && condList) return intersectArrays(baseList, condList);
            return baseList || [];
        }

        /* Clé avec pose : intersection entre base (sans pose) et liste pose */
        if (key.indexOf('|pose:') !== -1) {
            var posePart = key.split('|pose:')[1];
            var poseKey = 'pose:' + (posePart ? posePart.split('|')[0] : '');
            var baseKey = key.replace(/\|pose:[^|]+$/, '');
            var baseList = resolveKey(baseKey);
            var poseList = productMap[poseKey];
            if (baseList && poseList) return intersectArrays(baseList, poseList);
            return baseList || [];
        }

        /* Fallback : clé partielle */
        return resolveKey(key);
    }

    /* ── Navigation ── */

    function goToStep(n) {
        wizard.querySelectorAll('.filter-wizard__step').forEach(function (el) {
            el.classList.remove('is-active');
        });
        /* IMPORTANT : les dots ont aussi data-step, donc on cible explicitement l'étape */
        var step = wizard.querySelector('.filter-wizard__step[data-step="' + n + '"]');
        if (step) step.classList.add('is-active');

        wizard.querySelectorAll('.filter-wizard__dot').forEach(function (dot) {
            var s = parseInt(dot.dataset.step, 10);
            dot.classList.remove('is-active', 'is-done');
            if (s === n)     dot.classList.add('is-active');
            else if (s < n)  dot.classList.add('is-done');
        });

        var label = document.getElementById('fw215-current');
        if (label) label.textContent = n;

        /* Étape 3 : afficher les hauteurs selon le style (pleines vs semi-ajourées) */
        if (n === 3) {
            var style = answers.style || '';
            wizard.querySelectorAll('.filter-wizard__options-hauteur-pleines, .filter-wizard__options-hauteur-ajourees').forEach(function (block) {
                var blockStyle = block.getAttribute('data-style') || '';
                block.style.display = (blockStyle === style) ? '' : 'none';
            });
        }
    }

    /**
     * Affiche ou masque les produits de la liste.
     * @param allowedIds {number[]|null} null = tout afficher (reset), [] = tout masquer (aucun résultat), [id,...] = afficher uniquement ces IDs
     */
    function setProductListVisibility(allowedIds) {
        var list = document.getElementById('js-product-list');
        if (!list) return;

        var allowAll = (allowedIds == null);
        var allowed = {};
        if (!allowAll && allowedIds && allowedIds.length > 0) {
            allowedIds.forEach(function (id) { allowed[String(id)] = true; });
        }

        list.querySelectorAll('[data-id-product]').forEach(function (el) {
            var card = el.closest('.js-product') || el;
            if (allowAll) {
                card.style.display = '';
                return;
            }
            var id = el.getAttribute('data-id-product');
            card.style.display = allowed[id] ? '' : 'none';
        });
    }

    function showNoResults() {
        wizard.querySelectorAll('.filter-wizard__step').forEach(function (el) {
            el.classList.remove('is-active');
        });
        wizard.querySelectorAll('.filter-wizard__dot').forEach(function (dot) {
            dot.classList.remove('is-active');
            dot.classList.add('is-done');
        });
        var result = document.getElementById('fw215-result');
        var placeholder = document.getElementById('fw215-products');
        if (result) result.style.display = 'block';
        if (placeholder) placeholder.textContent = 'Aucun produit pour ce filtre';
        setProductListVisibility([]);
    }

    function showResults() {
        wizard.querySelectorAll('.filter-wizard__step').forEach(function (el) {
            el.classList.remove('is-active');
        });
        wizard.querySelectorAll('.filter-wizard__dot').forEach(function (dot) {
            dot.classList.remove('is-active');
            dot.classList.add('is-done');
        });

        var result = document.getElementById('fw215-result');
        if (result) result.style.display = 'block';

        var ids = resolveProducts();
        var placeholder = document.getElementById('fw215-products');

        if (ids === null) {
            setProductListVisibility(null);
            placeholder.textContent = 'Tous les produits de la catégorie';
        } else if (ids.length > 0) {
            setProductListVisibility(ids);
            placeholder.textContent = 'Produits affichés : ' + ids.length;
            var top = document.getElementById('js-product-list-top') || document.getElementById('js-product-list');
            if (top && top.scrollIntoView) {
                top.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else {
            placeholder.textContent = 'Aucun produit pour ce filtre';
            setProductListVisibility([]);
        }

        console.log('[Wizard 215] Réponses :', answers);
        console.log('[Wizard 215] Clé de filtre :', buildKey());
        console.log('[Wizard 215] IDs produits :', ids);
    }

    function resetWizard() {
        answers = {};
        wizard.querySelectorAll('.filter-wizard__option').forEach(function (el) {
            el.classList.remove('is-selected');
        });
        wizard.querySelectorAll('.filter-wizard__btn-validate:not(.filter-wizard__btn-reset)').forEach(function (btn) {
            btn.disabled = true;
        });
        var result = document.getElementById('fw215-result');
        if (result) result.style.display = 'none';

        /* Réaffiche tous les produits */
        setProductListVisibility(null);
        goToStep(1);
    }

    /* ── Événements (délégation sur le wizard) ── */

    wizard.addEventListener('click', function (e) {

        /* Réinitialiser tous les filtres (header ou bloc résultat) */
        if (e.target.closest('.filter-wizard__btn-reset') || e.target.closest('.filter-wizard__btn-reset-all')) {
            resetWizard();
            return;
        }

        /* Sélection d'une option */
        var option = e.target.closest('.filter-wizard__option');
        if (option) {
            var name  = option.dataset.name;
            var value = option.dataset.value;
            var step  = option.closest('.filter-wizard__step');

            step.querySelectorAll('.filter-wizard__option[data-name="' + name + '"]').forEach(function (el) {
                el.classList.remove('is-selected');
            });
            option.classList.add('is-selected');
            answers[name] = value;

            var btnValidate = step.querySelector('.filter-wizard__btn-validate');
            if (btnValidate) btnValidate.disabled = false;
            return;
        }

        /* Bouton Valider / Voir les produits */
        var btnVal = e.target.closest('.filter-wizard__btn-validate');
        if (btnVal && !btnVal.disabled) {
            if (btnVal.classList.contains('filter-wizard__btn-submit')) {
                showResults();
            } else if (btnVal.dataset.next === '3' && answers.style === 'semi-ajourees' && answers.decoratives === 'oui') {
                /* Lames persiennes + Lames décoratives Oui : afficher les résultats directement (sans étapes 3–5) */
                showResults();
            } else {
                var ids = resolveProducts();
                setProductListVisibility(ids);
                if (ids === null) {
                    goToStep(parseInt(btnVal.dataset.next, 10));
                } else if (ids.length === 0) {
                    showNoResults();
                } else {
                    goToStep(parseInt(btnVal.dataset.next, 10));
                }
            }
            return;
        }

        /* Bouton Retour */
        var btnBack = e.target.closest('.filter-wizard__btn-back');
        if (btnBack) {
            goToStep(parseInt(btnBack.dataset.prev, 10));
            return;
        }
    });

}());
{/literal}
</script>
