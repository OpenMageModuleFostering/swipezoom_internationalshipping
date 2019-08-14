<?php

/**
 *  Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_order_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
    * Retrieve collection class
    *
    * @return string
    */
    protected function _getCollectionClass()
    {
        return 'sales/order_grid_collection';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->join('sales_flat_order', 'main_table.entity_id = sales_flat_order.entity_id',array('swipezoom_order_number_temp'));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('real_order_id', array(
        'header'=> Mage::helper('sales')->__('Order #'),
        'width' => '80px',
        'type'  => 'text',
        'index' => 'increment_id',
		'filter_index' => 'main_table.increment_id'
        ));
        $this->addColumn('swipezoom_order_number_temp', array(
        'header'    => Mage::helper('catalog')->__('Swipezoom ID'),
        'header_css_class' => 'a-center',
        'index'     => 'swipezoom_order_number_temp',
        'width' => '80px',
		'filter_index'=>'sales_flat_order.swipezoom_order_number_temp',
        'renderer'  => 'Swipezoom_InternationalShipping_Block_Adminhtml_Sales_Grid_Swipezoomorder',
        'type' => 'text'
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
            'header'    => Mage::helper('sales')->__('Purchased from (store)'),
            'index'     => 'store_id',
            'type'      => 'store',
            'store_view'=> true,
            'display_deleted' => true,
            'filter_index' => 'main_table.store_id'
            ));
        }
        $this->addColumn('created_at', array(
        'header' => Mage::helper('sales')->__('Purchased On'),
        'index' => 'created_at',
        'type' => 'datetime',
        'width' => '100px',
        'filter_index' => 'main_table.created_at'
        ));
        $this->addColumn('billing_name', array(
        'header' => Mage::helper('sales')->__('Bill to Name'),
        'index' => 'billing_name',
		'filter_index' => 'main_table.billing_name'
        ));

        $this->addColumn('base_grand_total', array(
        'header' => Mage::helper('sales')->__('G.T. (Base)'),
        'index' => 'base_grand_total',
        'type'  => 'currency',
        'currency' => 'base_currency_code',
		'filter_index' => 'main_table.base_grand_total'
        ));
        $this->addColumn('grand_total', array(
        'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
        'index' => 'grand_total',
        'type'  => 'currency',
        'currency' => 'order_currency_code',
		'filter_index' => 'main_table.grand_total'
        ));
        $this->addColumn('status', array(
        'header' => Mage::helper('sales')->__('Status'),
        'index' => 'status',
        'type'  => 'options',
        'width' => '130px',
		'filter_index' => 'main_table.status',
        'options' => Mage::getSingleton('sales/order_config')->getStatuses()
        ));
        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/cancel')) {
            $this->getMassactionBlock()->addItem('cancel_order', array(
            'label'=> Mage::helper('sales')->__('Cancel'),
            'url'  => $this->getUrl('*/sales_order/massCancel'),
            ));
        }
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/hold')) {
            $this->getMassactionBlock()->addItem('hold_order', array(
            'label'=> Mage::helper('sales')->__('Hold'),
            'url'  => $this->getUrl('*/sales_order/massHold'),
            ));
        }
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/unhold')) {
            $this->getMassactionBlock()->addItem('unhold_order', array(
            'label'=> Mage::helper('sales')->__('Unhold'),
            'url'  => $this->getUrl('*/sales_order/massUnhold'),
            ));
        }
        $this->getMassactionBlock()->addItem('pdfinvoices_order', array(
        'label'=> Mage::helper('sales')->__('Print Invoices'),
        'url'  => $this->getUrl('/sales_order/pdfinvoices'),
        ));
        $this->getMassactionBlock()->addItem('pdfshipments_order', array(
        'label'=> Mage::helper('sales')->__('Print Packingslips'),
        'url'  => $this->getUrl('/sales_order/pdfshipments'),
        ));
        $this->getMassactionBlock()->addItem('pdfcreditmemos_order', array(
        'label'=> Mage::helper('sales')->__('Print Credit Memos'),
        'url'  => $this->getUrl('/sales_order/pdfcreditmemos'),
        ));
        $this->getMassactionBlock()->addItem('pdfdocs_order', array(
        'label'=> Mage::helper('sales')->__('Print All'),
        'url'  => $this->getUrl('/sales_order/pdfdocs'),
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}