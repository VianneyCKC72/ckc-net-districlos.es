# Patch Lengow (hors git)

Le module `lengow` n'est pas versionné. Après mise à jour du module Lengow, réappliquer les changements dans :

> **Aussi :** suivi marketplace via `dtc_ordertracking` → voir `modules/dtc_ordertracking/LENGOW_PATCH.md`  
> (`LengowMarketplace::getAllParams` — `tracking_number` = référence + `tracking_url` DTC).

> **Aussi :** explosion des packs à l'import → voir `modules/pm_advancedpack/LENGOW_PATCH.md`  
> (`LengowImportOrder::createCart` + `explodeAdvancedPackProducts`).

## 1. `modules/lengow/classes/models/LengowProduct.php`

1. Méthode `getImageLink()` : utiliser `getMarketplaceImageUrl()` au lieu de `Link::getImageLink()`.
2. Méthode `getImageUrlCombination()` : idem.
3. Ajouter la méthode protégée `getMarketplaceImageUrl($idImage)` (voir le fichier patché sur le serveur).

Sans ce patch, le flux Lengow continuera d'exposer les URLs `/img/p/...jpg` (contenu WebP si `PS_IMAGE_QUALITY = webp_all`).

## 2. `modules/lengow/classes/models/LengowExport.php` — Colonne `DESCRIPTION_MARKETPLACE`

Branche le champ "Description marketplace" (table `ps_dtc_lengow_marketplace_description`) sur le flux CSV Lengow. Lengow appelle déjà les deux méthodes ci-dessous (`self::setAdditionalFieldsValues` ligne ~577 et 599, `static::setAdditionalFields` ligne ~872) — il suffit de remplir leur corps vide.

Repère rapide : grep `[CUSTOM DISTRICLOS]` dans le fichier pour retrouver les zones modifiées.

### Remplacer les méthodes vides à la fin de la classe (vers la ligne 969) par :

```php
public static function setAdditionalFields($fields)
{
    // [CUSTOM DISTRICLOS] Colonne "description_marketplace" alimentée par
    // le module dtc_lengow_marketplace. Voir dtc_lengow_marketplace/LENGOW_PATCH.md
    if (self::isDtcMarketplaceDescriptionAvailable()) {
        $fields[] = 'description_marketplace';
    }

    return $fields;
}

public static function setAdditionalFieldsValues($product, $idProductAttribute = null, $arrayProduct = null)
{
    // [CUSTOM DISTRICLOS] Injection de la "description_marketplace" stockée
    // dans la table dédiée par le module dtc_lengow_marketplace.
    if (self::isDtcMarketplaceDescriptionAvailable() && $product instanceof Product) {
        $context = Context::getContext();
        $idLang = ($context && $context->language) ? (int) $context->language->id : 0;
        $idShop = ($context && $context->shop) ? (int) $context->shop->id : 0;

        $arrayProduct['description_marketplace'] = MarketplaceDescriptionRepository::getValue(
            (int) $product->id,
            $idLang,
            $idShop
        );
    }

    // this two lines are useless, but PrestaShop validator require it
    $product = $product;
    $idProductAttribute = $idProductAttribute;

    return $arrayProduct;
}

/**
 * [CUSTOM DISTRICLOS] Charge et vérifie la présence du repository dédié
 * au champ "Description marketplace". Retourne false si le module n'est
 * pas installé ou si la table custom n'existe pas, pour ne jamais casser
 * l'export Lengow.
 *
 * @return bool
 */
private static function isDtcMarketplaceDescriptionAvailable()
{
    if (!class_exists('MarketplaceDescriptionRepository', false)) {
        $repoPath = _PS_MODULE_DIR_ . 'dtc_lengow_marketplace/classes/MarketplaceDescriptionRepository.php';
        if (!file_exists($repoPath)) {
            return false;
        }
        require_once $repoPath;
    }

    return class_exists('MarketplaceDescriptionRepository')
        && MarketplaceDescriptionRepository::tableExists();
}
```

### Effet sur le flux

- Nouvelle colonne `DESCRIPTION_MARKETPLACE` ajoutée **à la fin** de `export/<shop>/flux-*.csv` (Lengow est en mode `legacy_fields` chez Districlos, d'où l'uppercase).
- Valeur lue par produit/shop/langue via `MarketplaceDescriptionRepository::getValue($idProduct, $idLang, $idShop)`.
- Les déclinaisons reprennent automatiquement la valeur du produit parent (Lengow rappelle `setAdditionalFieldsValues` sur chaque combinaison).
- Si le module `dtc_lengow_marketplace` est désinstallé ou si la table n'existe pas, la colonne **n'est pas ajoutée** : l'export Lengow continue normalement.

### Tests rapides après réapplication

1. Régénérer un flux ciblé :
   `https://www.cloture-discount.fr/modules/lengow/webservice/export.php?stream=1&format=csv&legacy_fields=1&product_ids=<id_produit>`
2. Vérifier la présence de la colonne `DESCRIPTION_MARKETPLACE` et sa valeur dans le CSV.

## 3. `modules/lengow/classes/models/LengowImportOrder.php` — Transporteur selon les produits commandés

Lengow ne permet qu'un transporteur par marketplace. Le patch délègue le choix au module
`dtc_lengow_marketplace`, configurable en BO (**Modules > Districlos - Marketplace Lengow > Configurer**).

### Règle

Trois listes de produits, chacune associée à un transporteur :

| Liste | Transporteur par défaut |
|---|---|
| Produits « portail » (packs portail + portillon inclus) | Grand colis (`id_reference` 1584) |
| Produits « portillon » | Petit colis (`id_reference` 1582) |
| Produits « autres » (lattes, poteaux, accessoires…) | Livraison Économique (`id_reference` 1096) |

Le **ciblage se fait produit par produit**, pas par catégorie : les catégories du projet ne sont
pas fiables pour cette distinction (la catégorie 169 « Portail aluminium » contient les portillons
alu comme sous-catégories, et `ps_product_carrier` ne discrimine pas non plus — 828 produits n'ont
ni la référence 1584 ni la 1582).

Un produit absent des trois listes est traité comme « autres ».

L'**ordre de priorité** est configurable et tranche les commandes mixtes. Par défaut
`portail > portillon > autres` : une commande portail + lattes part en grand colis.

Les transporteurs sont stockés par `id_reference` : PrestaShop crée un nouvel `id_carrier`
à chaque édition d'un transporteur, la référence reste stable.

### Modifications à réappliquer

Repère : grep `[CUSTOM DISTRICLOS]` dans le fichier.

1. Nouvelle propriété privée `$dtcMarketplaceOrderSkus = []` (près de `$carrierName`).
2. Dans `createCart(&$products)`, **première ligne de la méthode**, avant `getCartData()` :

```php
// [CUSTOM DISTRICLOS] SKU commandes, utilises par getCarrierId() pour choisir le transporteur
$this->dtcMarketplaceOrderSkus = array_keys($products);
```

3. Dans `getCarrierId()`, juste après le contrôle `$idCountry === 0` et **avant** le matching
   sémantique / manuel Lengow :

```php
// [CUSTOM DISTRICLOS] transporteur impose par le type de produits commandes (module dtc_lengow_marketplace)
$idCarrierByProductType = $this->getDtcMarketplaceIdCarrier();
if ($idCarrierByProductType) {
    return $idCarrierByProductType;
}
```

4. Nouvelle méthode privée `getDtcMarketplaceIdCarrier()` ajoutée avant `getCarrierId()`
   (voir le fichier patché : chargement de `MarketplaceCarrierResolver`, extraction des
   `id_product` depuis les SKU `id_product_id_product_attribute`, log Lengow du transporteur retenu).

### Garde-fous

- Le SKU utilisé est celui **envoyé par la marketplace** (avant explosion Advanced Pack),
  car `getCartData()` s'exécute avant `explodeAdvancedPackProducts()`. **Les produits packs
  doivent donc figurer eux-mêmes dans la liste « portail »**, pas seulement leurs composants.
- Si le module est absent, désinstallé, ou si la case « Activer la règle transporteur » est à
  Non, le comportement Lengow natif est intégralement conservé.
- Si aucune liste de produits n'est renseignée, la règle renvoie 0 et Lengow reprend la main :
  une configuration vide ne fait jamais partir toutes les commandes en Économique.
- Si le transporteur configuré n'est plus actif / plus associé à la boutique, la règle renvoie 0
  et Lengow reprend son matching par défaut.
- La configuration refuse d'être activée sans les 3 transporteurs et au moins un produit.
- Les produits désactivés déjà sélectionnés restent affichés dans les listes, sinon ils seraient
  silencieusement effacés au prochain enregistrement.

### Maintenance

Un **nouveau produit** n'est dans aucune liste : il part donc en Économique. Penser à l'ajouter
à la bonne liste lors de la création d'un portail, d'un portillon ou d'un pack.

### Côté module (versionné dans le module, pas dans Lengow)

- `classes/MarketplaceCarrierResolver.php` : classification et résolution vers un `id_carrier` actif.
- `views/templates/admin/carrier_configuration.tpl` : écran de configuration. Rendu maison et non
  `HelperForm` : avec ~800 produits × 3 listes, le `form.tpl` du core génère 5 Mo de HTML
  (il reboucle sur toutes les valeurs sélectionnées dans chaque `<option>`) contre 314 Ko ici.
- `upgrade/upgrade-1.3.0.php` : amorce une seule fois les listes de produits depuis les catégories
  portail / portillon, puis supprime les clés de configuration `DTC_LGWMP_CATEGORIES_*`.

### Vérification

1. BO : configurer le module, vérifier que les 3 transporteurs, l'ordre de priorité et les listes
   de produits sont bien enregistrés.
2. Importer une commande marketplace de test par type de produit.
3. Vérifier `ps_order_carrier.id_carrier` / le transporteur affiché sur la commande.
4. Vérifier la ligne `Districlos: transporteur "..." applique selon le type de produits commandes`
   dans les logs Lengow.

## 4. Multi-statuts « Commandes expédiées »

Plusieurs statuts PrestaShop peuvent déclencher l’action marketplace `ship`
(ex. Expédié + Livré + un statut Odoo).

### Fichiers patchés

| Fichier | Modification |
|---|---|
| `LengowConfiguration.php` | `PARAM_MULTIPLE` + select « Commandes expédiées » en multi |
| `LengowConfigurationForm.php` | Rendu `<select multiple>` + sauvegarde CSV (déjà gérée par `postProcess`) |
| `LengowMain.php` | `getShippedOrderStateIds()` / `isShippedOrderState()` ; `getOrderState(shipped)` = **premier** ID (cible import MP → PS) |
| `LengowHook.php` | Déclenchement `ship` si le nouveau statut est dans la liste |
| `LengowOrder.php` | Cron unsent + resend + annulation depuis « expédié » |
| `LengowToolbox.php` | Correspondance toolbox |

Repère : `grep [CUSTOM DISTRICLOS]` dans ces fichiers.

### Utilisation BO

Lengow → Commandes → Paramètres des commandes → **Commandes expédiées** :
sélection multiple (Ctrl+clic / Select2). Valeur stockée : `4,42,51` dans `LENGOW_ORDER_ID_SHIPPED`.

Compatibilité : une ancienne valeur mono (`4`) continue de fonctionner.
