<?php
$installer = $this;

$installer->startSetup();
$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'swipezoom_order_number', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_number', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_number_temp', 'text');

$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_shipping_charges', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_duties_taxes', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_insurance_charges', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_duties_tax_prepaid', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_insurance_paid', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_confirmed', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_confirmed_errormessage', 'text');

$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'swipezoom_address_billing_string', 'text');
$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'swipezoom_address_shipping_string', 'text');

$setup = new Mage_Eav_Model_Entity_Setup('Customer_setup');
$city = Mage::getModel('catalog/resource_eav_attribute')->load($setup->getAttributeId('customer_address','city'))->setData('is_required',0)->save();
$postcode = Mage::getModel('catalog/resource_eav_attribute')->load($setup->getAttributeId('customer_address','postcode'))->setData('is_required',0)->save();
$street = Mage::getModel('catalog/resource_eav_attribute')->load($setup->getAttributeId('customer_address','street'))->setData('is_required',0)->save();

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `packingdetail` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `swipezoomorderid` int(255) NOT NULL,
  `boxno` varchar(255) NOT NULL,
  `boxcode` varchar(255) NOT NULL,
  `productcode` varchar(255) NOT NULL,
  `productqty` int(255) NOT NULL,
  `productname` varchar(255) NOT NULL,
  `magentoorderid` VARCHAR( 255 ) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
SQLTEXT;

$installer->run($sql);

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `shipmentdetail` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `swipezoomorderid` int(255) NOT NULL,
  `couriername` varchar(255) NOT NULL,
  `courierservicename` varchar(255) NOT NULL,
  `courierwaybillno` varchar(255) NOT NULL,
  `pickupdue` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
SQLTEXT;

$installer->run($sql);

$this->_conn->addColumn($this->getTable('sales_flat_order'), 'swipezoom_order_creditmemo', 'text');

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `swipezoom_creditmemo` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `swipezoomorderid` int(255) NOT NULL,
  `realorderno` varchar(255) NOT NULL,
  `refswipezoomorderid` int(255) NOT NULL,
  `reforderno` varchar(255) NOT NULL,
  `creditmemoitems` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
SQLTEXT;

$installer->run($sql);

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `swipezoom_partshipreq` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `realorderid` int(255) NOT NULL,
  `swipezoomorderid` int(255) NOT NULL,
  `newszorderid` int(255) NOT NULL,
  `productprice` varchar(255) NOT NULL,
  `pricecurrency` varchar(255) NOT NULL,
  `salecurrency` varchar(255) NOT NULL,
  `excrate` varchar(255) NOT NULL,
  `ordertotal` double NOT NULL,
  `couriercharges` double NOT NULL,
  `insurancecharges` double NOT NULL,
  `szmarkup` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
SQLTEXT;

$installer->run($sql);

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `swipezoom_partshipreqitems` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `swipezoomorderid` int(255) NOT NULL,
  `lineitemno` int(255) NOT NULL,
  `productcode` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` float NOT NULL,
  `qty` varchar(255) NOT NULL,
  `salevalue` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
SQLTEXT;

$installer->run($sql);

$sql=<<<SQLTEXT
ALTER TABLE `swipezoom_partshipreq` ADD `courierduties` DOUBLE NOT NULL AFTER `couriercharges`;
SQLTEXT;

$installer->run($sql);

$this->_conn->addColumn($this->getTable('sales_flat_quote_address'), 'vat_amount', 'DECIMAL( 10, 2 )');
$this->_conn->addColumn($this->getTable('sales_flat_quote_address'), 'base_vat_amount', 'DECIMAL( 10, 2 )');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'vat_amount', 'DECIMAL( 10, 2 )');
$this->_conn->addColumn($this->getTable('sales_flat_order'), 'base_vat_amount', 'DECIMAL( 10, 2 )');

$installer->endSetup(); 