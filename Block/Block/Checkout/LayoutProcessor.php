<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Block\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout): array
    {
        unset($jsLayout["components"]["checkout"]["children"]["sidebar"]["children"]["summary"]);
        return $jsLayout;
    }
}
