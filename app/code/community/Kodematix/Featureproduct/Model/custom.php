<?php

class Kodematix_Featureproduct_Model_Custom
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'horizontal', 'label'=>Mage::helper('adminhtml')->__('Horizontal')),
            array('value' => 'vertical', 'label'=>Mage::helper('adminhtml')->__('Vertical')),
        );
    }

}
