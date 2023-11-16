<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Marketplacer\Seller\Api\Data\SellerCollectionInterfaceFactory;
use Marketplacer\SellerApi\Api\SellerRepositoryInterface;
use Marketplacer\SellerShipping\Model\Client\Request\Seller;

class SellerUpdateManager
{
    /**
     * @param Seller $requestSeller
     * @param SellerCollectionInterfaceFactory $collectionFactory
     * @param SellerRepositoryInterface $sellerRepository
     */
    public function __construct(
        private readonly Seller $requestSeller,
        private readonly SellerCollectionInterfaceFactory $collectionFactory,
        private readonly SellerRepositoryInterface $sellerRepository
    ) {
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateShipping(): array
    {
        $sellers = $this->requestSeller->getList();

        $map = [];
        foreach ($sellers as $item) {
            $seller = $item['node'];
            $map['seller_' . $seller['id']] = $seller;
        }
        $map = array_filter($map);

        $collection = $this->collectionFactory->create();
        $collection->addSourceIdsToFilter(array_keys($map));

        $result = [];
        $stores = [Store::DEFAULT_STORE_ID, null];
        foreach ($collection as $item) {
            foreach ($stores as $storeId) {
                $seller = $this->sellerRepository->getById($item->getSellerId(), $storeId);
                $sourceSeller = $map[$seller->getSourceCode()] ?? null;

                if ($sourceSeller) {
                    $seller->setBaseDomesticShippingCost(
                        (float)$sourceSeller['baseDomesticShippingCost']
                    )->setBaseDomesticShippingFreeThreshold(
                        (float)$sourceSeller['domesticShippingFreeThreshold']
                    );

                    $this->sellerRepository->save($seller);

                    $result[$seller->getSellerId()] = $seller->getSellerId();
                }
            }
        }

        return $result;
    }
}
