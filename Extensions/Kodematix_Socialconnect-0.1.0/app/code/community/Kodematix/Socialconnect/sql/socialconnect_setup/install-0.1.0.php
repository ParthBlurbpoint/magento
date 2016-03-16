<?php


$installer = $this;
$installer->startSetup();

$installer->setSocialCustomerAttributes(
    array(
        'socialconnect_googleid' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        ),            
        'socialconnect_googletoken' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        ),
        'socialconnect_facebookid' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        ),            
        'socialconnect_facebooktoken' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        ),
		'socialconnect_twitterid' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        ),            
        'socialconnect_twittertoken' => array(
            'type' => 'text',
            'visible' => false,
            'required' => false,
            'user_defined' => false                
        )		
    )
);


$installer->installSocialCustomerAttributes();

$installer->endSetup();