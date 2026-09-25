# Patch Lengow — explosion des packs marketplace

Le module `lengow` n'est pas versionné. Après une mise à jour Lengow, réappliquer
le bloc repéré par `[CUSTOM DISTRICLOS]` dans :

`modules/lengow/classes/models/LengowImportOrder.php`

## Objectif

La marketplace commande un SKU pack unique. À l'import, PrestaShop doit créer
les lignes des composants Advanced Pack, comme pour une commande du front.

## Fonctionnement

Dans `createCart(&$products)`, après la création du panier et avant
`LengowCart::addProducts()` :

1. `explodeAdvancedPackProducts()` détecte les produits AP5 ;
2. récupère leurs composants et déclinaisons via `AdvancedPack` ;
3. remplace le SKU pack par les SKU des composants ;
4. multiplie leurs quantités par la quantité de packs commandée ;
5. répartit le prix TTC marketplace selon les proportions AP5 ;
6. affecte le reliquat d'arrondi au dernier composant afin que la somme reste
   exactement égale au prix marketplace ;
7. rattache l'identifiant de ligne marketplace au premier composant pour éviter
   plusieurs actions Lengow sur une même ligne.

`mergeLengowProduct()` fusionne les éventuels composants identiques et recalcule
leur prix unitaire pondéré sans modifier le total.

## Garde-fous

- Si `pm_advancedpack` est désactivé ou absent, le comportement Lengow natif est
  conservé.
- Si un pack ne peut pas être lu ou calculé, il reste une ligne pack et l'erreur
  est écrite dans les logs Lengow.
- Les produits simples ne sont pas modifiés.
- Le panier et la commande Lengow contiennent directement les composants :
  le stock est donc décrémenté sur les composants.

## Vérification après réapplication

Importer une commande de test contenant un pack et vérifier :

1. aucune ligne pack dans `ps_order_detail` ;
2. une ligne par composant, avec les bonnes déclinaisons et quantités ;
3. somme TTC des composants = prix TTC du pack envoyé par la marketplace ;
4. stock décrémenté uniquement sur les composants ;
5. commande Odoo reçue avec les composants ;
6. une seule ligne `ps_lengow_order_line` pour la ligne pack marketplace.

## Limite connue

Une ligne marketplace représente le pack complet alors que PrestaShop contient
plusieurs composants. Elle est donc rattachée au premier composant. Un
remboursement partiel d'un autre composant ne peut pas être traduit
automatiquement en remboursement partiel du pack marketplace sans règle métier
supplémentaire.
