<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param \Marketplacer\Seller\Helper\Config $sellerConfig
     */
    public function __construct(
        private readonly \Marketplacer\Seller\Helper\Config $sellerConfig
    ) {
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'marketplacer_seller_shipping' => [
                'generalSellerId' => $this->sellerConfig->getGeneralSellerId(),
            ],
        ];
    }
}
