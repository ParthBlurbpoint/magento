<?php
class Etailthis_Productimport_Model_Cron{	
	public function fullImport(){
		
		//do something
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
					
					for($a=0;$a<=1500;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

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
					
					for($a=1501;$a<=2500;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=2501;$a<=5000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=5001;$a<=1000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=1001;$a<=5000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=5001;$a<=10000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=10001;$a<=15000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=15001;$a<=20000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
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
					
					for($a=20001;$a<=25000;$a++){
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
							if($csv['thumbnail'] == $csv['small_image']){
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
							}else{continue;}
							
							//IMAGE 
				
							try{
							//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
								$product->save();
								//echo 'Save';
							}catch(Exception $e){
								//echo $e;
							} //End Try Catch statement
								
						}
						
						
						} 
					 $endTime = date('Y-m-d H:i:s');  
					$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
 					$connection->beginTransaction();
					 
					$__fields = array();
					$__fields['Import_Start'] = $startTime;
					$__fields['Import_Finish'] = $endTime;
					$__fields['Import_Type'] = 'All Product';
					$__fields['Status_Number_of_products'] = 10;
					$connection->insert('etailthis_productimport', $__fields);
					 
					$connection->commit();

						//Mage::log(count($csv),null,'csvCount2.log');
				 }
			$result = 'Product Import SucessFully';
	} 
	
	
	


	public function PricingImport(){
	
		$ftp_location = 'ftp.ftps.etailthis.com'; 
		$location_login = 'et04@ftps.etailthis.com';
		$location_pwd = 'GL^gvvT6KS$@';
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename = 'Pricing.csv';
		$result = Mage::Helper('productimport')->downloadFile($filename,$conn_id);
	 //Import Start

    $csvfile = 'Import/Pricing.csv';
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
	   if($column == 'Pricing.csv'){
		 foreach ($array as $i=>$row) {

		 $value = ''; 
		  foreach($row as $val1){
		   
		   $value[] = trim(str_replace('"', '', $val1));
		  }
		  if (count($keys) == count($row)) {
				  $csv[$i] = array_combine($keys, $value); 
		   }else{}
		  
		 }
		 
		 foreach($csv as $rowdata){
	   
		   $product =  Mage::getModel('catalog/product')->loadByAttribute('sku',$rowdata['sku']);
		   Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		   $product->setPrice($rowdata['price'])->setCost($rowdata['cost'])->setMsrp($rowdata['msrp'])->setSpecialPrice($rowdata['special_price']);
		 
		   $product->save();
		 }
		 Mage::log(count($csv),null,'pricecron.log');
		 
		}
		//Pricing//
	   }
	}
	public function StockImport(){
		
		$ftp_location = 'ftp.ftps.etailthis.com'; 
		$location_login = 'et04@ftps.etailthis.com';
		$location_pwd = 'GL^gvvT6KS$@';
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename = 'Stock.csv';
		$result = Mage::Helper('productimport')->downloadFile($filename,$conn_id);
	 //Import Start

    $csvfile = 'Import/Stock.csv';
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
		  foreach ($array as $i=>$row) {
		  $value = ''; 
		   foreach($row as $val1){
			
			$value[] = trim(str_replace('"', '', $val1));
		   }
		    if (count($keys) == count($row)) {
				  $csv[$i] = array_combine($keys, $value); 
		   }else{}
		  
		   
		  }
		  foreach($csv as $rowdata){
			$product =  Mage::getModel('catalog/product')->loadByAttribute('sku',$rowdata['sku']);
			if (!($stockItem = $product->getStockItem())) {
			 $stockItem = Mage::getModel('cataloginventory/stock_item');
			 $stockItem->assignProduct($product)
				 ->setData('stock_id', 1)
				 ->setData('store_id', 1);
			}
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			$stockItem->setData('qty', $rowdata['qty'])
				->setData('is_in_stock', $rowdata['is_in_stock'])
				->setData('manage_stock', 1)
				->setData('use_config_manage_stock', 0)
				->save();
			
		 	 }Mage::log(count($csv),null,'stockcron.log');
	}
	}
	
}