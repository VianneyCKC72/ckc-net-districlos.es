# Hook de Promotion pour Utilisateurs Professionnels

## Description
Ce hook affiche une bannière de promotion spéciale pour les utilisateurs connectés en tant que professionnels ou proticuliers.

## Fonctionnalités

### 🎯 Ciblage Intelligent
- **Professionnels (groupe PRO)** : Promotion de 15% avec le code `PROPRO15`
- **Proticuliers** : Promotion de 10% avec le code `PROT10`
- **Particuliers/Visiteurs** : Aucune promotion affichée

### 🎨 Interface Utilisateur
- Bannière fixe en haut de page avec animation d'apparition
- Design responsive (mobile/desktop)
- Bouton de copie du code promo avec feedback visuel
- Bouton de fermeture avec mémorisation (24h)
- Styles différenciés selon le type d'utilisateur

### ⚙️ Utilisation du Hook

#### Dans un template Smarty :
```smarty
{hook h='displayPromotion'}
```

#### Dans un contrôleur PHP :
```php
$promoContent = Hook::exec('displayPromotion');
echo $promoContent;
```

#### Dans un template Twig (PrestaShop 1.7+) :
```twig
{{ hook('displayPromotion') }}
```

### 📁 Fichiers Créés

1. **Hook PHP** : `ckc_espacepro.php` (méthode `hookDisplayPromotion`)
2. **Template** : `views/templates/hook/promotion.tpl`
3. **Styles CSS** : `views/css/front/promotion.css`

### 🔧 Configuration

Le hook utilise les configurations existantes du module :
- `CKC_GROUP_PRO_ID` : ID du groupe professionnel
- `CKC_GROUP_PROTICULIER_ID` : ID du groupe proticulier

### 📱 Responsive Design

La bannière s'adapte automatiquement :
- **Desktop** : Bannière horizontale avec tous les éléments
- **Tablet** : Réorganisation en colonne
- **Mobile** : Version compacte avec bouton de fermeture repositionné

### 🎭 Personnalisation

#### Modifier les codes promo :
Éditez la méthode `hookDisplayPromotion()` dans `ckc_espacepro.php` :

```php
$promoCode = $isPro ? 'VOTRE_CODE_PRO' : 'VOTRE_CODE_PROTICULIER';
$promoDiscount = $isPro ? '20%' : '15%';
```

#### Modifier les styles :
Éditez le fichier `views/css/front/promotion.css` pour personnaliser l'apparence.

#### Modifier le template :
Éditez `views/templates/hook/promotion.tpl` pour changer la structure HTML.

### 🚀 Activation

Le hook est automatiquement enregistré lors de l'installation du module. Pour l'activer manuellement :

```php
$this->registerHook('displayPromotion');
```

### 💡 Exemples d'Intégration

#### Page d'accueil :
```smarty
{* Dans le template de la page d'accueil *}
<div id="wrapper">
    {hook h='displayPromotion'}
    {* Contenu de la page *}
</div>
```

#### Header global :
```smarty
{* Dans le header du thème *}
<body>
    {hook h='displayPromotion'}
    <header>
        {* Contenu du header *}
    </header>
```

### 🔍 Fonctionnalités Avancées

#### Mémorisation de fermeture :
La bannière utilise `localStorage` pour se souvenir si l'utilisateur l'a fermée (pendant 24h).

#### Copie automatique :
Le code promo peut être copié en un clic avec feedback visuel.

#### Animation fluide :
Animations CSS pour l'apparition et la disparition de la bannière.

### 🛠️ Dépannage

#### La bannière ne s'affiche pas :
1. Vérifiez que l'utilisateur est connecté et dans le bon groupe
2. Vérifiez que le hook est appelé dans le template
3. Vérifiez que le CSS est bien chargé

#### Problème de style :
1. Vérifiez que le fichier CSS est accessible
2. Vérifiez les conflits avec le thème existant
3. Utilisez les outils de développement du navigateur

#### Code promo ne se copie pas :
1. Vérifiez que le navigateur supporte `navigator.clipboard`
2. Le fallback utilise `document.execCommand` pour les anciens navigateurs

### 📊 Métriques Suggérées

Pour suivre l'efficacité de la promotion :
- Nombre d'affichages de la bannière
- Taux de copie du code promo
- Taux de fermeture de la bannière
- Conversions avec les codes promo

Ces métriques peuvent être ajoutées via Google Analytics ou un système de tracking personnalisé.
