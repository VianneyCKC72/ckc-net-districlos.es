<?php

class Validate extends ValidateCore
{
    /**
     * Override de la validation du nom pour autoriser les chiffres.
     */
    public static function isName($name)
    {
        // même règle que PS, mais sans bloquer 0-9
        return preg_match('/^[^!<>,;?=+()@#"°{}_$%:¤|]*$/u', $name);
    }
}
