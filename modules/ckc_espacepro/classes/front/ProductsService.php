<?php

require_once _PS_MODULE_DIR_ . 'ckc_espacepro/classes/SectionGuidesFichesServices.php';

class ProductsService
{
    protected $context;
    protected $idLang;
    protected $idShop;
    protected $db;
    protected $sectionService;

    public function __construct()
    {
        $this->sectionService = new SectionGuidesFichesServices();
        $this->context = \Context::getContext();
        $this->idLang = $this->context->language->id;
        $this->idShop = $this->context->shop->id;
        $this->db = \Db::getInstance();
    }

    /**
     * Récupère toutes les sections avec leurs produits et documents
     */
    public function getSections(): array
    {
        $sections = CkcSectionGuidesFiches::getSections();
        if (empty($sections)) {
            return [];
        }

        $categoryIds = array_column($sections, 'id_category');
        $categoryIds = array_filter($categoryIds, 'Validate::isUnsignedId');

        if (empty($categoryIds)) {
            return [];
        }

     
        $sql = new \DbQuery();
        $sql->select('
            cp.id_category, 
            p.id_product, 
            pl.name AS product_name, 
            pl.link_rewrite, 
            p.active, 
            i.id_image, 
            a.id_attachment, 
            al.name AS attachment_name
        ')
        ->from('category_product', 'cp')
        ->innerJoin('product', 'p', 'p.id_product = cp.id_product AND p.active = 1')
        ->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . (int)$this->idLang)
        ->leftJoin('image', 'i', 'i.id_product = p.id_product AND i.cover = 1')
        ->innerJoin('product_attachment', 'pa', 'pa.id_product = p.id_product')
        ->innerJoin('attachment', 'a', 'a.id_attachment = pa.id_attachment')
        ->innerJoin('attachment_lang', 'al', 'al.id_attachment = a.id_attachment AND al.id_lang = ' . (int)$this->idLang)
        ->where("
        cp.id_category IN (" . implode(',', $categoryIds) . ")
        AND a.file LIKE '%.pdf'
        AND (
                al.name LIKE 'Fiche technique%' OR
                al.name LIKE 'Plan de maçonnerie%' OR
                al.name LIKE 'Notice de montage%' OR
                al.name LIKE 'Plan technique%'
            )
        ")
        ->groupBy('p.id_product, a.id_attachment');

        $rows = $this->db->executeS($sql);

        $link = new \Link();
        $productsByCategory = [];

        foreach ($rows as $row) {
            $idCategory = (int)$row['id_category'];
            $idProduct = (int)$row['id_product'];
            $idAttachment = (int)$row['id_attachment'];

            if (!isset($productsByCategory[$idCategory][$idProduct])) {
                $imageUrl = $row['id_image']
                    ? $this->context->link->getImageLink($row['link_rewrite'], $row['id_image'], 'home_default')
                    : null;

                $productsByCategory[$idCategory][$idProduct] = [
                    'id'           => $idProduct,
                    'name'         => $row['product_name'],
                    'image'        => $imageUrl,
                    'link_rewrite' => $row['link_rewrite'],
                    'documents'    => [],
                ];
            }

            // Ajout du document PDF (déjà filtré par SQL)
            if (!isset($productsByCategory[$idCategory][$idProduct]['documents'][$idAttachment])) {
                $downloadUrl = $link->getPageLink('attachment', true, null, [
                    'id_attachment' => $idAttachment,
                    'filename'      => urlencode(\Tools::str2url($row['attachment_name']))
                ]);

                $productsByCategory[$idCategory][$idProduct]['documents'][$idAttachment] = [
                    'id_attachment' => $idAttachment,
                    'name'          => $row['attachment_name'],
                    'download_url'  => $downloadUrl,
                ];
            }
        }

        // Associer les produits filtrés aux sections
        foreach ($sections as &$section) {
            $idCategory = (int)$section['id_category'];

            if (!isset($productsByCategory[$idCategory])) {
                $section['products'] = [];
                continue;
            }

            $sectionProducts = [];
            foreach ($productsByCategory[$idCategory] as $product) {
                // Ré-indexation des documents
                $product['documents'] = array_values($product['documents']);
                $sectionProducts[] = $product;
            }

            $section['products'] = $sectionProducts;
        }

        return $sections;
    }


}
