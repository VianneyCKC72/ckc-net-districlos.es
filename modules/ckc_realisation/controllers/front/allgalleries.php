<?php

class Ckc_realisationAllgalleriesModuleFrontController extends ModuleFrontController
{
    public function initContent(){

        parent::initContent();

        $id_shop = Context::getContext()->shop->id;
        $id_lang = Context::getContext()->language->id;

        $allimages = Db::getInstance()->executeS('SELECT ck.*, ckl.*, p.id_category_default, cl.name as category_title, 
        cl.link_rewrite as category_link_rewrite, pl.name as product_name FROM `'._DB_PREFIX_.'ckc_realisation` ck 
        LEFT JOIN `'._DB_PREFIX_.'ckc_realisation_lang` ckl on ck.id_realisation = ckl.id_realisation 
        LEFT JOIN `'._DB_PREFIX_.'product` p on ck.id_product = p.id_product 
        LEFT JOIN `'._DB_PREFIX_.'product_lang` pl on pl.id_product = p.id_product 
        LEFT JOIN `'._DB_PREFIX_.'category_lang` cl on cl.id_category = p.id_category_default
        WHERE ck.id_shop = '.$id_shop.' AND ckl.id_lang = '.$id_lang.' AND cl.id_shop = '.$id_shop.' AND cl.id_lang = '.$id_lang.' AND pl.id_shop = '.$id_shop.' AND pl.id_lang = '.$id_lang);

        $allgalleries = [];


        foreach($allimages as $image){
            $fileInfo = getimagesize(Tools::getHttpHost(true) . __PS_BASE_URI__ .'modules/ckc_realisation/uploads/'.$id_shop.'/'.$image['filename']);
            $image['width'] = $fileInfo[0];
            $image['height'] = $fileInfo[1];
            if($image['id_customer'] != 0){
                $clientInfo = Db::getInstance()->executeS('SELECT pc.firstname, pc.lastname, pa.city FROM `'._DB_PREFIX_.'customer` pc 
                LEFT JOIN `'._DB_PREFIX_.'address` pa on pa.id_customer = pc.id_customer
                WHERE pc.id_customer = '.$image['id_customer'].' 
            AND pc.id_shop = '.$id_shop);
                if(!empty($clientInfo[0]['firstname'])){
                    $image['firstname'] = $clientInfo[0]['firstname'];
                    $image['lastname'] = $clientInfo[0]['lastname'];
                    $image['city'] = $clientInfo[0]['city'];
                } else {
                    $image['firstname'] = '';
                    $image['lastname'] = '';
                    $image['city'] = '';
                }
            } else {
                $image['firstname'] = '';
                $image['lastname'] = '';
                $image['city'] = '';
            }


            $allgalleries[$image['id_category_default']][] = $image;
        }

        $galleries = [];
        $count = 0;

        foreach($allgalleries as $id_category_default => $images){
            $count += 1;
            $galleries[$id_category_default] = $images;
            $galleries[$id_category_default][0]['count_color'] = $count;
        }

        $this->context->smarty->assign([
            'galleries' => $galleries,
            'id_shop' => $id_shop,
            'meta_title'=> 'Les réalisations de nos PROs'
        ]);

  /*      dump($galleries);*/
        $this->setTemplate('module:ckc_realisation/views/templates/front/allgalleries.tpl');
    }
}