<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Api;

use Magento\Quote\Api\Data\AddressInterface;

interface GuestShipmentEstimationInterface
{
    /**
     * Estimate shipping
     *
     * @param string $cartId The shopping cart ID.
     * @param int $addressId The estimate address id
     * @param int $sellerId The estimate address id
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods.
     */
    public function estimate(string $cartId, int $addressId, int $sellerId): array;

    /**
     * @param string $cartId
     * @param AddressInterface $address
     * @param int $sellerId
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods.
     */
    public function estimateByExtendedAddress(string $cartId, AddressInterface $address, int $sellerId): array;
}
