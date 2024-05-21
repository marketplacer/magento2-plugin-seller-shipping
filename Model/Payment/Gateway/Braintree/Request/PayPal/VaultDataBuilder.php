<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model\Payment\Gateway\Braintree\Request\PayPal;

use Magento\Payment\Gateway\Request\BuilderInterface;

class VaultDataBuilder implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(array $buildSubject): array
    {
        return ['options' => ['storeInVaultOnSuccess' => true]];
    }
}
