# Configurateur de produits

## Installation

**1.** Récupérer le projet
`git clone https://gitlab.com/ckcnet/prestashop/modules/product-configurator.git`

## A savoir

- Créer les catégories suivantes et reporter les ID si nécessaires dans controllers/front/configurator.php : 
    - Configurateur
        - Grillages Rigides
            - Pro
            - Pro plus
        - Grillages Souples
            - Soudé
            - Simple torsion
            - Agricole (Poule, ...)
        - Barreaudage
            - Industriel
            - Décoratif
            - Naturel
        - Options (désactivée)
            - Sobassements
            - Portillons
            - Occultations
            - Kits de montage
                - Kits scellement
                - Kits platines
        
- Modifier les ids des catégories dans classes/Config.php

- Ajouter dans le header.tpl du thème ces deux lignes lorsque le smart cache est activé :
```
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
```

- Affilier les produits aux bonnes catégories et s'assurer qu'ils peuvent être commandés si hors stock


---- INFORMATIONS SUPPLEMENTAIRES

-Images prévisualisation -> Tout se trouve dans /modules/ckc_configurator/views/images

Tout est rangé dans des dossiers : (portillons, occultations, hauteurs couleurs). La nomenclature coule de source en voyant tous les exemples.

- Concernant les données par défaut :
elles sont fixées dans /modules/ckc_configurator/classes/Configurator/Session.php méthod “default”

- Les hauteurs des poteaux :
Elles sont dans modules/ckc_configurator/classes/Product/Accessory.php méthod matchHeight l.187
Tu trouveras aussi le système du message “pose platine déconseillée” dans cette méthode

Imbriquer de nouvelles choses devrait se faire facilement si tu as besoin de rajouter une condition sur une type de pose par exemple

- Les ID des catégories sont renseignés dans modules/ckc_configurator/classes/Config.php
Tu trouveras aussi les types de pose (Si jamais tu veux les renommer un jour ^^)