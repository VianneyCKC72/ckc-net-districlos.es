<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class SyncGoogleReviewsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (Tools::getValue('token') !== substr(Tools::encrypt('ckcstorespresentation/syncgooglereviews'), 0, 10)) {
            die('Forbidden');
        }

        $apiKey = $this->module->getGoogleApiKey();
        if ($apiKey === '') {
            die('GOOGLE_API_KEY manquante');
        }

        $stores = Db::getInstance()->executeS(
            'SELECT s.`id_ckcstorespresentation`, p.`place_id`
            FROM `' . _DB_PREFIX_ . 'ckcstorespresentation` s
            INNER JOIN `' . _DB_PREFIX_ . 'presentation_place` p
                ON p.`id_ckcstorespresentation` = s.`id_ckcstorespresentation`
            WHERE TRIM(p.`place_id`) != ""'
        ) ?: [];

        $stats = ['added' => 0, 'skipped' => 0];

        self::ensureGoogleMapsUriColumn();

        foreach ($stores as $store) {
            $idStore = (int) $store['id_ckcstorespresentation'];
            $data = $this->fetchGoogle($store['place_id'], $apiKey);
            if (!$data) {
                continue;
            }

            $placeUpdate = [];
            if (!empty($data['rating'])) {
                $placeUpdate['google_rating'] = (float) $data['rating'];
                $placeUpdate['google_rating_count'] = (int) ($data['userRatingCount'] ?? 0);
                $placeUpdate['google_rating_updated_at'] = date('Y-m-d H:i:s');
            }
            $mapsUri = $data['googleMapsLinks']['reviewsUri']
                ?? $data['googleMapsUri']
                ?? null;
            if (!empty($mapsUri)) {
                $placeUpdate['google_maps_uri'] = pSQL((string) $mapsUri);
            }
            if ($placeUpdate) {
                Db::getInstance()->update('presentation_place', $placeUpdate, '`id_ckcstorespresentation` = ' . $idStore);
            }

            foreach ($data['reviews'] as $review) {
                if ((int) ($review['rating'] ?? 0) !== 5 || empty($review['publishTime'])) {
                    continue;
                }

                $author = Tools::substr((string) (($review['authorAttribution'] ?? [])['displayName'] ?? ''), 0, 255);
                $date = date('Y-m-d H:i:s', strtotime($review['publishTime']));
                $gid = (string) ($review['name'] ?? '');

                if ($this->alreadySaved($idStore, $gid, $author, $date)) {
                    ++$stats['skipped'];
                    continue;
                }

                $row = [
                    'id_ckcstorespresentation' => $idStore,
                    'author_name' => pSQL($author),
                    'rating' => 5,
                    'review_text' => pSQL(
                        (string) (($review['originalText'] ?? [])['text'] ?? ($review['text'] ?? [])['text'] ?? ''),
                        true
                    ),
                    'review_date' => pSQL($date),
                ];
                if ($gid !== '') {
                    $row['google_review_id'] = pSQL($gid);
                }

                if (Db::getInstance()->insert('presentation_reviews', $row)) {
                    ++$stats['added'];
                }
            }

            usleep(200000);
        }

        die(sprintf('OK — %d magasin(s) | %d avis ajoutés | %d ignorés', count($stores), $stats['added'], $stats['skipped']));
    }

    private function alreadySaved(int $idStore, string $gid, string $author, string $date): bool
    {
        $db = Db::getInstance();

        if ($gid !== '' && $db->getValue(
            'SELECT `id_review` FROM `' . _DB_PREFIX_ . 'presentation_reviews` WHERE `google_review_id` = "' . pSQL($gid) . '"'
        )) {
            return true;
        }

        $idReview = $db->getValue(
            'SELECT `id_review` FROM `' . _DB_PREFIX_ . 'presentation_reviews`
            WHERE `id_ckcstorespresentation` = ' . $idStore . '
            AND `author_name` = "' . pSQL($author) . '"
            AND `review_date` = "' . pSQL($date) . '"'
        );

        if ($idReview && $gid !== '') {
            $db->execute(
                'UPDATE `' . _DB_PREFIX_ . 'presentation_reviews`
                SET `google_review_id` = "' . pSQL($gid) . '"
                WHERE `id_review` = ' . (int) $idReview . '
                AND (`google_review_id` IS NULL OR `google_review_id` = "")'
            );
        }

        return (bool) $idReview;
    }

    private function fetchGoogle(string $placeId, string $apiKey): ?array
    {
        $ctx = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => "X-Goog-Api-Key: {$apiKey}\r\nX-Goog-FieldMask: rating,userRatingCount,reviews,googleMapsUri,googleMapsLinks\r\nX-Goog-Language-Code: fr\r\n",
            'ignore_errors' => true,
        ]]);

        $json = @file_get_contents('https://places.googleapis.com/v1/places/' . rawurlencode($placeId), false, $ctx);
        $data = is_string($json) ? json_decode($json, true) : null;

        if (!is_array($data) || isset($data['error'])) {
            return null;
        }

        $reviews = $data['reviews'] ?? [];
        usort($reviews, fn ($a, $b) => strtotime($b['publishTime'] ?? '') <=> strtotime($a['publishTime'] ?? ''));

        return [
            'rating' => $data['rating'] ?? null,
            'userRatingCount' => $data['userRatingCount'] ?? null,
            'reviews' => $reviews,
            'googleMapsUri' => $data['googleMapsUri'] ?? null,
            'googleMapsLinks' => $data['googleMapsLinks'] ?? null,
        ];
    }

    private static function ensureGoogleMapsUriColumn(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $columns = Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'presentation_place`'
        ) ?: [];

        foreach ($columns as $column) {
            if (($column['Field'] ?? '') === 'google_maps_uri') {
                return;
            }
        }

        Db::getInstance()->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . 'presentation_place`
            ADD `google_maps_uri` VARCHAR(512) NULL DEFAULT NULL'
        );
    }
}

// Alias exigés par le Dispatcher PrestaShop (nom_module + nom_controleur + ModuleFrontController).
class_alias(
    SyncGoogleReviewsModuleFrontController::class,
    'ckcstorespresentation' . 'syncgooglereviews' . 'ModuleFrontController'
);
class_alias(
    SyncGoogleReviewsModuleFrontController::class,
    'ckcstorespresentation' . 'SyncGoogleReviews' . 'ModuleFrontController'
);
