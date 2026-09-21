# Patch Lengow — suivi commande (hors git)

Le module `lengow` n'est pas versionné. Après mise à jour Lengow, réappliquer le patch dans :

`modules/lengow/classes/models/LengowMarketplace.php` → méthode `getAllParams()`.

Repère rapide : grep `[CUSTOM DISTRICLOS]` dans le fichier.

## Objectif

Districlos ne saisit en général **pas** de numéro de suivi transporteur sur les commandes marketplace.
Le suivi client passe par `dtc_ordertracking` (lien par référence + token).

Au passage en Expédié, Lengow envoie une action `ship` avec :
- `tracking_number` = n° transporteur **ou**, s'il est vide, la **référence commande** (requis Castorama / Leroy Merlin) ;
- `tracking_url` = URL `Dtc_ordertracking::getTrackingUrl()` si le module est actif, sinon logique native `Carrier.url` + `@`.

## 1. Fallback `tracking_number` (après lecture OrderCarrier / shipping_number)

```php
// [CUSTOM DISTRICLOS] Pas de n° transporteur : la référence commande sert de
// tracking_number (requis Castorama / Leroy Merlin). Voir dtc_ordertracking/LENGOW_PATCH.md
if ($trackingNumber === '' && !empty($lengowOrder->reference)) {
    $trackingNumber = (string) $lengowOrder->reference;
}
```

## 2. Remplacer le `case LengowAction::ARG_TRACKING_URL` par

```php
case LengowAction::ARG_TRACKING_URL:
    // [CUSTOM DISTRICLOS] Lien de suivi Districlos (référence + token)
    // via dtc_ordertracking, à la place de Carrier.url. Voir LENGOW_PATCH.md
    if (Module::isEnabled('dtc_ordertracking')) {
        if (!class_exists('Dtc_ordertracking', false)) {
            require_once _PS_MODULE_DIR_ . 'dtc_ordertracking/dtc_ordertracking.php';
        }
        if (class_exists('Dtc_ordertracking')
            && method_exists('Dtc_ordertracking', 'getTrackingUrl')
        ) {
            $params[$arg] = Dtc_ordertracking::getTrackingUrl($lengowOrder);
            break;
        }
    }
    if ($trackingNumber !== '') {
        $idActiveCarrier = LengowCarrier::getIdActiveCarrierByIdCarrier(
            (int) $lengowOrder->id_carrier,
            (int) $deliveryAddress->id_country
        );
        $idCarrier = $idActiveCarrier ?: (int) $lengowOrder->id_carrier;
        $carrier = new Carrier($idCarrier);
        $trackingUrl = str_replace('@', $trackingNumber, $carrier->url);
        if (Tools::strlen($trackingUrl) === 0) {
            if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'], true)) {
                break;
            }
            $defaultValue = $this->getDefaultValue((string) $arg);
            $trackingUrl = $defaultValue ?: $arg . ' not available';
        }
        $params[$arg] = $trackingUrl;
    }
    break;
```

## Effet

- Si `dtc_ordertracking` est actif → `tracking_url` = lien tokenisé (pas besoin du mode « référence seule » public).
- Si le module est désactivé / absent → comportement Lengow natif inchangé.
- Un vrai n° transporteur renseigné reste prioritaire pour `tracking_number` ; l’URL DTC est quand même envoyée tant que le module est actif.

## Tests rapides

1. Commande Lengow **sans** n° de suivi → passer en Expédié.
2. Vérifier dans `ps_lengow_actions` (params JSON de l’action `ship`) :
   - `tracking_number` = référence commande ;
   - `tracking_url` contient `dtc_ordertracking` / `track` + `token`.
3. Ouvrir l’URL → page de suivi Districlos.
