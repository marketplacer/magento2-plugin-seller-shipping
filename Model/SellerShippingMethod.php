<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Framework\DataObject;
use Marketplacer\SellerShipping\Api\SellerShippingMethodInterface;

class SellerShippingMethod extends DataObject implements SellerShippingMethodInterface
{

    /**
     * @return mixed
     */
    public function getMethods()
    {
        return (array)$this->getData(self::KEY_METHODS);
    }

    /**
     * @param $values
     *
     * @return SellerShippingMethodInterface
     */
    public function setMethods($values)
    {
        return $this->setData(self::KEY_METHODS, (array)$values);
    }
}