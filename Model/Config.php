<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public const XML_PATH_MARKETPLACER_USE_CART_PRICE_RULES = 'marketplacer_seller/general/use_cart_price_rules';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     *  Check whether to use standard cart price rules magento
     *
     * @return bool
     */
    public function isUseCartPriceRules(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_MARKETPLACER_USE_CART_PRICE_RULES
        );
    }
}
