<?php
namespace Dealt\Tax;

use Address;

class DealtFixedRateTaxManager implements \TaxManagerInterface
{
    /** @var \TaxCalculator */
    private $calculator;

    public function __construct(\TaxCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /** @return \TaxCalculator */
    public function getTaxCalculator()
    {
        return $this->calculator;
    }

    public static function isAvailableForThisAddress(Address $address)
    {
        // TODO: Implement isAvailableForThisAddress() method.
    }
}
