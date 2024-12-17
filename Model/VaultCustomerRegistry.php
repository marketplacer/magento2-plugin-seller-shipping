<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model;

class VaultCustomerRegistry
{
    public $vaultPaymentTokenGuest = null;

    /**
     * Retrieve Customer Vault Braintree Token
     *
     * @return string
     */
    public function getVaultPaymentTokenGuest(): string
    {
        return  $this->vaultPaymentTokenGuest;
    }

    /**
     * Set Customer Vault Braintree Token
     *
     * @param string $token
     * @return void
     */
    public function setVaultPaymentTokenGuest(string $token)
    {
        $this->vaultPaymentTokenGuest = $token;
    }
}
