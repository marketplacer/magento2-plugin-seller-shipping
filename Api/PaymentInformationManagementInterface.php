<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

interface PaymentInformationManagementInterface
{
    /**
     * Set payment information and place order for a specified cart.
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @param SellerShippingMethodInterface|null $sellerShippingMethod
     * @param string|null $email
     * @param int|null $quoteIdMask
     * @return int Order ID.
     * @throws CouldNotSaveException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        ?\Magento\Quote\Api\Data\AddressInterface $billingAddress = null,
        ?\Marketplacer\SellerShipping\Api\SellerShippingMethodInterface $sellerShippingMethod = null,
        $email = null,
        $quoteIdMask = null
    );
}
