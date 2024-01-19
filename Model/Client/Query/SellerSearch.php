<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model\Client\Query;

use GraphQL\QueryBuilder\QueryBuilder as GraphQLQueryBuilder;
use Magento\Framework\Serialize\Serializer\Json;

class SellerSearch
{
    public function __construct(
        private readonly Json $serializer
    ) {
    }

    /**
     * @param $after
     * @return string
     */
    public function build($after = null): string
    {
        $builder = new GraphQLQueryBuilder('sellerSearch');
        $variables = [];
        $builder->selectField(
            (new GraphQLQueryBuilder('sellers'))
                ->setArgument('after', $after)
                ->selectField(
                    (new GraphQLQueryBuilder('edges'))
                    ->selectField((new GraphQLQueryBuilder('node'))
                            ->selectField('businessName')
                            ->selectField('id')
                            ->selectField('online')
                            ->selectField('legacyId')
                            ->selectField('baseDomesticShippingCost')
                            ->selectField('domesticShippingFreeThreshold'))
                )->selectField(
                    (new GraphQLQueryBuilder('pageInfo'))
                        ->selectField('hasNextPage')
                        ->selectField('endCursor')
                )
        );
        $data = ['query' => (string)$builder->getQuery(), 'variables' => $variables];

        return $this->serializer->serialize($data);
    }
}
