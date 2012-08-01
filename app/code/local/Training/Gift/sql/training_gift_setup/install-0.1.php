<?php

die(__LINE__);
/** @var $installer Mage_Sales_Model_Resource_Setup */
$installer = Mage::getResourceModel('sales/setup', 'sales_setup');

$installer->startSetup();

$installer->addAttribute('quote_item', 'is_gift', array(
    'type'     => 'int',
    'required' => 0,
    'label'    => 'Is Gift Flag'
));

$installer->addAttribute('quote_item', 'gift_related_to_item', array(
    'type'     => 'int',
    'required' => 0,
    'label'    => 'Target Item Id'
));

$installer->endSetup();
