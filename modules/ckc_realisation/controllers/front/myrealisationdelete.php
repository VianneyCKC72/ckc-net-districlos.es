<?php
class Ckc_RealisationMyrealisationdeleteModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    private function jsonOut(array $payload)
    {
        // Rien n’a été envoyé avant :
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        die(Tools::jsonEncode($payload));
    }

    public function postProcess()
    {
        $isAjax  = (Tools::getValue('ajax') === '1') || (Tools::getValue('xhr') === '1');
        $backUrl = $this->context->link->getModuleLink($this->module->name, 'myrealisation', [], true);

        // Auth
        $c = $this->context->customer;
        if (!$c || !(int)$c->id) {
            if ($isAjax) { $this->jsonOut(['ok'=>false,'msg'=>'auth']); }
            Tools::redirect($backUrl.'&err=auth');
        }

        // CSRF
        if (Tools::getToken(false) !== Tools::getValue('token')) {
            if ($isAjax) { $this->jsonOut(['ok'=>false,'msg'=>'token']); }
            Tools::redirect($backUrl.'&err=token');
        }

        $id = (int)Tools::getValue('id_realisation', 0);
        if ($id <= 0) {
            if ($isAjax) { $this->jsonOut(['ok'=>false,'msg'=>'badid']); }
            Tools::redirect($backUrl.'&err=badid');
        }

        // Vérifier que la réalisation appartient au client (évite WHERE vide)
        $q = new DbQuery();
        $q->select('id_realisation, filename, id_shop');
        $q->from('ckc_realisation');
        $q->where('id_realisation = '.(int)$id);
        $q->where('id_customer = '.(int)$c->id);
        $row = Db::getInstance()->getRow($q);

        if (!$row) {
            if ($isAjax) { $this->jsonOut(['ok'=>false,'msg'=>'notfound']); }
            Tools::redirect($backUrl.'&err=notfound');
        }

        // Supprimer fichiers
        $dir = $this->module->storageDir((int)$row['id_shop']);
        @unlink($dir.$row['filename']);
        @unlink($dir.'thumb_'.$row['filename']);

        // Supprimer BDD
        require_once _PS_MODULE_DIR_.$this->module->name.'/classes/Realisation.php';
        $obj = new Realisation((int)$id);
        $ok  = Validate::isLoadedObject($obj) ? (bool)$obj->delete() : false;

        if ($isAjax) {
            $this->jsonOut(['ok'=>$ok, 'id'=>$id, 'msg'=>$ok ? 'deleted' : 'delete_failed']);
        }

        Tools::redirect($backUrl.($ok ? '&del=1' : '&err=delete'));
    }

    public function initContent()
    {
        // On passe par POST; accès direct → retour à la page principale
        Tools::redirect($this->context->link->getModuleLink($this->module->name, 'myrealisation', [], true));
    }
}

