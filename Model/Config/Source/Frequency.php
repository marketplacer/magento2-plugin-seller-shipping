<?php
declare(strict_types=1);

namespace Marketplacer\SellerShipping\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Frequency implements OptionSourceInterface
{
    /**
     * @var ?array
     */
    protected static $_options;

    public const CRON_HOURLY = 'H';
    public const CRON_DAILY = 'D';
    public const CRON_WEEKLY = 'W';
    public const CRON_MONTHLY = 'M';

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        if (!self::$_options) {
            self::$_options = [
                ['label' => __('Hourly'), 'value' => self::CRON_HOURLY],
                ['label' => __('Daily'), 'value' => self::CRON_DAILY],
                ['label' => __('Weekly'), 'value' => self::CRON_WEEKLY],
                ['label' => __('Monthly'), 'value' => self::CRON_MONTHLY],
            ];
        }
        return self::$_options;
    }
}
