<?php

namespace Marketplacer\SellerShipping\Gateway\Response;

use Braintree\Transaction;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Response\Handler;
use Marketplacer\SellerShipping\Model\VaultCustomerRegistry;
use Magento\Vault\Model\CreditCardTokenFactory;

class VaultMarketplacerRegistry extends Handler implements HandlerInterface
{
    /**
     * @var VaultCustomerRegistry
     */
    private $vaultCustomerRegistry;

    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Config $config,
        SubjectReader $subjectReader,
        DateTimeFactory $dateTime,
        ?Json $serializer = null
    ) {
        parent::__construct($paymentTokenFactory, $paymentExtensionFactory, $config, $subjectReader, $dateTime,
            $serializer);
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();

        // add vault payment token entity to extension attributes
        $paymentToken = $this->getVaultPaymentToken($transaction);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
            $this->vaultCustomerRegistry->setVaultPaymentTokenGuest($paymentToken->getGatewayToken());
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param Transaction $transaction
     * @return PaymentTokenInterface|null
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function getVaultPaymentToken(Transaction $transaction): ?PaymentTokenInterface
    {
        // Check token existing in gateway response
        $token = $transaction->creditCardDetails->token;
        if (empty($token) || empty($transaction->creditCardDetails->expirationYear)) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($transaction));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->getCreditCardType($transaction->creditCardDetails->cardType),
            'maskedCC' => $transaction->creditCardDetails->last4,
            'expirationDate' => $transaction->creditCardDetails->expirationDate
        ]));

        return $paymentToken;
    }

    /**
     * Get expiration date
     *
     * @param Transaction $transaction
     * @return string
     * @throws Exception
     */
    private function getExpirationDate(Transaction $transaction): string
    {
        $expDate = new DateTime(
            $transaction->creditCardDetails->expirationYear
            . '-'
            . $transaction->creditCardDetails->expirationMonth
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new DateTimeZone('UTC')
        );
        $expDate->add(new DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Get type of credit card mapped from Braintree
     *
     * @param string $type
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getCreditCardType(string $type): string
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$replaced];
    }
}
