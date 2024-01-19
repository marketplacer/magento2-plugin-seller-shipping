<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Api;

use Magento\Quote\Api\Data\AddressInterface;

interface ShipmentEstimationInterface
{
    /**
     * Estimate shipping
     *
     * @param int $cartId The shopping cart ID.
     * @param int $addressId The estimate address id
     * @param int $sellerId The estimate address id
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods.
     */
    public function estimate($cartId, $addressId, $sellerId): array;

    /**
     * @param int $cartId
     * @param AddressInterface $address
     * @param int $sellerId
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods.
     */
    public function estimateByExtendedAddress($cartId, AddressInterface $address, $sellerId): array;
}
