<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\TotalsInterface as QuoteTotalsInterface;
use Magento\Quote\Api\Data\TotalsInterfaceFactory;
use Magento\Quote\Model\Cart\Totals\ItemConverter;
use Magento\Quote\Model\Cart\TotalsConverter;
use Magento\Quote\Model\Quote;

class SellerTotalRepository
{
    /**
     * @param TotalsInterfaceFactory $totalsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param CouponManagementInterface $couponService
     * @param TotalsConverter $totalsConverter
     * @param ItemConverter $itemConverter
     */
    public function __construct(
        private readonly TotalsInterfaceFactory $totalsFactory,
        private readonly DataObjectHelper $dataObjectHelper,
        private readonly CouponManagementInterface $couponService,
        private readonly TotalsConverter $totalsConverter,
        private readonly ItemConverter $itemConverter
    ) {
    }

    /**
     * @param Quote $quote
     * @return QuoteTotalsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(Quote $quote): QuoteTotalsInterface
    {
        if ($quote->isVirtual()) {
            $quote->collectTotals();
            $addressTotalsData = $quote->getBillingAddress()->getData();
            $addressTotals = $quote->getBillingAddress()->getTotals();
        } else {
            $addressTotalsData = $quote->getShippingAddress()->getData();
            $addressTotals = $quote->getShippingAddress()->getTotals();
        }
        unset($addressTotalsData[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);

        $quoteTotals = $this->totalsFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $quoteTotals,
            $addressTotalsData,
            QuoteTotalsInterface::class
        );
        $items = array_map([$this->itemConverter, 'modelToDataObject'], $quote->getAllVisibleItems());
        $calculatedTotals = $this->totalsConverter->process($addressTotals);

        $quoteTotals->setTotalSegments($calculatedTotals);
        $quoteTotals->setCouponCode($this->couponService->get($quote->getId()));
        $quoteTotals->setItems($items);
        $quoteTotals->setItemsQty($quote->getItemsQty());
        $quoteTotals->setBaseCurrencyCode($quote->getBaseCurrencyCode());
        $quoteTotals->setQuoteCurrencyCode($quote->getQuoteCurrencyCode());

        return $quoteTotals;
    }
}
