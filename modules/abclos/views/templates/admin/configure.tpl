{**
 * Aide configuration AbClos (BO).
 *}
<div class="panel">
  <h3><i class="icon icon-info-circle"></i> AbClos</h3>
  <p>
    Collez la clé et l’URL du projet AbClos. Le panier et le revenu commande sont trackés automatiquement
    (pas besoin d’appeler <code>abclos.track</code> à la main).
  </p>
  <p class="help-block">
    Dans le dashboard AbClos, créez un objectif identifiant <strong>revenue</strong> (type Revenu)
    et liez-le à l’expérience. Le module envoie le montant TTC à la confirmation de commande.
    Ne créez <strong>pas</strong> d’objectif CLICK sur le bouton panier : le module envoie déjà <code>add_to_cart</code>.
  </p>
</div>
