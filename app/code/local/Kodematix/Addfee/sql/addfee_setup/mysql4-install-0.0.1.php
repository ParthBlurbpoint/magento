<?php 
$installer = $this;
$installer->startSetup();
$installer->run(" 
ALTER TABLE `{$installer->getTable('sales/order')}` ADD `order_fee` VARCHAR(255) NOT NULL;
ALTER TABLE `{$installer->getTable('sales/order_grid')}` ADD `order_fee` VARCHAR(255) NOT NULL;
ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `order_fee` VARCHAR(255) NOT NULL; 
");
$installer->run(" 
ALTER TABLE `{$installer->getTable('sales/order')}` ADD `order_ntotal` VARCHAR(255) NOT NULL;
ALTER TABLE `{$installer->getTable('sales/order_grid')}` ADD `order_ntotal` VARCHAR(255) NOT NULL;
ALTER TABLE `{$installer->getTable('sales/quote')}` ADD `order_ntotal` VARCHAR(255) NOT NULL; 
");
$installer->endSetup();
?>