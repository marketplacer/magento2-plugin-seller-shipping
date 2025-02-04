<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Customer\Model\Data\Address as CustomerAddress;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Marketplacer\Seller\Helper\Config;
use Marketplacer\SellerApi\Model\Order\SellerDataPreparer;
use Marketplacer\SellerShipping\Api\ShipmentEstimationInterface;

class ShipmentEstimation implements ShipmentEstimationInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param TotalsCollector $totalsCollector
     * @param Session $customerSession
     * @param ShippingMethodConverter $converter
     * @param Config $sellerConfig
     * @param DataObjectProcessor $dataObjectProcessor
     * @param SellerDataPreparer $sellerDataPreparer
     * @param ShippingMethodExtensionFactory $extensionFactory
     * @param SellerTotalRepository $sellerTotalRepository
     */
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly TotalsCollector $totalsCollector,
        private readonly Session $customerSession,
        private readonly ShippingMethodConverter $converter,
        private readonly Config $sellerConfig,
        private readonly DataObjectProcessor $dataObjectProcessor,
        private readonly SellerDataPreparer $sellerDataPreparer,
        private readonly ShippingMethodExtensionFactory $extensionFactory,
        private readonly SellerTotalRepository $sellerTotalRepository
    ) {
    }

    /**
     * Estimate shipping
     *
     * @param int $cartId The shopping cart ID.
     * @param int $addressId The estimate address id
     * @param int $sellerId The estimate address id
     * @return ShippingMethodInterface[] An array of shipping methods.
     * @throws InputException
     * @throws LocalizedException
     */
    public function estimate($cartId, $addressId, $sellerId): array
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        $address = $this->getAddress($addressId, $quote);


        $this->prepareForSeller($sellerId ?: (int)$this->sellerConfig->getGeneralSellerId(), $quote);

        $isGeneralSeller = (int)$sellerId === (int)$this->sellerConfig->getGeneralSellerId();


        $resultMethods = [];

        foreach ($this->getShippingMethods(
            $quote,
            $address,
            $sellerId
        ) as $method) {
            if ($isGeneralSeller ? $method->getCarrierCode() !== 'marketplacer'
                : $method->getCarrierCode() === 'marketplacer') {

                $resultMethods[] = $method;
            }
        }

        return $resultMethods;
    }

    /**
     * @param int $cartId
     * @param AddressInterface $address
     * @param int $sellerId
     * @return ShippingMethodInterface[] An array of shipping methods.
     * @throws NoSuchEntityException
     */
    public function estimateByExtendedAddress($cartId, AddressInterface $address, $sellerId): array
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        $this->prepareForSeller($sellerId ?: (int)$this->sellerConfig->getGeneralSellerId(), $quote);

        $isGeneralSeller = (int)$sellerId === (int)$this->sellerConfig->getGeneralSellerId();


        $resultMethods = [];

        foreach ($this->getShippingMethods(
            $quote,
            $address,
            $sellerId
        ) as $method) {
            if ($isGeneralSeller ? $method->getCarrierCode() !== 'marketplacer'
                : $method->getCarrierCode() === 'marketplacer' || !$sellerId) {

                $resultMethods[] = $method;
            }
        }

        return $resultMethods;
    }

    /**
     * Get list of available shipping methods
     *
     * @param Quote $quote
     * @param ExtensibleDataInterface $address
     * @param $sellerId
     * @return ShippingMethodInterface[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getShippingMethods(Quote $quote, ExtensibleDataInterface $address, $sellerId): array
    {
        $output = [];
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        $quoteCustomerGroupId = $quote->getCustomerGroupId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $isCustomerGroupChanged = $quoteCustomerGroupId !== $customerGroupId;
        if ($isCustomerGroupChanged) {
            $quote->setCustomerGroupId($customerGroupId);
        }
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $method = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());

                $extensionAttributes =  $method->getExtensionAttributes();
                if ($extensionAttributes === null) {
                    $extensionAttributes = $this->extensionFactory->create();
                }

                $extensionAttributes->setSellerId($sellerId);

                $shippingAddress->setShippingMethod($method->getCarrierCode() . '_' . $method->getMethodCode());
                $shippingAddress->setCollectShippingRates(true);

                $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
                $totals = $this->sellerTotalRepository->get($quote);
                $taxAmount = $totals->getShippingTaxAmount();
                $extensionAttributes->setTaxAmount($taxAmount);

                $method->setExtensionAttributes($extensionAttributes);
                $output[] = $method;
            }
        }
        if ($isCustomerGroupChanged) {
            $quote->setCustomerGroupId($quoteCustomerGroupId);
        }
        return $output;
    }


    /**
     * @param int $sellerId
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    private function prepareForSeller(int $sellerId, Quote $quote): void
    {
        $items = array_filter($quote->getShippingAddress()->getAllItems(), function ($item) use ($sellerId) {
            return (int)$this->sellerDataPreparer->getSellerIdByQuoteItem($item) === $sellerId;
        });
        $quote->getShippingAddress()->setData('cached_items_all', $items);

        $items = array_filter($quote->getAllItems(), function ($item) use ($sellerId) {
            return (int)$this->sellerDataPreparer->getSellerIdByQuoteItem($item) === $sellerId;
        });

        $quote->setItems($items);
    }

    /**
     * Get transform address interface into Array
     *
     * @param ExtensibleDataInterface $address
     * @return array
     */
    private function extractAddressData($address): array
    {
        $className = \Magento\Customer\Api\Data\AddressInterface::class;
        if ($address instanceof AddressInterface) {
            $className = AddressInterface::class;
        } elseif ($address instanceof EstimateAddressInterface) {
            $className = EstimateAddressInterface::class;
        }

        $addressData = $this->dataObjectProcessor->buildOutputDataArray(
            $address,
            $className
        );
        unset($addressData[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);

        return $addressData;
    }

    /**
     * Gets the address if exists for customer
     *
     * @param int $addressId
     * @param Quote $quote
     * @return CustomerAddress
     * @throws InputException The shipping address is incorrect.
     */
    private function getAddress(int $addressId, Quote $quote): CustomerAddress
    {
        $addresses = $quote->getCustomer()->getAddresses();
        foreach ($addresses as $address) {
            if ($addressId === (int)$address->getId()) {
                return $address;
            }
        }

        throw new InputException(__('The shipping address is missing. Set the address and try again.'));
    }
}
