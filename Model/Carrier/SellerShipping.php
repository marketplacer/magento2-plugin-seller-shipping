<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Marketplacer\Seller\Helper\Config as SellerConfig;
use Marketplacer\SellerApi\Api\Data\MarketplacerSellerInterface;
use Marketplacer\SellerApi\Api\SellerRepositoryInterface;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Marketplacer\SellerApi\Model\Order\SellerDataPreparer;
use Marketplacer\SellerShipping\Model\Config;
use Psr\Log\LoggerInterface;

class SellerShipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'marketplacer';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param SellerRepositoryInterface $sellerRepository
     * @param SellerDataPreparer $sellerDataPreparer
     * @param SellerConfig $sellerConfig
     * @param Config $sellerShippingConfig
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        private readonly ResultFactory $rateResultFactory,
        private readonly MethodFactory $rateMethodFactory,
        private readonly SellerRepositoryInterface $sellerRepository,
        private readonly SellerDataPreparer $sellerDataPreparer,
        private readonly SellerConfig $sellerConfig,
        private readonly Config $sellerShippingConfig,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

     /**
      * Collect and get rates
      *
      * @param RateRequest $request
      * @return DataObject|bool|null
      * @throws LocalizedException
      */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->rateResultFactory->create();

        if (count($this->sellerDataPreparer->getSellerIdsByQuoteItems($request->getAllItems())) > 1) {
            $this->createStubMethods($result);
        } else {
            foreach ($this->getSellerIdsWithoutGeneral($request->getAllItems()) as $id) {
                $seller = $this->sellerRepository->getById($id);

                $shippingPrice = $this->getShippingPrice($request, $seller);

                $method = $this->rateMethodFactory->create();

                $method->setCarrier($this->_code);

                if ($shippingPrice == 0) {
                    $method->setCarrierTitle(__('Free Shipping'));

                    $method->setMethod($this->_code . '_freeshipping');
                    $method->setMethodTitle(__('Free'));

                    $method->setPrice('0.00');
                    $method->setCost('0.00');
                } else {
                    $method->setCarrierTitle($this->getConfigData('title'));

                    $method->setMethod($this->_code . '_standard_flat_fee');
                    $method->setMethodTitle($this->getConfigData('name'));

                    $method->setPrice($shippingPrice);
                    $method->setCost($shippingPrice);
                }

                $result->append($method);
            }
        }

        return $result;
    }

    /**
     *
     * @param Result $result
     * @return void
     */
    private function createStubMethods(Result $result): void
    {
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);

        $method->setCarrierTitle(__('Free Shipping'));

        $method->setMethod($this->_code . '_freeshipping');
        $method->setMethodTitle(__('Free'));

        $method->setPrice('0.00');
        $method->setCost('0.00');

        $result->append($method);


        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);

        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code . '_standard_flat_fee');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice('0.00');
        $method->setCost('0.00');

        $result->append($method);
    }

    /**
     * Get count of free boxes
     *
     * @param RateRequest $request
     * @param int $id
     * @param bool $resultFreeBoxes
     * @return int
     * @throws LocalizedException
     */
    private function getBoxesCount(RateRequest $request, int $id, bool $resultFreeBoxes = false): int
    {
        $result = 0;
        if ($this->getSellerItems($request->getAllItems(), $id)) {
            foreach ($this->getSellerItems($request->getAllItems(), $id) as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $result += $this->getBoxesCountFromChildren($item, $resultFreeBoxes);
                } elseif ($resultFreeBoxes) {
                    if ($item->getFreeShipping()) {
                        $result += $item->getQty();
                    }
                } else {
                    $result += $item->getQty();
                }
            }
        }
        return (int)$result;
    }

    /**
     * Returns free boxes count of children
     *
     * @param CartItemInterface $item
     * @param bool $resultFreeBoxes
     * @return int
     */
    private function getBoxesCountFromChildren($item, bool $resultFreeBoxes = false): int
    {
        $result = 0;
        foreach ($item->getChildren() as $child) {
            if ($resultFreeBoxes) {
                if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                    $result += $item->getQty() * $child->getQty();
                }
            } else {
                $result += $item->getQty() * $child->getQty();
            }
        }
        return $result;
    }

    /**
     * @param array $items
     * @return array
     * @throws LocalizedException
     */
    private function getSellerIdsWithoutGeneral(array $items): array
    {
        return array_filter(
            $this->sellerDataPreparer->getSellerIdsByQuoteItems($items),
            function ($id) {
                return $id !== $this->sellerConfig->getGeneralSellerId();
            }
        );
    }

    /**
     * @param MarketplacerSellerInterface $seller
     * @param $cost
     * @return bool
     */
    private function isFree(MarketplacerSellerInterface $seller, $cost): bool
    {
        $isFree = $seller->getBaseDomesticShippingFreeThreshold() > 0
            && $cost > $seller->getBaseDomesticShippingFreeThreshold();

        return $isFree || $cost == 0;
    }

    /**
     * @param CartItemInterface[] $items
     *
     * @return float
     */
    private function getSellerItemsCost(array $items): float
    {
        $result = 0.00;
        foreach ($items as $item) {
            if (!$item->getParentItem() || !$item->getId()) {
                $result += (float)$item->getPrice() * $item->getQty();
            }
        }
        return $result;
    }

    /**
     * @param $items
     * @param $id
     * @return array
     * @throws LocalizedException
     */
    private function getSellerItems($items, $id): array
    {
        return array_filter($items, function ($item) use ($id) {
            return $this->sellerDataPreparer->getSellerIdByQuoteItem($item) == $id;
        });
    }

    /**
     * Returns shipping price
     *
     * @param RateRequest $request
     * @param MarketplacerSellerInterface $seller
     * @return float
     * @throws LocalizedException
     */
    private function getShippingPrice(RateRequest $request, MarketplacerSellerInterface $seller): float
    {
        $shippingPrice = (float)$seller->getBaseDomesticShippingCost();
        $sellerCost = $this->getSellerItemsCost($this->getSellerItems($request->getAllItems(), $seller->getSellerId()));
        if ($this->sellerShippingConfig->isUseCartPriceRules()) {
            $freeBoxes = $this->getBoxesCount($request, (int)$seller->getSellerId(), true);
            $sellerBoxes = $this->getBoxesCount($request, (int)$seller->getSellerId());

            if ($sellerBoxes == $freeBoxes) {
                $shippingPrice = 0.00;
            }

            if ($shippingPrice > 0.00 && $this->isFree($seller, $sellerCost)) {
                $shippingPrice = 0.00;
            }

        } elseif ($this->isFree($seller, $sellerCost)) {
            $shippingPrice = 0.00;
        }

        return $shippingPrice;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [
            $this->_code . '_standard_flat_fee' => $this->getConfigData('name'),
            $this->_code . '_freeshipping' => __('Free')
        ];
    }
}
