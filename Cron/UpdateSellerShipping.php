<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Cron;

use Marketplacer\SellerShipping\Model\SellerUpdateManager;
use Psr\Log\LoggerInterface;

class UpdateSellerShipping
{
    /**
     * @param SellerUpdateManager $sellerUpdateManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SellerUpdateManager $sellerUpdateManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Main cron job entry point
     *
     */
    public function execute()
    {
        try {
            $result = $this->sellerUpdateManager->updateShipping();

            $this->logger->info(__('A total of %1 record(s) have been updated.', count($result)));

        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $this;
    }
}
