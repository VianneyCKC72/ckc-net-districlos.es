<?php
class Ckc_EspaceproLoginModuleFrontController extends ModuleFrontController
{
    public $ssl  = true;
    public $auth = false;

    public function postProcess()
    {
        if (!Tools::isSubmit('submitLogin')) {
            Tools::redirect($this->context->link->getPageLink('index', true));
        }

        $email = trim(Tools::getValue('email'));
        $pass  = trim(Tools::getValue('password'));
        $back  = trim(Tools::getValue('back'));

        //
        // jpasse un code car lerreur en get sa deconne..
        // 1 = champs vides, 2 = email invalide, 3 = identifiants incorrects, 4 = compte inactif
        if ($email === '' || $pass === '') {
            return $this->fail(1, $email, $back);
        }
        if (!Validate::isEmail($email)) {
            return $this->fail(2, $email, $back);
        }

        $customer = new Customer();
        $auth     = $customer->getByEmail($email, $pass);

        if (!isset($auth->id) || !$auth->id) {
            return $this->fail(3, $email, $back);
        }
        if ((int)$customer->active !== 1) {
            return $this->fail(4, $email, $back);
        }


        $this->context->updateCustomer($customer);

        $target = $this->context->link->getPageLink('index', true);

        // pouir faire comme le shop, j'utilise urlBelongsToShop avec l'url en back
        if ($back && Tools::urlBelongsToShop($back)) {
            $target = $back;
        }
        Tools::redirect($target);
    }

    private function fail(int $code, string $email, string $back = '')
    {
        $params = [
            'pro_login_error' => $code,
            'login_email'     => $email,
        ];
        if ($back && Tools::urlBelongsToShop($back)) {
            $params['back'] = $back;
        }
        Tools::redirect($this->context->link->getPageLink('index', true, null, $params));
        exit;
    }
}
