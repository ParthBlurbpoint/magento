<?php
/**
 * Class provides data for Magento BO
 *  @author Sergey Stepanchuk <info@bintime.com>
 *
 */
class Etailthis_Productimport_Model_System_Config_CatRewrite
{
    public function toOptionArray()
    {    
    	$paramsArray = array(
    	    'REWRITE' => 'Overwrite',
            'MERGE' => 'Merge',
    	);
        return $paramsArray;
    }
}
