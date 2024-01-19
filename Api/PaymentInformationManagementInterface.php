<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Api;

interface PaymentInformationManagementInterface
{
    /**
     * Set payment information and place order for a specified cart.
     *
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @param \Marketplacer\SellerShipping\Api\SellerShippingMethodInterface|null $sellerShippingMethod
     * @return int Order ID.
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null,
        \Marketplacer\SellerShipping\Api\SellerShippingMethodInterface $sellerShippingMethod = null
    );
}
