<?php
/**
 * This script takes care of storing a proper API key for the snappic frontend to use.
 *
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

$installer = $this;
$installer->startSetup();

Mage::log('Assigning an API key to SOAP user...', null, 'snappic.log');
$apiKey = Mage::helper('altolabs_snappic')->getSoapApiKey();
Mage::getModel('api/user')
    ->load('Snappic', 'username')
    ->setApiKey($apiKey)
    ->setApiKeyConfirmation($apiKey)
    ->save();

$installer->endSetup();
