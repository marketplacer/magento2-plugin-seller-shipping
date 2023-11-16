<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Controller\Adminhtml\Seller;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Marketplacer\SellerShipping\Model\SellerUpdateManager;

class UpdateSellerShipping extends Action
{
    public const  ADMIN_RESOURCE = 'Marketplacer_Seller::seller';

    /**
     * @param SellerUpdateManager $sellerUpdateManager
     * @param Context $context
     */
    public function __construct(
        private readonly SellerUpdateManager $sellerUpdateManager,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        try {
            $result = $this->sellerUpdateManager->updateShipping();

            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been updated.', count($result))
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
