<?php

class MyAccountController extends MyAccountControllerCore
{
    public function init()
    {
        parent::init();

        Tools::redirect(
            $this->context->link->getPageLink(
                'identity',
                true
            )
        );
    }
}
