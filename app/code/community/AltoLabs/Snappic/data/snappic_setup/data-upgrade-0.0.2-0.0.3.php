<?php

Mage::log('Checking for SOAP user...', null, SNAPPIC_LOG);
$apiKey = Mage::helper('altolabs_snappic')->getSoapApiKey();
Mage::getModel('api/user')
    ->load('Snappic', 'username')
    ->setApiKey($apiKey)
    ->setApiKeyConfirmation($apiKey)
    ->save();
