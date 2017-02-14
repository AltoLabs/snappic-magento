<?php
/* This file is Copyright AltoLabs 2016. */

$installer = $this;
$installer->startSetup();

$helper = Mage::helper('altolabs_snappic');
$token = $helper->getToken();
$secret = $helper->getSecret();

$connect = Mage::getSingleton('altolabs_snappic/connect');
$connect->setSendable(array('token' => $token, 'secret' => $secret))
        ->notifySnappicApi('application/installed');

$installer->endSetup();
