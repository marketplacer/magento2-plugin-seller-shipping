<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\ViewModel\Checkout\Onepage\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Marketplacer\Base\ViewModel\BaseViewModel;
use Magento\Framework\Escaper;

class SuccessAdditionalInfo extends BaseViewModel
{
    public function __construct(
        private readonly UrlInterface $url,
        private readonly StoreManagerInterface $storeManager,
        private readonly Session $checkoutSession,
        Escaper $escaper,
        array $data = []
    ) {
        parent::__construct($escaper, $data);
    }

    /**
     * @return array
     */
    public function getOrderIds(): array
    {
        $ids = $this->checkoutSession->getOrderIds();
        if ($ids && is_array($ids)) {
            return $ids;
        }
        return [];
    }

    /**
     * @param int $id
     * @return string
     */
    public function getSellerName(int $id): string
    {
        $sellers = $this->getSellers();
        return $sellers[$id] ?? '';
    }

    /**
     * @return array
     */
    private function getSellers(): array
    {
        return $this->checkoutSession->getSellers() ?: [];
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getContinueUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getViewOrderUrl(int $orderId): string
    {
        return $this->url->getUrl('sales/order/view/', ['order_id' => $orderId, '_secure' => true]);
    }
}
