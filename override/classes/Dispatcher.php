<?php
/**
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
class Dispatcher extends DispatcherCore
{
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public $etsAwuDispatcher = null;
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    protected function __construct()
    {
        if (Module::isEnabled('ets_awesomeurl') && Module::getInstanceByName('ets_awesomeurl')) {
            if (file_exists(_PS_MODULE_DIR_ . 'ets_awesomeurl/classes/EtsAwuDispatcher.php')) {
                require_once _PS_MODULE_DIR_ . 'ets_awesomeurl/classes/EtsAwuDispatcher.php';
            }
            if (class_exists('EtsAwuDispatcher')) {
                $this->etsAwuDispatcher = EtsAwuDispatcher::getDispatcher();
                if ($this->enabledRemoveIdInUrl() && (int) Configuration::get('PS_REWRITING_SETTINGS')) {
                    $this->default_routes = $this->etsAwuDispatcher->getDefaultRouteNoId();
                }
            }
            parent::__construct();
            $this->etsAwuDispatcher->mergeRssRoute($this);
        } else {
            parent::__construct();
        }

        // CKC Vianney — /en/ met à jour context->language, pas $GLOBALS['language'].
        // CE s'en sert pour les miniatures : slug EN, préfixe langue FR (absent).
        $context = Context::getContext();
        if (isset($context->language) && $context->language instanceof Language) {
            $GLOBALS['language'] = $context->language;
        }
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getController($id_shop = null)
    {
        if (Tools::getIsset('controller') && (Tools::getValue('controller') != '')) {
            return parent::getController($id_shop);
        }
        if (Module::isEnabled('ets_awesomeurl') && Module::getInstanceByName('ets_awesomeurl')) {
            $this->etsAwuDispatcher && $this->etsAwuDispatcher->checkForRedirect($this->request_uri);
            parent::getController($id_shop);
            if ($this->etsAwuDispatcher) {
                if ($this->enabledRemoveIdInUrl()) {
                    $this->controller = $this->etsAwuDispatcher->getController($this, $this->controller);
                }
                if (('404' == $this->controller || 'pagenotfound' == $this->controller) && (int) Configuration::get('ETS_AWU_ENABLE_REDIRECT_NOTFOUND')) {
                    if ($this->enabledRemoveIdInUrl()) {
                        $this->etsAwuDispatcher->redirectToOldUrl($this, true);
                    } else {
                        $this->etsAwuDispatcher->redirectToOldUrl($this);
                    }
                }
            }
        }
        return $this->controller;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getControllerForRedirect($id_shop = null)
    {
        if ($this->etsAwuDispatcher) {
            $this->controller = '';
            unset($_GET['controller']);
            $controller = parent::getController($id_shop);
            $this->controller = $controller;
        }
        return $this->controller;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getControllerChecking($id_shop = null)
    {
        $this->controller = '';
        unset($_GET['controller']);
        return $this->getController($id_shop);
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getRoutes()
    {
        return $this->routes;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function enabledRemoveIdInUrl()
    {
        return (int) Configuration::get('ETS_AWU_ENABLE_REMOVE_ID_IN_URL')
            && !defined('_PS_ADMIN_DIR_')
            && (int) Configuration::get('PS_REWRITING_SETTINGS');
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function setOldRoutes($id_shop = null)
    {
        $context = Ets_awesomeurl::getContextStatic();
        if (isset($context->shop) && null === $id_shop) {
            $id_shop = (int) $context->shop->id;
        }
        $language_ids = Language::getIDs();
        if (isset($context->language) && !in_array($context->language->id, $language_ids)) {
            $language_ids[] = (int) $context->language->id;
        }
        foreach ($this->default_routes as $id => $route) {
            $route = $this->computeRoute(
                $route['rule'],
                $route['controller'],
                $route['keywords'],
                isset($route['params']) ? $route['params'] : []
            );
            foreach ($language_ids as $id_lang) {
                $this->routes[$id_shop][$id_lang][$id] = $route;
            }
        }
        if ($this->use_routes) {
            $sql = 'SELECT m.page, ml.url_rewrite, ml.id_lang
					FROM `' . _DB_PREFIX_ . 'meta` m
					LEFT JOIN `' . _DB_PREFIX_ . 'meta_lang` ml ON (m.id_meta = ml.id_meta' . Shop::addSqlRestrictionOnLang('ml', (int) $id_shop) . ')
					ORDER BY LENGTH(ml.url_rewrite) DESC';
            if ($results = Db::getInstance()->executeS($sql)) {
                foreach ($results as $row) {
                    if ($row['url_rewrite']) {
                        $this->addRoute(
                            $row['page'],
                            $row['url_rewrite'],
                            $row['page'],
                            $row['id_lang'],
                            [],
                            [],
                            $id_shop
                        );
                    }
                }
            }
            if (!$this->empty_route) {
                $this->empty_route = [
                    'routeID' => 'index',
                    'rule' => '',
                    'controller' => 'index',
                ];
            }
        }
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function validateRoute($route_id, $rule, &$errors = [])
    {
        $ETS_AWU_ENABLE_REMOVE_ID_IN_URL = (int) Tools::getValue('ETS_AWU_ENABLE_REMOVE_ID_IN_URL');
        if ($ETS_AWU_ENABLE_REMOVE_ID_IN_URL
            || ((int) Configuration::get('ETS_AWU_ENABLE_REMOVE_ID_IN_URL') && !(int) Configuration::get('PS_REWRITING_SETTINGS'))) {
            $errors = [];
            if (!isset($this->default_routes[$route_id])) {
                return false;
            }
            foreach ($this->default_routes[$route_id]['keywords'] as $keyword => $data) {
                if (isset($data['param']) && ('module' == $route_id || ('module' != $route_id && 'rewrite' == $keyword)) && !preg_match('#\{([^{}]*:)?' . $keyword . '(:[^{}]*)?\}#', $rule)) {
                    $errors[] = $keyword;
                }
            }
            return (count($errors)) ? false : true;
        }
        return parent::validateRoute($route_id, $rule, $errors);
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getFontController()
    {
        return $this->front_controller;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function setFrontController($front_controller)
    {
        $this->front_controller = $front_controller;
        return $this;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function getUseDefaultController()
    {
        return $this->useDefaultController();
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function setDefaultRoutes($routes)
    {
        $this->default_routes = $routes;
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function publicLoadRoutes()
    {
        parent::loadRoutes();
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    public function computeRoute($rule, $controller, array $keywords = [], array $params = [])
    {
        if (method_exists(get_parent_class($this), 'computeRoute')) {
            return parent::computeRoute($rule, $controller, $keywords, $params);
        }
        $regexp = preg_quote($rule, '#');
        if ($keywords) {
            $transform_keywords = [];
            preg_match_all(
                '#\\\{(([^{}]*)\\\:)?(' .
                implode('|', array_keys($keywords)) . ')(\\\:([^{}]*))?\\\}#',
                $regexp,
                $m
            );
            for ($i = 0, $total = count($m[0]); $i < $total; ++$i) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $transform_keywords[$keyword] = [
                    'required' => isset($keywords[$keyword]['param']),
                    'prepend' => stripslashes($prepend),
                    'append' => stripslashes($append),
                ];
                $prepend_regexp = $append_regexp = '';
                if ($prepend || $append) {
                    $prepend_regexp = '(' . $prepend;
                    $append_regexp = $append . ')?';
                }
                if (isset($keywords[$keyword]['param'])) {
                    $regexp = str_replace(
                        $m[0][$i],
                        $prepend_regexp .
                        '(?P<' . $keywords[$keyword]['param'] . '>' . $keywords[$keyword]['regexp'] . ')' .
                        $append_regexp,
                        $regexp
                    );
                } else {
                    $regexp = str_replace(
                        $m[0][$i],
                        $prepend_regexp .
                        '(' . $keywords[$keyword]['regexp'] . ')' .
                        $append_regexp,
                        $regexp
                    );
                }
            }
            $keywords = $transform_keywords;
        }
        $regexp = '#^/' . $regexp . '$#u';
        return [
            'rule' => $rule,
            'regexp' => $regexp,
            'controller' => $controller,
            'keywords' => $keywords,
            'params' => $params,
        ];
    }
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    protected function setRequestUri()
    {
        parent::setRequestUri();
        if (!isset(${'_GET'}['isolang']) && (int) Configuration::get('ETS_AWU_ENABLE_REMOVE_LANG_CODE_IN_URL') && ($idLang = (int) Configuration::get('PS_LANG_DEFAULT'))) {
            if (($iso = Language::getIsoById($idLang))) {
                $_GET['isolang'] = $iso;
            }
        }
    }
}
