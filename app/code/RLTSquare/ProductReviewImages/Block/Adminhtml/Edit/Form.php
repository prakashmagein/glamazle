<?php
/**
 * NOTICE OF LICENSE
 * You may not sell, distribute, sub-license, rent, lease or lend complete or portion of software to anyone.
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @package   RLTSquare_ProductReviewImages
 * @copyright Copyright (c) 2022 RLTSquare (https://www.rltsquare.com)
 * @contacts  support@rltsquare.com
 * @license  See the LICENSE.md file in module root directory
 */

namespace RLTSquare\ProductReviewImages\Block\Adminhtml\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Review\Helper\Data;
use Magento\Store\Model\System\Store;
use Magento\Framework\Escaper;

/**
 * Class Form
 *
 * @package RLTSquare\ProductReviewImages\Block\Adminhtml\Edit
 */
class Form extends \Magento\Review\Block\Adminhtml\Edit\Form
{
    /**
     * Review data
     *
     * @var Data
     */
    protected Data $reviewData;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Catalog product factory
     *
     * @var ProductFactory
     */
    protected ProductFactory $productFactory;

    /**
     * Catalog product factory
     *
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * Core system store model
     *
     * @var Store
     */
    protected Store $systemStore;

    /**
     *
     * @var Escaper $escaper
     */
    protected Escaper $escape;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductFactory $productFactory
     * @param Data $reviewData
     * @param ProductRepositoryInterface $productRepository
     * @param Escaper $escape
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        CustomerRepositoryInterface $customerRepository,
        ProductFactory $productFactory,
        Data $reviewData,
        ProductRepositoryInterface $productRepository,
        Escaper $escape,
        array $data = []
    ) {
        $this->reviewData = $reviewData;
        $this->customerRepository = $customerRepository;
        $this->productFactory = $productFactory;
        $this->systemStore = $systemStore;
        $this->productRepository = $productRepository;
        $this->escape = $escape;
        parent::__construct($context, $registry, $formFactory,$systemStore,$customerRepository, $productFactory,$reviewData,$data);
    }
    /**
     * override,
     * add custom fieldsets in form
     *
     * @return mixed
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        $review = $this->_coreRegistry->registry('review_data');
        $product =  $this->productRepository->getById($review->getEntityPkValue());

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl(
                        'review/*/save',
                        [
                            'id' => $this->getRequest()->getParam('id'),
                            'ret' => $this->_coreRegistry->registry('ret')
                        ]
                    ),
                    'method' => 'post'
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'review_details',
            ['legend' => __('Review Details'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'product_name',
            'note',
            [
                'label' => __('Product'),
                'text' => '<a href="' . $this->getUrl(
                        'catalog/product/edit',
                        ['id' => $product->getId()]
                    ) . '" onclick="this.target=\'blank\'">' . $this->escapeHtml(
                        $product->getName()
                    ) . '</a>'
            ]
        );

        try {
            $customer = $this->customerRepository->getById($review->getCustomerId());
            $customerText = __(
                '<a href="%1" onclick="this.target=\'blank\'">%2 %3</a> <a href="mailto:%4">(%4)</a>',
                $this->getUrl('customer/index/edit', ['id' => $customer->getId(), 'active_tab' => 'review']),
                $this->escape->escapeHtml($customer->getFirstname()),
                $this->escape->escapeHtml($customer->getLastname()),
                $this->escape->escapeHtml($customer->getEmail())
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customerText = ($review->getStoreId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID)
                ? __('Administrator') : __('Guest');
        }

        $fieldset->addField('customer', 'note', ['label' => __('Author'), 'text' => $customerText]);

        $fieldset->addField(
            'summary-rating',
            'note',
            [
                'label' => __('Summary Rating'),
                'text' => $this->getLayout()->createBlock(
                    \Magento\Review\Block\Adminhtml\Rating\Summary::class
                )->toHtml()
            ]
        );

        $fieldset->addField(
            'detailed-rating',
            'note',
            [
                'label' => __('Detailed Rating'),
                'required' => true,
                'text' => '<div id="rating_detail">' . $this->getLayout()->createBlock(
                        \Magento\Review\Block\Adminhtml\Rating\Detailed::class
                    )->toHtml() . '</div>'
            ]
        );

        $fieldset->addField(
            'status_id',
            'select',
            [
                'label' => __('Status'),
                'required' => true,
                'name' => 'status_id',
                'values' => $this->_reviewData->getReviewStatusesOptionArray()
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->hasSingleStore()) {
            $field = $fieldset->addField(
                'select_stores',
                'multiselect',
                [
                    'label' => __('Visibility'),
                    'required' => true,
                    'name' => 'stores[]',
                    'values' => $this->systemStore->getStoreValuesForForm()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
            $review->setSelectStores($review->getStores());
        } else {
            $fieldset->addField(
                'select_stores',
                'hidden',
                ['name' => 'stores[]', 'value' => $this->_storeManager->getStore(true)->getId()]
            );
            $review->setSelectStores($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField(
            'nickname',
            'text',
            ['label' => __('Nickname'), 'required' => true, 'name' => 'nickname']
        );

        $fieldset->addField(
            'title',
            'text',
            ['label' => __('Summary of Review'), 'required' => true, 'name' => 'title']
        );

        $fieldset->addField(
            'detail',
            'textarea',
            ['label' => __('Review'), 'required' => true, 'name' => 'detail', 'style' => 'height:24em;']
        );

        $fieldset->addField(
            'review-media',
            'note',
            [
                'label' => __('Review Media'),
                'text' => $this->getLayout()->createBlock(
                    \RLTSquare\ProductReviewImages\Block\Adminhtml\Edit\Media::class
                )->toHtml()
            ]
        );

        $fieldset->addField(
            'deleted_media',
            'text',
            [
                'name' => 'deleted_media',
                'style' => 'visibility:hidden;'
            ]
        );

        $form->setUseContainer(true);
        $form->setValues($review->getData());
        $this->setForm($form);
        return \Magento\Backend\Block\Widget\Form::_prepareForm();
    }
}
