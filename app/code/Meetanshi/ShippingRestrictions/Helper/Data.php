<?php

namespace Meetanshi\ShippingRestrictions\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Meetanshi\ShippingRestrictions\Model\Rule;
use Magento\Shipping\Model\Config;

class Data extends AbstractHelper
{
    protected $_counter;
    protected $_firstTime = true;
    protected $objectManager;
    protected $coreRegistry;
    protected $shippingConfig;
    protected $customerCollection;

    public function __construct(Context $context, ObjectManagerInterface $objectManager, Registry $registry, CollectionFactory $customerCollection, Config $shippingConfig)
    {
        $this->objectManager = $objectManager;
        $this->coreRegistry = $registry;
        $this->customerCollection = $customerCollection;
        $this->shippingConfig = $shippingConfig;
        parent::__construct($context);
    }

    public function getAllGroups()
    {
        $customerGroups = $this->customerCollection->create()->load()->toOptionArray();

        $found = false;
        foreach ($customerGroups as $group) {
            if ($group['value'] == 0) {
                $found = true;
            }
        }
        if (!$found) {
            array_unshift($customerGroups, ['value' => 0, 'label' => __('NOT LOGGED IN')]);
        }

        return $customerGroups;
    }

    public function getAllCarriers()
    {
        $carriers = [];
        foreach ($this->scopeConfig->getValue('carriers') as $code => $config) {
            if (!empty($config['title'])) {
                $carriers[] = ['value'=>$code, 'label'=>$config['title'] . ' [' . $code . ']'];
            }
        }
        return $carriers;
    }

    public function getAllMethods()
    {
        $activeCarriers = $this->shippingConfig->getActiveCarriers();
        foreach ($activeCarriers as $carrierCode => $carrierModel) {
            $options = array();
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    $code = $carrierCode . '_' . $methodCode;
                    $options[] = array('value' => $code, 'label' => $method);
                }
                $carrierTitle = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/title');

            }
            $methods[] = array('value' => $options, 'label' => $carrierTitle);
        }
        return $methods;
    }

    public function getStatuses()
    {
        return [
            '1' => __('Active'),
            '0' => __('Inactive'),
        ];
    }

    public function getDays()
    {
        return [
            [
                'value' => '7',
                'label' => __('Sunday')
            ],
            [
                'value' => '1',
                'label' => __('Monday')
            ],
            [
                'value' => '2',
                'label' => __('Tuesday')
            ],
            [
                'value' => '3',
                'label' => __('Wednesday')
            ],
            [
                'value' => '4',
                'label' => __('Thursday')
            ],
            [
                'value' => '5',
                'label' => __('Friday')
            ],
            [
                'value' => '6',
                'label' => __('Saturday')
            ],
        ];
    }

    public function getTime()
    {
        $data = [
            ['value' => 0, 'label' => __('Please select...')]
        ];

        for ($i = 0; $i < 24; $i++) {
            for ($j = 0; $j < 60; $j = $j + 15) {
                $time = $i . ':' . $j;
                $format = date('H:i', strtotime($time));
                $data[] = ['value' => $i * 100 + $j + 1, 'label' => $format];
            }
        }
        return $data;
    }

    public function getAdminStatus()
    {
        return ['1' => __('Yes'),
            '0' => __('No')];
    }
}
