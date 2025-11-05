<?php

class CkcstockmultistoreStockSyncModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        try {
            $this->syncStockSetBased();
            die(json_encode(['status' => 'success', 'message' => 'Synchronisation des stocks terminée.']));
        } catch (Throwable $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    private function syncStockSetBased()
    {
        $username = 'districlos_refonte';
        $password = 'TXM1qjk9teu_cfb9wxv';
        $database1 = 'districlos_refonte';
        $database2 = 'districlostools';

        // Charger le mapping de références pour les produits fin de vie
        $mappingFile = _PS_MODULE_DIR_ . 'dtc_productendoflife/reference_mapping.json';
        $referenceMapping = [];
        if (file_exists($mappingFile)) {
            $mappingData = json_decode(file_get_contents($mappingFile), true);
            $referenceMapping = $mappingData['mappings'] ?? [];
        }
        // Créer un mapping inversé pour identifier les références BRUT
        $brutReferences = array_unique(array_values($referenceMapping));

        $pdo = new PDO(
            "mysql:host=87.98.141.171;dbname={$database1};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $pdo->beginTransaction();
        $targetShopId = 1;
        $targetShopGroupId = 1;

        try {
            $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_store_map");
            $pdo->exec("
                CREATE TEMPORARY TABLE tmp_store_map (
                    key_ad VARCHAR(32) PRIMARY KEY,
                    id_store INT NOT NULL
                ) ENGINE=MEMORY
            ");

            $pdo->exec("
                INSERT INTO tmp_store_map (key_ad, id_store) VALUES
                  ('283-83',15),('227-27',6),('271-71',2),('257-57',5),('34-34',8),
                  ('262-62',10),('273-73',13),('249-49',9),('235-35',3),('263-63',1),
                  ('226-26',4),('277-77',14),('233-33',7),('269-69',12),('231-31',11),
                  ('28B-831',16),('245-45',17),('DX-DX',18)
            ");

            $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_source_stock");
            $pdo->exec("
                CREATE TEMPORARY TABLE tmp_source_stock (
                    reference VARCHAR(64) NOT NULL,
                    id_store INT NOT NULL,
                    qty INT NOT NULL,
                    PRIMARY KEY (reference, id_store)
                ) ENGINE=MEMORY
            ");

            $pdo->exec("
                INSERT INTO tmp_source_stock (reference, id_store, qty)
                SELECT
                    rs.REFERENCE,
                    sm.id_store,
                    GREATEST(CAST(rs.STOCK_DISPONIBLE AS SIGNED), 0) AS qty
                FROM `{$database2}`.`rep_stock` rs
                INNER JOIN tmp_store_map sm
                    ON sm.key_ad = CONCAT(rs.AGENCE, '-', rs.DEPOT)
            ");

            $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_mapped_stock");
            $pdo->exec("
                CREATE TEMPORARY TABLE tmp_mapped_stock (
                    id_product INT NOT NULL,
                    id_product_attribute INT NOT NULL,
                    id_store INT NOT NULL,
                    qty INT NOT NULL,
                    PRIMARY KEY (id_store, id_product, id_product_attribute)
                ) ENGINE=MEMORY
            ");

            $pdo->exec("
                INSERT INTO tmp_mapped_stock (id_product, id_product_attribute, id_store, qty)
                SELECT
                    pa.id_product,
                    pa.id_product_attribute,
                    ss.id_store,
                    ss.qty
                FROM tmp_source_stock ss
                INNER JOIN `{$database1}`.`"._DB_PREFIX_."product_attribute` pa
                    ON pa.reference = ss.reference
                INNER JOIN `{$database1}`.`"._DB_PREFIX_."product` p
                    ON p.id_product = pa.id_product AND p.active = 1
            ");

            // Gérer les références BRUT qui n'existent pas dans PrestaShop (entrées virtuelles)
            if (!empty($brutReferences)) {
                $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_brut_refs");
                $pdo->exec("
                    CREATE TEMPORARY TABLE tmp_brut_refs (
                        reference VARCHAR(64) PRIMARY KEY
                    ) ENGINE=MEMORY
                ");

                // Insérer toutes les références BRUT du mapping
                $brutValues = array_map(function($ref) use ($pdo) {
                    return '(' . $pdo->quote($ref) . ')';
                }, $brutReferences);
                $pdo->exec("
                    INSERT INTO tmp_brut_refs (reference) VALUES " . implode(',', $brutValues)
                );

                // Créer des entrées virtuelles pour les références BRUT qui ont du stock mais n'existent pas dans PrestaShop
                $pdo->exec("
                    INSERT INTO `{$database1}`.`"._DB_PREFIX_."ckcstockstores_stocks` (id_store, id_product, id_product_attribute, quantity)
                    SELECT 
                        ss.id_store,
                        -1 AS id_product,
                        ABS(CRC32(ss.reference)) AS id_product_attribute,
                        ss.qty
                    FROM tmp_source_stock ss
                    INNER JOIN tmp_brut_refs br ON br.reference = ss.reference
                    LEFT JOIN `{$database1}`.`"._DB_PREFIX_."product_attribute` pa ON pa.reference = ss.reference
                    WHERE pa.reference IS NULL
                    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
                ");
            }

            $pdo->exec("
                INSERT INTO `{$database1}`.`"._DB_PREFIX_."ckcstockstores_stocks` (id_store, id_product, id_product_attribute, quantity)
                SELECT id_store, id_product, id_product_attribute, qty
                FROM tmp_mapped_stock
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
            ");

            $pdo->exec("
                UPDATE `{$database1}`.`"._DB_PREFIX_."ckcstockstores_stocks` s
                LEFT JOIN tmp_mapped_stock t
                  ON t.id_store = s.id_store
                 AND t.id_product = s.id_product
                 AND t.id_product_attribute = s.id_product_attribute
                SET s.quantity = 0
                WHERE t.id_store IS NULL
                  AND s.id_product != -1
            ");

            // Nettoyer les entrées virtuelles qui ne sont plus dans rep_stock
            if (!empty($brutReferences)) {
                $pdo->exec("
                    UPDATE `{$database1}`.`"._DB_PREFIX_."ckcstockstores_stocks` s
                    LEFT JOIN tmp_source_stock ss 
                      ON ss.id_store = s.id_store 
                     AND ABS(CRC32(ss.reference)) = s.id_product_attribute
                    LEFT JOIN tmp_brut_refs br 
                      ON ABS(CRC32(br.reference)) = s.id_product_attribute
                    SET s.quantity = 0
                    WHERE s.id_product = -1
                      AND ss.reference IS NULL
                      AND br.reference IS NOT NULL
                ");
            }

            $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_totals");
            $pdo->exec("
                CREATE TEMPORARY TABLE tmp_totals (
                    id_product INT NOT NULL,
                    id_product_attribute INT NOT NULL,
                    id_shop INT NOT NULL,
                    id_shop_group INT NOT NULL,
                    qty INT NOT NULL,
                    PRIMARY KEY (id_product, id_product_attribute, id_shop, id_shop_group)
                ) ENGINE=MEMORY
            ");

            $pdo->exec("
                INSERT INTO tmp_totals (id_product, id_product_attribute, id_shop, id_shop_group, qty)
                SELECT
                    id_product,
                    id_product_attribute,
                    {$targetShopId} AS id_shop,
                    {$targetShopGroupId} AS id_shop_group,
                    SUM(qty) AS qty
                FROM tmp_mapped_stock
                GROUP BY id_product, id_product_attribute
            ");

            $pdo->exec("
                INSERT INTO `{$database1}`.`"._DB_PREFIX_."stock_available`
                    (id_product, id_product_attribute, id_shop, id_shop_group, quantity, physical_quantity)
                SELECT id_product, id_product_attribute, id_shop, id_shop_group, qty, qty
                FROM tmp_totals
                ON DUPLICATE KEY UPDATE
                    quantity = VALUES(quantity),
                    physical_quantity = VALUES(physical_quantity)
            ");

            $pdo->exec("
                UPDATE `{$database1}`.`"._DB_PREFIX_."stock_available` sa
                LEFT JOIN tmp_totals t
                  ON t.id_product = sa.id_product
                 AND t.id_product_attribute = sa.id_product_attribute
                SET sa.quantity = 0, sa.physical_quantity = 0
                WHERE t.id_product IS NULL
            ");

            // pack
            $pdo->exec("
                UPDATE `{$database1}`.`"._DB_PREFIX_."product` p
                SET p.out_of_stock = 1
                WHERE p.id_product IN (
                    SELECT ap.id_pack FROM `{$database1}`.`"._DB_PREFIX_."pm_advancedpack` ap
                )
            ");

            $pdo->exec("
                UPDATE `{$database1}`.`"._DB_PREFIX_."stock_available` p
                SET p.out_of_stock = 1
                WHERE p.id_product IN (
                    SELECT ap.id_pack FROM `{$database1}`.`"._DB_PREFIX_."pm_advancedpack` ap
                )
            ");

            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
