<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Ui\Component\Control\Seller;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class UpdateSellerShipping implements ButtonProviderInterface
{
    /**
     * @param UrlInterface $url
     */
    public function __construct(
        private readonly UrlInterface $url
    ) {
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Update'),
            'class' => 'save primary',
            'on_click' => sprintf("location.href = '%s';", $this->url->getUrl('*/*/updateSellerShipping')),
        ];
    }
}
