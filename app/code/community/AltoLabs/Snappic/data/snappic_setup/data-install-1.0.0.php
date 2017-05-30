<?php
/* This file is Copyright AltoLabs 2016. */

$installer = $this;
$installer->startSetup();

$helper = Mage::helper('altolabs_snappic');

$token = $helper->getToken();
$secret = $helper->getSecret();

$connect = Mage::getSingleton('altolabs_snappic/connect');
$connect->setSendable(array('token' => $token, 'secret' => $secret))
        ->notifySnappicApi('app/installed', true);

$configPath = $helper->getConfigPath('environment/sandboxed');
Mage::app()->getConfig()->saveConfig($configPath, 1);
Mage::app()->getConfig()->reinit();

$installer->endSetup();
