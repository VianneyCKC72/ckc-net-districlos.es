<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* __string_template__e103b1fd245c1cd58c10759f6133c1c69df41884535f0bff3fd8619b478ca9cd */
class __TwigTemplate_da32628b0f50faf47e705330f7168b2568193e46b4697ae66583c548d3426883 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'stylesheets' => [$this, 'block_stylesheets'],
            'extra_stylesheets' => [$this, 'block_extra_stylesheets'],
            'content_header' => [$this, 'block_content_header'],
            'content' => [$this, 'block_content'],
            'content_footer' => [$this, 'block_content_footer'],
            'sidebar_right' => [$this, 'block_sidebar_right'],
            'javascripts' => [$this, 'block_javascripts'],
            'extra_javascripts' => [$this, 'block_extra_javascripts'],
            'translate_javascripts' => [$this, 'block_translate_javascripts'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!DOCTYPE html>
<html lang=\"fr\">
<head>
  <meta charset=\"utf-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
<meta name=\"apple-mobile-web-app-capable\" content=\"yes\">
<meta name=\"robots\" content=\"NOFOLLOW, NOINDEX\">

<link rel=\"icon\" type=\"image/x-icon\" href=\"/img/favicon.ico\" />
<link rel=\"apple-touch-icon\" href=\"/img/app_icon.png\" />

<title>Notifications des modules • Districlos</title>

  <script type=\"text/javascript\">
    var help_class_name = 'AdminModulesNotifications';
    var iso_user = 'fr';
    var lang_is_rtl = '0';
    var full_language_code = 'fr';
    var full_cldr_language_code = 'fr-FR';
    var country_iso_code = 'FR';
    var _PS_VERSION_ = '8.2.0';
    var roundMode = 2;
    var youEditFieldFor = '';
        var new_order_msg = 'Une nouvelle commande a été passée sur votre boutique.';
    var order_number_msg = 'Numéro de commande : ';
    var total_msg = 'Total : ';
    var from_msg = 'Du ';
    var see_order_msg = 'Afficher cette commande';
    var new_customer_msg = 'Un nouveau client s\\'est inscrit sur votre boutique.';
    var customer_name_msg = 'Nom du client : ';
    var new_msg = 'Un nouveau message a été posté sur votre boutique.';
    var see_msg = 'Lire le message';
    var token = '14f0c1ced15312c06e4d3e550e553f6b';
    var currentIndex = 'index.php?controller=AdminModulesNotifications';
    var employee_token = 'a502bb5caa8ebe6f9c53192bf8927760';
    var choose_language_translate = 'Choisissez la langue :';
    var default_language = '1';
    var admin_modules_link = '/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/manage?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A';
    var admin_notification_get_link = '/admin024h9xfokhvsbvfmnzn/index.php/common/notifications?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A';
    var admin_notification_push_link = adminNotificationPushLink = '/admin024h9xfokhvsbvfmnzn/index.php/common/notifications/ack?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0";
        // line 40
        echo "A';
    var tab_modules_list = '';
    var update_success_msg = 'Mise à jour réussie';
    var search_product_msg = 'Rechercher un produit';
  </script>



<link
      rel=\"preload\"
      href=\"/admin024h9xfokhvsbvfmnzn/themes/new-theme/public/2d8017489da689caedc1.preload..woff2\"
      as=\"font\"
      crossorigin
    >
      <link href=\"/admin024h9xfokhvsbvfmnzn/themes/new-theme/public/create_product_default_theme.css\" rel=\"stylesheet\" type=\"text/css\"/>
      <link href=\"/admin024h9xfokhvsbvfmnzn/themes/new-theme/public/theme.css\" rel=\"stylesheet\" type=\"text/css\"/>
      <link href=\"/js/jquery/plugins/chosen/jquery.chosen.css\" rel=\"stylesheet\" type=\"text/css\"/>
      <link href=\"/js/jquery/plugins/fancybox/jquery.fancybox.css\" rel=\"stylesheet\" type=\"text/css\"/>
      <link href=\"/modules/blockwishlist/public/backoffice.css\" rel=\"stylesheet\" type=\"text/css\"/>
      <link href=\"/admin024h9xfokhvsbvfmnzn/themes/default/css/vendor/nv.d3.css\" rel=\"stylesheet\" type=\"text/css\"/>
  
  <script type=\"text/javascript\">
var baseAdminDir = \"\\/admin024h9xfokhvsbvfmnzn\\/\";
var baseDir = \"\\/\";
var changeFormLanguageUrl = \"\\/admin024h9xfokhvsbvfmnzn\\/index.php\\/configure\\/advanced\\/employees\\/change-form-language?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\";
var currency = {\"iso_code\":\"EUR\",\"sign\":\"\\u20ac\",\"name\":\"Euro\",\"format\":null};
var currency_specifications = {\"symbol\":[\",\",\"\\u202f\",\";\",\"%\",\"-\",\"+\",\"E\",\"\\u00d7\",\"\\u2030\",\"\\u221e\",\"NaN\"],\"currencyCode\":\"EUR\",\"currencySymbol\":\"\\u20ac\",\"numberSymbols\":[\",\",\"\\u202f\",\";\",\"%\",\"-\",\"+\",\"E\",\"\\u00d7\",\"\\u2030\",\"\\u221e\",\"NaN\"],\"positivePattern\":\"#,##0.00\\u00a0\\u00a4\",\"negativePattern\":\"-#,##0.00\\u00a0\\u00a4\",\"maxFractionDigits\":2,\"minFractionDigits\":2,\"groupingUsed\":true,\"primaryGroupSize\":3,\"secondaryGroupSize\":3};
var number_specifications = {\"symbol\":[\",\",\"\\u202f\",\";\",\"%\",\"-\",\"+\",\"E\",\"\\u00d7\",\"\\u2030\",\"\\u221e\",\"NaN\"],\"numberSymbols\":[\",\",\"\\u202f\",\";\",\"%\",\"-\",\"+\",\"E\",\"\\u00d7\",\"\\u2030\",\"\\u221e\",\"NaN\"],\"positivePattern\":\"#,##0.###";
        // line 67
        echo "\",\"negativePattern\":\"-#,##0.###\",\"maxFractionDigits\":3,\"minFractionDigits\":0,\"groupingUsed\":true,\"primaryGroupSize\":3,\"secondaryGroupSize\":3};
var prestashop = {\"debug\":false};
var show_new_customers = \"1\";
var show_new_messages = \"1\";
var show_new_orders = \"1\";
</script>
<script type=\"text/javascript\" src=\"/admin024h9xfokhvsbvfmnzn/themes/new-theme/public/main.bundle.js\"></script>
<script type=\"text/javascript\" src=\"/js/jquery/plugins/jquery.chosen.js\"></script>
<script type=\"text/javascript\" src=\"/js/jquery/plugins/fancybox/jquery.fancybox.js\"></script>
<script type=\"text/javascript\" src=\"/js/admin.js?v=8.2.0\"></script>
<script type=\"text/javascript\" src=\"/admin024h9xfokhvsbvfmnzn/themes/new-theme/public/cldr.bundle.js\"></script>
<script type=\"text/javascript\" src=\"/js/tools.js?v=8.2.0\"></script>
<script type=\"text/javascript\" src=\"/admin024h9xfokhvsbvfmnzn/themes/new-theme/public/create_product.bundle.js\"></script>
<script type=\"text/javascript\" src=\"/modules/blockwishlist/public/vendors.js\"></script>
<script type=\"text/javascript\" src=\"/js/vendor/d3.v3.min.js\"></script>
<script type=\"text/javascript\" src=\"/admin024h9xfokhvsbvfmnzn/themes/default/js/vendor/nv.d3.min.js\"></script>
<script type=\"text/javascript\" src=\"/modules/ps_emailalerts/js/admin/ps_emailalerts.js\"></script>
<script type=\"text/javascript\" src=\"/modules/ps_faviconnotificationbo/views/js/favico.js\"></script>
<script type=\"text/javascript\" src=\"/modules/ps_faviconnotificationbo/views/js/ps_faviconnotificationbo.js\"></script>

  <script>
  if (undefined !== ps_faviconnotificationbo) {
    ps_faviconnotificationbo.initialize({
      backgroundColor: '#DF0067',
      textColor: '#FFFFFF',
      notificationGetUrl: '/admin024h9xfokhvsbvfmnzn/index.php/common/notifications?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A',
      CHECKBOX_ORDER: 1,
      CHECKBOX_CUSTOMER: 1,
      CHECKBOX_MESSAGE: 1,
      timer: 120000, // Refresh every 2 minutes
    });
  }
</script>


";
        // line 102
        $this->displayBlock('stylesheets', $context, $blocks);
        $this->displayBlock('extra_stylesheets', $context, $blocks);
        echo "</head>";
        echo "

<body
  class=\"lang-fr adminmodulesnotifications\"
  data-base-url=\"/admin024h9xfokhvsbvfmnzn/index.php\"  data-token=\"F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\">

  <header id=\"header\" class=\"d-print-none\">

    <nav id=\"header_infos\" class=\"main-header\">
      <button class=\"btn btn-primary-reverse onclick btn-lg unbind ajax-spinner\"></button>

            <i class=\"material-icons js-mobile-menu\">menu</i>
      <a id=\"header_logo\" class=\"logo float-left\" href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminDashboard&amp;token=651ec98d335ae4dfaabe27f04eb8baf7\"></a>
      <span id=\"shop_version\">8.2.0</span>

      <div class=\"component\" id=\"quick-access-container\">
        <div class=\"dropdown quick-accesses\">
  <button class=\"btn btn-link btn-sm dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" id=\"quick_select\">
    Accès rapide
  </button>
  <div class=\"dropdown-menu\">
          <a class=\"dropdown-item quick-row-link \"
         href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/sell/orders?token=26bf0da7f50f776d18d5de426c45040e\"
                 data-item=\"Commandes\"
      >Commandes</a>
          <a class=\"dropdown-item quick-row-link \"
         href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminStats&amp;module=statscheckup&amp;token=fc695bca415bbdbf788b41144341cd72\"
                 data-item=\"Évaluation du catalogue\"
      >Évaluation du catalogue</a>
          <a class=\"dropdown-item quick-row-link \"
         href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/manage?token=26bf0da7f50f776d18d5de426c45040e\"
                 data-item=\"Modules installés\"
      >Modules installés</a>
          <a class=\"dropdown-item quick-row-link \"
         href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCartRules&amp;addcart_rule&amp;token=e6711c20532b801";
        // line 136
        echo "eb253ae06141ca861\"
                 data-item=\"Nouveau bon de réduction\"
      >Nouveau bon de réduction</a>
          <a class=\"dropdown-item quick-row-link new-product-button\"
         href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/products-v2/create?token=26bf0da7f50f776d18d5de426c45040e\"
                 data-item=\"Nouveau produit\"
      >Nouveau produit</a>
          <a class=\"dropdown-item quick-row-link \"
         href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/categories/new?token=26bf0da7f50f776d18d5de426c45040e\"
                 data-item=\"Nouvelle catégorie\"
      >Nouvelle catégorie</a>
        <div class=\"dropdown-divider\"></div>
          <a id=\"quick-add-link\"
        class=\"dropdown-item js-quick-link\"
        href=\"#\"
        data-rand=\"97\"
        data-icon=\"icon-AdminModulesSf\"
        data-method=\"add\"
        data-url=\"index.php/improve/modules/alerts?-YQXjYJcDPmUh910PX0hZv1JT0A\"
        data-post-link=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminQuickAccesses&token=42f1790c867802b3c59b9e9413e271c5\"
        data-prompt-text=\"Veuillez nommer ce raccourci :\"
        data-link=\"Alertes - Liste\"
      >
        <i class=\"material-icons\">add_circle</i>
        Ajouter la page actuelle à l'accès rapide
      </a>
        <a id=\"quick-manage-link\" class=\"dropdown-item\" href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminQuickAccesses&token=42f1790c867802b3c59b9e9413e271c5\">
      <i class=\"material-icons\">settings</i>
      Gérez vos accès rapides
    </a>
  </div>
</div>
      </div>
      <div class=\"component component-search\" id=\"header-search-container\">
        <div class=\"component-search-body\">
          <div class=\"component-search-top\">
            <form id=\"header_search\"
      class=\"bo_search_form dropdown-form js-dropdown-form collapsed\"
      method=\"post\"
      action=\"/admin024h9xf";
        // line 175
        echo "okhvsbvfmnzn/index.php?controller=AdminSearch&amp;token=5a1b1a9f6b7a167d2c939d61928ba7d7\"
      role=\"search\">
  <input type=\"hidden\" name=\"bo_search_type\" id=\"bo_search_type\" class=\"js-search-type\" />
    <div class=\"input-group\">
    <input type=\"text\" class=\"form-control js-form-search\" id=\"bo_query\" name=\"bo_query\" value=\"\" placeholder=\"Rechercher (ex. : référence produit, nom du client, etc.)\" aria-label=\"Barre de recherche\">
    <div class=\"input-group-append\">
      <button type=\"button\" class=\"btn btn-outline-secondary dropdown-toggle js-dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
        Partout
      </button>
      <div class=\"dropdown-menu js-items-list\">
        <a class=\"dropdown-item\" data-item=\"Partout\" href=\"#\" data-value=\"0\" data-placeholder=\"Que souhaitez-vous trouver ?\" data-icon=\"icon-search\"><i class=\"material-icons\">search</i> Partout</a>
        <div class=\"dropdown-divider\"></div>
        <a class=\"dropdown-item\" data-item=\"Catalogue\" href=\"#\" data-value=\"1\" data-placeholder=\"Nom du produit, référence, etc.\" data-icon=\"icon-book\"><i class=\"material-icons\">store_mall_directory</i> Catalogue</a>
        <a class=\"dropdown-item\" data-item=\"Clients par nom\" href=\"#\" data-value=\"2\" data-placeholder=\"Nom\" data-icon=\"icon-group\"><i class=\"material-icons\">group</i> Clients par nom</a>
        <a class=\"dropdown-item\" data-item=\"Clients par adresse IP\" href=\"#\" data-value=\"6\" data-placeholder=\"123.45.67.89\" data-icon=\"icon-desktop\"><i class=\"material-icons\">desktop_mac</i> Clients par adresse IP</a>
        <a class=\"dropdown-item\" data-item=\"Commandes\" href=\"#\" data-value=\"3\" data-placeholder=\"ID commande\" data-icon=\"icon-credit-card\"><i class=\"material-icons\">shopping_basket</i> Commandes</a>
        <a class=\"dropdown-item\" data-item=\"Factures\" href=\"#\" data-value=\"4\" data-placeholder=\"Numéro de facture\" data-icon=\"icon-book\"><i class=\"material-icons\">book</i> Factures</a>
        <a class=\"dropdown-ite";
        // line 192
        echo "m\" data-item=\"Paniers\" href=\"#\" data-value=\"5\" data-placeholder=\"ID panier\" data-icon=\"icon-shopping-cart\"><i class=\"material-icons\">shopping_cart</i> Paniers</a>
        <a class=\"dropdown-item\" data-item=\"Modules\" href=\"#\" data-value=\"7\" data-placeholder=\"Nom du module\" data-icon=\"icon-puzzle-piece\"><i class=\"material-icons\">extension</i> Modules</a>
      </div>
      <button class=\"btn btn-primary\" type=\"submit\"><span class=\"d-none\">RECHERCHE</span><i class=\"material-icons\">search</i></button>
    </div>
  </div>
</form>

<script type=\"text/javascript\">
 \$(document).ready(function(){
    \$('#bo_query').one('click', function() {
    \$(this).closest('form').removeClass('collapsed');
  });
});
</script>
            <button class=\"component-search-cancel d-none\">Annuler</button>
          </div>

          <div class=\"component-search-quickaccess d-none\">
  <p class=\"component-search-title\">Accès rapide</p>
      <a class=\"dropdown-item quick-row-link\"
       href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/sell/orders?token=26bf0da7f50f776d18d5de426c45040e\"
             data-item=\"Commandes\"
    >Commandes</a>
      <a class=\"dropdown-item quick-row-link\"
       href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminStats&amp;module=statscheckup&amp;token=fc695bca415bbdbf788b41144341cd72\"
             data-item=\"Évaluation du catalogue\"
    >Évaluation du catalogue</a>
      <a class=\"dropdown-item quick-row-link\"
       href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/manage?token=26bf0da7f50f776d18d5de426c45040e\"
             data-item=\"Modules installés\"
    >Modules installés</a>
      <a class=\"dropdown-item quick-row-link\"
       href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCartRules&amp;addcart_rule&amp;token=e6711c20532b801eb253ae06141ca861\"
             data-item=\"Nouveau bon de réduction\"
    >Nouveau bon de rédu";
        // line 227
        echo "ction</a>
      <a class=\"dropdown-item quick-row-link\"
       href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/products-v2/create?token=26bf0da7f50f776d18d5de426c45040e\"
             data-item=\"Nouveau produit\"
    >Nouveau produit</a>
      <a class=\"dropdown-item quick-row-link\"
       href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/categories/new?token=26bf0da7f50f776d18d5de426c45040e\"
             data-item=\"Nouvelle catégorie\"
    >Nouvelle catégorie</a>
    <div class=\"dropdown-divider\"></div>
      <a id=\"quick-add-link\"
      class=\"dropdown-item js-quick-link\"
      href=\"#\"
      data-rand=\"188\"
      data-icon=\"icon-AdminModulesSf\"
      data-method=\"add\"
      data-url=\"index.php/improve/modules/alerts?-YQXjYJcDPmUh910PX0hZv1JT0A\"
      data-post-link=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminQuickAccesses&token=42f1790c867802b3c59b9e9413e271c5\"
      data-prompt-text=\"Veuillez nommer ce raccourci :\"
      data-link=\"Alertes - Liste\"
    >
      <i class=\"material-icons\">add_circle</i>
      Ajouter la page actuelle à l'accès rapide
    </a>
    <a id=\"quick-manage-link\" class=\"dropdown-item\" href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminQuickAccesses&token=42f1790c867802b3c59b9e9413e271c5\">
    <i class=\"material-icons\">settings</i>
    Gérez vos accès rapides
  </a>
</div>
        </div>

        <div class=\"component-search-background d-none\"></div>
      </div>

      
      
      <div class=\"header-right\">
                  <div class=\"component\" id=\"header-shop-list-container\">
              <div class=\"shop-list\">
    <a class=\"link\" id=\"header_shopname\" href=\"https://districlos.ckc-net.com/\" target= \"_blank\">
      <i class=\"material-icons\">visibility</i>
      <span>Voir ma boutique</span>
    </a>
  </div>
          </div>
                          <div class=\"component header-right-";
        // line 272
        echo "component\" id=\"header-notifications-container\">
            <div id=\"notif\" class=\"notification-center dropdown dropdown-clickable\">
  <button class=\"btn notification js-notification dropdown-toggle\" data-toggle=\"dropdown\">
    <i class=\"material-icons\">notifications_none</i>
    <span id=\"notifications-total\" class=\"count hide\">0</span>
  </button>
  <div class=\"dropdown-menu dropdown-menu-right js-notifs_dropdown\">
    <div class=\"notifications\">
      <ul class=\"nav nav-tabs\" role=\"tablist\">
                          <li class=\"nav-item\">
            <a
              class=\"nav-link active\"
              id=\"orders-tab\"
              data-toggle=\"tab\"
              data-type=\"order\"
              href=\"#orders-notifications\"
              role=\"tab\"
            >
              Commandes<span id=\"_nb_new_orders_\"></span>
            </a>
          </li>
                                    <li class=\"nav-item\">
            <a
              class=\"nav-link \"
              id=\"customers-tab\"
              data-toggle=\"tab\"
              data-type=\"customer\"
              href=\"#customers-notifications\"
              role=\"tab\"
            >
              Clients<span id=\"_nb_new_customers_\"></span>
            </a>
          </li>
                                    <li class=\"nav-item\">
            <a
              class=\"nav-link \"
              id=\"messages-tab\"
              data-toggle=\"tab\"
              data-type=\"customer_message\"
              href=\"#messages-notifications\"
              role=\"tab\"
            >
              Messages<span id=\"_nb_new_messages_\"></span>
            </a>
          </li>
                        </ul>

      <!-- Tab panes -->
      <div class=\"tab-content\">
                          <div class=\"tab-pane active empty\" id=\"orders-notifications\" role=\"tabpanel\">
            <p class=\"no-notification\">
              Pas de nouvelle commande pour le moment :(<br>
              Avez-vous consulté vos <strong><a href=\"https://distri";
        // line 324
        echo "clos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCarts&action=filterOnlyAbandonedCarts&token=06f5ebd95d4bbceb7d696085a6df9989\">paniers abandonnés</a></strong> ?<br> Votre prochaine commande s'y trouve peut-être !
            </p>
            <div class=\"notification-elements\"></div>
          </div>
                                    <div class=\"tab-pane  empty\" id=\"customers-notifications\" role=\"tabpanel\">
            <p class=\"no-notification\">
              Aucun nouveau client pour l'instant :(<br>
              Êtes-vous actifs sur les réseaux sociaux en ce moment ?
            </p>
            <div class=\"notification-elements\"></div>
          </div>
                                    <div class=\"tab-pane  empty\" id=\"messages-notifications\" role=\"tabpanel\">
            <p class=\"no-notification\">
              Pas de nouveau message pour l'instant.<br>
              On dirait que vos clients sont satisfaits :)
            </p>
            <div class=\"notification-elements\"></div>
          </div>
                        </div>
    </div>
  </div>
</div>

  <script type=\"text/html\" id=\"order-notification-template\">
    <a class=\"notif\" href='order_url'>
      #_id_order_ -
      de <strong>_customer_name_</strong> (_iso_code_)_carrier_
      <strong class=\"float-sm-right\">_total_paid_</strong>
    </a>
  </script>

  <script type=\"text/html\" id=\"customer-notification-template\">
    <a class=\"notif\" href='customer_url'>
      #_id_customer_ - <strong>_customer_name_</strong>_company_ - enregistré le <strong>_date_add_</strong>
    </a>
  </script>

  <script type=\"text/html\" id=\"message-notification-template\">
    <a class=\"notif\" href='message_url'>
    <span class=\"message-notification-status _status_\">
      <i class=\"material-icons\">fiber_manual_record</i> _status_
    </span>
      - <strong>_customer_name_</strong> (_company_) - <i class=\"material-icons\">access_time</i> _date_add_
    </a>
  </script>
          </div>
        
  ";
        // line 371
        echo "      <div class=\"component\" id=\"header-employee-container\">
          <div class=\"dropdown employee-dropdown\">
  <div class=\"rounded-circle person\" data-toggle=\"dropdown\">
    <i class=\"material-icons\">account_circle</i>
  </div>
  <div class=\"dropdown-menu dropdown-menu-right\">
    <div class=\"employee-wrapper-avatar\">
      <div class=\"employee-top\">
        <span class=\"employee-avatar\"><img class=\"avatar rounded-circle\" src=\"https://districlos.ckc-net.com/img/pr/default.jpg\" alt=\"Admin\" /></span>
        <span class=\"employee_profile\">Ravi de vous revoir Admin</span>
      </div>

      <a class=\"dropdown-item employee-link profile-link\" href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/employees/1/edit?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\">
      <i class=\"material-icons\">edit</i>
      <span>Votre profil</span>
    </a>
    </div>

    <p class=\"divider\"></p>

    
    <a class=\"dropdown-item employee-link text-center\" id=\"header_logout\" href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminLogin&amp;logout=1&amp;token=8688fff763956efe4f235764ad7feb57\">
      <i class=\"material-icons d-lg-none\">power_settings_new</i>
      <span>Déconnexion</span>
    </a>
  </div>
</div>
        </div>
              </div>
    </nav>
  </header>

  <nav class=\"nav-bar d-none d-print-none d-md-block\">
  <span class=\"menu-collapse\" data-toggle-url=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/employees/toggle-navigation?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\">
    <i class=\"material-icons rtl-flip\">chevron_left</i>
    <i class=\"material-icons rtl-flip\">chevron_left</i>
  </span>

  <div class=\"nav-bar-overflow\">
      <div class=\"logo-container\">
          <a id=\"header_logo\" class=\"logo float-left\" href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminDashboard&amp;token=651ec98d335ae4dfaabe27f04eb8baf7\"></a>
          <span id=\"shop_version\" class=\"header-vers";
        // line 412
        echo "ion\">8.2.0</span>
      </div>

      <ul class=\"main-menu\">
              
                    
                    
          
            <li class=\"link-levelone\" data-submenu=\"1\" id=\"tab-AdminDashboard\">
              <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminDashboard&amp;token=651ec98d335ae4dfaabe27f04eb8baf7\" class=\"link\" >
                <i class=\"material-icons\">trending_up</i> <span>Tableau de bord</span>
              </a>
            </li>

          
                      
                                          
                    
          
            <li class=\"category-title\" data-submenu=\"2\" id=\"tab-SELL\">
                <span class=\"title\">Vendre</span>
            </li>

                              
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"3\" id=\"subtab-AdminParentOrders\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/orders/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-shopping_basket\">shopping_basket</i>
                      <span>
                      Commandes
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-3\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"4\" id=\"subtab-AdminOrders\">
                                <a href=\"/admin024h9xf";
        // line 454
        echo "okhvsbvfmnzn/index.php/sell/orders/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Commandes
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"5\" id=\"subtab-AdminInvoices\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/orders/invoices/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Factures
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"6\" id=\"subtab-AdminSlip\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/orders/credit-slips/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Avoirs
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"7\" id=\"subtab-AdminDeliverySlip\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/orders/delivery-slips/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Bons de livraison
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltw";
        // line 485
        echo "o\" data-submenu=\"8\" id=\"subtab-AdminCarts\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCarts&amp;token=06f5ebd95d4bbceb7d696085a6df9989\" class=\"link\"> Paniers
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"9\" id=\"subtab-AdminCatalog\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/products?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-store\">store</i>
                      <span>
                      Catalogue
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-9\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"10\" id=\"subtab-AdminProducts\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/products?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Produits
                                </a>
                              </li>

                                                                                  
                              
   ";
        // line 517
        echo "                                                         
                              <li class=\"link-leveltwo\" data-submenu=\"11\" id=\"subtab-AdminCategories\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/categories?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Catégories
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"12\" id=\"subtab-AdminTracking\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/monitoring/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Suivi
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"13\" id=\"subtab-AdminParentAttributesGroups\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminAttributesGroups&amp;token=cec30ab44aa32405ab3ad4db623cd5ef\" class=\"link\"> Attributs &amp; caractéristiques
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"16\" id=\"subtab-AdminParentManufacturers\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/catalog/brands/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Marques et four";
        // line 543
        echo "nisseurs
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"19\" id=\"subtab-AdminAttachments\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/attachments/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Fichiers
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"20\" id=\"subtab-AdminParentCartRules\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCartRules&amp;token=e6711c20532b801eb253ae06141ca861\" class=\"link\"> Réductions
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"23\" id=\"subtab-AdminStockManagement\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/stocks/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Stock
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone ha";
        // line 577
        echo "s_submenu\" data-submenu=\"24\" id=\"subtab-AdminParentCustomer\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/customers/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-account_circle\">account_circle</i>
                      <span>
                      Clients
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-24\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"25\" id=\"subtab-AdminCustomers\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/customers/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Clients
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"26\" id=\"subtab-AdminAddresses\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/addresses/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Adresses
                                </a>
                              </li>

                                                                                                                                    </ul>
                                        </li>
                             ";
        // line 606
        echo "                 
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"28\" id=\"subtab-AdminParentCustomerThreads\">
                    <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCustomerThreads&amp;token=02dd58f14319f429eb41b864a31851ef\" class=\"link\">
                      <i class=\"material-icons mi-chat\">chat</i>
                      <span>
                      SAV
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-28\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"29\" id=\"subtab-AdminCustomerThreads\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCustomerThreads&amp;token=02dd58f14319f429eb41b864a31851ef\" class=\"link\"> SAV
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"30\" id=\"subtab-AdminOrderMessage\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/sell/customer-service/order-messages/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Messages prédéfinis
                                ";
        // line 634
        echo "</a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"31\" id=\"subtab-AdminReturn\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminReturn&amp;token=725455e8eab7111ba3a20cc8e39949da\" class=\"link\"> Retours produits
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone\" data-submenu=\"32\" id=\"subtab-AdminStats\">
                    <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminStats&amp;token=fc695bca415bbdbf788b41144341cd72\" class=\"link\">
                      <i class=\"material-icons mi-assessment\">assessment</i>
                      <span>
                      Statistiques
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                        </li>
                              
          
                      
                                          
                    
          
            <li class=\"category-title link-active\" data-submenu=\"37\" id=\"tab-IMPROVE\">
                <span class=\"title\">Personnaliser</span>
            </li>

                              
                  
        ";
        // line 674
        echo "                                              
                                                          
                  <li class=\"link-levelone has_submenu link-active open ul-open\" data-submenu=\"38\" id=\"subtab-AdminParentModulesSf\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/manage?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-extension\">extension</i>
                      <span>
                      Modules
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_up
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-38\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo link-active\" data-submenu=\"39\" id=\"subtab-AdminModulesSf\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/manage?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Gestionnaire de modules 
                                </a>
                              </li>

                                                                                                                                    </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"43\" id=\"subtab-AdminParentThemes\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/design/themes/?_token=";
        // line 702
        echo "F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-desktop_mac\">desktop_mac</i>
                      <span>
                      Apparence
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-43\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"128\" id=\"subtab-AdminThemesParent\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/design/themes/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Thème et logo
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"45\" id=\"subtab-AdminParentMailTheme\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/design/mail_theme/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Thème d&#039;e-mail
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"47\" id=\"subtab-AdminCmsContent\">
                                <a href=\"/";
        // line 732
        echo "admin024h9xfokhvsbvfmnzn/index.php/improve/design/cms-pages/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Pages
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"48\" id=\"subtab-AdminModulesPositions\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/design/modules/positions/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Positions
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"49\" id=\"subtab-AdminImages\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminImages&amp;token=eb8fa0a2bfe47385e9e5bf6100d791a9\" class=\"link\"> Images
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"118\" id=\"subtab-AdminLinkWidget\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/modules/link-widget/list?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Liste de liens
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                                       ";
        // line 762
        echo "       
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"50\" id=\"subtab-AdminParentShipping\">
                    <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCarriers&amp;token=63bc13a47e7715656dc79d44e203a794\" class=\"link\">
                      <i class=\"material-icons mi-local_shipping\">local_shipping</i>
                      <span>
                      Livraison
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-50\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"51\" id=\"subtab-AdminCarriers\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminCarriers&amp;token=63bc13a47e7715656dc79d44e203a794\" class=\"link\"> Transporteurs
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"52\" id=\"subtab-AdminShipping\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/shipping/preferences/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Préférences
                                </a>
                 ";
        // line 791
        echo "             </li>

                                                                              </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"53\" id=\"subtab-AdminParentPayment\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/payment/payment_methods?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-payment\">payment</i>
                      <span>
                      Paiement
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-53\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"54\" id=\"subtab-AdminPayment\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/payment/payment_methods?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Moyens de paiement
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"55\" id=\"subtab-AdminPaymentPreferences\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve";
        // line 822
        echo "/payment/preferences?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Préférences
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"56\" id=\"subtab-AdminInternational\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/international/localization/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-language\">language</i>
                      <span>
                      International
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-56\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"57\" id=\"subtab-AdminParentLocalization\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/international/localization/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Localisation
                                </a>
                              </li>

                                                                                  
                              
                                                            
                    ";
        // line 854
        echo "          <li class=\"link-leveltwo\" data-submenu=\"62\" id=\"subtab-AdminParentCountries\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/international/zones/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Zones géographiques
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"66\" id=\"subtab-AdminParentTaxes\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/international/taxes/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Taxes
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"69\" id=\"subtab-AdminTranslations\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/international/translations/settings?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Traductions
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                              
          
                      
                                          
                    
          
            <li class=\"category-title\" data-submenu=\"70\" id=\"tab-CONFIGURE\">
                <span class=\"title\">Configurer</span>
            </li>

                              
                  
                                                      
                  
                 ";
        // line 891
        echo " <li class=\"link-levelone has_submenu\" data-submenu=\"71\" id=\"subtab-ShopParameters\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/preferences/preferences?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-settings\">settings</i>
                      <span>
                      Paramètres de la boutique
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-71\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"72\" id=\"subtab-AdminParentPreferences\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/preferences/preferences?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Paramètres généraux
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"75\" id=\"subtab-AdminParentOrderPreferences\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/order-preferences/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Commandes
                                </a>
                              </li>

                                                                                  ";
        // line 918
        echo "
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"78\" id=\"subtab-AdminPPreferences\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/product-preferences/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Produits
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"79\" id=\"subtab-AdminParentCustomerPreferences\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/customer-preferences/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Clients
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"83\" id=\"subtab-AdminParentStores\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/contacts/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Contact
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"86\" id=\"subtab-AdminParentMeta\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/shop/seo-urls/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Trafic et";
        // line 946
        echo " SEO
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"89\" id=\"subtab-AdminParentSearchConf\">
                                <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminSearchConf&amp;token=355c42903d31d8d0951c76e13ad4dfdd\" class=\"link\"> Rechercher
                                </a>
                              </li>

                                                                              </ul>
                                        </li>
                                              
                  
                                                      
                  
                  <li class=\"link-levelone has_submenu\" data-submenu=\"92\" id=\"subtab-AdminAdvancedParameters\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/system-information/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\">
                      <i class=\"material-icons mi-settings_applications\">settings_applications</i>
                      <span>
                      Paramètres avancés
                      </span>
                                                    <i class=\"material-icons sub-tabs-arrow\">
                                                                    keyboard_arrow_down
                                                            </i>
                                            </a>
                                              <ul id=\"collapse-92\" class=\"submenu panel-collapse\">
                                                      
                              
                                                            
                              <li class=\"link-leveltwo\" data-subme";
        // line 978
        echo "nu=\"93\" id=\"subtab-AdminInformation\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/system-information/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Informations
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"94\" id=\"subtab-AdminPerformance\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/performance/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Performances
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"95\" id=\"subtab-AdminAdminPreferences\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/administration/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Administration
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"96\" id=\"subtab-AdminEmails\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/emails/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> E-mail
                                </a>
                              </li>

                                                                            ";
        // line 1007
        echo "      
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"97\" id=\"subtab-AdminImport\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/import/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Importer
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"98\" id=\"subtab-AdminParentEmployees\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/employees/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Équipe
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"102\" id=\"subtab-AdminParentRequestSql\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/sql-requests/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Base de données
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"105\" id=\"subtab-AdminLogs\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/logs/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Logs
             ";
        // line 1036
        echo "                   </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"106\" id=\"subtab-AdminWebservice\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/webservice-keys/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Webservice
                                </a>
                              </li>

                                                                                                                                                                                                                                                    
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"110\" id=\"subtab-AdminFeatureFlag\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/feature-flags/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Fonctionnalités nouvelles et expérimentales
                                </a>
                              </li>

                                                                                  
                              
                                                            
                              <li class=\"link-leveltwo\" data-submenu=\"111\" id=\"subtab-AdminParentSecurity\">
                                <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/configure/advanced/security/?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" class=\"link\"> Security
                                </a>
                              </li>

                                                                              </ul>
                                   ";
        // line 1064
        echo "     </li>
                              
          
                  </ul>
  </div>
  
</nav>


<div class=\"header-toolbar d-print-none\">
    
  <div class=\"container-fluid\">

    
      <nav aria-label=\"Breadcrumb\">
        <ol class=\"breadcrumb\">
                      <li class=\"breadcrumb-item\">Gestionnaire de modules </li>
          
                      <li class=\"breadcrumb-item active\">
              <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/alerts?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" aria-current=\"page\">Alertes</a>
            </li>
                  </ol>
      </nav>
    

    <div class=\"title-row\">
      
          <h1 class=\"title\">
            Notifications des modules          </h1>
      

      
        <div class=\"toolbar-icons\">
          <div class=\"wrapper\">
            
                                                          <a
                  class=\"btn btn-primary pointer\"                  id=\"page-header-desc-configuration-add_module\"
                  href=\"#\"                  title=\"Installer un module\"                  data-toggle=\"pstooltip\"
                  data-placement=\"bottom\"                                  >
                  <i class=\"material-icons\">cloud_upload</i>                  Installer un module
                </a>
                                      
            
                              <a class=\"btn btn-outline-secondary btn-help btn-sidebar\" href=\"#\"
                   title=\"Aide\"
                   data-toggle=\"sidebar\"
                   data-target=\"#right-sidebar\"
                   data-url=\"/admin024h9xfokhvsbvfmnzn/index.php/common/sidebar/https%253A%252F%252Fhelp.prestashop-project.org%252Ffr%252Fdoc%252FAdminModules%253Fversion%253D8.2.0%2526country%253Dfr/Aide?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\"
                   id=\"product_form_open_help\"
                >
                  Aide
                </a>
                                    </d";
        // line 1116
        echo "iv>
        </div>

      
    </div>
  </div>

  
      <div class=\"page-head-tabs\" id=\"head_tabs\">
      <ul class=\"nav nav-pills\">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <li class=\"nav-item\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/manage?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" id=\"subtab-AdminModulesManage\" class=\"nav-link tab \" data-submenu=\"40\">
                      Modules
                      <span class=\"notification-container\">
                        <span class=\"notification-counter\"></span>
                      </span>
                    </a>
                  </li>
                                                                <li class=\"nav-item\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/alerts?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\" id=\"subtab-AdminModulesNotifications\" class=\"nav-link tab active current\" data-submenu=\"41\">
                      Alertes
                      <span class=\"notification-container\">
                        <span class=\"notification-counter\"></span>
                      </span>
                    </a>
                  </li>
                                                                <li class=\"nav-item\">
                    <a href=\"/admin024h9xfokhvsbvfmnzn/index.php/improve/modules/updates?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1";
        // line 1143
        echo "JT0A\" id=\"subtab-AdminModulesUpdates\" class=\"nav-link tab \" data-submenu=\"42\">
                      Mises à jour
                      <span class=\"notification-container\">
                        <span class=\"notification-counter\"></span>
                      </span>
                    </a>
                  </li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          </ul>
    </div>
  
  <div class=\"btn-floating\">
    <button class=\"btn btn-primary collapsed\" data-toggle=\"collapse\" data-target=\".btn-floating-container\" aria-expanded=\"false\">
      <i class=\"material-icons\">add</i>
    </button>
    <div class=\"btn-floating-container collapse\">
      <div class=\"btn-floating-menu\">
        
                              <a
              class=\"btn btn-floating-item   pointer\"              id=\"page-header-desc-floating-configuration-add_module\"
              href=\"#\"              title=\"Installer un module\"              data-toggle=\"pstooltip\"
              data-placement=\"bottom\"            >
              Installer un module
              <i clas";
        // line 1165
        echo "s=\"material-icons\">cloud_upload</i>            </a>
                  
                              <a class=\"btn btn-floating-item btn-help btn-sidebar\" href=\"#\"
               title=\"Aide\"
               data-toggle=\"sidebar\"
               data-target=\"#right-sidebar\"
               data-url=\"/admin024h9xfokhvsbvfmnzn/index.php/common/sidebar/https%253A%252F%252Fhelp.prestashop-project.org%252Ffr%252Fdoc%252FAdminModules%253Fversion%253D8.2.0%2526country%253Dfr/Aide?_token=F3XtaXZNxyWwN2SQ-YQXjYJcDPmUh910PX0hZv1JT0A\"
            >
              Aide
            </a>
                        </div>
    </div>
  </div>
  
</div>

<div id=\"main-div\">
          
      <div class=\"content-div  with-tabs\">

        

                                                        
        <div id=\"ajax_confirmation\" class=\"alert alert-success\" style=\"display: none;\"></div>
<div id=\"content-message-box\"></div>


  ";
        // line 1192
        $this->displayBlock('content_header', $context, $blocks);
        $this->displayBlock('content', $context, $blocks);
        $this->displayBlock('content_footer', $context, $blocks);
        $this->displayBlock('sidebar_right', $context, $blocks);
        echo "

        

      </div>
    </div>

  <div id=\"non-responsive\" class=\"js-non-responsive\">
  <h1>Oh non !</h1>
  <p class=\"mt-3\">
    La version mobile de cette page n'est pas encore disponible.
  </p>
  <p class=\"mt-2\">
    Cette page n'est pas encore disponible sur mobile, merci de la consulter sur ordinateur.
  </p>
  <p class=\"mt-2\">
    Merci.
  </p>
  <a href=\"https://districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/index.php?controller=AdminDashboard&amp;token=651ec98d335ae4dfaabe27f04eb8baf7\" class=\"btn btn-primary py-1 mt-3\">
    <i class=\"material-icons rtl-flip\">arrow_back</i>
    Précédent
  </a>
</div>
  <div class=\"mobile-layer\"></div>

      <div id=\"footer\" class=\"bootstrap\">
    
</div>
  

      <div class=\"bootstrap\">
      
    </div>
  
";
        // line 1226
        $this->displayBlock('javascripts', $context, $blocks);
        $this->displayBlock('extra_javascripts', $context, $blocks);
        $this->displayBlock('translate_javascripts', $context, $blocks);
        echo "</body>";
        echo "
</html>";
    }

    // line 102
    public function block_stylesheets($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function block_extra_stylesheets($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 1192
    public function block_content_header($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function block_content_footer($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function block_sidebar_right($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 1226
    public function block_javascripts($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function block_extra_javascripts($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function block_translate_javascripts($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function getTemplateName()
    {
        return "__string_template__e103b1fd245c1cd58c10759f6133c1c69df41884535f0bff3fd8619b478ca9cd";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1393 => 1226,  1372 => 1192,  1361 => 102,  1352 => 1226,  1312 => 1192,  1283 => 1165,  1259 => 1143,  1230 => 1116,  1176 => 1064,  1146 => 1036,  1115 => 1007,  1084 => 978,  1050 => 946,  1020 => 918,  991 => 891,  952 => 854,  918 => 822,  885 => 791,  854 => 762,  822 => 732,  790 => 702,  760 => 674,  718 => 634,  688 => 606,  657 => 577,  621 => 543,  593 => 517,  559 => 485,  526 => 454,  482 => 412,  439 => 371,  390 => 324,  336 => 272,  289 => 227,  252 => 192,  233 => 175,  192 => 136,  153 => 102,  116 => 67,  87 => 40,  46 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "__string_template__e103b1fd245c1cd58c10759f6133c1c69df41884535f0bff3fd8619b478ca9cd", "");
    }
}
