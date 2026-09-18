# Patch Lengow (hors git)

Le module `lengow` n'est pas versionné. Après mise à jour du module Lengow, réappliquer les changements dans :

`modules/lengow/classes/models/LengowProduct.php`

1. Méthode `getImageLink()` : utiliser `getMarketplaceImageUrl()` au lieu de `Link::getImageLink()`.
2. Méthode `getImageUrlCombination()` : idem.
3. Ajouter la méthode protégée `getMarketplaceImageUrl($idImage)` (voir le fichier patché sur le serveur).

Sans ce patch, le flux Lengow continuera d'exposer les URLs `/img/p/...jpg` (contenu WebP si `PS_IMAGE_QUALITY = webp_all`).
