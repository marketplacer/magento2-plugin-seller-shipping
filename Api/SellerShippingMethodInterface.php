<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Api;

interface SellerShippingMethodInterface
{
    public const KEY_METHODS = 'methods';

    /**
     * @return mixed
     */
    public function getMethods();

    /**
     * @param $values
     *
     * @return $this
     */
    public function setMethods($values);
}
