<?php

class ProductsService
{
    // public const KEYWORDS_CLOTURES  = ['Cloture', 'Clôture', 'Clotures', 'Clôtures'];
    // public const KEYWORDS_PORTAILS  = ['Portails', 'Portail', 'Portillons'];
    // public const KEYWORDS_GRILLAGES = ['Grillages', 'Grillage'];

    public const CLOTURES_PRO = 370;
    public const PORTAILS_PRO = 371;
    public const GRILLAGES_PRO = 372;


    protected $context;
    protected $idLang;
    protected $IdShop;
    protected $db;

    public function __construct()
    {
        $this->context = \Context::getContext();
        $this->idLang = $this->context->language->id;
        $this->IdShop = $this->context->shop->id;
        $this->db = \Db::getInstance();
    }

    /**
     * on récupère l'url pour voir quel type de document on affiche (fiches techniques ou guides)
     */
    public function resolveCategoryFromRequest(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        if (str_contains($requestUri, 'fiches-techniques-pro')) {
            return 'Fiche Technique Pro';
        }

        if (str_contains($requestUri, 'guides-pro')) {
            return 'Notice de montage';
        }

        return '';

    }

    public function getTitleBtn() 
    {
         $requestUri = $_SERVER['REQUEST_URI'];

        if (str_contains($requestUri, 'fiches-techniques-pro')) {
            return 'Télécharger la fiche technique';
        }

        if (str_contains($requestUri, 'guides-pro')) {
            return 'Télécharger le guide';
        }

        return 'Télécharger';
    }

    public function getSections(array $titles, string $documentSlug): array
    {
        $results = [];

        $titleToCategoryId = [
            'Clôture'  => self::CLOTURES_PRO,
            'Portail'  => self::PORTAILS_PRO,
            'Grillage' => self::GRILLAGES_PRO,
        ];

        foreach ($titles as $title) {
            if (!isset($titleToCategoryId[$title])) {
                continue;
            }

            $categoryId = $titleToCategoryId[$title];

            $results[] = [
                'title' => $title,
                'products' => $this->getProductsByCategories([$categoryId], $documentSlug),
            ];
        }

        $results = array_filter($results, function ($result) {
            return !empty($result['products']);
        });

        return $results;
    }



    // /**
    //  * récupérer les sections des pages 
    //  */
    // public function getSections(array $sectionsData, string $documentSlug): array
    // {
    //     $result = [];

    //     foreach ($sectionsData as $section) {
    //         $result[] = [
    //             'title' => $section['title'],
    //             'products' => $this->getProductsByKeyWordsCategory($section['keywords'], $documentSlug),
    //         ];
    //     }

    //     return $result;
    // }

    // /**
    //  * Récupération des catégories par mots-clés
    //  * $words => mots clés dans la catégories
    //  * $documentSlug => 'Fiche technique' ou 'Notice de montage'
    //  */
    // private function getProductsByKeyWordsCategory(array $words, string $documentSlug): ?array {
    //     $idLang = $this->idLang;

    //     if (empty($words)) {
    //         return null;
    //     }

    //     $likeClauses = [];

    //     foreach ($words as $word) {
    //         $word = pSQL(trim($word), true);
    //         $likeClauses[] = "cl.name LIKE '{$word}%'";
    //     }

    //     $sql = new \DbQuery();
    //     $sql->select('DISTINCT c.id_category')
    //         ->from('category', 'c')
    //         ->leftJoin('category_lang', 'cl', 'cl.id_category = c.id_category')
    //         ->where('cl.id_lang = ' . (int) $idLang)
    //         ->where('c.active = 1')
    //         ->where(implode(' OR ', $likeClauses));

    //         $results = $this->db->executeS($sql);

    //         if (!$results) {
    //             return null;
    //         }

    //     $categoryIds = array_column($results, 'id_category');

        
    //     return $this->getProductsByCategories($categoryIds, $documentSlug);

    // }



    /**
     * Récupération des produits de la catégorie
     */
    private function getProductsByCategories(array $categoryIds, string $documentSlug): ?array
    {
        $categoryIds = array_filter($categoryIds, 'Validate::isUnsignedId');


        if (empty($categoryIds)) {
            return null;
        }

        $sql = new \DbQuery();
        $sql->select('DISTINCT cp.id_product')
            ->from('category_product', 'cp')
            ->where('cp.id_category IN (' . implode(',', array_map('intval', $categoryIds)) . ')');

        $results = $this->db->executeS($sql);

        if (!$results) {
            return null;
        }

        $productIds = array_column($results, 'id_product');


        return $this->getSimpleProducts($productIds, $documentSlug);
    }


    private function getSimpleProducts(array $productIds, string $documentSlug): array
    {


        $productIds = array_filter($productIds, 'Validate::isUnsignedId');

        if (empty($productIds)) {
            return [];
        }

        $sql = new \DbQuery();
        $sql->select('DISTINCT p.id_product, pl.name, pl.link_rewrite, p.active')
            ->from('product', 'p')
            ->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . (int)$this->idLang)
            ->where('p.id_product IN (' . implode(',', $productIds) . ')');

        $rawProducts = $this->db->executeS($sql);

        $products = [];

        foreach ($rawProducts as $row) {
            if (!$row['active']) {
                continue;
            }

            // Image
            $image = \Product::getCover((int)$row['id_product']);
            $imageUrl = $image
                ? $this->context->link->getImageLink($row['link_rewrite'], $image['id_image'], 'home_default')
                : null;

            $products[] = [
                'id' => (int)$row['id_product'],
                'name' => $row['name'],
                'image' => $imageUrl,
                'link_rewrite' => $row['link_rewrite'],
            ];
        }

        return $this->attachDocumentsToProducts($products, $documentSlug);
    }


    private function attachDocumentsToProducts(array $products, string $documentSlug): array
    {
        if (empty($products)) {
            return [];
        }

        $productIds = array_column($products, 'id');

        $sql = new \DbQuery();
        $sql->select('pa.id_product, a.id_attachment, al.name')
            ->from('product_attachment', 'pa')
            ->leftJoin('attachment', 'a', 'a.id_attachment = pa.id_attachment')
            ->leftJoin('attachment_lang', 'al', 'al.id_attachment = a.id_attachment AND al.id_lang = ' . (int)$this->idLang)
            ->where('pa.id_product IN (' . implode(',', array_map('intval', $productIds)) . ')')
            ->where('al.name LIKE "' . pSQL($documentSlug) . '%"');

        $documents = $this->db->executeS($sql);

        $documentMap = [];

        foreach ($documents as $doc) {
            $link = new \Link();
            $downloadUrl = $link->getPageLink('attachment', true, null, [
                'id_attachment' => (int)$doc['id_attachment'],
                'filename' => urlencode(\Tools::str2url($doc['name']))
            ]);

            $documentMap[$doc['id_product']] = [
                'id_attachment' => $doc['id_attachment'],
                'name' => $doc['name'],
                'download_url' => $downloadUrl,
            ];
        }

        foreach ($products as $key => $product) {
            if (isset($documentMap[$product['id']])) {
                $products[$key]['document'] = $documentMap[$product['id']];
            } else {
                unset($products[$key]);
            }
        }

        return array_values($products);
    }

}


