<?php 
class Etailthis_Productimport_IndexController extends Mage_Core_Controller_Front_Action
{
	public function indexAction(){
	   $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Etail this"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("etail this", array(
                "label" => $this->__("Etail this"),
                "title" => $this->__("Etail this")
		   ));

      $this->renderLayout(); 
	  
      /*
        $import=Mage::getModel('sinchimport/sinch');
        
        $import->run_sinch_import();
	
        echo "Finish import<br>";*/

	}
	
	public function startImportProcessAction(){
		$result = Mage::helper('productimport')->StartImport();
		if($result){
			echo $result = Mage::helper('productimport')->MergeAllCSV();
		}
		
		return $result;
	
	}
	
	/*Update Price*/
		
	public function UpdatePriceAction(){
	  $ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		$filename =  'Pricing.csv';
		if ((!$conn_id) || (!$login_result)) {
			echo "FTP connection has failed!";
		}else{
			echo "FTP conneced!";
			ftp_pasv($conn_id, true);
		$filepath = 'Import/Pricing.csv';
		// get the file
		// change products.csv to the file you want
		$local = $filepath;//fopen($filepath,"w");
		$result = ftp_get($conn_id, $filepath,$filename, FTP_BINARY);
		if($result){
			$startTime = date('Y-m-d H:i:s');
			if (($handle = fopen($filepath, "r")) !== FALSE) {
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
					foreach ($array as $i=>$row) {
					$value = '';	
						foreach($row as $val1){
							
							$value[] = trim(str_replace('"', '', $val1));
						}
						$csv[$i] = array_combine($keys, $value);	
						
					}
					
					foreach($csv as $rowdata){
			
							$product =  Mage::getModel('catalog/product')->loadByAttribute('sku',$rowdata['sku']);
							$product->setPrice($rowdata['price'])->setCost($rowdata['cost'])->setMsrp($rowdata['msrp'])->setSpecialPrice($rowdata['special_price']);
								Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
							$product->save();
							
							
					}
			}
			 $endTime = date('Y-m-d H:i:s');
		ftp_close($conn_id);
		$totalupdate = count($csv);
		Mage::helper('productimport')->logEnty($totalupdate,'Price Import',$startTime,$endTime);
		
		}
		unlink('Import/Pricing.csv');
		}
		$jsonData = 'Import Price Sucessfully';
   		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody($jsonData);
    //Pricing//
   
	}
	
	/*
	 Create category csv process
	*/
	
	public function categoryImportAction(){
		$count = 0;
		$row = 0;
		
		//Start Process
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
						//echo $childCategory->getName().'ParentID'. $childCategory->getParentId();		echo '<br />';
					if (null !== $childCategory->getId()) {
						
						//echo "Found Category: " . $childCategory->getData('name');
						continue;		
					}else {
						$category->addData($general);
					try {
						// $category->setId($i); // Here you cant set your own entity id
					$category->save();
						
						 
						//echo "Success! Id: ".$category->getName(). '= '.$category->getId();	
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
	
	/*
		Import Attributes
	*/
	public function AttributeImportAction(){
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		//$ftp_location = 'ftp.ftps.etailthis.com'; 
		//$location_login = 'et04@ftps.etailthis.com';
		//$location_pwd = 'GL^gvvT6KS$@';
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
		
		}
		
	}

	/*
		Stock & Price Update 
	*/
	
	public function StockAndPriceAction(){
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		
		
	if ((!$conn_id) || (!$login_result)) {
		echo "FTP connection has failed!";
		exit;
	} else {
		 $filepath = 'Import/Stock.csv';
    	$filename = 'Stock.csv';
		ftp_pasv($conn_id, true);
		$local = $filepath;
		$result = ftp_get($conn_id, $local,$filename, FTP_BINARY);
		
    if (($handle = fopen($filepath, "r")) !== FALSE) {
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
			
	 $startTime = date('Y-m-d H:i:s');
      foreach ($array as $i=>$row) {
      $value = ''; 
       foreach($row as $val1){
        
        $value[] = trim(str_replace('"', '', $val1));
       }
       $csv[$i] = array_combine($keys, $value); 
       
      }

     	foreach($csv as $rowdata){
				$product =  Mage::getModel('catalog/product')->loadByAttribute('sku',$rowdata['sku']);
			
				
				$oStockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
					$sStockId = $oStockItem->getId();
					$aStock = array();
					if (!$sStockId) {
	
						$oStockItem
							->setProductId($product->getId())
							->setStockId(1);
					}
					else { 
						$aStock = $oStockItem->getData();
					}
					$aStock['qty'] = $rowdata['qty'];
					
					$aStock['is_in_stock'] = $rowdata['is_in_stock'];
				
					$aStock['manage_stock'] =1;
					$aStock['use_config_manage_stock'] =0;
					foreach ($aStock as $k => $v) {
						$oStockItem->setData($k, $v);
					}
					Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
					$oStockItem->save();
					$product->save();
		}
		$jsonData = array('Stock Imported');
	   }
		$endTime = date('Y-m-d H:i:s');
	   
		$totalupdate = count($csv);
		Mage::helper('productimport')->logEnty($totalupdate,'Stock Import',$startTime,$endTime);
		 ftp_close($conn_id);
		unlink('Import/Stock.csv');
		
		$this->UpdatePriceAction();
		$jsonData = json_encode($jsonData);
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody($jsonData);
	 
		
}
	}
	public function RelatedProductSaveAction(){
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

}    
