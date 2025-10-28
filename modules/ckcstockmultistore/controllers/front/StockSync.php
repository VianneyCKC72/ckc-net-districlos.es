<?php

class CkcstockmultistoreStockSyncModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        try {
            // Exécution de la synchronisation des stocks
            $this->syncStock();

            die(json_encode(['status' => 'success', 'message' => 'Synchronisation des stocks terminée.']));
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    private function syncStock()
    {
        $username = 'districlos_refonte';
        $password = 'TXM1qjk9teu_cfb9wxv';
        $database1 = 'districlos_refonte';
        $database2 = 'districlostools';

        try {
            $db = new PDO('mysql:host=87.98.141.171', $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            ]);
        } catch (PDOException $e) {

            throw new Exception('Erreur de connexion : ' . $e->getMessage());
        }

        // Récupérer les stocks externes
        $selectQuery = "SELECT AGENCE, DEPOT, REFERENCE, STOCK_DISPONIBLE FROM `" . $database2 . "`.`rep_stock`";
        $stmt = $db->prepare($selectQuery);
        $stmt->execute();
        $stockData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mapping agence-dépôt vers ID boutique
        $agencyDepotMap = [
            '283-83' => 15, '227-27' => 6, '271-71' => 2, '257-57' => 5, '34-34' => 8,
            '262-62' => 10, '273-73' => 13, '249-49' => 9, '235-35' => 3, '263-63' => 1,
            '226-26' => 4, '277-77' => 14, '233-33' => 7, '269-69' => 12, '231-31' => 11,
            '28B-831' => 16, '245-45' => 17, 'DX-DX' => 18
        ];

        $productsStocks = [];

        Db::getInstance()->Execute('UPDATE '.$database1.'.' . _DB_PREFIX_ . 'ckcstockstores_stocks SET quantity = 0');

        foreach ($stockData as $row) {
            $reference = $row['REFERENCE'];
            $key = $row['AGENCE'] . '-' . $row['DEPOT'];
            if (isset($agencyDepotMap[$key])) {
                $shop = $agencyDepotMap[$key];
                $stockDisponible = (int) $row['STOCK_DISPONIBLE'];
                if ($stockDisponible < 0) {
                    $stockDisponible = 0;
                }
                
                // Vérifier si le produit existe dans PrestaShop
                $stmt = $db->prepare("
                    SELECT pa.id_product, pa.id_product_attribute 
                    FROM `".$database1."`.`" . _DB_PREFIX_ . "product_attribute` pa
                    INNER JOIN `".$database1."`.`" . _DB_PREFIX_ . "product` p ON p.id_product = pa.id_product
                    WHERE pa.reference = :reference AND p.active = 1
                    LIMIT 1
                ");
                
                $stmt->bindValue(':reference', $reference, PDO::PARAM_STR);
                $stmt->execute();
                $product = $stmt->fetch();
                
                if ($product) {
                    $id_product = (int) $product['id_product'];
                    $id_product_attribute = (int) $product['id_product_attribute'];
                    if (!isset($productsStocks[$id_product.'-'.$id_product_attribute])) {
                        $productsStocks[$id_product.'-'.$id_product_attribute] = 0;
                    }
                
                    Db::getInstance()->Execute('UPDATE '.$database1.'.' . _DB_PREFIX_ . 'stock_available SET quantity = 0 WHERE id_product = '.$id_product.' AND id_product_attribute = '.$id_product_attribute);
                
                    $product = Db::getInstance()->ExecuteS('SELECT * FROM '.$database1.'.'. _DB_PREFIX_ . 'ckcstockstores_stocks WHERE id_store = '.$shop.' AND id_product = '.$id_product.' AND id_product_attribute = '.$id_product_attribute. ' LIMIT 1');
                
                    if ($product) {
                        Db::getInstance()->Execute('UPDATE '.$database1.'.' . _DB_PREFIX_ . 'ckcstockstores_stocks SET quantity = '.$stockDisponible.' WHERE id_store = '.$shop.' AND id_product = '.$id_product.' AND id_product_attribute = '.$id_product_attribute);
                    } else {
                        Db::getInstance()->Execute('INSERT INTO '.$database1.'.' . _DB_PREFIX_ . 'ckcstockstores_stocks (id_store, id_product, id_product_attribute, quantity) VALUES ('.$shop.', '.$id_product.', '.$id_product_attribute.', '.$stockDisponible.')');
                    }
                
                    $productsStocks[$id_product.'-'.$id_product_attribute] += $stockDisponible;
                }
            }
        }
        
        foreach ($productsStocks as $key => $productStock) {
            $id_product = explode('-', $key)[0];
            $id_product_attribute = explode('-', $key)[1];
            $stmt = $db->prepare("
                SELECT * FROM `".$database1."`.`" . _DB_PREFIX_ . "stock_available` 
                WHERE id_product = :id_product AND id_product_attribute = :id_product_attribute LIMIT 1");
            $stmt->bindValue(':id_product', $id_product, PDO::PARAM_INT);
            $stmt->bindValue(':id_product_attribute', $id_product_attribute, PDO::PARAM_INT);
            $stmt->execute();

            $product = $stmt->fetch();
            if ($product) {
                Db::getInstance()->Execute('UPDATE '.$database1.'.' . _DB_PREFIX_ . 'stock_available SET quantity = '.$productStock.' WHERE id_product = '.$id_product.' AND id_product_attribute = '.$id_product_attribute);
            } else {
                Db::getInstance()->Execute('INSERT INTO '.$database1.'.' . _DB_PREFIX_ . 'stock_available (id_product, id_product_attribute, quantity) VALUES ('.$id_product.', '.$id_product_attribute.', '.$productStock.')');
            }
        }

        // Mise à zéro des stocks des références non trouvées
        $stmt = $db->prepare("
            UPDATE ".$database1.".`" . _DB_PREFIX_ . "stock_available` sa
            INNER JOIN ".$database1.".`" . _DB_PREFIX_ . "product_attribute` pa ON pa.id_product_attribute = sa.id_product_attribute
            SET sa.quantity = 0, sa.physical_quantity = 0
            WHERE sa.id_stock_available NOT IN (
                SELECT sa2.id_stock_available FROM ".$database1.".`" . _DB_PREFIX_ . "stock_available` sa2
                INNER JOIN ".$database1.".`" . _DB_PREFIX_ . "product_attribute` pa2 ON pa2.id_product_attribute = sa2.id_product_attribute
                INNER JOIN `" . $database2 . "`.`rep_stock` rs2 ON rs2.REFERENCE = pa2.reference
            )
        ");
        $stmt->execute();

        // Mise à zéro des stocks des références non trouvées
        $stmt = $db->prepare("
            UPDATE ".$database1.".`" . _DB_PREFIX_ . "ckcstockstores_stocks` sa
            INNER JOIN ".$database1.".`" . _DB_PREFIX_ . "product_attribute` pa ON pa.id_product_attribute = sa.id_product_attribute
            SET sa.quantity = 0
            WHERE sa.id_stock NOT IN (
                SELECT sa2.id_stock FROM ".$database1.".`" . _DB_PREFIX_ . "ckcstockstores_stocks` sa2
                INNER JOIN ".$database1.".`" . _DB_PREFIX_ . "product_attribute` pa2 ON pa2.id_product_attribute = sa2.id_product_attribute
                INNER JOIN `" . $database2 . "`.`rep_stock` rs2 ON rs2.REFERENCE = pa2.reference
            )
        ");
        $stmt->execute();
    }
}
