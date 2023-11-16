<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model\Client\Request;

use Marketplacer\Base\Api\ServiceClientInterface;
use Marketplacer\Base\Model\Config;
use Marketplacer\SellerShipping\Model\Client\Query\SellerSearchFactory;

class Seller
{
    /**
     * @param ServiceClientInterface $serviceClient
     * @param Config $config
     * @param SellerSearchFactory $sellerSearchFactory
     */
    public function __construct(
        private readonly ServiceClientInterface $serviceClient,
        private readonly Config $config,
        private readonly SellerSearchFactory $sellerSearchFactory
    ) {
    }

    /**
     * @return \Generator
     */
    public function getList(): \Generator
    {
        $query = $this->sellerSearchFactory->create();

        $hasNextPage = true;
        $endCursor = null;

        while ($hasNextPage) {
            $requestResult = $this->serviceClient->request([], $this->config->getApiEndpoint(), $query->build($endCursor));

            $hasNextPage = $requestResult['data']['sellerSearch']['sellers']['pageInfo']['hasNextPage'] ?? false;
            if ($hasNextPage) {
                $endCursor = $requestResult['data']['sellerSearch']['sellers']['pageInfo']['endCursor'];
            }
            yield from $requestResult['data']['sellerSearch']['sellers']['edges'] ?? [];
        }
    }
}
