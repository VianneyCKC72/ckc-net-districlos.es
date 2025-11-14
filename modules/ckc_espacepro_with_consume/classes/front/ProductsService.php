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
            ->innerJoin('product', 'p', 'p.id_product = cp.id_product')
            ->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . (int)$this->idLang)
            ->leftJoin('image', 'i', 'i.id_product = p.id_product AND i.cover = 1')
            ->leftJoin('product_attachment', 'pa', 'pa.id_product = p.id_product')
            ->leftJoin('attachment', 'a', 'a.id_attachment = pa.id_attachment')
            ->leftJoin('attachment_lang', 'al', 'al.id_attachment = a.id_attachment AND al.id_lang = ' . (int)$this->idLang)
            ->where('cp.id_category IN (' . implode(',', $categoryIds) . ')');

        $rows = $this->db->executeS($sql);

        $link = new \Link();
        $productsByCategory = [];

        foreach ($rows as $row) {
            if (!(int)$row['active']) {
                continue;
            }

            $idCategory = (int)$row['id_category'];
            $idProduct = (int)$row['id_product'];

            if (!isset($productsByCategory[$idCategory][$idProduct])) {
                $imageUrl = $row['id_image']
                    ? $this->context->link->getImageLink($row['link_rewrite'], $row['id_image'], 'home_default')
                    : null;

                $productsByCategory[$idCategory][$idProduct] = [
                    'id' => $idProduct,
                    'name' => $row['product_name'],
                    'image' => $imageUrl,
                    'link_rewrite' => $row['link_rewrite'],
                    'documents' => [],
                ];
            }

            // Filtrer uniquement les documents PDF
            if (!empty($row['id_attachment']) && isset($row['attachment_name'])) {
                $ext = strtolower(pathinfo($row['attachment_name'], PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $downloadUrl = $link->getPageLink('attachment', true, null, [
                        'id_attachment' => (int)$row['id_attachment'],
                        'filename' => urlencode(\Tools::str2url($row['attachment_name']))
                    ]);

                    $productsByCategory[$idCategory][$idProduct]['documents'][] = [
                        'id_attachment' => (int)$row['id_attachment'],
                        'name' => $row['attachment_name'],
                        'download_url' => $downloadUrl,
                    ];
                }
            }
        }

        // Associer les produits à chaque section, seulement s’ils ont des PDF
        foreach ($sections as &$section) {
            $idCategory = (int)$section['id_category'];
            $section['products'] = isset($productsByCategory[$idCategory])
                ? array_values(array_filter($productsByCategory[$idCategory], function ($p) {
                    return !empty($p['documents']); // garde uniquement les produits avec des PDFs
                }))
                : [];
        }

        return $sections;
    }

}
