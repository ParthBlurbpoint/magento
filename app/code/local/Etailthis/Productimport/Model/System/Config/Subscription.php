<?php
/**
 * Class provides data for Magento BO
 *  @author Sergey Stepanchuk <info@bintime.com>
 *
 */
class Bintime_Sinchimport_Model_System_Config_Subscription
{
    public function toOptionArray()
    {    
    	$paramsArray = array(
    		'1' => '1 am',
            '2' => '2 am',
            '3' => '3 am',
            '4' => '4 am',
            '5' => '5 am',
            '6' => '6 am',
            '7' => '7 am',
            '8' => '8 am',
            '9' => '9 am',
            '10' => '10 am',
            '11' => '11 am',
            '12' => '12 am',
            '13' => '1 pm',
            '14' => '2 pm',
            '15' => '3 pm',
            '16' => '4 pm',
            '17' => '5 pm',
            '18' => '6 pm',
            '19' => '7 pm',
            '20' => '8 pm',
            '21' => '9 pm',
            '22' => '10 pm',
            '23' => '11 pm',
    		'0' => '12 pm'
    	);
        return $paramsArray;
    }
}
