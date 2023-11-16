<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model\Config\Backend\SellerShipping;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Marketplacer\SellerShipping\Model\Config\Source\Frequency;

class Update extends Value
{
    public const CRON_STRING_PATH = 'crontab/default/jobs/mp_ss_update_seller_shipping/schedule/cron_expr';


    public const CRON_MODEL_PATH = 'crontab/default/jobs/mp_ss_update_seller_shipping/run/model';

    /**
     * @var ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var string
     */
    protected $_runModelPath = '';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Processing object after save data
     *
     * @return $this
     * @throws LocalizedException
     */
    public function afterSave()
    {
        $time = $this->getData('groups/update_seller_shipping_cron/fields/time/value') ?:
            explode(
                ',',
                $this->_config->getValue(
                    'marketplacer_seller/update_seller_shipping_cron/time',
                    $this->getScope(),
                    $this->getScopeId()
                ) ?: '0,0,0'
            );
        $frequency = $this->getValue();

        $minute = (int)($time[1] ?? 0);
        $hour = (int)($time[0] ?? 0);
        $cronExprArray = [
            $frequency === Frequency::CRON_HOURLY ? '0' : $minute, //Minute
            $frequency === Frequency::CRON_HOURLY ? '*' : $hour, //Hour
            $frequency === Frequency::CRON_MONTHLY ? '1' : '*', //Day of the Month
            '*', //Month of the Year
            $frequency === Frequency::CRON_WEEKLY ? '1' : '*', //Day of the Week
        ];


        $cronExprString = implode(' ', $cronExprArray);

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__('We can\'t save the cron expression.'));
        }

        return parent::afterSave();
    }
}
