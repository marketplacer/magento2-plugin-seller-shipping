<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Marketplacer\SellerShipping\Model\Gateway;

use Magento\Payment\Gateway\Command\Result\ArrayResult;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Validator\PaymentNonceResponseValidator;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Marketplacer\SellerShipping\Model\VaultCustomerRegistry;

class BraintreeNonceCommand
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private PaymentTokenManagementInterface $tokenManagement;

    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $adapter;

    /**
     * @var ArrayResultFactory
     */
    private ArrayResultFactory $resultFactory;

    /**
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * @var PaymentNonceResponseValidator
     */
    private PaymentNonceResponseValidator $responseValidator;

    /**
     * @var VaultCustomerRegistry
     */
    private VaultCustomerRegistry $vaultMarketpplacerRegistry;

    /**
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param BraintreeAdapter $adapter
     * @param ArrayResultFactory $resultFactory
     * @param SubjectReader $subjectReader
     * @param PaymentNonceResponseValidator $responseValidator
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        BraintreeAdapter $adapter,
        ArrayResultFactory $resultFactory,
        SubjectReader $subjectReader,
        PaymentNonceResponseValidator $responseValidator,
        VaultCustomerRegistry $vaultMarketpplacerRegistry
    ) {
        $this->tokenManagement = $tokenManagement;
        $this->adapter = $adapter;
        $this->resultFactory = $resultFactory;
        $this->subjectReader = $subjectReader;
        $this->responseValidator = $responseValidator;
        $this->vaultMarketpplacerRegistry = $vaultMarketpplacerRegistry;
    }

    /**
     * @return ArrayResult|ResultInterface|null
     * @throws LocalizedException
     */
    public function execute(): ArrayResult|ResultInterface|null
    {
        $paymentToken = $this->vaultMarketpplacerRegistry->getVaultPaymentTokenGuest();

        $data = $this->adapter->createNonce($paymentToken);
        $result = $this->responseValidator->validate(['response' => ['object' => $data]]);

        if (!$result->isValid()) {
            throw new LocalizedException(__(implode("\n", $result->getFailsDescription())));
        }

        return $this->resultFactory->create([
            'array' => [
                'paymentMethodNonce' => $data->paymentMethodNonce->nonce,
                'details' => $data->paymentMethodNonce->details
            ]
        ]);
    }
}
