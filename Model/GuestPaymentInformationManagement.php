<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Marketplacer\SellerShipping\Api\GuestPaymentInformationManagementInterface;
use Marketplacer\SellerShipping\Api\SellerShippingMethodInterface;

class GuestPaymentInformationManagement implements GuestPaymentInformationManagementInterface
{
    /**
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param PaymentInformationManagement $paymentInformationManagement
     */
    public function __construct(
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly PaymentInformationManagement $paymentInformationManagement
    ) {
    }

    /**
     * Set payment information and place order for a specified cart.
     *
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @param SellerShippingMethodInterface|null $sellerShippingMethod
     * @return int Order ID.
     * @throws CouldNotSaveException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null,
        ?SellerShippingMethodInterface $sellerShippingMethod = null
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->paymentInformationManagement->savePaymentInformationAndPlaceOrder(
            (int)$quoteIdMask->getQuoteId(),
            $paymentMethod,
            $billingAddress,
            $sellerShippingMethod,
            $email,
            $cartId
        );
    }
}
