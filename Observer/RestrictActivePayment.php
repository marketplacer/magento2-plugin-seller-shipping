<?php

namespace Marketplacer\SellerShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Marketplacer\SellerShipping\Model\Config;

class RestrictActivePayment implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Restrict active payment method
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isAvailablePayment($observer->getEvent()->getMethodInstance()->getCode())) {
            $checkResult = $observer->getEvent()->getResult();
            $checkResult->setData('is_available', false);
        }
    }
}
