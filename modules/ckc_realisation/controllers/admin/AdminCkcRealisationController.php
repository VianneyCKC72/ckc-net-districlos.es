<?php
class AdminCkcRealisationController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'ckc_realisation';
        $this->className = 'Realisation';
        $this->lang = true;
        $this->list_id = 'ckc_realisation';
        $this->identifier = 'id_realisation';
        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_realisation' => ['title' => $this->l('ID'), 'align' => 'text-center', 'class' => 'fixed-width-xs'],
            'id_shop'        => ['title' => $this->l('Shop')],
            'id_customer'    => ['title' => $this->l('Client')],
            'title'          => ['title' => $this->l('Titre'), 'filter_key' => 'b!title'],
            'status'         => ['title' => $this->l('Statut')],
            'source'         => ['title' => $this->l('Source')],
            'date_add'       => ['title' => $this->l('Ajout'), 'type' => 'datetime'],
        ];

        $this->bulk_actions = [
            'approve' => ['text' => $this->l('Approuver'), 'confirm' => $this->l('Approuver les éléments sélectionnés ?')],
            'reject'  => ['text' => $this->l('Rejeter'), 'confirm' => $this->l('Rejeter les éléments sélectionnés ?')],
            'delete'  => ['text' => $this->l('Supprimer'), 'confirm' => $this->l('Supprimer définitivement ?')],
        ];
    }

    public function renderList()
    {
        // Configure les actions ligne
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        // Filtres rapides (facultatif)
        $this->fields_list['status']['type'] = 'select';
        $this->fields_list['status']['list'] = [
            'pending'  => $this->l('En attente'),
            'approved' => $this->l('Approuvée'),
            'rejected' => $this->l('Rejetée'),
        ];

        // Génère l'HTML via HelperList
        $listHtml = parent::renderList();

        // Assigne à Smarty et utilise ton template
        $this->context->smarty->assign([
                                           'list' => $listHtml,
                                           'title' => $this->l('Réalisations'),
                                           'new_link' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                                       ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/list.tpl'
        );
    }

    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) { return; }
        $languages = Language::getLanguages(false);
        $this->fields_form = [
            'legend' => ['title' => $this->l('Réalisation')],
            'input' => [
                ['type' => 'select', 'label' => $this->l('Shop'), 'name' => 'id_shop',
                 'options' => ['query' => array_map(fn($id)=>['id'=>$id,'name'=>$id], Shop::getShops(false,null,true)),
                               'id' => 'id', 'name' => 'name']],
                ['type' => 'text', 'label' => $this->l('Magasin (id_store)'), 'name' => 'id_store', 'required' => true],
                ['type' => 'text', 'label' => $this->l('Client (id_customer)'), 'name' => 'id_customer'],
                ['type' => 'file', 'label' => $this->l('Photo'), 'name' => 'file', 'desc' => $this->l('JPEG/PNG max 5MB')],
                ['type' => 'select', 'label' => $this->l('Statut'), 'name' => 'status',
                 'options' => ['query' => [
                     ['id'=>'pending','name'=>$this->l('En attente')],
                     ['id'=>'approved','name'=>$this->l('Approuvée')],
                     ['id'=>'rejected','name'=>$this->l('Rejetée')],
                 ], 'id'=>'id','name'=>'name']],
                ['type' => 'text', 'label' => $this->l('Titre'), 'name' => 'title', 'lang' => true],
                ['type' => 'text', 'label' => $this->l('Texte ALT'), 'name' => 'alt', 'lang' => true],
            ],
            'submit' => ['title' => $this->l('Enregistrer')]
        ];
        return parent::renderForm();
    }

    public function processAdd()
    {
        $this->validateAndHandleUpload();
        return parent::processAdd();
    }

    public function processUpdate()
    {
        $this->validateAndHandleUpload(false);
        return parent::processUpdate();
    }

    protected function validateAndHandleUpload($required = true)
    {
        if (!isset($_FILES['file'])) {
            if ($required) $this->errors[] = $this->l('Fichier requis');
            return;
        }
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $mime = mime_content_type($file['tmp_name']);
                $allowed = ['image/jpeg','image/png'];
                if (!in_array($mime, $allowed)) {
                    $this->errors[] = $this->l('Format non supporté');
                    return;
                }
                if ($file['size'] > 5*1024*1024) {
                    $this->errors[] = $this->l('Fichier trop volumineux (>5MB)');
                    return;
                }
                $idShop = (int)Tools::getValue('id_shop', (int)$this->context->shop->id);
                $dir = Realisation::storageDir($idShop);
                @mkdir($dir, 0755, true);
                $basename = uniqid('real_', true).($mime==='image/png'?'.png':'.jpg');
                $dest = $dir.$basename;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $this->errors[] = $this->l('Échec de l’upload');
                    return;
                }
                // thumbnail
                ImageManager::thumbnail($dest, 'thumb_'.$basename, 360, 'jpg', true, $dir);

                $_POST['filename'] = $basename;
                $_POST['mime'] = $mime;
                $_POST['filesize'] = (int)$file['size'];
            } else {
                $this->errors[] = $this->l('Erreur upload (code ').$file['error'].')';
            }
        }
    }

    public function processBulkApprove() { $this->bulkUpdateStatus('approved'); }
    public function processBulkReject()  { $this->bulkUpdateStatus('rejected'); }

    protected function bulkUpdateStatus($status)
    {
        $ids = Tools::getValue($this->table.'Box');
        if (is_array($ids)) {
            Db::getInstance()->update(
                'ckc_realisation',
                ['status' => pSQL($status)],
                'id_realisation IN ('.implode(',', array_map('intval',$ids)).')'
            );
        }
    }

    public function processDelete()
    {
        $obj = $this->loadObject();
        if ($obj && $obj->filename) {
            @unlink($obj->getFilePath());
            @unlink($obj->getThumbPath());
        }
        return parent::processDelete();
    }
}
