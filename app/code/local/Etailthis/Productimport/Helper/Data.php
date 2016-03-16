<?php
class Etailthis_Productimport_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function setconnection(){
		$ftp_location = 'ftp.ftps.etailthis.com'; 
		$location_login = 'et04@ftps.etailthis.com';
		$location_pwd = 'GL^gvvT6KS$@';
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		if ((!$conn_id) || (!$login_result)) {
		 $data = "FTP connection has failed!";
		
		} else {

		 $data = 'Connected !';
		}
		return $data;
		
	
	}
	public function downloadFile($filename,$conn_id){
		
		$filepath = 'Import/'.$filename;
		$local = fopen($filepath,"w");
		$result = ftp_fget($conn_id, $local,$filename, FTP_BINARY);
		fwrite($local, $result); 
		fclose($local); 
		
		if (!$result) {
   		 $data = "FTP download has failed!";
		} else {
			$data =  "Downloaded ";    
		}
		
		
		//
	 	return $data;	
	}
	/*
	Download File From FTP
	*/
	public function StartImport(){
		//Download process
		$ftp_location = Mage::getStoreConfig('etailthis/sinch_ftp/ftp_server');
		$location_login = Mage::getStoreConfig('etailthis/sinch_ftp/login');
		$location_pwd = Mage::getStoreConfig('etailthis/sinch_ftp/password');
		$conn_id = ftp_connect("$ftp_location");
		$login_result = ftp_login($conn_id, $location_login, $location_pwd);
		if ((!$conn_id) || (!$login_result)) {
			echo "FTP connection has failed!";
		}else{
			
			ftp_pasv($conn_id, true);
			
			$csvfile = array('Products.csv','Categories.csv','Searchables.csv','Configuration.csv','ContentProviders.csv','MerchantCentre.csv','SupplierData.csv','Pricing.csv','Stock.csv');
			
			foreach($csvfile as $file){
			$filepath = 'Import/'.$file;
			$result = ftp_get($conn_id, $filepath,$file, FTP_BINARY);
				if($result){
						$result = 'Download';				
				}else{
					$result = 'Failed'.$file;	
				}
			}
		}
		return $result;
	}
	
	/* Merge File */
	public function MergeAllCSV(){$nn = 0;
$files = array('Products.csv','Categories.csv','Configuration.csv','Searchables.csv','MerchantCentre.csv','ContentProviders.csv','Pricing.csv','Stock.csv','SupplierData.csv');
    foreach ($files as $filename) {

        if (($handle = fopen('Import/'.$filename, "r")) !== FALSE) {
             
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
    
    
                $c = count($data);
                
    $x=0;
                while ($x<$c)
                {
     
                    $csvarray[$nn][] = $data[$x];
     $x++;
     
                }
    
                $nn++;
            }
            
   $nn=0;
  
            fclose($handle);
        }

    }
 
   $fp = fopen('Import/master.csv', 'w');//output file set here *

    foreach ($csvarray as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);
	return 'Download';
	}
	/* End Merge File */
	
	public function reIndexAll(){
		$indexCollection = Mage::getModel('index/process')->getCollection();
		foreach ($indexCollection as $index) {
			$index->reindexAll();
		}
		$process = Mage::getModel('index/process')->load(2);
		$process->reindexAll();
	}
	public function logEnty($data,$importtype,$startTime,$endTime){
		
			if($data != ''){
			$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
			$connection->beginTransaction();
			$__fields = array();
			$__fields['Import_Start'] = $startTime;
			$__fields['Import_Finish'] = $endTime;
			$__fields['Import_Type'] = $importtype;
			$__fields['Status_Number_of_products'] = $data;
			$connection->insert('etailthis_productimport', $__fields);
			$connection->commit();
			}
			return;
		
		
	}
}
	 