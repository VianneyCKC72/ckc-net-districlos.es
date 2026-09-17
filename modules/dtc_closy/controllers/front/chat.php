<?php
/**
 * Endpoints AJAX JSON pour le panneau Closy (YourGPT API).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyConfig.php';
require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyPanelService.php';
require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyProductResolver.php';
require_once _PS_MODULE_DIR_ . 'dtc_closy/classes/DtcClosyChatSecurity.php';

class Dtc_closyChatModuleFrontController extends ModuleFrontController
{
    private const MAX_MESSAGE_LENGTH = 1000;

    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        if (!DtcClosyPanelService::isConfigured()) {
            $this->jsonError('Panneau Closy non configuré.', 503);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Méthode non autorisée.', 405);
        }

        if (!DtcClosyChatSecurity::validateToken(Tools::getValue('token'))) {
            $this->jsonError('Accès refusé.', 403);
        }

        if (!DtcClosyChatSecurity::checkRateLimit()) {
            $this->jsonError('Trop de requêtes. Réessayez dans un instant.', 429);
        }

        $action = Tools::getValue('action');
        $service = new DtcClosyPanelService();
        $idLang = (int) $this->context->language->id;

        try {
            if ($action === 'session') {
                $visitorUid = trim((string) Tools::getValue('visitor_uid'));
                $session = $service->createSession($visitorUid !== '' ? $visitorUid : null);
                $this->jsonOk($session);
            }

            if ($action === 'messages') {
                $sessionUid = trim((string) Tools::getValue('session_uid'));
                if ($sessionUid === '') {
                    $this->jsonError('session_uid manquant.');
                }
                $limit = (int) Tools::getValue('limit');
                if ($limit <= 0) {
                    $limit = 100;
                }
                $page = (int) Tools::getValue('page');
                if ($page <= 0) {
                    $page = 1;
                }
                $messages = $service->getSessionMessages($sessionUid, $limit, $page);
                $this->jsonOk(['messages' => $messages]);
            }

            if ($action === 'send') {
                $sessionUid = Tools::getValue('session_uid');
                $message = trim((string) Tools::getValue('message'));
                $idProduct = (int) Tools::getValue('id_product');
                $isFirstMessage = (string) Tools::getValue('is_first_message') === '1';
                $mode = (string) Tools::getValue('mode');

                if ($sessionUid === '' || $sessionUid === null) {
                    $this->jsonError('session_uid manquant.');
                }
                if ($message === '') {
                    $this->jsonError('Message vide.');
                }
                if (Tools::strlen($message) > self::MAX_MESSAGE_LENGTH) {
                    $this->jsonError('Message trop long. Merci de limiter votre demande à 1000 caractères.');
                }

                $result = $service->sendMessage(
                    $sessionUid,
                    $message,
                    $idProduct,
                    $idLang,
                    $isFirstMessage,
                    $mode
                );
                $this->jsonOk([
                    'reply' => $result['message'],
                    'message_id' => $result['message_id'],
                ]);
            }

            if ($action === 'resolveProducts') {
                $urls = $this->decodeUrlsParam(Tools::getValue('urls'));
                $urls = array_values(array_filter($urls, static function ($url) {
                    return is_string($url) && DtcClosyProductResolver::urlLooksLikeProductPage($url);
                }));

                if (count($urls) === 0) {
                    $this->jsonOk(['products' => []]);
                }

                $resolver = new DtcClosyProductResolver($this->context);
                $products = $resolver->resolveProductsFromUrls($urls);

                $this->jsonOk(['products' => $products]);
            }

            $this->jsonError('Action inconnue.');
        } catch (PrestaShopException $e) {
            $this->jsonError($e->getMessage());
        } catch (Exception $e) {
            $this->jsonError('Erreur serveur.');
        }
    }

    /**
     * @param mixed $urlsRaw
     *
     * @return list<string>
     */
    private function decodeUrlsParam($urlsRaw): array
    {
        if (is_string($urlsRaw) && $urlsRaw !== '') {
            $decoded = json_decode($urlsRaw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($urlsRaw) ? $urlsRaw : [];
    }

    private function jsonOk(array $data): void
    {
        echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
