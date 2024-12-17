<?php

namespace Marketplacer\SellerShipping\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayPal\Braintree\Model\CustomFields\Pool;

class VaultTokenExtensionBuilder implements BuilderInterface
{
    const CUSTOM_FIELDS = 'storeInVaultOnSuccess';

    /**
     * @var Pool $pool
     */
    protected $pool;

    /**
     * CustomFieldsDataBuilder constructor
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        return ['options' => [self::CUSTOM_FIELDS => true]];
    }
}
