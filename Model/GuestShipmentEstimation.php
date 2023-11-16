<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Marketplacer\SellerShipping\Api\GuestShipmentEstimationInterface;

class GuestShipmentEstimation implements GuestShipmentEstimationInterface
{
    /**
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ShipmentEstimation $shipmentEstimation
     */
    public function __construct(
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly ShipmentEstimation $shipmentEstimation
    ) {
    }

    /**
     * Estimate shipping
     *
     * @param string $cartId The shopping cart ID.
     * @param int $addressId The estimate address id
     * @param int $sellerId The estimate address id
     * @return ShippingMethodInterface[] An array of shipping methods.
     */
    public function estimate(string $cartId, int $addressId, int $sellerId): array
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->shipmentEstimation->estimate((int)$quoteIdMask->getQuoteId(), $addressId, $sellerId);
    }

    /**
     * @param string $cartId
     * @param AddressInterface $address
     * @param int $sellerId
     * @return ShippingMethodInterface[] An array of shipping methods.
     */
    public function estimateByExtendedAddress(string $cartId, AddressInterface $address, int $sellerId): array
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->shipmentEstimation->estimateByExtendedAddress(
            (int)$quoteIdMask->getQuoteId(),
            $address,
            $sellerId
        );
    }
}
