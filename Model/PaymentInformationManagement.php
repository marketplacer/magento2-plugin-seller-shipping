<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Checkout\Api\Exception\PaymentProcessingRateLimitExceededException;
use Magento\Checkout\Api\PaymentInformationManagementInterface as PaymentInformationManagementInterfaceAlias;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Checkout\Model\AddressComparatorInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestPaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Marketplacer\SellerApi\Api\Data\OrderInterface;
use Marketplacer\SellerApi\Api\Data\ProductAttributeInterface;
use Marketplacer\SellerShipping\Api\PaymentInformationManagementInterface;
use Marketplacer\SellerShipping\Api\SellerShippingMethodInterface;
use PayPal\Braintree\Gateway\Command\GetPaymentNonceCommand;
use PayPal\Braintree\Model\Ui\ConfigProvider;
use PayPal\Braintree\Model\Ui\PayPal\ConfigProvider as PaypalConfigProvider;
use PayPal\Braintree\Observer\DataAssignObserver;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterfaceFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask;

class PaymentInformationManagement implements PaymentInformationManagementInterface
{
    private $saveRateLimitDisabled;
    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CartExtensionFactory $cartExtensionFactory
     * @param ShippingAssignmentProcessor $shippingAssignmentProcessor
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param GetPaymentNonceCommand $getPaymentNonceCommand
     * @param ToOrderPayment $toOrderPayment
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param Session $checkoutSession
     * @param AddressComparatorInterface $addressComparator
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartExtensionFactory $cartExtensionFactory,
        private readonly ShippingAssignmentProcessor $shippingAssignmentProcessor,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        private readonly GetPaymentNonceCommand $getPaymentNonceCommand,
        private readonly ToOrderPayment $toOrderPayment,
        private readonly \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement,
        private readonly Session $checkoutSession,
        private readonly AddressComparatorInterface $addressComparator,
        private readonly GuestPaymentMethodManagementInterface $paymentMethodManagement,
        private readonly PaymentProcessingRateLimiterInterface $paymentsRateLimiter,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly QuoteIdToMaskedQuoteIdInterfaceFactory $quoteIdToMaskedQuoteIdFactory,
        private readonly QuoteIdMask $quoteIdMaskResource
    ) {
    }
    /**
     * Set payment information and place order for a specified cart.
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @param SellerShippingMethodInterface|null $sellerShippingMethod
     * @return int Order ID.
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null,
        SellerShippingMethodInterface $sellerShippingMethod = null,
        $email = null,
        $quoteIdMask = null
    ) {
        $methods  = [];
        $result = null;
        foreach ($sellerShippingMethod->getMethods() as $item) {
            $methods[$item['sellerId']] = $item['method'];
        }

        $quote = $this->cartRepository->get($cartId);

        $itemsBySeller = array_filter($this->getCartItemsBySeller($quote->getItems()), function ($items) {
            return \count($items);
        });

        $orderId = null;
        $orderIds = [];
        $sellers = [];

        $paymentToken = null;
        $method = $quote->getPayment()->getMethod();

        $isUseToken = in_array($method, [
            ConfigProvider::CODE,
            PaypalConfigProvider::PAYPAL_CODE,
            ConfigProvider::CC_VAULT_CODE,
            PaypalConfigProvider::PAYPAL_VAULT_CODE
        ], true);

        foreach ($itemsBySeller as $sellerId => $items) {
            $quote->getShippingAddress()->setShippingMethod($methods[$sellerId]) ;
            $prepared = $this->prepareQuote($quote, $items, $orderId !== null, true);

            if ($isUseToken && $paymentToken) {
                $orderPayment = $this->toOrderPayment->convert($quote->getPayment());
                $this->setVaultPayment($orderPayment, $paymentToken, $paymentMethod);
            }

            $this->paymentsRateLimiter->limit();
            try {
                //Have to do this hack because of savePaymentInformation() plugins.
                $this->saveRateLimitDisabled = true;
                $quoteIdToMaskedQuoteId = $this->quoteIdToMaskedQuoteIdFactory->create();
                $this->savePaymentInformation($quoteIdToMaskedQuoteId->execute((int)$prepared->getId()), $email, $paymentMethod, $billingAddress);
            } finally {
                $this->saveRateLimitDisabled = false;
            }

            $orderId = $this->paymentInformationManagement->savePaymentInformationAndPlaceOrder(
                $prepared->getId(),
                $paymentMethod,
                $billingAddress
            );

            if ($orderId !== null) {
                $order = $this->orderRepository->get($orderId);
                if ($isUseToken && !$paymentToken) {
                    $paymentToken = $this->getPaymentToken($order);
                }

                $orderIds[(int)$order->getId()] = $order->getIncrementId();
                $sellers[(int)$order->getId()] = $order->getData(OrderInterface::SELLER_NAMES);

            }
            if ($result === null) {
                $result = $orderId;
            }
        }

        $this->checkoutSession->setOrderIds($orderIds);
        $this->checkoutSession->setSellers($sellers);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformation(
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if (!$this->saveRateLimitDisabled) {
            try {
                $this->savingRateLimiter->limit();
            } catch (PaymentProcessingRateLimitExceededException $ex) {
                //Limit reached
                return false;
            }
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
        $shippingAddress = $quote->getShippingAddress();
        if ($this->addressComparator->isEqual($shippingAddress, $billingAddress)) {
            $shippingAddress->setSameAsBilling(1);
        }
        if ($billingAddress) {
            $billingAddress->setEmail($email);
            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->setBillingAddress($billingAddress);
            $quote->setDataChanges(true);
        } else {
            $quote->getBillingAddress()->setEmail($email);
        }
        $this->limitShippingCarrier($quote);

        if (!(float)$quote->getItemsQty()) {
            throw new CouldNotSaveException(__('Some of the products are disabled.'));
        }

        $this->paymentMethodManagement->set($cartId, $paymentMethod);
        return true;
    }

    /**
     * Limits shipping rates request by carrier from shipping address.
     *
     * @param Quote $quote
     *
     * @return void
     * @see \Magento\Shipping\Model\Shipping::collectRates
     */
    private function limitShippingCarrier(Quote $quote) : void
    {
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && $shippingAddress->getShippingMethod()) {
            $shippingRate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
            if ($shippingRate) {
                $shippingAddress->setLimitCarrier($shippingRate->getCarrier());
            }
        }
    }

    /**
     * Returns vault payment method.
     *
     * For placing sequence of orders, we need to replace the original method on the vault method.
     *
     * @param string $method
     * @return string
     */
    private function getVaultPaymentMethod(string $method): string
    {
        $vaultPaymentMap = [
            ConfigProvider::CODE => ConfigProvider::CC_VAULT_CODE,
            PaypalConfigProvider::PAYPAL_CODE => PaypalConfigProvider::PAYPAL_VAULT_CODE
        ];

        return $vaultPaymentMap[$method] ?? $method;
    }


    /**
     * Sets vault payment method.
     *
     * @param OrderPaymentInterface $orderPayment
     * @param PaymentTokenInterface $paymentToken
     * @return void
     */
    private function setVaultPayment(
        OrderPaymentInterface $orderPayment,
        PaymentTokenInterface $paymentToken,
        PaymentInterface $paymentMethod
    ): void {
        $vaultMethod = $this->getVaultPaymentMethod(
            $orderPayment->getMethod()
        );
        $orderPayment->setMethod($vaultMethod);

        $publicHash = $paymentToken->getPublicHash();
        $customerId = $paymentToken->getCustomerId();

        $result = $this->getPaymentNonceCommand->execute(
            ['public_hash' => $publicHash, 'customer_id' => $customerId]
        )->get();

        $orderPayment->setAdditionalInformation(
            DataAssignObserver::PAYMENT_METHOD_NONCE,
            $result['paymentMethodNonce']
        );
        $orderPayment->setAdditionalInformation(
            PaymentTokenInterface::PUBLIC_HASH,
            $publicHash
        );
        $orderPayment->setAdditionalInformation(
            PaymentTokenInterface::CUSTOMER_ID,
            $customerId
        );

        $vaultMethod = $this->getVaultPaymentMethod(
            $paymentMethod->getMethod()
        );
        $paymentMethod->setMethod($vaultMethod);

        $publicHash = $paymentToken->getPublicHash();
        $customerId = $paymentToken->getCustomerId();

        $result = $this->getPaymentNonceCommand->execute(
            ['public_hash' => $publicHash, 'customer_id' => $customerId]
        )->get();

        $data = $paymentMethod->getAdditionalData();
        $data[DataAssignObserver::PAYMENT_METHOD_NONCE] = $result['paymentMethodNonce'];
        $data[PaymentTokenInterface::PUBLIC_HASH] = $publicHash;
        $data[PaymentTokenInterface::CUSTOMER_ID] = $customerId;

        $paymentMethod->setAdditionalData($data);
    }

    /**
     * Returns payment token.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return PaymentTokenInterface
     * @throws \BadMethodCallException
     */
    private function getPaymentToken(\Magento\Sales\Api\Data\OrderInterface $order): PaymentTokenInterface
    {
        $orderPayment = $order->getPayment();
        $extensionAttributes = $this->getExtensionAttributes($orderPayment);
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        if ($paymentToken === null) {
            throw new \BadMethodCallException('Vault Payment Token should be defined for placed order payment.');
        }

        return $paymentToken;
    }

    /**
     * Gets payment extension attributes.
     *
     * @param OrderPaymentInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(OrderPaymentInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * @param Quote $quote
     * @param array $items
     * @param bool $copyNew
     * @param bool $active
     * @return Quote
     * @throws LocalizedException
     */
    public function prepareQuote(Quote $quote, $items = [], $copyNew = true, $active = false)
    {
        if ($copyNew) {
            $newQuote = clone $quote;
            $newQuote->setId(null);
            $newQuote->setIsActive($active ? 1 : 0);

            $cartExtension = $newQuote->getExtensionAttributes();
            if ($cartExtension === null) {
                $cartExtension = $this->cartExtensionFactory->create();

            }
            $cartExtension->setData('negotiable_quote', null);
            $cartExtension->setShippingAssignments([]);
            $newQuote->setExtensionAttributes($cartExtension);

            $newQuote->setData('items_collection');

            $this->cartRepository->save($newQuote);

            $newParentItemIds = [];
            // copy items with their options
            foreach ($items as $item) {
                // save child items later
                if ($item->getParentItem()) {
                    continue;
                }
                $oldItemId = $item->getId();
                $newItem = clone $item;
                $newItem->setId(null);
                $newItem->isDeleted(false);
                $newQuote->addItem($newItem);
                $newItem->save();
                $newParentItemIds[$oldItemId] = $newItem->getId();
            }

            // save children with new parent id
            foreach ($items as $item) {
                if (!$item->getParentItem() || !isset($newParentItemIds[$item->getParentItemId()])) {
                    continue;
                }
                $newItem = clone $item;
                $newItem->setId(null);
                $newItem->isDeleted(false);
                $newItem->setParentItemId($newParentItemIds[$item->getParentItemId()]);
                $newQuote->addItem($newItem);
                $newItem->save();
            }

            // copy billing and shipping addresses
            foreach ($newQuote->getAddressesCollection() as $address) {
                $address->setQuote($newQuote);
                $address->setId(null);
                $address->save();
            }

            // copy payment info
            foreach ($newQuote->getPaymentsCollection() as $payment) {
                $payment->setQuote($newQuote);
                $payment->setId(null);
                $payment->save();
            }
        } else {
            $newQuote = $quote;

            $newQuote->removeAllItems();
            foreach ($items as $item) {
                $item->isDeleted(false);
                $newQuote->addItem($item);
            }
        }


        $shippingAssignments = [];
        if (!$newQuote->isVirtual() && $newQuote->getItemsQty() > 0) {
            $shippingAssignments[] = $this->shippingAssignmentProcessor->create($newQuote);
        }
        $cartExtension = $newQuote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }
        $cartExtension->setShippingAssignments($shippingAssignments);
        $newQuote->setExtensionAttributes($cartExtension);

        $newQuote->setIsActive(1);

        $this->cartRepository->save($newQuote);
        $quoteIdMaskModel = $this->quoteIdMaskFactory->create();
        $quoteIdMaskModel->setQuoteId($newQuote->getId());
        $this->quoteIdMaskResource->save($quoteIdMaskModel);

        return $newQuote;
    }

    /**
     * Get the cart item separated by the sellers they belong to
     *
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @return array[]
     */
    private function getCartItemsBySeller(array $items): array
    {
        $result = [
            'default' => []
        ];
        foreach ($items as $item) {
            $sellerAttribute = $item->getProduct()
                ->getCustomAttribute(ProductAttributeInterface::SELLER_ATTRIBUTE_CODE);
            if ($sellerAttribute !== null) {
                $result[$sellerAttribute->getValue()][] = $item;
            } else {
                $result['default'][] = $item;
            }
        }
        return $result;
    }
}
