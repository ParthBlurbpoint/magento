<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('etailthis_productimport')};

    CREATE TABLE {$this->getTable('etailthis_productimport')} (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `Import_Start` timestamp NULL DEFAULT NULL,
	  `Import_Finish` timestamp NULL DEFAULT NULL,
	  `Import_Type` varchar(100) DEFAULT NULL,
	  `Status_Number_of_products` int(1) DEFAULT 0,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8
  ");
  
$installer->endSetup();


/*$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `etailthis_productimport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Import_Start` timestamp NULL DEFAULT NULL,
  `Import_Finish` timestamp NULL DEFAULT NULL,
  `Import_Type` varchar(100) DEFAULT NULL,
  `Status_Number_of_products` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQLTEXT;

$installer->run($sql);
//demo 
//Mage::getModel('core/url_rewrite')->setId(null);
//demo 
*/