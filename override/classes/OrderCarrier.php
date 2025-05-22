<?php

class OrderCarrier extends OrderCarrierCore
{
    public function add($autodate = true, $nullValues = false)
    {
        if (parent::add($autodate, $nullValues)) {
            Hook::exec('actionObjectOrderCarrierAddAfter', ['object' => $this]);

            return true;
        }

        return false;
    }
}
