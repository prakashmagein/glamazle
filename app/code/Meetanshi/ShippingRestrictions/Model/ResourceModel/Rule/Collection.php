<?php

namespace Meetanshi\ShippingRestrictions\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\State;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Collection extends AbstractCollection
{
    protected $dateTime;

    protected $timeZone;

    protected $storeManager;

    private $state;

    private $customerRepositoryInterface;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        DateTime $dateTime,
        TimezoneInterface $timeZone,
        StoreManagerInterface $storeManager,
        State $state,
        CustomerRepositoryInterface $customerRepositoryInterface
    )
    {
        $this->dateTime = $dateTime;
        $this->timeZone = $timeZone;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, null, null);
    }

    public function customerGroupFilter($groupId)
    {
        $groupId = (int)$groupId;
        $this->addFieldToFilter(['customer_groups', 'customer_groups'], [
            [''],
            ['finset' => $groupId]
        ]);

        return $this;
    }

    public function isActive()
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function storeFilter($storeId)
    {
        $storeId = (int)$storeId;
        $this->addFieldToFilter(['stores', 'stores'], [
            [''],
            ['finset' => $storeId]
        ]);

        return $this;
    }

    public function daysFilter()
    {
        $this->addFieldToFilter(['days', 'days', 'days'], [
            ['null' => true],
            [''],
            ['finset' => $this->dateTime->date('N')]
        ]);

        $timeZoneDate = $this->timeZone->date();
        $time = $timeZoneDate->format('H') * 100 + $timeZoneDate->format('i') + 1;

        $this->getSelect()->where('(from_time IS NULL) OR (to_time IS NULL)
        OR from_time="" OR from_time="0" OR to_time="" OR to_time="0" OR
        (from_time < ' . $time . ' AND to_time > ' . $time . ') OR
        (from_time < ' . $time . ' AND to_time < from_time) OR
        (to_time > ' . $time . ' AND to_time < from_time)');

        return $this;
    }

    protected function _construct()
    {
        $this->_init('Meetanshi\ShippingRestrictions\Model\Rule', 'Meetanshi\ShippingRestrictions\Model\ResourceModel\Rule');
    }
}
