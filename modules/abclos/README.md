# Module AbClos (PrestaShop)

Intègre le snippet **AbClos** (A/B testing code-first) sur la boutique Districlos, de façon réversible via configuration BO.

## Prérequis

- PrestaShop 1.7 / 8 (Districlos)
- AbClos démarré en local : `http://localhost:3000`
- Compte démo : `demo@abclos.local` / `demo1234`
- Clé API seed : `abc_demo_local_key_districlos01`

## Installation

1. Le dossier `modules/abclos` est déjà dans le projet.
2. Back-office PrestaShop → **Modules** → rechercher **AbClos** → **Installer**.
3. **Configurer** :
   - Activer le snippet : **Oui**
   - Clé API : `abc_demo_local_key_districlos01`
   - Endpoint : `http://localhost:3000`
   - Debug : **Oui** (local)
   - Anti-flicker : **Oui**
   - Tracker « Ajouter au panier » : **Oui**
   - Clé du goal panier : `add_to_cart` (créer le même goal dans AbClos)
4. Vider le cache PrestaShop (paramètres avancés → performances) et éventuellement désactiver CCC en local.

Désinstaller ou désactiver le module / le switch = plus aucun snippet.

## Test rapide (fiche produit)

1. Dans AbClos, créez une expérience **RUNNING** avec :
   - une variante CSS visible (ex. `button.add-to-cart { outline: 3px solid red !important; }`)
   - une **Page** (targeting URL) qui matche l’URL réelle Presta
2. Ouvrez une fiche produit boutique (fenêtre privée recommandée).
3. Console navigateur :
   - logs `[AbClos]` si debug ON
   - cookie `abclos_exps`
   - variante appliquée si la règle URL matche
4. Cliquez **Ajouter au panier** → le goal `add_to_cart` doit apparaître dans AbClos → Résultats.

### Revenu (automatique)

Sur la page **confirmation de commande**, le module envoie tout seul :

```js
abclos.track('revenue', { value: TOTAL_TTC })
```

Prérequis AbClos : objectif clé `revenue` (type Revenu) lié à l’expérience **RUNNING**.
Le visiteur doit avoir vu une page du test avant (cookie d’assignment).

### Track manuel (console)

```js
window.abclos.track('add_to_cart');
window.abclos.track('revenue', { value: 129 });
```

## Targeting URL (AbClos → Pages)

Les URLs PrestaShop ne sont **pas** forcément `/product`. Sur Districlos (rewrite), une fiche ressemble souvent à :

```text
https://districlos.local/fr/ Nom-categorie / Nom-produit-ID.html
```

(adapter au vhost Laragon réel)

Recommandations de règles AbClos :

| Type | Exemple | Usage |
|------|---------|--------|
| Contains | `.html` | Large (beaucoup de fiches) |
| Contains | `/fr/` | Périmètre langue |
| Regex | `-[0-9]+\.html` | Fiches produit typiques PS |
| Exact | URL complète copiée depuis la barre d’adresse | Test d’une seule page |

Pour un test isolé : ouvrez la fiche, copiez l’URL, créez une règle **exact** ou **contains** sur un fragment unique du slug.

## Sélecteur « Ajouter au panier » (goal CLICK AbClos)

Thème classic Districlos (`product-add-to-cart.tpl`) :

```css
button.add-to-cart[data-button-action="add-to-cart"]
```

Alternative plus large :

```css
[data-button-action="add-to-cart"]
```

Le module propose aussi un track JS automatique (`views/js/track-cart.js`) via `prestashop.on('updateCart')` + fallback clic — plus fiable qu’un goal click seul (ajax, packs, sticky).

## Fichiers

```text
modules/abclos/
  abclos.php                 # Module + config + hook displayHeader
  views/templates/front/hook/snippet.tpl
  views/js/track-cart.js
  views/templates/admin/configure.tpl
  README.md
```

## Notes

- Ne pas committer de clés de production ; les défauts sont localhost / seed démo.
- Le module `ckcabtest` (ABlyft) est indépendant : évitez d’activer les deux en même temps pour un même test.
- Mixed content : si la boutique est en `https://`, un endpoint `http://localhost:3000` peut être bloqué par le navigateur — tester en `http://` local ou servir AbClos en HTTPS.
