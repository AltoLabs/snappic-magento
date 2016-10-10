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

Mage::log('Checking for SOAP user...', null, SNAPPIC_LOG);
$apiKey = Mage::helper('altolabs_snappic')->getSoapApiKey();
Mage::getModel('api/user')
    ->load('Snappic', 'username')
    ->setApiKey($apiKey)
    ->setApiKeyConfirmation($apiKey)
    ->save();
