<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
create table storelocator(store_id int not null auto_increment,name varchar(255),status smallint(16),street_address varchar(255),country varchar(64),state varchar(64),city varchar(64),zipcode varchar(64),phone varchar(255),fax varchar(64),url varchar(255),email varchar(255),store_logo varchar(255),
description text,trading_hours varchar(255),radius varchar(64),latitude varchar(255),longitude varchar(255),
zoom_level varchar(64),created_at timestamp,updated_at timestamp,primary key(store_id));
		
SQLTEXT;

$installer->run($sql);
//demo 
//Mage::getModel('core/url_rewrite')->setId(null);
//demo 
$installer->endSetup();
	 