<?php
class Etailthis_Productimport_Model_Observer {
	const XML_PATH_SCHEDULE_LIFETIME        = 'system/cron/schedule_lifetime';
    public function setStatus() {
   	$indexCollection = Mage::getModel('index/process')->getCollection();
		foreach ($indexCollection as $index) {
			$index->reindexAll();
		}
		$process = Mage::getModel('index/process')->load(2);
		$process->reindexAll();


	 }
	
	  
	public function fullImportFinal1(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=0;$a<=3000;$a++){
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							

						    $product = Mage::getModel('catalog/product');
							
							
							$product->setSku($csv['sku']);
							//
					foreach($csv as $key=>$row_data){
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}
					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
							//$product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
							
							$data =  explode('|',$csv['category_ids']);
							//Category LIST
						$categories = array($category_id);
						$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids); 
							//Category LIST	
							
							//IMages 
							/*if($csv['thumbnail'] == $csv['small_image']){
								$fullpath_img1 =    'media' . DS . 'import'. DS .$csv['thumbnail'];
								if(file_exists($fullpath_img1)){
								
								$product->addImageToMediaGallery($fullpath_img1,array('small_image', 'thumbnail'), false, false);
								
								}else{
								continue;
								}
							}else{
								$fullpath_img1 =    'media' . DS . 'import'. DS .$csv['thumbnail'];
								if(file_exists($fullpath_img1)){
								
									$product->addImageToMediaGallery($fullpath_img1, 'thumbnail', false);
								
								}else{
									continue;
								}
								$fullpath_img2 =  'media' . DS . 'import'. DS .$csv['small_image'];
								if(file_exists($fullpath_img2)){
									$product->addImageToMediaGallery($fullpath_img2,'small_image', false, false);
								}else{continue;}
							}
							$fullpath_img =  'media' . DS . 'import'. DS .$csv['image'];
							if(file_exists($fullpath_img)){
							
							$product->addImageToMediaGallery($fullpath_img,'image', false, false);
							}else{continue;}*/
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								$prod[] = $product->getId();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}

						//Mage::log(count($csv),null,'csvCount2.log');
				 }
			$result = 'Product Import SucessFully';
	} 
	
	public function fullImport2(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=3001;$a<=6000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport3(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=6001;$a<=9000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport4(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=9001;$a<=1200;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	
	public function fullImport5(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=12001;$a<=15000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport6(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=15001;$a<=18000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport7(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=18001;$a<=21000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport8(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=21001;$a<=24000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport9(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$prod = array();
					for($a=24001;$a<=27000;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	public function fullImport10(){	
		 $startTime = date('Y-m-d H:i:s');
		
		$attributes =  Mage::getModel('eav/entity_attribute_set')->getCollection();
			$attributesets ='';
			foreach($attributes as $attributes_val){
				$attributesets[$attributes_val['attribute_set_id']] = $attributes_val['attribute_set_name'];
			}
		 		 $csvfiles = 'Import/Products.csv';
				 if (($handle = fopen($csvfiles, "r")) !== FALSE) {
					$array = array(); 
					$row = 0 ;
					while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
					$i= 0 ;
					$array[] = $data;
					$num = count($data);
					$row++;
					if($row == 1) continue;
					} // End While Loop
					fclose($handle);
					$keys = array_shift($array);
					$total = count($array);
					$prod = array();
					for($a=27001;$a<=$total;$a++){
						
							$row = $array[$a];	
						if (count($keys) == count($row)) {
								$csv = array_combine($keys, $row);
	 					}else{}
					
					
					$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$csv['sku']); 
						
   						//Product exist or not condition
						if (!$product){
							$product = Mage::getModel('catalog/product');
							$product->setSku($csv['sku']);
							//
							foreach($csv as $key=>$row_data){
					//echo '<pre>';	print_r($csv['attribute_set_name'] );
						$attributeSetId = array_search ($csv['attribute_set_name'] , $attributesets);
										if($attributeSetId){
										 $attributeSetId ; 
										}else{
										 $attributeSetId  = 4; 
										}

					$attribute_code = $key;
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $key); 
					$attr_value = $attribute_details->getFrontendInput();
						if($attr_value == 'select' || $attr_value == 'multiselect'){
							
							$attribute_model = Mage::getModel('eav/entity_attribute');
							$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
							$attribute_code = $attribute_model->getIdByCode('catalog_product', $key);
							$attribute = $attribute_model->load($attribute_code);
						
							$attribute_options_model->setAttribute($attribute);
							$options = $attribute_options_model->getAllOptions(false);
							$arg_value = $row_data;
							// determine if this option exists
							$value_exists = false;
							foreach($options as $option) {
								if ($option['label'] == $arg_value) {
									$value_exists = true;
									break;
								}
							}
							// if this option does not exist, add it.
						if (!$value_exists) {
							$attribute->setData('option', array(
								'value' => array(
									'option' => array($arg_value,$arg_value)
								)
							));
							$attribute->save();
						}
					
						$product->setData($arg_attribute, $arg_value);
							$entity = 'catalog_product';
						$attr1 = Mage::getResourceModel('catalog/eav_attribute')
							->loadByCode($entity,$key);
							if (null !== $attr1->getId()) {
								$attr2 = $product->getResource()->getAttribute($key);
								if ($attr2->usesSource()) {
								 	$material = $attr2->getSource()->getOptionId($row_data);
								//	$product->setSupplier($material);
									$product->setData($key, $material);
									

								}
							}else{
								echo 'asdasd';
								continue;
							}
						}else{
								$product->setData($key,$row_data);	
						}
						
					}
							$product->setName($csv['name']);
							$product->setPrice($csv['price']);
							$product->setTypeId('simple');
							$product->setAttributeSetId($attributeSetId); // need to look this up
							$product->setWeight(0);
							$product->setTaxClassId($csv['tax_class_id']); // taxable goods
							$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); // catalog, search
							$product->setStatus($csv['status']); // enabled
							$product->setStockData(array(
							   'use_config_manage_stock' => 0, //'Use config settings' checkbox
							   'manage_stock'=>$csv['manage_stock'], //manage stock
							   'min_sale_qty'=>$csv['min_sale_qty'], //Minimum Qty Allowed in Shopping Cart
							   'max_sale_qty'=>$csv['max_sale_qty'], //Maximum Qty Allowed in Shopping Cart
							   'is_in_stock' => 1, //Stock Availability
							   'min_qty'=> $csv['min_qty'],
								'qty_increments'=> $csv['qty_increments'],
								'is_qty_decimal'=> $csv['is_qty_decimal'],
								'backorders'=> $csv['backorders'],
								'use_config_qty_increments'=> $csv['use_config_qty_increments'],
								'use_config_backorders'=> $csv['use_config_backorders'],
								'use_config_manage_stock'=> $csv['use_config_manage_stock'],
								'use_config_min_sale_qty'=> $csv['use_config_min_sale_qty'],
								'use_config_max_sale_qty'=> $csv['use_config_max_sale_qty'],
								'use_config_min_qty'=> $csv['use_config_min_qty'],
								'use_config_notify_stock_qty'=> $csv['use_config_notify_stock_qty'],
								'use_config_enable_qty_inc'=> $csv['use_config_enable_qty_inc'],
								'enable_qty_increments'=> $csv['enable_qty_increments'],
								'options_container'=> $csv['options_container'],
								'qty' =>$csv['qty']//qty
						   ));
						   
							$data =  explode('|',$csv['category_ids']);
							$categories = array($category_id);
							$cat_name = Mage::getModel('catalog/category')
							->getCollection()
							->addFieldToFilter('name',$data)->addAttributeToSelect('*');
							
							$catid = '';
							$catname = '';
							foreach($cat_name as $cat){
								$catid[] = $cat->getEntityId(); 
								$catname[] = $cat->getName();
							}
						
							if(!empty($catid)){
							$catids = implode(',',$catid);
							}
							$product->setCategoryIds($catids);
							
							try{
								$product->save();
								$prod[] = $product->getId();
								

							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
							
						}
						
						}
					
					$totalProduct =  count($prod);
						//Mage::log($prod,null,'aaaaaaaa.log');
						
						if($totalProduct != '' || $totalProduct != NULL)
						{
							$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
							$connection->beginTransaction();
							$__fields = array();
							$__fields['Import_Start'] = $startTime;
							$__fields['Import_Finish'] = $endTime;
							$__fields['Import_Type'] = 'All Product';
							$__fields['Status_Number_of_products'] = $totalProduct;
							$connection->insert('etailthis_productimport', $__fields);
							$connection->commit();
						}
				 }
			$result = 'Product Import SucessFully';
	} 
	  
	public function mergeAllFiles(){
	
	 $startTime = date('Y-m-d H:i:s');  

		$fh = fopen('Import/Products.csv', 'r');
		$fhg = fopen('Import/Categories.csv', 'r');
        while (($data = fgetcsv($fh, 0, ",")) !== FALSE) {
            $awards[]=$data;
        }
        while (($data = fgetcsv($fhg, 0, ",")) !== FALSE) {
                $contracts[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($contracts);$x++)
        {
            if($x==0){
               // unset($awards[0][0]);
                $line[$x]=array_merge($contracts[0],$awards[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($awards);$y++)
                {
                    if($awards[$y][0] == $contracts[$x][0]){
                     //unset($awards[$y][0]);
                        $line[$x]=array_merge($contracts[$x],$awards[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line[$x]=$contracts[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final.csv', 'w');//output file set here

       foreach ($line as $fields) {
         fputcsv($fp, $fields);

        }
	  fclose($fp);
/***********************/	 	  
  $file1 = fopen('Import/final.csv', 'r');
  $file2 = fopen('Import/Searchables.csv', 'r');
        while (($data = fgetcsv($file1, 0, ",")) !== FALSE) {
            $file1data[]=$data;
        }
        while (($data = fgetcsv($file2, 0, ",")) !== FALSE) {
                $file2data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file2data);$x++)
        {
            if($x==0){
               // unset($file1data[0][0]);
                $line1[$x]=array_merge($file2data[0],$file1data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file1data);$y++)
                {
                    if($file1data[$y][0] == $file2data[$x][0]){
                     //unset($file1data[$y][0]);
                        $line1[$x]=array_merge($file2data[$x],$file1data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line1[$x]=$file2data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final1.csv', 'w');//output file set here

       foreach ($line1 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
	 
/***********************/	 
 
  $file33 = fopen('Import/final1.csv', 'r');
  $file44 = fopen('Import/Configuration.csv', 'r');
        while (($data = fgetcsv($file33, 0, ",")) !== FALSE) {
            $file33data[]=$data;
        }
        while (($data = fgetcsv($file44, 0, ",")) !== FALSE) {
                $file44data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file44data);$x++)
        {
            if($x==0){
               // unset($file33data[0][0]);
                $line22[$x]=array_merge($file44data[0],$file33data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file33data);$y++)
                {
                    if($file33data[$y][0] == $file44data[$x][0]){
                     //unset($file33data[$y][0]);
                        $line222[$x]=array_merge($file44data[$x],$file33data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line22[$x]=$file44data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final2.csv', 'w');//output file set here

       foreach ($line22 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
/***********************/	 
	   $file3 = fopen('Import/final2.csv', 'r');
  $file4 = fopen('Import/Imagery.csv', 'r');
        while (($data = fgetcsv($file3, 0, ",")) !== FALSE) {
            $file3data[]=$data;
        }
        while (($data = fgetcsv($file4, 0, ",")) !== FALSE) {
                $file4data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file4data);$x++)
        {
            if($x==0){
               // unset($file3data[0][0]);
                $line2[$x]=array_merge($file4data[0],$file3data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file3data);$y++)
                {
                    if($file3data[$y][0] == $file4data[$x][0]){
                     //unset($file3data[$y][0]);
                        $line2[$x]=array_merge($file4data[$x],$file3data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line2[$x]=$file4data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final3.csv', 'w');//output file set here

       foreach ($line2 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
/***********************/	 	 
	 $file5 = fopen('Import/final3.csv', 'r');
$file6 = fopen('Import/ContentProviders.csv', 'r');
        while (($data = fgetcsv($file5, 0, ",")) !== FALSE) {
            $file5data[]=$data;
        }
        while (($data = fgetcsv($file6, 0, ",")) !== FALSE) {
                $file6data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file6data);$x++)
        {
            if($x==0){
               // unset($file5data[0][0]);
                $line3[$x]=array_merge($file6data[0],$file5data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file5data);$y++)
                {
                    if($file5data[$y][0] == $file6data[$x][0]){
                     //unset($file5data[$y][0]);
                        $line3[$x]=array_merge($file6data[$x],$file5data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line3[$x]=$file6data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final4.csv', 'w');//output file set here

       foreach ($line3 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
/***********************/	 
	  $file7 = fopen('Import/final4.csv', 'r');
$file8 = fopen('Import/RelatedProducts.csv', 'r');
        while (($data = fgetcsv($file7, 0, ",")) !== FALSE) {
            $file7data[]=$data;
        }
        while (($data = fgetcsv($file8, 0, ",")) !== FALSE) {
                $file8data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file8data);$x++)
        {
            if($x==0){
               // unset($file7data[0][0]);
                $line4[$x]=array_merge($file8data[0],$file7data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file7data);$y++)
                {
                    if($file7data[$y][0] == $file8data[$x][0]){
                     //unset($file7data[$y][0]);
                        $line4[$x]=array_merge($file8data[$x],$file7data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line4[$x]=$file8data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final5.csv', 'w');//output file set here

       foreach ($line4 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
/***********************/	 
$file9 = fopen('Import/final5.csv', 'r');
$file10 = fopen('Import/SupplierData.csv', 'r');
        while (($data = fgetcsv($file9, 0, ",")) !== FALSE) {
            $file9data[]=$data;
        }
        while (($data = fgetcsv($file10, 0, ",")) !== FALSE) {
                $file10data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file10data);$x++)
        {
            if($x==0){
               // unset($file7data[0][0]);
                $line5[$x]=array_merge($file10data[0],$file9data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file9data);$y++)
                {
                    if($file9data[$y][0] == $file10data[$x][0]){
                     //unset($file9data[$y][0]);
                        $line5[$x]=array_merge($file10data[$x],$file9data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line5[$x]=$file10data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final6.csv', 'w');//output file set here

       foreach ($line5 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
/***********************/	 
	 $file11 = fopen('Import/final6.csv', 'r');
$file12 = fopen('Import/Pricing.csv', 'r');
        while (($data = fgetcsv($file11, 0, ",")) !== FALSE) {
            $file11data[]=$data;
        }
        while (($data = fgetcsv($file12, 0, ",")) !== FALSE) {
                $file12data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file12data);$x++)
        {
            if($x==0){
               // unset($file7data[0][0]);
                $line6[$x]=array_merge($file12data[0],$file11data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file11data);$y++)
                {
                    if($file11data[$y][0] == $file12data[$x][0]){
                     //unset($file11data[$y][0]);
                        $line6[$x]=array_merge($file12data[$x],$file11data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line6[$x]=$file12data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final7.csv', 'w');//output file set here

       foreach ($line6 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
	 
/***********************/	 
$file13 = fopen('Import/final7.csv', 'r');
$file14 = fopen('Import/Stock.csv', 'r');
        while (($data = fgetcsv($file13, 0, ",")) !== FALSE) {
            $file13data[]=$data;
        }
        while (($data = fgetcsv($file14, 0, ",")) !== FALSE) {
                $file14data[]=$data;
        }
		
 // 2nd section   
        for($x=0;$x< count($file14data);$x++)
        {
            if($x==0){
               // unset($file7data[0][0]);
                $line7[$x]=array_merge($file14data[0],$file13data[0]); //header
            }
            else{
                $deadlook=0;
                for($y=0;$y <= count($file13data);$y++)
                {
                    if($file13data[$y][0] == $file14data[$x][0]){
                     //unset($file13data[$y][0]);
                        $line7[$x]=array_merge($file14data[$x],$file13data[$y]);
                        $deadlook=1;
                    }           
                }
                if($deadlook==0)
                    $line7[$x]=$file14data[$x];
            }
        }
  // 3 section     
        $fp = fopen('Import/final8.csv', 'w');//output file set here

       foreach ($line7 as $fields) {
         fputcsv($fp, $fields);
        }
	 fclose($fp);
	 
		
	}	
	public function addImages(){  
	
	
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename =  'Imagery.csv';
			if ((!$conn_id) || (!$login_result)) {
				echo "FTP connection has failed!";
				exit;
			} else {
				
				//echo "FTP conneced!";
				ftp_pasv($conn_id, true); 
				$filepath = 'Import/Imagery.csv';
				$local = $filepath;//fopen($filepath,"w");
				$result = ftp_get($conn_id, $filepath,$filename, FTP_BINARY);
				if($result){
					require_once('pclzip.lib.php'); 
					$zipfile = new PclZip('media/import/Media.zip');
					if ($zipfile -> extract() == 0) {
					echo 'Error : ' . $zipfile -> errorInfo(true);
					}else{
						$csvfile = 'Import/Imagery.csv';
						if($result){
							$startTime = date('Y-m-d H:i:s');		
						if (($handle = fopen($csvfile, "r")) !== FALSE) {
							$array = array(); 
							$row = 0 ;
							while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
							$i= 0 ;
							$array[] = $data;
							$num = count($data);
							$row++;
							if($row == 1) continue;
						   }
						  
						   fclose($handle);
						   $keys = array_shift($array);
						   $prod = array();
						 foreach ($array as $i=>$row) {
							if (count($keys) == count($row)) {
								$csv[$i] = array_combine($keys, $row);
							}
							echo $csv[$i]['sku'];
							$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$csv[$i]['sku']);
							//$fullpath = 'http://imp.etailthis.com/media/import';
							$ch = curl_init ($csv[$i]['image']);
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
							$rawdata=curl_exec($ch);
							curl_close ($ch);
							$fullpath = $fullpath.$csv[$i]['image'];
							if(file_exists($fullpath)) {
								unlink($fullpath);
							}
							//$fp = fopen($fullpath,'x');
							//fwrite($fp, $rawdata);
							//fclose($fp);
							$fullpath_img1 =    'media' . DS . 'import'. DS .$csv[$i]['thumbnail'];
						if(file_exists($fullpath_img1)){
					
						   $product->addImageToMediaGallery($fullpath_img1, 'thumbnail', false);
						   
						}else{
							continue;
						}
							
							//$fullpath = 'http://imp.etailthis.com/media/import';
							$ch = curl_init ($csv[$i]['small_image']);
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
							$rawdata=curl_exec($ch);
							curl_close ($ch);
						   $fullpath = $fullpath.$csv[$i]['image'];
							if(file_exists($fullpath)) {
								unlink($fullpath);
							}
							 $fullpath_img2 =  'media' . DS . 'import'. DS .$csv[$i]['small_image'];
						if(file_exists($fullpath_img2)){
							 $product->addImageToMediaGallery($fullpath_img2,'small_image', false, false);
						}else{continue;}
							$ch = curl_init ($csv[$i]['thumbnail']);
							curl_setopt($ch, CURLOPT_HEADER, 0);   
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
							$rawdata=curl_exec($ch);
							curl_close ($ch);
						   // $fullpath = $fullpath.$csv[$i]['image'];
							//$fullpath1 =  Mage::getBaseDir('media') . DS . 'import'.''.$csv[$i]['image'];
							if(file_exists($fullpath)) {
								unlink($fullpath);
							}
							 $fullpath_img =  'media' . DS . 'import'. DS .$csv[$i]['image'];
							//if(file_exists($fullpath_img)){
								
								$product->addImageToMediaGallery($fullpath_img,'image', false, false);
							//}else{continue;}
							try{
								Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save(); 
								$prod = $product->getId(); 
								
							}catch(Exception $e){
								
							}
							
							
						 }
						 
						}	
						}
						}
						$totalupdate = count($prod);
													$endTime = date('Y-m-d H:i:s');
					 Mage::helper('productimport')->logEnty($totalupdate,'Images import',$startTime,$endTime);
				}
				unlink('Import/Imagery.csv');
				$files = glob('media/import/*.jpg');
				foreach($files as $file) {
					unlink($file);
				}
								
			}
		
		

	
	
	
	
	}
	
	public function assignRelatedProducts(){
		
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename =  'Relatedproduct.csv';
		if ((!$conn_id) || (!$login_result)) {
			echo "FTP connection has failed!";
		}else{
			echo "FTP conneced!";
			ftp_pasv($conn_id, true);
		$filepath = 'Import/Relatedproduct.csv';
		// get the file
		// change products.csv to the file you want
		$local = $filepath;//fopen($filepath,"w");
		$result = ftp_get($conn_id, $filepath,$filename, FTP_BINARY);
		
		
		if($result){
		
				 if (($handle = fopen($filepath, "r")) !== FALSE) {
					 $array = array(); 
					 $row = 0 ;
					 while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
						$i= 0 ;
						$array[] = $data;
						$num = count($data);
						$row++;
						if($row == 1) continue;
					 } // End While Loop
				  $startTime = date('Y-m-d H:i:s');
				   fclose($handle);
				  $keys = array_shift($array);
				   		foreach ($array as $i=>$row) {
						if (count($keys) == count($row)) {
								$csv[$i] = array_combine($keys, $row);
						}
								$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$csv[$i][$keys[0]]);
								 $productId = $product->getId(); 
								 $relationids = explode(',',$csv[$i][$keys[1]]);
								 $up_sellids = explode(',',$csv[$i][$keys[3]]);

								 $cross_sellids = explode(',',$csv[$i][$keys[5]]);
								foreach($relationids as $relid){
									$linked_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$relid);
									$related = $product->getRelatedProductIds();
									
									if(!in_array($linked_product->getId(),$related)){
										
										$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
										$connection->beginTransaction();
										$__fields = array();
										$__fields['product_id'] = $productId; // Real Product Id
										echo $__fields['linked_product_id'] = $linked_product->getId(); // Assignproduct id
										$__fields['link_type_id'] = 1; //Related : 1 , Crosselll : 5 , upsell : 4
										$connection->insert('catalog_product_link', $__fields);
										$connection->commit();	
										}
									
								}
								foreach($up_sellids as $upsellid){
									$linked_productupsell = Mage::getModel('catalog/product')->loadByAttribute('sku',$upsellid);
									$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
									$upsell = $product->getUpSellProductIds();
									
									if(!in_array($linked_productupsell->getId(),$upsell)){
										$connection->beginTransaction();
										$__fields = array();
										$__fields['product_id'] = $productId; // Real Product Id
										$__fields['linked_product_id'] = $linked_productupsell->getId(); // Assignproduct id
										$__fields['link_type_id'] = 4; //Related : 1 , Crosselll : 5 , upsell : 4
										$connection->insert('catalog_product_link', $__fields);
										$connection->commit();	
							 		 }
								}
								foreach($cross_sellids as $id){
									$cross_sellcol = Mage::getModel('catalog/product')->loadByAttribute('sku',$id);
									$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
									$crosssell = $product->getCrossSellProductIds();
									if(!in_array($cross_sellcol->getId(),$crosssell)){
									$connection->beginTransaction();
										 
										$__fields = array();
										$__fields['product_id'] = $productId; // Real Product Id
										$__fields['linked_product_id'] = $cross_sellcol->getId(); // Assignproduct id
										$__fields['link_type_id'] = 5; //Related : 1 , Crosselll : 5 , upsell : 4
										$connection->insert('catalog_product_link', $__fields);
										$connection->commit();	
									
									}
								}
						}
				 
				 }
				 				  $endTime = date('Y-m-d H:i:s');
				 ftp_close($conn_id);
				 unlink('Import/Relatedproduct');
				 $totalupdate = count($csv);
				 Mage::helper('productimport')->logEnty($totalupdate,'Attributes',$startTime,$endTime);
				 }
				
		}
		
		
		
	}
	
	public function AttributeUpdate(){
		
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename = 'AttributeSets.csv';
		$result = Mage::Helper('productimport')->downloadFile($filename,$conn_id);
			
		if($result){
		$csvfile = 'Import/AttributeSets.csv';
		$startTime = date('Y-m-d H:i:s');
			if (($handle = fopen($csvfile, "r")) !== FALSE) {
		   $array = array(); 
		   $row = 0 ;
			while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
			  $i= 0 ;
			  $array[] = $data;
				$num = count($data);
				$row++;
				if($row == 1) continue;
			}
		
			fclose($handle);
			$keys = array_shift($array);
					$keys1 = '';
					foreach($keys as $key){
						$keys1[] = trim(str_replace('"', '', $key));
					}
					foreach ($array as $i=>$row) {
						$value = '';	
						foreach($row as $val1){
							$value[] = trim(str_replace('"', '', $val1));
						}
						$csv[$i] = array_combine($keys1, $value);	
						
						
					}
						foreach($csv as $row_data){
							$model=Mage::getModel('eav/entity_setup','core_setup');
							$skeletonID =  Mage::getModel('catalog/product')->getDefaultAttributeSetId();
							$code = $row_data['attribute_code'];
							$entity = 'catalog_product';
							$attr = Mage::getResourceModel('catalog/eav_attribute')
								->loadByCode($entity,$code);
							if ($attr->getId()) {	
							$attributeId=$attr->getId();
								$attr1 = explode('|', $row_data['attribute_set']);
								$attrsets ='';
								
								foreach($attr1 as $sets){
										if(!is_numeric($sets) && $sets != ''){
											$attrsets[] = $sets;									
										}
								}
								foreach($attrsets as $key => $row1){
								 if ($key % 2 == 0)
									  {
										
										
									echo $attributeSetName = trim($row1);
										$attribute_set = Mage::getModel("eav/entity_attribute_set")->getCollection();
										$attributeSetDetails = $attribute_set->addFieldToFilter("attribute_set_name", $attributeSetName)->getFirstItem()->getData();
										$attributeSetId = $attributeSetDetails['attribute_set_id'];
										if(!$attributeSetId){
											$entityTypeId = Mage::getModel('catalog/product')
											  ->getResource()
											  ->getEntityType()
											  ->getId();
											$attributeSet = Mage::getModel('eav/entity_attribute_set')
															  ->setEntityTypeId($entityTypeId)
															  ->setAttributeSetName($attributeSetName);
											$attributeSet->validate();
												$attributeSet->save();
											$attributeSet->initFromSkeleton($skeletonID)->save();
											$attributeSetId = $attributeSet->getId();
											
										}
										//echo "even";
									  }
									  else
									  { 
										echo $attributeGroup = trim($row1);
										$attributeGroupData = $model->getAttributeGroup('catalog_product',$attributeSetId,$attributeGroup);
										if(!$attributeGroupData){
											$modelGroup = Mage::getModel('eav/entity_attribute_group');
											//set the group name
											$modelGroup->setAttributeGroupName($attributeGroup)
												->setAttributeSetId($attributeSetId)
												->setSortOrder(100);
											$modelGroup->save();	
											Mage::getModel($modelGroup->getAttributeGroupName(),null,'modelGroup.log');				
										}
										$attributeGroupId = $model->getAttributeGroupId('catalog_product', $attributeSetId, $attributeGroup);
										$model->addAttributeToSet('catalog_product',$attributeSetId,$attributeGroupId,$attributeId);
									  }
									
							}
							}
							
							else{
								 //Attribute not exist 
								 $data=array(
								 'type'=>$row_data['backend_type'],
								 'input'=>$row_data['frontend_input'],
								 'label'=>$row_data['frontend_label'],
								 'global'=>$row_data['is_global'],
								 'is_required'=>$row_data['is_required'],
								 'is_comparable'=>$row_data['is_comparable'],
								 'is_searchable'=>$row_data['is_searchable'],
								 'is_unique'=>$row_data['is_unique'],
								 'is_configurable'=>$row_data['backend_type'],
								 'is_visible_on_front' => $row_data['is_visible_on_front'],
								 'is_filterable_in_search' => $row_data['is_filterable_in_search'],
								 'user_defined'=>'1',
								 );
							$model=Mage::getModel('eav/entity_setup','core_setup');
								 $model->addAttribute('catalog_product',$row_data['attribute_code'],$data);
								 
								 $attributeId=$model->getAttributeId('catalog_product',$row_data['attribute_code']);
								 $attr1 = explode('|', $row_data['attribute_set']);
								$attrsets ='';
								
								foreach($attr1 as $sets){
								  if(!is_numeric($sets) && $sets != ''){
								   $attrsets[] = $sets;         
								  }
								}
								foreach($attrsets as $key => $row1){
								 if ($key % 2 == 0)
								   {
								  
								  
								   $attributeSetName = trim($row1);
								  $attribute_set = Mage::getModel("eav/entity_attribute_set")->getCollection();
								  $attributeSetDetails = $attribute_set->addFieldToFilter("attribute_set_name", $attributeSetName)->getFirstItem()->getData();
								  $attributeSetId = $attributeSetDetails['attribute_set_id'];
								  if(!$attributeSetId){
								   $entityTypeId = Mage::getModel('catalog/product')
									 ->getResource()
									 ->getEntityType()
									 ->getId();
								   $attributeSet = Mage::getModel('eav/entity_attribute_set')
										 ->setEntityTypeId($entityTypeId)
										 ->setAttributeSetName($attributeSetName);
								   $attributeSet->validate();
								  $attributeSet->save();
								   $attributeSet->initFromSkeleton($skeletonID)->save();
								   $attributeSetId = $attributeSet->getId();
								  }
								  //echo "even";
								   }
								   else
								   { 
								  $attributeGroup = trim($row1);
								  $attributeGroupData = $model->getAttributeGroup('catalog_product',$attributeSetId,$attributeGroup);
								  if(!$attributeGroupData){
								   $modelGroup = Mage::getModel('eav/entity_attribute_group');
								   //set the group name
								   $modelGroup->setAttributeGroupName($attributeGroup)
									->setAttributeSetId($attributeSetId)
									->setSortOrder(100);
								   $modelGroup->save(); 
								   Mage::getModel($modelGroup->getAttributeGroupName(),null,'modelGroup.log');    
								  }
								  $attributeGroupId = $model->getAttributeGroupId('catalog_product', $attributeSetId, $attributeGroup);
								  $model->addAttributeToSet('catalog_product',$attributeSetId,$attributeGroupId,$attributeId);
								   }
								 
							   }
								 //End 
							   }
							
							

						}
				
				}
		$endTime = date('Y-m-d H:i:s');
		ftp_close($conn_id);
		$totalupdate = count($csv);
		Mage::helper('productimport')->logEnty($totalupdate,'Attributes',$startTime,$endTime);
		unlink('Import/AttributeSets.csv');
		}
	}
	public function categoryUpdate(){
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename = 'RootCatalogue.csv';
		$local = fopen('Import/RootCatalogue.csv',"w");
		$result = ftp_get($conn_id, $local,$filename, FTP_BINARY);
			if($result){
			$startTime = date('Y-m-d H:i:s');
			if (($handle = fopen("Import/RootCatalogue.csv", "r")) !== FALSE) {
			$array = array(); 
			while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
			$i= 0 ;
			$array[] = $data;
			$num = count($data);
			//echo "<p> $num fields in line $row: <br /></p>\n";
			$row++;
		
			if($row == 1) continue;
		}

			fclose($handle);
			$arrayA = array();
			$i=0;
			$l=0;
			$keys = array_shift($array);
			$keys1 = '';
			foreach($keys as $key){
						
					$keys11= str_replace(' (admin)','', $key);
					$keys1[] = str_replace(' ','',$keys11);
					}
		foreach ($array as $i=>$row) {
				
				$csv[$i] = array_combine($keys1, $row);
				$data =  explode('|',$csv[$i][$keys1[1]]);
				$categorylist = implode(',',$data);
				
				if(trim($csv[$i][$keys1[1]]) == ''){
					if($csv[$i] == $csv[0]){
					$cat_name = Mage::getModel('catalog/category')
						->getCollection()
						->addFieldToFilter('name','Default Category')->addAttributeToSelect('*');
						$a = Mage::getModel('catalog/category')->load(2);
						$a->setName('rt - office supplies');
						 
						$a->save();

					}else{
							$category = Mage::getModel('catalog/category');
							$category->setStoreId(Mage::app()->getStore()->getStoreId()); // No store is assigned to this category
							$rootCategory['name'] = trim($csv[$i][$keys1[0]]);
							$rootCategory['path'] = "1"; // this is the catgeory path - 1 for root category
							$rootCategory['display_mode'] = "PRODUCTS";
							$rootCategory['is_active'] = $csv[$i][$keys1[12]];
							$rootCategory['is_anchor'] = $csv[$i][$keys1[12]];
							$rootCategory['include_in_menu'] = $csv[$i][$keys1[11]];
							$rootCategory['meta_title'] = $csv[$i][$keys1[5]]; //Page title
							$rootCategory['url_key'] = $csv[$i][$keys1[6]]; //Url key
							$rootCategory['meta_description'] = $csv[$i][$keys1[7]]; 
							$rootCategory['meta_keywords'] = $csv[$i][$keys1[8]];
							$rootCategory['description']=$csv[$i][$keys1[9]];
							//$general['image'] = $csv[$i][$keys1[10]];
							$rootCategory['landing_page'] = ""; //has to be created in advance, here comes id
							$rootCategory['display_mode'] = "PRODUCTS"; //static block and the products are shown on the page
							$rootCategory['is_active'] = $csv[$i][$keys1[12]];
							$rootCategory['is_anchor'] = $csv[$i][$keys1[13]];
							$rootCategory['page_layout'] = 'two_columns_left';
							$childCategory = Mage::getModel('catalog/category')->getCollection()
									->addAttributeToFilter('is_active', true)
									//->addIdFilter($parentCategory->getChildren())
									->addAttributeToFilter('name', $csv[$i][$keys1[0]])
									->addAttributeToFilter('parent_id',1)
									->getFirstItem() ;
								
							if (null !== $childCategory->getId()) {
								 //found
								 echo 'Found'.$childCategory->getId();
							}else{
									$category->addData($rootCategory);
									
									try {
									$category->save();
									$rootCategoryId = $category->getId();
									}
									catch (Exception $e){
									echo $e->getMessage();
									}
							
							}
				}
				
				}else{
					$cat_name = Mage::getModel('catalog/category')
					->getCollection()
					->addFieldToFilter('name',$data)->addAttributeToSelect('*');
					
					$catid = '';
					foreach($cat_name as $cat){
						$catid[] = $cat->getEntityId(); 
					}
		
			
					$catids = implode(',',$catid);
					$catids = str_replace(',','/',$catids);
					 $parentId = htmlspecialchars('1/'.$catids);
					
					$category = Mage::getModel('catalog/category');
					$category->setStoreId(0); 
					//if update
					if ($id) {
					$category->load($id);
					}
						$general['name'] = $csv[$i][$keys1[0]];//$csv[$i][$keys1[0]];
						$general['path'] = $parentId;//$parentId; // catalog path here you can add your own ID
						$general['meta_title'] = $csv[$i][$keys1[5]]; //Page title
						$general['url_key'] = $csv[$i][$keys1[6]]; //Url key
						$general['meta_description'] = $csv[$i][$keys1[7]]; 
						$general['meta_keywords'] = $csv[$i][$keys1[8]];
						$general['description']=$csv[$i][$keys1[9]];
						//$general['image'] = $csv[$i][$keys1[10]];
						$general['landing_page'] = ""; //has to be created in advance, here comes id
						$general['display_mode'] = "PRODUCTS"; //static block and the products are shown on the page
						$general['is_active'] = $csv[$i][$keys1[12]];
						$general['is_anchor'] = $csv[$i][$keys1[13]];
						$general['page_layout'] = 'two_columns_left';
						$general['include_in_menu'] = $csv[$i][$keys1[11]];
					
					$childCategory = Mage::getModel('catalog/category')->getCollection()
									->addAttributeToFilter('is_active', true)
									 ->addAttributeToFilter('parent_id', $catids)
									->addAttributeToFilter('name', $general['name'])
									->getFirstItem()    // Assuming your category names are unique ??
								;
						
					if (null !== $childCategory->getId()) {
						
						continue;		
					}else {
						$category->addData($general);
					try {
						
					$category->save();
						
						}
						catch (Exception $e){
							echo $e->getMessage();
						}
					  }  
					}
								
								}
	
		} 
			 $endTime = date('Y-m-d H:i:s');
			ftp_close($conn_id);
			$totalupdate = count($csv);
			Mage::helper('productimport')->logEnty($totalupdate,'RootCatalogue',$startTime,$endTime);
			unlink('Import/RootCatalogue.csv');
				
			}
			return;
			
		
		
	}
}